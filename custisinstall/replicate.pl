#!/usr/bin/perl
# TODO (Bug 71834) rewrite into PHP and possibly use API for import
# A script for Wiki page replication
# Replicates pages with used images and templates, using MW4Intranet patch:
# http://wiki.4intra.net/MW_Import_Export

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

my $BUFSIZE = 0x10000;

my $config = read_config(shift @ARGV) || die <<EOF;
MediaWiki replication script by Vitaliy Filippov <vitalif\@mail.ru>

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

sub replicate
{
    my ($src, $dest) = @_;
    my $ua = MYLWPUserAgent->new;
    $ua->cookie_jar(HTTP::Cookies->new);
    my ($uri, $response);
    # Login into source wiki
    login_into($ua, $src, 'source wiki');
    # Read category page list
    $response = $ua->request(POST "$src->{url}/index.php?title=Special:Export&action=submit", [ addcat => "Добавить", catname => $src->{category} ]);
    die logp()." Could not post '$src->{url}/index.php?title=Special:Export&action=submit&catname=$src->{category}': ".$response->status_line unless $response->is_success;
    my $text = $response->content;
    ($text) = $text =~ m!<textarea[^<>]*>(.*?)</textarea>!iso;
    die logp()." No pages in category $src->{category}" unless $text;
    decode_entities($text);
    my $ts = clock_gettime(CLOCK_REALTIME);
    # Read export XML / multipart file
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
        ]),
        $fn, # Let LWP::UserAgent write response content into this file
    );
    die logp()." Could not retrieve export XML file from '$src->{url}/index.php?title=Special:Export&action=submit': ".$response->status_line
        unless $response->is_success;
    # Optionally filter the file and remove "confidential data"
    if ($src->{removeconfidential})
    {
        $fh = filter_confidential($fh);
        $fn = $fh->filename;
    }
    my $tx = clock_gettime(CLOCK_REALTIME);
    print sprintf(logp()." Retrieved %d bytes in %.2f seconds\n", -s $fn, $tx-$ts);
    # Login into destination wiki
    login_into($ua, $dest, 'destination wiki');
    # Retrieve token for importing
    $response = $ua->request(GET "$dest->{url}/index.php?title=Special:Import");
    die logp()." Could not retrieve Special:Import page from '$dest->{url}/index.php?title=Special:Import: ".$response->status_line
        unless $response->is_success;
    $text = $response->content;
    my $token = $text =~ /<input([^<>]*name="editToken"[^<>]*)>/iso &&
        $1 =~ /value=\"([^\"]*)\"/iso && $1 || undef;
    # Run the import
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

# Filter opened file $fh into a new temporary file and return
# a descriptor of it opened at the beginning
sub filter_confidential
{
    my ($fh) = @_;
    my $fh_filtered = File::Temp->new;
    my $buffer = '';
    my $boundary;
    sysread($fh, $buffer, $BUFSIZE);
    # Check if the file is multipart
    if ($buffer =~ /^(Content-Type:\s*multipart[^\n]*boundary=([^\n]+)\n\2\n)/so)
    {
        $boundary = "$2\n";
        syswrite($fh_filtered, $buffer, length $1);
        substr($buffer, 0, length($1), '');
    }
    my (@p, $found, $overlap);
    my $state = 0;
    $overlap = 22; # max length of string we are searching for + 2 newlines
    $overlap = length $boundary if $boundary && length $boundary > $overlap;
    do
    {
        # Find first substring
        $p[0] = $state < 3 && $boundary ? index($buffer, $boundary) : -1;
        $p[1] = $state == 0 ? index($buffer, '{{CONFIDENTIAL-BEGIN') : -1;
        $p[2] = $state == 1 ? index($buffer, 'CONFIDENTIAL-END}}') : -1;
        $p[3] = $state == 1 ? index($buffer, '</text>') : -1;
        $found = -1;
        for my $i (0..$#p)
        {
            if ($p[$i] >= 0 && ($found < 0 || $p[$found] >= 0 && $p[$found] > $p[$i]))
            {
                $found = $i;
            }
        }
        if ($found <= 0)
        {
            # Nothing found or multipart boundary
            $state = 3 if $found == 0;
            # Allow some overlap to find substrings on buffer boundary
            syswrite($fh_filtered, $buffer, length($buffer) - $overlap);
            $buffer = $overlap ? substr($buffer, -$overlap) : '';
        }
        elsif ($found == 1)
        {
            # Confidential begins, remove empty newlines before {{CONFIDENTIAL-BEGIN}}
            my $str = substr($buffer, 0, $p[$found], '');
            $str =~ s/\s+$//so;
            syswrite($fh_filtered, $str);
            $state = 1;
        }
        elsif ($found == 2)
        {
            # Confidential ends
            substr($buffer, 0, $p[$found]+18, '');
            $state = 0;
        }
        elsif ($found == 3)
        {
            # Revision text ends while we are inside confidential, exit confidential
            substr($buffer, 0, $p[$found], '');
            $state = 0;
        }
        if (length $buffer <= $overlap)
        {
            my $eof = !sysread($fh, $buffer, $BUFSIZE, length $buffer);
            if ($eof)
            {
                # Set $overlap to 0 at EOF
                $overlap = 0;
            }
        }
    } while (length $buffer > $overlap);
    seek($fh_filtered, 0, 0);
    # Replace $fh with new temporary file object
    return $fh_filtered;
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
