#!/usr/bin/perl
# Тесты для MediaWiki:
# 1) логинится.
# 2) идёт на редактирование Test/Keepalive, записывает небольшой контент.
# 3) выбирает случайную страницу методом Special:Random, открывает её, а потом ищет её Sphinx'ом по названию.
# При этом всё время проверяет ошибки PHP.
# Если что - дохнет и пишет на STDERR ошибку.

use utf8;
no warnings 'utf8';
use strict;
use POSIX qw(strftime);
use HTTP::Cookies;
use HTTP::Request::Common;
use HTML::Entities;
use XML::LibXML;

use Encode::Escape;
BEGIN { $\ = '' };

use URI::Escape;

our $logp = '';
sub logp { strftime("[%Y-%m-%d %H:%M:%S]$logp", localtime) }

my $tests = {
    login => {
        ''           => 'login into MediaWiki',
        user         => 'MediaWiki username to test under (default TestBot)',
        password     => 'MediaWiki password to test under',
        httpuser     => 'Username for HTTP authentication (default benderbot@custis.ru)',
        httppassword => 'Password for HTTP authentication',
    },
    save => {
        ''           => 'Save some content to Test/Keepalive or --savepage',
        savepage     => 'Test page (default Test/Keepalive)',
    },
    random_search => {
        ''           => 'Search random page using Sphinx',
        searchurl    => 'URL for search, {PAGE} is page placeholder, {TITLE} is title placeholder (default не скажу)',
    },
    search => {
        ''           => 'Search for specific page using Sphinx',
        searchurl    => 'URL for search, {PAGE} is page placeholder, {TITLE} is title placeholder (default не скажу)',
        searchtitle  => 'Search title',
    },
    checkurl => {
        ''           => 'Check URL for PHP errors and optionally validate it as XML',
        checkurl     => 'URL for checking',
        validate     => 'Perform XML validation on downloaded content',
    },
    categorizedrc => {
        ''           => 'Check categorized Special:RecentChanges for PHP errors',
        checkcat     => 'Test',
    },
};
my $booleans = { validate => 1 };
my @order = qw(login save random_search categorizedrc);

# параметры
my $params = {};
my @url;
my @tests;
my $ct;

while ($_ = shift)
{
    if ($_ eq '--help' || $_ eq '-h')
    {
        help();
    }
    elsif (/^--/)
    {
        my $p = lc $';
        if (!$booleans->{$p})
        {
            my $v = shift;
            if (!exists $ct->{$p})
            {
                $ct->{$p} = $v;
            }
            elsif (!ref $ct->{$p})
            {
                $ct->{$p} = [ $ct->{$p}, $v ];
            }
            else
            {
                push @{$ct->{$p}}, $v;
            }
        }
        else
        {
            $ct->{$p} = 1;
        }
    }
    elsif ($_ eq '-T')
    {
        push @tests, $ct = { '' => shift };
    }
    else
    {
        push @url, $_;
    }
}
@tests = map { { '' => $_ } } @order unless @tests;
@tests = grep { $tests->{$_->{''}} } @tests;
die logp()." no valid test(s) specified" unless @tests;

# создаём юзерагента
my $ua = MYLWPUserAgent->new;
$ua->cookie_jar(HTTP::Cookies->new);
for my $url (@url)
{
    $url =~ s/\/+$//so;
    for my $t (@tests)
    {
        no strict 'refs';
        my $s = 'test_'.$t->{''};
        eval { &$s($url, $t, $ua) };
        die logp()." error during test ".$t->{''}." on url $url:\n".$@ if $@;
    }
}
exit 0;

sub help
{
    print <<EOF;
MediaWiki test script.
USAGE: $0 [-T test1] [test1 options] [-T test2] [test2 options] ... URL1 URL2 ...
OPTIONS:
-h                  Print help and exit
-T test             Run test 'test'. The same test may be ran multiple times.
AVAILABLE TESTS:
EOF
    foreach (keys %$tests)
    {
        my $t = $tests->{$_};
        print "-T $_".(' ' x (17-length $_)).$t->{''}."\n";
        for (keys %$t)
        {
            print "--$_".(' ' x (20-length $_)).$t->{$_}."\n" if $_;
        }
    }
    exit;
}

sub make_request
{
    my ($ua, $req, $msg, $expect) = @_;
    my $response = $ua->request($req);
    $expect = { map { $_ => 1 } $expect =~ /(\d+)/g };
    die logp()." [".$req->uri."] Could not $msg: ".$response->status_line.': '.$response->content
        if !$expect->{$response->code};
    check_php_warnings($response->content);
    return $response;
}

sub test_login
{
    my ($url, $params, $ua) = @_;
    my $bu = $params->{httpuser} || 'benderbot@custis.ru';
    # логинимся по назначению, если надо
    my $uri = URI->new($url)->canonical;
    my $response;
    $ua->credentials($uri->host_port, undef, $bu, $params->{httppassword});
    if ($params->{password})
    {
        $response = make_request($ua, GET("$url/index.php?title=Special:UserLogin"), "retrieve login form", 200);
        my ($token) = $response->content =~ /<input[^<>]*name="wpLoginToken"[^<>]*value="([^"]+)"[^<>]*>/so;
        my $user = $params->{user} || 'TestBot';
        $response = make_request($ua, POST("$url/index.php?title=Special:UserLogin&action=submitlogin&type=login",
            Content => [
                wpName         => $user,
                wpPassword     => $params->{password},
                wpLoginAttempt => 1,
                wpLoginToken   => $token,
            ],
        ), "login into wiki '$url' under user '$user'", 302);
    }
    elsif ($params->{httppassword})
    {
        make_request($ua, GET("$url/index.php/Main_Page"), "login into wiki '$url' under HTTP user '$bu'", '200 302');
    }
}

sub test_save
{
    my ($url, $params, $ua) = @_;
    my $tp = $params->{savepage} || 'Test/Keepalive';

    # вытаскиваем форму редактирования
    my $response = make_request($ua, GET("$url/index.php?title=$tp&action=edit"), "retrieve edit page", 200);
    my $text = $response->content;

    # вытаскиваем скрытые поля
    my ($form) = $text =~ /<form[^<>]*name=["']editform["'][^<>]*>(.*?)<\/form>/iso;
    die logp()." No <form name=\"editform\">.*?</form> in response content found" unless $form;
    my $hidden = [ $form =~ /(<input[^<>]*type=["']hidden["'][^<>]*>)/giso ];
    for (@$hidden)
    {
        my ($v) = /value=["']([^"']*)["']/iso;
        my ($n) = /name=["']([^"']*)["']/iso;
        $_ = [ $n => $v ];
    }
    $hidden = { map { @$_ } @$hidden };

    # записываем тестовый контент
    $response = make_request($ua, POST("$url/index.php?title=$tp&action=submit", Content => [
        %$hidden,
        wpTextbox1  => "Страница для автоматических тестов MediaWiki.\n\n".strftime('Automatic test: %Y-%m-%d %H:%M:%S.', localtime),
        wpSummary   => strftime('Automatic test: %Y-%m-%d %H:%M:%S', localtime),
        wpSave      => "Записать страницу",
        wpMinoredit => 1,
    ]), "save wikitext into $tp", 302);
    my $u = $response->header('Location');
    make_request($ua, GET($u), "request updated page", 200);
}

sub test_random_search
{
    my ($url, $params, $ua) = @_;

    # получаем редирект на случайную страницу
    my ($title, $i, $response, $text) = ('', 0);
    while ((length($title) < 2 || $title eq 'Доступ запрещён' || $title eq 'Permission denied') && $i < 10)
    {
        $response = make_request($ua, GET("$url/index.php/Special:Random"), "retrieve random page redirect", 200);
        $text = $response->content;
        Encode::_utf8_on($text);
        ($title) = $text =~ /wgTitle\s*=\s*\"((?:[^\"\\]+|\\\"|\\\\)+)\"/iso;
        $title = 'Permission denied' if $text =~ /Запрошенное действие могут выполнять только участники из групп/so;
        die $text if $title eq 'Random';
        $title = decode 'unicode-escape', $title;
        $i++;
    }
    return if length $title < 2;

    # ищем эту страницу поиском
    $params->{searchtitle} = $title;
    test_search($url, $params, $ua);
}

sub test_search
{
    my ($url, $params, $ua) = @_;
    my $su = $params->{searchurl} || '/Special:Search?search={TITLE}&limit=20&offset={OFFSET}&ns0=1&ns1=1&ns2=1&ns3=1&ns4=1&ns5=1&ns6=1&ns7=1&ns8=1&ns9=1&ns10=1&ns11=1&ns12=1&ns13=1&ns14=1&ns15=1&ns100=1&ns101=1&ns102=1&ns103=1&ns104=1&ns105=1&redirs=1';
    my $title = $params->{searchtitle} || return;
    my $page = 1;
    my @found;
    my @all_found;
    my $text;
    my $u;
    my $u1 = $su;
    $u = $title;
    Encode::_utf8_on($u);
    $u =~ s!([/\.\(\)\!\#\-])!\\$1!gso;
    Encode::_utf8_off($u);
    $u1 =~ s/\{TITLE\}/uri_escape($u)/gsoe;
    do
    {
        $u = $u1;
        $u =~ s/\{PAGE\}/uri_escape($page)/gsoe;
        $u =~ s/\{OFFSET\}/uri_escape(($page-1)*20)/gsoe;
        $u = "$url/index.php$u";
        $text = make_request($ua, GET($u), "get search page", 200)->content;
        @found = $text =~ /<li>(.*?)<\/li>/giso;
        decode_entities($_) for @found;
        push @all_found, @found;
        return if grep { /\Q$title\E/is } @found;
        $page++;
    } while ($text =~ />\s*$page\s*</is);
    die logp()." Not found: $title. Found pages:\n".join("\n", @all_found)."\n";
}

sub test_checkurl
{
    my ($url, $params, $ua) = @_;
    my $text = make_request($ua, GET($url.$params->{checkurl}), 'check URL', 200)->content;
    if ($params->{validate})
    {
        my $parser = XML::LibXML->new();
        $parser->line_numbers(1) if $parser->can('line_numbers'); # (XML::LibXML > 1.56)
        $parser->load_ext_dtd(0);
        $parser->expand_entities(1);
        my $doc = eval { $parser->parse_string($text) };
        if ($@)
        {
            my $m = $@;
            $m =~ s/^\s*:\s*(\d+)\s*:\s*//so;
            my $l = $1;
            die logp()." XML validation failed at URL '$params->{checkurl}', LINE $l. ERROR: $m";
        }
    }
}

sub test_categorizedrc
{
    my ($url, $params, $ua) = @_;
    test_checkurl($url, { checkurl => '/index.php/Special:RecentChanges?categories='.($params->{checkcat} || 'Test') }, $ua);
}

sub check_php_warnings
{
    my ($text) = @_;
    if ($text =~ /^(.*?)<!DOCTYPE/iso ||
        $text =~ /^(.*?)<\?\s*xml/iso)
    {
        $text = $1;
    }
    $text =~ s!</[a-z0-9_:\-]+(\s+[^<>]*|\/)?>!!giso;
    my @warnings = $text =~ /(Warning|Error|Fatal):\s*([^\n]*?in[^\n]*?on\s+line[^\n]*)/giso;
    die logp()." Discovered PHP warning(s):\n".join("\n", @warnings)."\n\nIn $text\n" if @warnings;
    return 1;
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
