#!/usr/bin/perl
# Автоматический скрипт репликации вики-статей
# Реплицирует как сами статьи, так и изображения, используемые в них

use strict;
use lib qw(.);

use URI;
use File::Path 2.07;
use File::Temp qw(tempfile);
use Time::HiRes qw(CLOCK_REALTIME clock_gettime);
use LWP::UserAgent;

use URI::Escape;
use HTTP::Cookies;
use HTTP::Date;
use HTTP::Request::Common;
use HTTP::Async;
use HTML::Entities;

my $config = read_config(shift @ARGV) || die <<EOF;
MediaWiki replicate script by Vitaliy Filippov <vfilippov\@custis.ru>

USAGE: $0 <replication-config.ini> [targets...]
When called without target list, $0 will attempt to replicate all targets
found in config file. There must be 2 sections in config file according to
each target and named "<Target>SourceWiki" and "<Target>DestinationWiki".

Config file fragment syntax (Replace __Test__ with desired [target] name):

[__Test__SourceWiki]
URL=<source wiki url>
Category=<source category name>
FullHistory=<'yes' or 'no' (default), 'yes' replicates all page revisions, not only the last one>
BasicLogin=<HTTP basic auth username, if needed>
BasicPassword=<HTTP basic auth password, if needed>

[__Test__DestinationWiki]
URL=<destination wiki url>
Path=<destination wiki installation path>
BasicLogin=<HTTP basic auth username, if needed>
BasicPassword=<HTTP basic auth password, if needed>
User=<name of a user having import rights in destination wiki>
Password=<his password>
EOF

my @targets = map { lc } @ARGV;
@targets = keys %$config unless @targets;
for (@targets)
{
    # replicate targets
    print "[$_] Begin replication\n";
    eval { replicate($config->{$_}->{src}, $config->{$_}->{dest}, $_) };
    print STDERR "[$_] Could not replicate:\n$@" if $@;
}

sub replicate
{
    my ($src, $dest, $targetname) = @_;
    my $ua = MYLWPUserAgent->new;
    $ua->cookie_jar(HTTP::Cookies->new);
    my $uri = URI->new($src->{url})->canonical;
    $ua->credentials($uri->host.':'.$uri->port, undef, $src->{basiclogin} || undef, $src->{basicpassword});
    # Читаем список страниц категории
    my $response = $ua->request(POST "$src->{url}/index.php?title=Special:Export&action=submit", [ addcat => "Добавить", catname => $src->{category} ]);
    die "[$targetname] Could not post '$src->{url}/index.php?title=Special:Export&action=submit&catname=$src->{category}': ".$response->status_line unless $response->is_success;
    my $text = $response->content;
    ($text) = $text =~ m!<textarea[^<>]*>(.*?)</textarea>!iso;
    die "[$targetname] No pages in category $src->{category}" unless $text;
    decode_entities($text);
    my $ts = clock_gettime(CLOCK_REALTIME);
    my $q = { async => HTTP::Async->new };
    # Читаем экспортную XML-ку
    my ($fh, $fn) = tempfile();
    $response = $ua->request(
        POST("$src->{url}/index.php?title=Special:Export&action=submit", [
            templates  => 1,
            images     => 1,
            wpDownload => 1,
            curonly    => !$src->{fullhistory} ? 1 : 0,
            pages      => $text,
        ]),
        sub
        {
            # Меняем ссылки в тексте (тупо регэкспом) и параллельно (HTTP::Async) сливаем картинки
            $_[0] =~ s/(<src[^<>]*>)(.*?)(<\/src\s*>)/$1.enqueue($2,$src->{url},$dest->{url},$dest->{path},$q).$3/egiso;
            print $fh $_[0];
        }
    );
    die "[$targetname] Could not retrieve export XML file from '$src->{url}/index.php?title=Special:Export&action=submit': ".$response->status_line
        unless $response->is_success;
    my $tx = clock_gettime(CLOCK_REALTIME);
    print sprintf("[$targetname] Retrieved %d bytes in %.2f seconds\n", tell($fh), $tx-$ts);
    close $fh;
    # Дожидаемся сливания картинок
    my $total = 0;
    1 while my $response = $q->{async}->wait_for_next_response;
    for (@{$q->{fh}})
    {
        $total += tell $_;
        close $_;
    }
    my $ti = clock_gettime(CLOCK_REALTIME);
    print sprintf("[$targetname] Retrieved %d objects (total %d bytes) in %.2f seconds\n", scalar(@{$q->{fh}}), $total, $ti-$tx);
    # Логинимся по назначению, если надо
    $uri = URI->new($dest->{url})->canonical;
    $ua->credentials($uri->host.':'.$uri->port, undef, $dest->{basiclogin} || undef, $dest->{basicpassword});
    if ($dest->{user} && $dest->{password})
    {
        $response = $ua->request(POST("$dest->{url}/index.php?title=Special:UserLogin&action=submitlogin&type=login",
            Content => [
                wpName         => $dest->{user},
                wpPassword     => $dest->{password},
                wpLoginAttempt => 1,
            ],
        ));
        die "[$targetname] Could not login into destination wiki under user '$dest->{user}': ".$response->status_line
            unless $response->code == 302;
    }
    # Запускаем импорт исправленной XML-ки
    $response = $ua->request(POST("$dest->{url}/index.php?title=Special:Import&action=submit",
        Content_Type => 'form-data',
        Content      => [
            source    => 'upload',
            xmlimport => [ $fn ],
        ],
    ));
    die "[$targetname] Could not import XML data into '$dest->{url}/index.php?title=Special:Import&action=submit': ".$response->status_line
        unless $response->is_success;
    die "[$targetname] Could not import XML data into $dest->{url}: $1" if $response->content =~ /<p[^<>]*class\s*=\s*["']?error[^<>]*>\s*(.*?)\s*<\/p\s*>/iso;
    my $tp = clock_gettime(CLOCK_REALTIME);
    print sprintf("[$targetname] Imported in %.2f seconds\n", $tp-$ti);
    # Всё ОК
    1;
}

sub enqueue
{
    my ($url, $wikiurl, $towiki, $topath, $q) = @_;
    $url =~ s/^$wikiurl//s;
    $url =~ s/^\/*//so;
    my $fh;
    my $fn = "$topath/$url";
    $fn = uri_unescape($fn);
    my @mtime = ([stat $fn]->[9]);
    # Чтобы не перезасасывать неизменённые файлы
    if ($mtime[0])
    {
        @mtime = (If_Modified_Since => time2str($mtime[0]));
    }
    else
    {
        @mtime = ();
    }
    my $path = $fn;
    $path =~ s!/*[^/]*$!!so;
    mkpath($path) unless -d $path;
    # Уже поставлено в очередь?
    return $towiki.'/'.$url if $q->{alr}->{$url};
    $q->{alr}->{$url} = 1;
    my $e = -e $fn;
    if ($e && open ($fh, "+<", $fn) ||
        !$e && open ($fh, ">", $fn))
    {
        binmode $fh;
        push @{$q->{fh}}, $fh;
        $q->{async}->add_with_opts(
            GET("$wikiurl/$url", @mtime),
            {
                callback => sub
                {
                    if (length $_[0])
                    {
                        truncate $fh, 0 unless tell $fh;
                        print $fh $_[0];
                    }
                }
            }
        );
        return $towiki.'/'.$url;
    }
    else
    {
        warn "Could not write into $fn: $!";
    }
    return $url;
}

sub read_config
{
    my ($file) = @_;
    my $fh;
    return undef unless open $fh, "<$file";
    my $cfg = {};
    my $target;
    my $th = {};
    my ($key, $h, $k, $v);
    while (<$fh>)
    {
        s/\s+$//so;
        s/(^|\s+)(;|\#).*$//so;
        next unless $_;
        if (/^\s*\[([^\]]*)(Source|Destination)Wiki\]\s*$/iso)
        {
            $target = lc $1;
            $key = lc $2 eq 'source' ? 'src' : 'dest';
            $th->{$target} = 1 if $cfg->{$target}->{lc $2 eq 'source' ? 'dest' : 'src'};
        }
        elsif (/^\s*([^=]*[^\s=])\s*=\s*(.*)/iso)
        {
            $k = lc $1;
            $v = $2;
            $h = $cfg;
            $h = ($h->{$target}->{$key} ||= {}) if $target && $key;
            if ($k eq 'url' || $k eq 'path')
            {
                $v =~ s!/+$!!so;
            }
            elsif ($k eq 'fullhistory')
            {
                $v = lc $v;
                $v = $v eq 'yes' || $v eq 'true' || $v eq 'on' || $v eq '1' ? 1 : 0;
            }
            $h->{$k} = $v;
        }
    }
    close $fh;
    for (keys %$cfg)
    {
        delete $cfg->{$_} unless $th->{$_};
    }
    return undef unless keys %$cfg;
    return $cfg;
}

package MYLWPUserAgent;

use base 'LWP::UserAgent';

sub get_basic_credentials
{
    my ($self, $realm, $uri, $proxy) = @_;
    return $self->credentials($uri->host_port, undef);
}

1;
__END__
