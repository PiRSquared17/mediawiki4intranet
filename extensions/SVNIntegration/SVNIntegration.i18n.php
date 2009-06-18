<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author Stefan Macke <development@stefan-macke.com>
 * @copyright Copyright (C) 2008 Stefan Macke 
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 *
 * An extension that adds SVN integration to MediaWiki.
 * 
 */

/*
 $Id: SVNIntegration.i18n.php 80 2008-11-07 10:53:44Z Stefan $
*/

# Alert the user that this is not a valid entry point to MediaWiki if they try to access the extension file directly.
if (!defined('MEDIAWIKI')) {
	echo <<<EOT
To install the SVNIntegration extension, put the following line in LocalSettings.php:
require_once( "$IP/extensions/SVNIntegration/SVNIntegration.setup.php" );
EOT;
	exit(1);
}

$messages['en'] = array(
	'svnintegration'                     => 'SVNIntegration',
	'svnintegration-desc'                => 'Adds SVN integration to MediaWiki',
	'svnintegration-short'               => 'SVNIntegration', # Do not translate or duplicate this message to other languages
	'svnintegration-title'               => 'Adds SVN integration to MediaWiki',
	'svnintegration-invalidurl'          => 'Not a valid URL: ',
	'svnintegration-printfileheader'     => 'File contents from SVN: ',
	'svnintegration-filelinktext'        => 'Link to file in SVN: ',
	'svnintegration-fileinfoheader'      => 'File information for ',
	'svnintegration-fileinfopath'        => 'Path',
	'svnintegration-fileinfoname'        => 'Name',
	'svnintegration-fileinfourl'         => 'Complete URL',
	'svnintegration-fileinfobase'        => 'Repository base',
	'svnintegration-fileinfouuid'        => 'UUID',
	'svnintegration-fileinforev'         => 'Current revision',
	'svnintegration-fileinfonode'        => 'Node type',
	'svnintegration-fileinfoauthor'      => 'Last changed by',
	'svnintegration-fileinforevchanged'  => 'Last changed revision',
	'svnintegration-fileinfodatechanged' => 'Last changed date',
	'svnintegration-fileinfomessage'     => 'Comment for revision $1',
	'svnintegration-historyheader'       => 'Revision history for ',
	'svnintegration-historyrev'          => 'Revision $1 by $2 from $3',
	'svnintegration-todoheader'          => 'TODO-Tags in ',
	'svnintegration-todoline'            => 'in line ',
	'svnintegration-fromline'            => 'from line ',
	'svnintegration-toline'              => 'to line ',
);

$messages['de'] = array(
	'svnintegration'                     => 'SVNIntegration',
	'svnintegration-desc'                => 'Erweitert MediaWiki um eine SVN-Integration',
	'svnintegration-short'               => 'SVNIntegration', # Do not translate or duplicate this message to other languages
	'svnintegration-title'               => 'Erweitert MediaWiki um eine SVN-Integration',
	'svnintegration-invalidurl'          => 'Ungültige URL: ',
	'svnintegration-printfileheader'     => 'Dateiinhalte aus SVN: ',
	'svnintegration-filelinktext'        => 'Link zur Datei in SVN: ',
	'svnintegration-fileinfoheader'      => 'Dateiinformationen für ',
	'svnintegration-fileinfopath'        => 'Pfad',
	'svnintegration-fileinfoname'        => 'Name',
	'svnintegration-fileinfourl'         => 'Komplette URL',
	'svnintegration-fileinfobase'        => 'Repository-Pfad',
	'svnintegration-fileinfouuid'        => 'UUID',
	'svnintegration-fileinforev'         => 'Aktuelle Revision',
	'svnintegration-fileinfonode'        => 'Knotentyp',
	'svnintegration-fileinfoauthor'      => 'Zuletzt geändert von',
	'svnintegration-fileinforevchanged'  => 'Zuletzt geändert in Revision',
	'svnintegration-fileinfodatechanged' => 'Zuletzt geändert am',
	'svnintegration-fileinfomessage'     => 'Kommentar zu Revision $1',
	'svnintegration-historyheader'       => 'Revisionshistorie für ',
	'svnintegration-historyrev'          => 'Revision $1 von $2 vom $3',
	'svnintegration-todoheader'          => 'TODO-Tags in ',
	'svnintegration-todoline'            => 'in Zeile ',
	'svnintegration-fromline'            => 'von Zeile ',
	'svnintegration-toline'              => 'bis Zeile ',
);

$messages['ru'] = array(
	'svnintegration'                     => 'SVNIntegration',
	'svnintegration-desc'                => 'Добавляет функции интеграции с Subversion в MediaWiki',
	'svnintegration-short'               => 'SVNIntegration', # Do not translate or duplicate this message to other languages
	'svnintegration-title'               => 'Добавляет функции интеграции с Subversion в MediaWiki',
	'svnintegration-invalidurl'          => 'Некорректный URL: ',
	'svnintegration-printfileheader'     => 'Содержимое файла из SVN: ',
	'svnintegration-filelinktext'        => 'Ссылка на файл в SVN: ',
	'svnintegration-fileinfoheader'      => 'Информация о файле ',
	'svnintegration-fileinfopath'        => 'Путь',
	'svnintegration-fileinfoname'        => 'Имя',
	'svnintegration-fileinfourl'         => 'Полный URL',
	'svnintegration-fileinfobase'        => 'Репозиторий',
	'svnintegration-fileinfouuid'        => 'UUID',
	'svnintegration-fileinforev'         => 'Текущая ревизия',
	'svnintegration-fileinfonode'        => 'Тип записи',
	'svnintegration-fileinfoauthor'      => 'Последний изменявший',
	'svnintegration-fileinforevchanged'  => 'Последняя ревизия',
	'svnintegration-fileinfodatechanged' => 'Дата последнего изменения',
	'svnintegration-fileinfomessage'     => 'Комментарий к ревизии $1',
	'svnintegration-historyheader'       => 'История ревизий файла ',
	'svnintegration-historyrev'          => 'Ревизия $1, автор $2, от $3',
	'svnintegration-todoheader'          => 'TODO-метки в ',
	'svnintegration-todoline'            => 'на строке ',
	'svnintegration-fromline'            => 'строки с ',
	'svnintegration-toline'              => 'по ',
);

?>
