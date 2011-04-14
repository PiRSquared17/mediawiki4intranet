#!/usr/bin/perl
# mod_perl-скрипт для проксирования запросов к Subversion-репозиториям
# с проверкой свойств по регулярному выражению или просто на существование
# (файлы, для которых проверка не удаётся, представляются как несуществующие)

package SVNPropCheck;

use strict;
use POSIX qw(strftime);
use Encode qw(from_to);
use File::Path 2.06 qw(make_path);
use LWP::MediaTypes;

use APR::Const;
use Apache2::Const qw(:common :http);
use Apache2::ServerRec;
use Apache2::ServerUtil;
use Apache2::RequestRec;
use Apache2::RequestIO;
use Apache2::RequestUtil;
use Apache2::Directive;

use SVN::Core;
use SVN::Client;
use SVN::Ra;

my $instances = [];

# Создание объекта и установка обработчика PerlMapToStorageHandler
# <Perl>
#     use lib qw( Путь к данному файлу );
#     use SVNPropCheck;
#     SVNPropCheck->init({ Хеш параметров });
# </Perl>
# Параметры:
#  1. location - родительский URL, для дочерних которого модуль должен быть активен
#     Пример: "http://wiki.office.custis.ru/wiki/subversion/"
#  2. repos_url - URL к репозиторию Subversion, из которого будут браться файлы.
#     В случае, если параметр не указан или имеет ложное значение, но задан
#     параметр repos_parent, первый компонент всех дочерних URI берётся в качестве
#     имени репозитория и приписывается к repos_parent.
#     Пример: "https://svn.office.custis.ru/3rdparty/"
#  3. repos_parent - родительский URL, приписывая имя конкретного репозитория к
#     которому, можно получать URL отдельных репозиториев.
#     Пример: "https://svn.office.custis.ru/"
#  3. repos_username - имя пользователя Subversion (нужен доступ только на чтение)
#  4. repos_password - пароль пользователя Subversion (нужен доступ только на чтение)
#  5. check_prop_name - название свойства, значение которого делает файлы доступными
#     Пример: "wiki:visible"
#  6. check_prop_re - регулярное выражение для проверки значения свойства.
#     В случае, если параметр не указан или имеет значение undef, указанное
#     свойство просто должно быть задано.
#  7. cache_path - директория локального кэша файлов.
#  8. enc_from_to - массив из двух названий кодировок. Первая из них - входная
#     кодировка обрабатываемых адресов, вторая - кодировка, в которой имена файлов
#     должны передаваться библиотекам Subversion для доступа. Параметр необязательный,
#     и если он не указан, перекодировка не осуществляется.
#     Пример: [ "cp1251", "utf8" ]
#  9. access_log - если true, то логгировать все запросы на STDERR
# 10. mime_types - путь к файлу /etc/mime.types или подобному
sub init
{
    my $class = shift;
    $class = ref($class) || $class;
    my ($params) = @_;
    my $ra;
    unless ($params->{cache_path} && $params->{location} &&
        $params->{repos_username} && exists $params->{repos_password})
    {
        # ругаемся
        warn __PACKAGE__.": parameters cache_path, location, repos_username, repos_password are mandatory";
        return undef;
    }
    $params->{cache_path} =~ s!/+$!!so;
    my $auth_providers = [
        SVN::Client::get_ssl_server_trust_prompt_provider(sub {
            $_[0]->accepted_failures(
                $SVN::Auth::SSL::NOTYETVALID |
                $SVN::Auth::SSL::EXPIRED |
                $SVN::Auth::SSL::CNMISMATCH |
                $SVN::Auth::SSL::UNKNOWNCA |
                $SVN::Auth::SSL::OTHER
            );
        }),
        SVN::Client::get_simple_provider(),
        SVN::Client::get_simple_prompt_provider(sub {
            $_[0]->username($params->{repos_username});
            $_[0]->password($params->{repos_password});
        }, 3),
    ];
    if ($params->{repos_url})
    {
        # открываем репозиторий
        $ra = SVN::Ra->new(
            url  => $params->{repos_url},
            auth => $auth_providers,
        );
    }
    if (!$ra && !$params->{repos_parent})
    {
        # ругаемся
        warn __PACKAGE__.": need one of correct repos_url or repos_parent";
        return undef;
    }
    # ищем подходящий виртхост
    my $murl = $params->{location};
    $murl =~ s!^(https?://)?(www\.)?!!iso;
    $murl =~ s!/{2,}!/!gso;
    $murl =~ s!/+(\?.*)?$!!so;
    my $main = Apache2::ServerUtil->server;
    my ($s, $surl, $rurl) = ($main);
    while ($s = $s->next)
    {
        $surl = $s->server_hostname . '/' . $s->path;
        $surl =~ s!/{2,}!/!gso;
        $surl =~ s!/+(\?.*)?$!!so;
        $surl =~ s!^www\.!!iso;
        if ($surl && $murl =~ /^\Q$surl\E/is)
        {
            $rurl = $';
            # этот виртхост нам подходит...
            last;
        }
    }
    unless ($s)
    {
        $s = $main;
        $rurl = $murl;
        $rurl =~ s!^[^/]+!!so;
    }
    # создаём объект себя
    my $self = bless {
        params    => $params,
        check_url => $murl,
        ra        => $ra,
        server    => $s,
        auth_prov => $auth_providers,
    }, $class;
    my $ino = @$instances;
    push @$instances, $self;
    if (!$ino)
    {
        # на первый раз инициализируем LWP::MediaTypes
        # он всё равно глобальный, так что смысла
        # всасывать типы из разных файлов нет
        LWP::MediaTypes::read_media_types($params->{mime_types} || '/etc/mime.types');
    }
    my $cfg = <<"EOF";
<Location "$rurl">
    RewriteEngine off
    Options +ExecCGI
    SetHandler perl-script
    PerlSetVar ${class}Instance $ino
    PerlResponseHandler ${class}
</Location>
EOF
    $s->add_config([split("\n", $cfg)]);
    return $self;
}

# Отправить сообщение об ошибке
sub print_error
{
    my ($r, $errmsg, $diemsg) = @_;
    $diemsg ||= '';
    $diemsg =~ s/ at \S+ line \d+.*$//so;
    $errmsg =~ s/\.*$/./so;
    $errmsg .= ":\n$diemsg" if $diemsg;
    print STDERR (strftime("[%Y-%m-%d %H:%M:%S] ", localtime) . __PACKAGE__ . $errmsg . "\n");
    $errmsg =~ s/\n/<br \/>/gso;
    my $p = __PACKAGE__;
    $errmsg = "<html><head><title>$p: Error</title></head><body><h1>Error</h1><p>$errmsg</p><hr /><p>$p/0.5</p></body></html>";
    $r->content_type('text/html; charset=utf-8');
    $r->print($errmsg);
    return OK;
}

# кэш объектов SVN::Ra
my $RAS = {};

# обработчик
sub handler
{
    my ($r) = @_;
    my $self = $instances->[$r->dir_config(__PACKAGE__.'Instance')];
    my $LP = strftime("[%Y-%m-%d %H:%M:%S] ", localtime) . __PACKAGE__;
    # проверяем, относится ли к нам этот URL
    my $murl = $self->{check_url};
    my $uri = $r->hostname . $r->uri;
    $uri =~ s/^www\.//iso;
    return DECLINED unless $uri =~ s/^\Q$murl\E//so;
    # превращаем URL в относительный и получаем свойства файла
    $uri =~ s!^/+!!so;
    my $ra = $self->{ra};
    my $rname = '';
    unless ($ra)
    {
        # необходимо открыть репозиторий Subversion
        $uri =~ s!^([^/]+)/*!!so;
        unless ($rname = $1)
        {
            # пустой урл
            return print_error($r, "Requested URL does not contain repository name");
        }
        my $K = $self->{params}->{repos_username} . '@' . $self->{params}->{repos_parent} . $rname;
        $ra = $RAS->{$K};
        unless ($ra)
        {
            # открываем репозиторий
            eval { $ra = SVN::Ra->new(
                url  => $self->{params}->{repos_parent} . $rname,
                auth => $self->{auth_prov},
            ) };
            unless ($ra)
            {
                # репозиторий не открывается
                return print_error($r, "Failed to open Subversion repository '$rname'", $@);
            }
            $RAS->{$K} = $ra;
        }
    }
    if ($self->{params}->{enc_from_to})
    {
        # перекодируем имя файла
        from_to($uri, $self->{params}->{enc_from_to}->[0], $self->{params}->{enc_from_to}->[1]);
    }
    my ($revnum, $props);
    if ($uri !~ /\/$/so)
    {
        eval
        {
            ($revnum, $props) = $ra->get_file($uri, $SVN::Core::INVALID_REVNUM, undef);
        };
    }
    # проверяем, есть ли файл
    if (!$props)
    {
        if ($@ && $@ =~ /405\s+Method\s+Not\s+Allowed/so)
        {
            return print_error($r, "Unknown repository '$rname'", $@);
        }
        else
        {
            return print_error($r, "File '$uri' not found in Subversion repository '$rname'", $@);
        }
    }
    # кэшируем файл, если нужно
    my $path = $self->{params}->{cache_path} . '/' . $rname . $uri;
    my $dir = $path;
    $dir =~ s!/+[^/]*$!!so;
    unless (-d $dir || make_path($dir))
    {
        return print_error($r, "Failed to create cache path '$dir'");
    }
    my ($uptodate, $mime_type, $fd, $cached_rev);
    if (-f $path && open $fd, "<$path.rev")
    {
        $cached_rev = <$fd>;
        $mime_type = <$fd>;
        chomp $mime_type;
        close $fd;
        $cached_rev =~ s/^\s*//so;
        $cached_rev =~ s/\s*$//so;
        if ($props->{'svn:entry:committed-rev'} <= $cached_rev && $mime_type)
        {
            # закэшировано актуальное
            if ($self->{params}->{access_log})
            {
                # логгируем запрос
                print STDERR "$LP: file $rname$uri is up to date, latest ".$props->{'svn:entry:committed-rev'}.", cached $cached_rev\n";
            }
            $uptodate = 1;
        }
    }
    if (!$uptodate)
    {
        # проверка значения свойства - только при обновлении
        if ($self->{params}->{check_prop_name})
        {
            my ($n, $re) = ($self->{params}->{check_prop_name}, $self->{params}->{check_prop_re});
            my $ok = defined $re && $props->{$n} =~ /$re/ || !defined $re && exists $props->{$n};
            if ($self->{params}->{check_prop_inherit})
            {
                # тупое наследование - интересно, будут ли тормоза?
                my $diruri = $uri;
                my $props;
                while (!$ok && $diruri =~ s!/+[^/]*$!!iso)
                {
                    $props = {};
                    eval { (undef, undef, $props) = $ra->get_dir($diruri, $SVN::Core::INVALID_REVNUM) };
                    $ok = defined $re && $props->{$n} =~ /$re/ || !defined $re && $props->{$n};
                }
            }
            if (!$ok)
            {
                return print_error($r, "Access to '$uri' from Subversion repository '$rname' is forbidden");
            }
        }
        # угадать MIME-тип
        $mime_type = $props->{'svn:mime-type'};
        if (!$mime_type)
        {
            $mime_type = LWP::MediaTypes::guess_media_type($path);
        }
        # записываем содержимое файла
        eval
        {
            die "Could not open $path: $!" unless open $fd, ">$path";
            ($revnum, $props) = $ra->get_file($uri, $revnum, $fd);
            close $fd;
            die "Could not open $path.rev: $!" unless open $fd, ">$path.rev";
            print $fd $props->{'svn:entry:committed-rev'}, "\n", $mime_type, "\n";
            close $fd;
        };
        if ($@)
        {
            return print_error($r, "Failed to checkout '$uri' @ rev.$revnum from Subversion repository '$rname' into local file '$path': $@");
        }
        # логгируем запрос
        if ($self->{params}->{access_log})
        {
            print STDERR $cached_rev
                ? "$LP: file $rname$uri, updated to latest $revnum = ".$props->{'svn:entry:committed-rev'}." from cached $cached_rev\n"
                : "$LP: file $rname$uri, checked out $revnum\n";
        }
    }
    $r->content_type($mime_type);
    unless ($r->sendfile($path) == APR::Const::SUCCESS)
    {
        return print_error($r, "Cannot read $path");
    }
    return OK;
}

1;
__END__
