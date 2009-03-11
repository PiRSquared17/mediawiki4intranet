#!/usr/bin/perl
# Автоматический скрипт репликации вики-статей
# Реплицирует как сами статьи, так и изображения, используемые в них

use strict;
use Time::HiRes;
use File::Temp qw(tempfile);
use LWP::UserAgent;
use HTTP::Date;
use HTTP::Request::Common;
use HTTP::Async::Polite;
use HTML::Entities;

# $0 http://wiki.office.custis.ru/wiki/ CustisWikiToLib http://lib.custis.ru/ /usr/share/wikis/lib/
my $fullhistory = 0;
while (@ARGV && $ARGV[0] =~ /^-/)
{
    $fullhistory = 1 if $ARGV[0] eq '-f' || $ARGV[0] eq '--full-history';
    shift @ARGV;
}

die "USAGE: $0 [-f|--full-history] <SourceWikiURL> <SourceCategoryName> <DestinationWikiUrl> <DestinationWikiDirectory>" if @ARGV < 4;
my ($wikiurl, $category, $towiki, $topath) = @ARGV;

$wikiurl =~ s/\/+$//so;
$towiki  =~ s/\/+$//so;
$topath  =~ s/\/+$//so;

# Читаем список страниц категории
my $ua = LWP::UserAgent->new;
my $response = $ua->request(POST "$wikiurl/index.php?title=Special:Export&action=submit", [ addcat => "Добавить", catname => $category ]);

die "Could not post '$wikiurl/index.php?title=Special:Export&action=submit&catname=$category': ".$response->status_line
    unless $response->is_success;

my $text = $response->content;
($text) = $text =~ m!<textarea[^<>]*>(.*?)</textarea>!iso;

die "No pages in category $category" unless $text;
decode_entities($text);

my $ts = clock_gettime(CLOCK_REALTIME);
my ($fh, $fn) = tempfile();
my $q = { async => HTTP::Async::Polite->new };
# Читаем экспортную XML-ку
$response = $ua->request(
    POST("$wikiurl/index.php?title=Special:Export&action=submit", [
        templates  => 1,
        images     => 1,
        wpDownload => 1,
        curonly    => !$fullhistory,
        pages      => $text,
    ]),
    sub
    {
        # Меняем ссылки в тексте (тупо регэкспом) и параллельно (HTTP::Async) сливаем картинки
        $_[0] =~ s/(<src[^<>]*>)(.*)(<\/src\s*>)/$1.enqueue($2,$wikiurl,$towiki,$topath,$q).$3/egiso;
        print $fh $_[0];
    }
);

die "Could not retrieve export XML file from '$wikiurl/index.php?title=Special:Export&action=submit': ".$response->status_line
    unless $response->is_success;

my $tx = clock_gettime(CLOCK_REALTIME);
print sprintf("Retrieved %d bytes in %.2f seconds\n", tell($fh), $tx-$ts);
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
print sprintf("Retrieved %d objects (total %d bytes) in %.2f seconds\n", scalar(@{$q->{fh}}), $total, $ti-$tx);

# Запускаем импорт исправленной XML-ки
$response = $ua->request(POST("$towiki/index.php?title=Special:Import&action=submit",
    Content_Type => 'form-data',
    Content      => [
        source    => 'upload',
        xmlimport => [ $fn ],
    ],
));

die "Could not import XML data into '$towiki/index.php?title=Special:Import&action=submit': ".$response->status_line
    unless $response->is_success;

my $tp = clock_gettime(CLOCK_REALTIME);
print sprintf("Imported in %.2f seconds\n", $tp-$ti);
# Тихо уходим...
exit;

sub enqueue
{
    my ($url, $wikiurl, $towiki, $topath, $q) = @_;
    if ($url =~ s/^$wikiurl\/*//s)
    {
        my $fh;
        my @mtime = ([stat "$topath/$url"]->[9]);
        # Чтобы не перезасасывать неизменённые файлы
        if ($mtime[0])
        {
            @mtime = (If_Modified_Since => time2str($mtime[0]));
        }
        else
        {
            @mtime = ();
        }
        if (open $fh, "+<", "$topath/$url")
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
    }
    return $url;
}

1;
__END__
