<?php

# An extension to support removing "confidential" data from page texts on export
# License: GPLv2 or later
# (c) Vitaliy Filippov, 2011

$wgHooks['ExportFilterText'][] = 'ExportRemoveConfidential::remove';
$wgHooks['ExportAfterChecks'][] = 'ExportRemoveConfidential::checkbox';
$wgExtensionMessagesFiles['RemoveConfidential'] = dirname(__FILE__).'/RemoveConfidential.i18n.php';
$wgAutoloadClasses['ExportRemoveConfidential'] = dirname(__FILE__).'/RemoveConfidential.class.php';

if (!isset($wgExportConfidentialRegexp))
    $wgExportConfidentialRegexp = '#\{\{\s*CONFIDENTIAL-BEGIN.*?\}\}.*?(\{\{\s*CONFIDENTIAL-END\s*\}\}\s*|$)#s';

if (!isset($wgExportConfidentialTemplates))
    $wgExportConfidentialTemplates = array('CONFIDENTIAL');
