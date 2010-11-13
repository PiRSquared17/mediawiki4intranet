#!/usr/bin/perl
# TODO (Bug 71834) переписать на PHP
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
use POSIX qw(strftime);
use File::Path 2.07;
use File::Temp;
use Time::HiRes qw(CLOCK_REALTIME clock_gettime);
use LWP::UserAgent;
use Digest::SHA1;

use MIME::Base64;
use URI::Escape;
use HTTP::Cookies;
use HTTP::Date;
use HTTP::Request::Common;
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
RemoveConfidential=<'yes' or 'no' (default)>
FullHistory=<'yes' or 'no' (default), 'yes' replicates all page revisions, not only the last one>
BasicLogin=<HTTP basic auth username, if needed>
BasicPassword=<HTTP basic auth password, if needed>

[__Test__DestinationWiki]
URL=<destination wiki url>
BasicLogin=<HTTP basic auth username, if needed>
BasicPassword=<HTTP basic auth password, if needed>
User=<name of a user having import rights in destination wiki>
Password=<his password>
EOF

$| = 1;

my $replicating;
sub logp { strftime("[%Y-%m-%d %H:%M:%S] [$replicating]", localtime) }

my $log;
my @targets = map { lc } @ARGV;
@targets = keys %$config unless @targets;
for (@targets)
{
    # replicate targets
    $replicating = $_;
    print logp()." Begin replication\n";
    eval { replicate($config->{$_}->{src}, $config->{$_}->{dest}) };
    print STDERR logp()." Could not replicate:\n$@" if $@;
}

sub replicate
{
    my ($src, $dest) = @_;
    my $ua = MYLWPUserAgent->new;
    $ua->cookie_jar(HTTP::Cookies->new);
    my ($uri, $response);
    # Логинимся в исходную вики, если надо
    $uri = URI->new($src->{url})->canonical;
    $ua->credentials($uri->host_port, undef, $src->{basiclogin} || undef, $src->{basicpassword});
    if ($src->{user} && $src->{password})
    {
        $response = $ua->request(POST("$src->{url}/index.php?title=Special:UserLogin&action=submitlogin&type=login",
            Content => [
                wpName         => $src->{user},
                wpPassword     => $src->{password},
                wpLoginAttempt => 1,
            ],
        ));
        die logp()." Could not login into source wiki under user '$src->{user}': ".$response->status_line
            unless $response->code == 302;
    }
    # Читаем список страниц категории
    $response = $ua->request(POST "$src->{url}/index.php?title=Special:Export&action=submit", [ addcat => "Добавить", catname => $src->{category} ]);
    die logp()." Could not post '$src->{url}/index.php?title=Special:Export&action=submit&catname=$src->{category}': ".$response->status_line unless $response->is_success;
    my $text = $response->content;
    ($text) = $text =~ m!<textarea[^<>]*>(.*?)</textarea>!iso;
    die logp()." No pages in category $src->{category}" unless $text;
    decode_entities($text);
    my $ts = clock_gettime(CLOCK_REALTIME);
    # Читаем экспортную XML-ку
    my $fh = File::Temp->new;
    my $fn = $fh->filename;
    my $auth;
    $auth = 'Basic '.encode_base64($src->{basiclogin}.':'.$src->{basicpassword}) if $src->{basiclogin};
    $response = $ua->request(
        POST("$src->{url}/index.php?title=Special:Export&action=submit", [
            templates     => 1,
            images        => 1,
            selfcontained => 1,
            wpDownload    => 1,
            curonly       => !$src->{fullhistory} ? 1 : 0,
            pages         => $text,
        ])
    );
    die logp()." Could not retrieve export XML file from '$src->{url}/index.php?title=Special:Export&action=submit': ".$response->status_line
        unless $response->is_success;
    my $text = $response->content;
    $response->content('');
    my $text2 = '';
    # Нужно вырезать "конфиденциальные данные"
    if ($src->{removeconfidential})
    {
        if ($text =~ /^Content-Type:\s*multipart[^\n]*boundary=([^\n]+)\n\1\n/so)
        {
            # из файлов и т.п. вырезать ничего не нужно!
            $text2 = index($text, $&, length $&);
            $text2 = substr($text, $text2, length($text)-$text2, '');
        }
        $text =~ s/<!--\s*begindsp\s*\@?\s*-->.*?<!--\s*enddsp\s*\@?\s*-->//giso;
        $text =~ s/\{\{CONFIDENTIAL-BEGIN.*?\{\{CONFIDENTIAL-END.*?\}\}//giso;
    }
    print $fh $text;
    print $fh $text2;
    my $tx = clock_gettime(CLOCK_REALTIME);
    print sprintf(logp()." Retrieved %d bytes in %.2f seconds\n", tell($fh), $tx-$ts);
    close $fh;
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
        die logp()." Could not login into destination wiki under user '$dest->{user}': ".$response->status_line
            unless $response->code == 302;
    }
    # Вытаскиваем editToken, мля. Какой от него толк - хрен знает.
    $response = $ua->request(GET "$dest->{url}/index.php?title=Special:Import");
    die logp()." Could not retrieve Special:Import page from '$dest->{url}/index.php?title=Special:Import: ".$response->status_line
        unless $response->is_success;
    $text = $response->content;
    my $token = $text =~ /<input([^<>]*name="editToken"[^<>]*)>/iso &&
        $1 =~ /value=\"([^\"]*)\"/iso && $1 || undef;
    # Запускаем импорт исправленной XML-ки
    $response = $ua->request(POST("$dest->{url}/index.php?title=Special:Import&action=submit",
        Content_Type => 'form-data',
        Content      => [
            source    => 'upload',
            editToken => $token,
            xmlimport => [ $fn ],
        ],
    ));
    die logp()." Could not import XML data into '$dest->{url}/index.php?title=Special:Import&action=submit': ".$response->status_line
        unless $response->is_success;
    die logp()." Could not import XML data into $dest->{url}: $1" if $response->content =~ /<p[^<>]*class\s*=\s*["']?error[^<>]*>\s*(.*?)\s*<\/p\s*>/iso;
    my $tp = clock_gettime(CLOCK_REALTIME);
    print sprintf(logp()." Imported in %.2f seconds\n", $tp-$tx);
    $text = $response->content;
    # Извлекаем отчёт
    my ($report) = $text =~ /<!--\s*start\s*content\s*-->.*?<ul>(.*?)<\/ul>/iso;
    for ($report)
    {
        s/&nbsp;/ /giso;
        s/\s+/ /giso;
        s/<li[^<>]*>/\n/giso;
        s/<\/?[a-z0-9_:\-]+(\/?\s+[^<>]*)?>//giso;
        decode_entities($_);
        s/^\s+//so;
        s/\s+$//so;
    }
    die logp()." Could not replicate, no import report found in response content:\n$text\n"
        unless $report;
    print logp()." Report:\n$report\n";
    # Всё ОК
    1;
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
            if ($k eq 'url')
            {
                $v =~ s!/+$!!so;
            }
            elsif ($k eq 'fullhistory' || $k eq 'removeconfidential')
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
