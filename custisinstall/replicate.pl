#!/usr/bin/perl
# OBSOLETE: better use PHP version "replicate.php".

# A script for MediaWiki4Intranet page replication, automatically
# replicating used images and templates, and supporting incremental
# replication.
# Скрипт для репликации вики-страниц между разными MediaWiki,
# поддерживает автоматическую репликацию использованных изображений
# и шаблонов, и инкрементальную репликацию.

# REQUIRES modified MediaWiki import/export mechanism, see MediaWiki4Intranet patch:
# http://wiki.4intra.net/MW_Import_Export

# ТРЕБУЕТ модифицированного механизма импорта/экспорта MediaWiki, см. патч MediaWiki4Intranet:
# http://wiki.4intra.net/MW_Import_Export

use strict;
use constant HELP_TEXT => <<EOF;
MediaWiki4Intranet replication script
Copyright (c) 2010+ Vitaliy Filippov <vitalif\@mail.ru>

USAGE: $0 [OPTIONS] <replication-config.ini> [targets...]

OPTIONS:

-t HOURS
  only select pages which were changed during last HOURS hours.
  I.e. if the replication script is ran each day, you can specify -t 24 to
  export only pages changed since last run, or better -t 25 to allow some
  overlap with previous day and make replication more reliable.

-t 'YYYY-MM-DD[ HH:MM:SS]'
  same as above, but specify date/time, not the relative period in hours.

-i
  when using regular incremental replication (-t option), the following
  situation may be possible:
  * template was created, say, on 2011-10-11
  * it is outside replication category, therefore does not replicate by itself
    (-t 24 is used each day)
  * article was created, say, on 2011-10-13, in replication category
  * so article replicates 2011-10-14, but the template does not
    (because it's not modified during last 24 hours)
  This replication script by default ignores last modification date for
  templates and images. You can change this behaviour using this -i option.

When called without target list, $0 will attempt to replicate all targets
found in config file. There must be 2 sections in config file according to
each target and named "<Target>SourceWiki" and "<Target>DestinationWiki".

Config file fragment syntax (Replace __Test__ with desired [target] name):

[__Test__SourceWiki]
URL=<source wiki url>
Category=<source category name for selecting pages>
NotCategory=<source category name for replication denial>
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

my $BUFSIZE = 0x10000;

my $since_time;
my $ignore_since_images = 1;
my $config_file;
my @targets;

for (my $i = 0; $i < @ARGV; $i++)
{
    if ($ARGV[$i] eq '-t')
    {
        $since_time = $ARGV[++$i];
        if ($since_time !~ /^\s*(\d{4,}-\d{2}-\d{2}(?:\s+\d{2}:\d{2}:\d{2})?)\s*$/s)
        {
            $since_time = int(time - 3600*$since_time);
            $since_time = strftime("%Y-%m-%d %H:%M:%S", localtime($since_time));
        }
        else
        {
            $since_time = $1;
        }
    }
    elsif ($ARGV[$i] eq '-i')
    {
        $ignore_since_images = 0;
    }
    elsif (!defined $config_file)
    {
        $config_file = $ARGV[$i];
    }
    else
    {
        push @targets, lc $ARGV[$i];
    }
}

my $config = read_config($config_file) || die HELP_TEXT();

$| = 1;

my $replicating;
sub logp { strftime("[%Y-%m-%d %H:%M:%S] [$replicating]", localtime) }

my $log;
@targets = keys %$config unless @targets;
for (@targets)
{
    # Replication targets
    $replicating = $_;
    print logp()." Begin replication\n";
    eval { replicate($config->{$_}->{src}, $config->{$_}->{dest}) };
    print STDERR logp()." Could not replicate:\n$@" if $@;
}

# Login into wiki described by $params with LWP user agent $ua,
# $msgdesc is the wiki description for log messages
sub login_into
{
    my ($ua, $params, $msgdesc) = @_;
    my $uri = URI->new($params->{url})->canonical;
    $ua->credentials($uri->host_port, undef, $params->{basiclogin} || undef, $params->{basicpassword});
    if ($params->{user} && $params->{password})
    {
        my $response = $ua->request(GET("$params->{url}/index.php?title=Special:UserLogin"));
        die logp()." Could not retrieve login form from the $msgdesc: ".$response->status_line
            unless $response->code == 200;
        my ($token) = $response->content =~ /<input[^<>]*name="wpLoginToken"[^<>]*value="([^"]+)"[^<>]*>/so;
        $response = $ua->request(POST("$params->{url}/index.php?title=Special:UserLogin&action=submitlogin&type=login",
            Content => [
                wpName         => $params->{user},
                wpPassword     => $params->{password},
                wpLoginAttempt => 1,
                wpLoginToken   => $token,
            ],
        ));
        die logp()." Could not login into $msgdesc under user '$params->{user}': ".$response->status_line
            unless $response->code == 302;
    }
}

# Generate export page list from wiki $url using $params and $desc as error description
sub page_list_load
{
    my ($ua, $url, $desc, $params) = @_;
    my $response = $ua->request(
        POST "$url/index.php?title=Special:Export&action=submit",
        [
            addcat => "Добавить",
            @$params
        ],
    );
    unless ($response->is_success)
    {
        die logp()." Could not retrieve page list from $desc ".
            "($url/index.php?title=Special:Export&action=submit): ".
            $response->status_line;
    }
    my $text = $response->content;
    ($text) = $text =~ m!<textarea[^<>]*>(.*?)</textarea>!iso;
    $text =~ s/^\s*//so;
    $text =~ s/\s*$//so;
    decode_entities($text);
    return $text;
}

# Retrieve list of Wiki pages from category $cat,
# NOT in category $notcat, with all used images and
# templates by default, but only modified after $modifydate
sub page_list
{
    my ($ua, $src, $cat, $notcat, $modifydate, $ignore_since_images) = @_;
    $cat ||= '';
    $notcat ||= '';
    $modifydate ||= '';
    $ignore_since_images = $ignore_since_images && $modifydate ne '';
    my $desc = "Category:$cat";
    $desc .= " MINUS category:$notcat" if $notcat ne '';
    $desc .= ", modified after $modifydate" if $modifydate ne '';
    $desc .= ", with all used images/templates" if !$ignore_since_images;
    my $text = page_list_load($ua, $src->{url}, $desc, [
        catname     => $cat,
        notcategory => $notcat,
        modifydate  => $modifydate,
        ($ignore_since_images ? () : (
            templates => 1,
            images    => 1,
        ))
    ]);
    if (!$text)
    {
        print logp()." No pages need replication in $desc\n";
    }
    elsif ($ignore_since_images)
    {
        # Add templates and images in a separate request, without passing modifydate
        $text = page_list_load($ua, $src->{url}, $desc, [
            notcategory => ($notcat ||= ''),
            templates   => 1,
            images      => 1,
            pages       => $text,
        ]);
    }
    return $text;
}

sub replicate
{
    my ($src, $dest) = @_;
    my $ua = MYLWPUserAgent->new;
    $ua->cookie_jar(HTTP::Cookies->new);
    my ($uri, $response);
    # Login into source wiki
    login_into($ua, $src, 'source wiki');
    # Read page list for replication
    my $text = page_list($ua, $src, $src->{category}, $src->{notcategory}, $since_time, $ignore_since_images);
    if (!$text)
    {
        # No pages
        return 1;
    }
    my $ts = clock_gettime(CLOCK_REALTIME);
    # Read export XML / multipart file
    my $fh = File::Temp->new;
    my $fn = $fh->filename;
    my $auth;
    $auth = 'Basic '.encode_base64($src->{basiclogin}.':'.$src->{basicpassword}) if $src->{basiclogin};
    $response = $ua->request(
        POST("$src->{url}/index.php?title=Special:Export&action=submit", [
            images        => 1,
            selfcontained => 1,
            wpDownload    => 1,
            pages         => $text,
            curonly       => 1,
            ($src->{removeconfidential} ? () : (confidential => 1)),
            ($src->{fullhistory}        ? () : (curonly => 1)),
        ]),
        $fn, # Let LWP::UserAgent write response content into this file
    );
    die logp()." Could not retrieve export XML file from '$src->{url}/index.php?title=Special:Export&action=submit': ".$response->status_line
        unless $response->is_success;
    my $tx = clock_gettime(CLOCK_REALTIME);
    print logp().sprintf(" Retrieved %d bytes in %.2f seconds\n", -s $fn, $tx-$ts);
    # Login into destination wiki
    login_into($ua, $dest, 'destination wiki');
    # Retrieve token for importing
    $response = $ua->request(GET "$dest->{url}/index.php?title=Special:Import");
    die logp()." Could not retrieve Special:Import page from '$dest->{url}/index.php?title=Special:Import: ".$response->status_line
        unless $response->is_success;
    $text = $response->content;
    my $token = $text =~ /<input([^<>]*name="editToken"[^<>]*)>/iso &&
        $1 =~ /value=\"([^\"]*)\"/iso && $1 || undef;
    # Run import
    $response = $ua->request(POST("$dest->{url}/index.php?title=Special:Import&action=submit",
        Content_Type => 'form-data',
        Content      => [
            source    => 'upload',
            editToken => $token,
            xmlimport => [ $fn ],
        ],
    ));
    die logp()." Could not import into '$dest->{url}/index.php?title=Special:Import&action=submit': ".$response->status_line
        unless $response->is_success;
    die logp()." Could not import into $dest->{url}: $1" if $response->content =~ /<p[^<>]*class\s*=\s*["']?error[^<>]*>\s*(.*?)\s*<\/p\s*>/iso;
    my $tp = clock_gettime(CLOCK_REALTIME);
    print sprintf(logp()." Imported in %.2f seconds\n", $tp-$tx);
    $text = $response->content;
    # Extract the import report
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
    # Finished
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
