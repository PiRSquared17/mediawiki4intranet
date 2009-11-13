#!/usr/bin/perl
# Простой тест для MediaWiki - логинится, идёт на редактирование Test/Keepalive, записывает небольшой контент.
# При этом всё время проверяет ошибки PHP.
# Если что - дохнет и пишет на STDERR ошибку.

use strict;
use POSIX qw(strftime);
use HTTP::Cookies;
use HTTP::Request::Common;

sub logp { strftime("[%Y-%m-%d %H:%M:%S]", localtime) }

# параметры
my $basiclogin = 'benderbot@custis.ru';
my $basicpassword = '';
my $user = 'BenderBot';
my $password = '';
my @url;
my $testpage = 'Test/Keepalive';

while ($_ = shift)
{
    if    ($_ eq '-u')  { $user = shift || $user; }
    elsif ($_ eq '-p')  { $password = shift || $password; }
    elsif ($_ eq '-bu') { $basiclogin = shift || $basiclogin; }
    elsif ($_ eq '-bp') { $basicpassword = shift || $basicpassword; }
    elsif ($_ eq '-t')  { $testpage = shift || $testpage; }
    elsif ($_ eq '-h' || $_ eq '--help')
    {
        help();
    }
    else
    {
        push @url, $_;
    }
}

test_save($_) for @url;
exit 0;

sub help
{
    print <<EOF;
Simple MediaWiki test script.
USAGE: $0 [OPTIONS] URL URL...
OPTIONS:
-u UserName     set mediawiki username
-p Password     set mediawiki user password
-bu login       set login for HTTP authentication
-bp password    set password for HTTP authentication
-t TestPage     set page for testing
-h              print help and exit
EOF
    exit;
}

sub test_save
{
    my ($url) = @_;

    # создаём юзерагента
    my $ua = MYLWPUserAgent->new;
    $ua->cookie_jar(HTTP::Cookies->new);

    # логинимся по назначению, если надо
    my $uri = URI->new($url)->canonical;
    $ua->credentials($uri->host_port, undef, $basiclogin || undef, $basicpassword);
    my $response;
    if ($user && $password)
    {
        $response = $ua->request(POST("$url/index.php?title=Special:UserLogin&action=submitlogin&type=login",
            Content => [
                wpName         => $user,
                wpPassword     => $password,
                wpLoginAttempt => 1,
            ],
        ));
        die logp()." Could not login into wiki '$url' under user '$user': ".$response->status_line
            unless $response->code == 302;
    }

    # вытаскиваем форму редактирования
    $response = $ua->request(GET "$url/index.php?title=Test/Keepalive&action=edit");
    die logp()." Could not retrieve edit page from '$url/index.php?title=Test/Keepalive&action=edit: ".$response->status_line
        unless $response->is_success;
    my $text = $response->content;
    check_php_warnings($text);

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
    $response = $ua->request(POST "$url/index.php?title=Test/Keepalive&action=submit", Content => [
        %$hidden,
        wpTextbox1 => "Страница для автоматических тестов MediaWiki.\n\n".strftime('Automatic test: %Y-%m-%d %H:%M:%S.', localtime),
        wpSummary  => strftime('Automatic test: %Y-%m-%d %H:%M:%S', localtime),
        wpSave     => "Записать страницу",
    ]);
    die logp()." Could not save wikitext into Test/Keepalive: ".$response->status_line."\n".$response->content
        unless $response->code == 302;
    check_php_warnings($response->content);
    my $u = $response->header('Location');
    $response = $ua->request(GET $u);
    die logp()." Could not GET $u" unless $response->code == 200;
    check_php_warnings($response->content);
}

sub check_php_warnings
{
    my ($text) = @_;
    if ($text =~ /^(.*?)<!DOCTYPE/iso)
    {
        $text = $1;
    }
    $text =~ s!</[a-z0-9_:\-]+(\s+[^<>]*|\/)?>!!giso;
    my @warnings = $text =~ /Warning:\s*([^\n]*?in[^\n]*?on\s+line[^\n]*)/giso;
    die logp()." Discovered PHP warning(s):\n".join("\n", @warnings) if @warnings;
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
