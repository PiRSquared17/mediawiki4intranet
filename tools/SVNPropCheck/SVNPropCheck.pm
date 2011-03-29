#!/usr/bin/perl
# PerlMapToStorageHandler для проксирования запросов к Subversion-репозиториям
# с проверкой свойств по регулярному выражению или просто на существование
# (файлы, для которых проверка не удаётся, представляются как несуществующие)

package SVNPropCheck;

use strict;
use Encode qw(from_to);
use File::Path 2.06 qw(make_path);

use Apache2::Const qw(:common :http);
use Apache2::ServerRec;
use Apache2::ServerUtil;
use Apache2::RequestRec;
use Apache2::Directive;

use SVN::Core;
use SVN::Client;
use SVN::Ra;

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
#  5. check_prop_name - название свойства, значение которого делает файлы публичными
#     Пример: "wiki:visible"
#  6. check_prop_re - регулярное выражение для проверки значения свойства.
#     В случае, если параметр не указан или имеет значение undef, указанное
#     свойство просто должно быть задано.
#  7. check_prop_inherit - включить (истина) или выключить (ложь) наследование для
#     проверки свойств. При включённом установленное значение свойства на каталог
#     открывает все файлы в нём.
#  8. cache_path - директория локального кэша файлов.
#  9. enc_from_to - массив из двух названий кодировок. Первая из них - входная
#     кодировка обрабатываемых адресов, вторая - кодировка, в которой имена файлов
#     должны передаваться библиотекам Subversion для доступа. Параметр необязательный,
#     и если он не указан, перекодировка не осуществляется.
#     Пример: [ "cp1251", "utf8" ]
sub init
{
    my $class = shift;
    $class = ref($class) || $class;
    my ($params) = @_;
    my $ra;
    unless ($params->{cache_path} && $params->{location} &&
        $params->{repos_username} && exists $params->{repos_password} &&
        $params->{check_prop_name})
    {
        # ругаемся
        warn __PACKAGE__.": parameters cache_path, location, repos_username, repos_password, check_prop_name are mandatory";
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
        ras       => {},
        server    => $s,
        auth_prov => $auth_providers,
    }, $class;
    # устанавливаем PerlMapToStorageHandler
    $s->set_handlers(PerlMapToStorageHandler => sub { $self->handler(@_) });
    my $cfg;
    if ($rurl)
    {
        $cfg = <<"EOF";
Alias $rurl '$params->{cache_path}'
<Location "$rurl">
    SetHandler perl-script
</Location>
EOF
    }
    else
    {
        $cfg = <<"EOF";
DocumentRoot '$params->{cache_path}'
<Location "/">
    Options -ExecCGI -Indexes
    SetHandler perl-script
</Location>
EOF
    }
    $s->add_config([split("\n", $cfg)]);
    return $self;
}

# обработчик
sub handler
{
    my $self = shift;
    my ($r) = @_;
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
        $uri =~ s!^([^/]+/)/*!!so;
        unless ($rname = $1)
        {
            # пустой урл
            warn "Requested URL does not contain repository name";
            return HTTP_BAD_REQUEST;
        }
        $ra = $self->{ras}->{$rname};
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
                warn "Failed to open Subversion repository '$rname': $@.";
                return HTTP_BAD_REQUEST;
            }
            $self->{ras}->{$rname} = $ra;
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
        eval { ($revnum, $props) = $ra->get_file($uri, $SVN::Core::INVALID_REVNUM, undef) };
    }
    # проверяем, есть ли файл
    if (!$props)
    {
        if ($@ && $@ =~ /405\s+Method\s+Not\s+Allowed/so)
        {
            warn "Unknown repository '$rname': $@";
            return HTTP_BAD_REQUEST;
        }
        warn "File '$uri' not found in Subversion repository '$rname'".($@ ? ": $@" : "");
        return NOT_FOUND;
    }
    # проверка значения свойства
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
            warn "Denied access to '$uri' from Subversion repository '$rname'";
            return FORBIDDEN;
        }
    }
    # кэшируем файл, если нужно
    my $path = $self->{params}->{cache_path} . '/' . $rname . $uri;
    my $dir = $path;
    $dir =~ s!/+[^/]*$!!so;
    unless (-d $dir || make_path($dir))
    {
        warn "Failed to create path '$dir'";
        return SERVER_ERROR;
    }
    my $fd;
    if (-f $path && open $fd, "<$path.rev")
    {
        local $/ = undef;
        my $cached_rev = <$fd>;
        close $fd;
        $cached_rev =~ s/^\s*//so;
        $cached_rev =~ s/\s*$//so;
        # если закэшировано более или менее старое - пропускаем
        return DECLINED if $revnum <= $cached_rev;
    }
    # записываем содержимое файла
    eval
    {
        die "Could not open $path: $!" unless open $fd, ">$path";
        ($revnum, $props) = $ra->get_file($uri, $revnum, $fd);
        close $fd;
        die "Could not open $path.rev: $!" unless open $fd, ">$path.rev";
        print $fd $revnum;
        close $fd;
    };
    if ($@)
    {
        warn "Failed to checkout '$uri' @ rev.$revnum from Subversion repository '$rname' into local file '$path': $@";
        return SERVER_ERROR;
    }
    # ну и пускай апач его того, отдаёт
    return DECLINED;
}

1;
__END__
