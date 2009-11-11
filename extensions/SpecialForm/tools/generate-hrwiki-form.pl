#!/usr/bin/perl

use utf8;
no warnings 'utf8';
use strict;

my $options = [
    RDBMS      => ['Работали с РСУБД',        qw(Oracle MSSQL MySQL PostgreSQL Firebird Informix IBM_DB2 SAP Sybase)],
    VCS        => ['Системы контроля версий', qw(Bazaar Darcs Git Mercurial BitKeeper TeamWare Perforce CVS Subversion ClearCase PVCS SourceSafe Visual_Studio_Team_System)],
    Lang       => ['Языки программирования',  {JavaWeb => 'Java (web: апплеты...)'}, {JavaClient => 'Java (клиент: Swing/AWT...)'}, {J2EE => 'Java (сервер: J2EE, Servlet...)'}, 'JavaScript', { Cplain => 'C' }, { CPlusPlus => 'C++' }, { CSharp => 'C#' }, 'Visual Basic', 'Python', 'PHP', 'Perl', 'Ruby', 'Delphi'],
    OLAP       => ['Работали с OLAP',         qw(Cognos Hyperion Microsoft_OLAP Oracle_OLAP SAS)],
    OS         => ['Пользователь ОС',         qw(Windows Linux BSD MacOS)],
    AutoMake   => ['Системы сборки',          { Make => 'make (nmake, gmake)' }, { Ant => 'Ant/NAnt' }, { CMake => 'CMake/CBuild' }, qw(Autotools Maven Scons)],
    SoftDesign => ['Языки моделирования',     'UML', 'XML-схемы', 'IDEF0/1X/4', 'ER-диаграммы'],
    Markup     => ['Языки разметки',          qw(HTML XML DocBook Разметка_MediaWiki)],
    Misc       => [['Работали ли Вы с системами:', 'Web-системы'],             qw(Mantis Jira Bugzilla TrackStudio Trac Confluence MediaWiki DokuWiki)],
    Games      => [['Какими игровыми видами спорта вы увлекаетесь?', 'Спорт'], {Football => 'Футбол'}, {Volleyball => 'Волейбол'}, {Basketball => 'Баскетбол'}, {Hockey => 'Хоккей'}],
];

my ($formid, $itemtext, $categoryid);
for (my $i = 0; $i < @$options; $i+=2)
{
    my $t = $options->[$i];
    my $o = $options->[$i+1];
    my $f = shift @$o;
    my $formname;
    if (ref $f)
    {
        $formname = $f->[0];
        $f = $f->[1];
    }
    $formname ||= $f;
    print STDERR "'''$formname:'''\n";
    print "== $f ==\n\n";
    foreach (@$o)
    {
        if (ref $_)
        {
            ($formid) = keys %$_;
            $itemtext = $_->{$formid};
            $categoryid = $formid;
        }
        else
        {
            $formid = $categoryid = $itemtext = $_;
            $formid =~ s/[^a-z]+/_/giso;
            $formid =~ s/_$|^_//iso;
            $itemtext =~ s/_/ /giso;
        }
        $formid = lc($t . '_' . $formid);
        print STDERR "$formid|$itemtext|checkbox\n";
        print "{{#if:{{{$formid|}}}|* {{$t|$categoryid}}}}\n";
    }
    print STDERR lc($t)."_other|Другое|text\n";
    print "{{#if:{{{".lc($t)."_other|}}}|* Другое: {{{".lc($t)."_other}}}}}\n\n";
}
