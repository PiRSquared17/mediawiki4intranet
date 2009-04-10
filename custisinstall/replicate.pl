#!/usr/bin/perl
# Автоматический скрипт репликации вики-статей
# Реплицирует как сами статьи, так и изображения, используемые в них

use strict;

BEGIN
{
    require File::Basename;
    my ($a) = $0 =~ /^(.*)$/iso;
    chdir(File::Basename::dirname($a));
}

use lib qw(.);
use URI;
use File::Path 2.07;
use File::Temp;
use Time::HiRes qw(CLOCK_REALTIME clock_gettime);
use LWP::UserAgent;
use Digest::SHA1;
use IPC::Run;

use MIME::Base64;
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
ForceImageDownload=<'yes' or 'no' (default), 'yes' means force image fetching>
BasicLogin=<HTTP basic auth username, if needed>
BasicPassword=<HTTP basic auth password, if needed>

[__Test__DestinationWiki]
URL=<destination wiki url>
Path=<destination wiki installation path>
Remote=<username\@server for remote image publishing via SCP/SSH>
RemoteIncoming=<incoming image path on remote server>
BasicLogin=<HTTP basic auth username, if needed>
BasicPassword=<HTTP basic auth password, if needed>
User=<name of a user having import rights in destination wiki>
Password=<his password>
SwitchUser=<UNIX username to change to before copying files into Wiki directory>
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
    $ua->credentials($uri->host_port, undef, $src->{basiclogin} || undef, $src->{basicpassword});
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
    my $fh = File::Temp->new;
    my $fn = $fh->filename;
    my $auth;
    $auth = 'Basic '.encode_base64($src->{basiclogin}.':'.$src->{basicpassword}) if $src->{basiclogin};
    $response = $ua->request(
        POST("$src->{url}/index.php?title=Special:Export&action=submit", [
            templates  => 1,
            images     => 1,
            wpDownload => 1,
            curonly    => !$src->{fullhistory} ? 1 : 0,
            pages      => $text,
        ])
    );
    die "[$targetname] Could not retrieve export XML file from '$src->{url}/index.php?title=Special:Export&action=submit': ".$response->status_line
        unless $response->is_success;
    my $text = $response->content;
    $response->content('');
    $text =~ s/(<src[^<>]*>)(.*?)(<\/src\s*>)/$1.enqueue($2,$src->{url},$dest->{url},$dest->{path},$q,$auth,$src->{forceimagedownload}).$3/egiso;
    $text =~ s/&lt;!--\s*begindsp\s*\@?\s*--&gt;.*?&lt;!--\s*enddsp\s*\@?\s*--&gt;//giso;
    print $fh $text;
    my $tx = clock_gettime(CLOCK_REALTIME);
    print sprintf("[$targetname] Retrieved %d bytes in %.2f seconds\n", tell($fh), $tx-$ts);
    close $fh;
    # Дожидаемся сливания картинок
    my $total = 0;
    1 while my $response = $q->{async}->wait_for_next_response;
    unless ($dest->{remote})
    {
        # Публикуем картинки локально, вызывая PHP код
        my $listfh = File::Temp->new;
        my $listfn = $listfh->filename;
        chmod 0644, $listfn;
        my $publish = 0;
        for (@{$q->{fnseq}})
        {
            $total += tell $q->{fhby}->{$_};
            close $q->{fhby}->{$_};
            # Проверяем контрольные суммы
            if ($src->{forceimagedownload} || sha1_file($q->{fhby}->{$_}->filename) ne sha1_file($q->{expath}->{$_}))
            {
                print $listfh "$_\n".$q->{fhby}->{$_}->filename."\n";
                $publish++;
            }
        }
        if ($publish > 0)
        {
            print "[$targetname] Invoking PHP\n";
            my $cmd = "$dest->{path}/custisinstall/loadimages.php < $listfn";
            $cmd = "su $dest->{switchuser} -s /bin/sh -c '$cmd'" if $dest->{switchuser};
            system($cmd);
        }
    }
    else
    {
        # Публикуем картинки удалённо, по SCP, на другом конце ожидается ./inimages.pl
        my @publish = ();
        my ($in, $out, $err, $rd, $do, $sha1);
        $rd = IPC::Run::start(['ssh', $dest->{remote}, '-o', 'BatchMode=yes'], \$in, \$out, \$err);
        die "[$targetname] Failed to open SSH session to '$dest->{remote}'"
            unless $rd;
        for (@{$q->{fnseq}})
        {
            $total += tell $q->{fhby}->{$_};
            close $q->{fhby}->{$_};
            # Проверяем контрольные суммы
            $do = $src->{forceimagedownload};
            if (!$do)
            {
                $| = 1;
                $in .= "sha1sum '$q->{expath}->{$_}'\n";
                IPC::Run::pump($rd) until "$out\n$err" =~ /\Q$q->{expath}->{$_}\E/s;
                if ($out =~ /([a-f0-9]{40})\s*\Q$q->{expath}->{$_}\E/s)
                {
                    $sha1 = $1;
                }
                else
                {
                    $sha1 = '';
                }
                $do = 1 if sha1_file($q->{fhby}->{$_}->filename) ne $sha1;
            }
            push @publish, $q->{fhby}->{$_}->filename if $do;
        }
        IPC::Run::finish($rd);
        if (@publish)
        {
            print "[$targetname] Pausing $dest->{remoteincoming} monitoring on $dest->{remote}\n";
            system "ssh '$dest->{remote}' -o BatchMode=yes 'touch $dest->{remoteincoming}/.pause'";
            print "[$targetname] Uploading ".scalar(@publish)." objects to $dest->{remote}:$dest->{remoteincoming}/\n";
            system "scp -B ".join(" ", map { "'$_'" } @publish)." '$dest->{remote}:$dest->{remoteincoming}/'";
            print "[$targetname] Resuming $dest->{remoteincoming} monitoring on $dest->{remote}\n";
            system "ssh '$dest->{remote}' -o BatchMode=yes 'rm $dest->{remoteincoming}/.pause'";
        }
    }
    my $ti = clock_gettime(CLOCK_REALTIME);
    print sprintf("[$targetname] Retrieved %d objects (total %d bytes) in %.2f seconds\n", scalar(keys %{$q->{fhby}}), $total, $ti-$tx);
    # Логинимся по назначению, если надо
    $uri = URI->new($dest->{url})->canonical;
    $ua->credentials($uri->host_port, undef, $dest->{basiclogin} || undef, $dest->{basicpassword});
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
    my ($url, $wikiurl, $towiki, $topath, $q, $auth, $force) = @_;
    $url =~ s/^$wikiurl//s;
    $url =~ s/^\/*//so;
    # Уже поставлено в очередь?
    return $towiki.'/'.$url if $q->{alr}->{$url};
    $q->{alr}->{$url} = 1;
    my $fh;
    my $fn = "$topath/$url";
    $fn = uri_unescape($fn);
    # архивные картинки вызывают странные баги в MediaWiki, так что пропускаем их.
    # - баги типа Fatal error: Cannot redeclare wfspecialupload() (previously
    # declared in /home/www/localhost/WWW/wiki/includes/specials/SpecialUpload.php:12)
    # in /home/www/localhost/WWW/wiki/includes/specials/SpecialUpload.php on line 15
    return $towiki.'/'.$url if $fn =~ /!/;
    my @mtime = ([stat $fn]->[9]);
    my $exfn = $fn;
    # Чтобы не перезасасывать неизменённые файлы
    if (!$force && $mtime[0])
    {
        @mtime = (If_Modified_Since => time2str($mtime[0]));
    }
    else
    {
        @mtime = ();
    }
    push @mtime, Authorization => $auth if $auth;
    $fn =~ s/^.*\///so;
    $q->{expath}->{$fn} = $exfn;
    $q->{async}->add_with_opts(
        GET("$wikiurl/$url", @mtime),
        {
            callback => sub
            {
                if (length $_[0])
                {
                    my $fh;
                    unless ($fh = $q->{fhby}->{$fn})
                    {
                        $fh = $q->{fhby}->{$fn} = File::Temp->new(TEMPLATE => "/tmp/tempXXXX", SUFFIX => "^$fn");
                        chmod 0644, $fh->filename;
                        push @{$q->{fnseq}}, $fn;
                    }
                    print $fh $_[0];
                }
            }
        }
    );
    return $towiki.'/'.$url;
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
            if ($k eq 'url' || $k eq 'path' || $k eq 'remoteincoming')
            {
                $v =~ s!/+$!!so;
            }
            elsif ($k eq 'fullhistory' || $k eq 'forceimagedownload')
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

sub sha1_file
{
    my ($fn) = @_;
    my $hash = '';
    if (open FH, "<$fn")
    {
        $hash = Digest::SHA1->new;
        $hash->addfile(*FH);
        $hash = $hash->hexdigest;
        close FH;
    }
    return $hash;
}

package MYLWPUserAgent;

use base 'LWP::UserAgent';

sub get_basic_credentials
{
    my ($self, $realm, $uri, $proxy) = @_;
    return @{ $self->{basic_authentication}->{$uri->host_port}->{""} || [] };
}

1;
__END__
