<?php

# An extension to support removing "confidential" data from page texts on export
# (c) Vitaliy Filippov, 2011

class ExportRemoveConfidential
{
    static function checkbox($special, &$form)
    {
        global $wgRequest;
        $form .= Xml::checkLabel(wfMsg('export-confidential'), 'confidential',
            'wpConfidential', $wgRequest->getCheck('confidential') ? true : false) . '<br />';
        return true;
    }
    static function remove(&$text)
    {
        global $wgExportConfidentialRegexp, $wgExportConfidentialTemplates, $wgContLang, $wgRequest;
        static $template_regexp;
        if ($wgRequest->getCheck('confidential'))
            return true;
        if ($wgExportConfidentialRegexp)
            $text = preg_replace($wgExportConfidentialRegexp, '', $text);
        if ($wgExportConfidentialTemplates)
        {
            if (!$template_regexp)
            {
                $templates = $wgExportConfidentialTemplates;
                foreach ($templates as &$t)
                    $t = '(?i:' . preg_quote(mb_substr($t, 0, 1)) . ')' . preg_quote(mb_substr($t, 1));
                $templates = implode('|', $templates);
                $ns = $wgContLang->getNsText(NS_TEMPLATE) . '|template';
                $template_regexp = "/\{\{\s*(?:(?:(?i:$ns)\s*:\s*)?(?:$templates)\s*(?:\}\}|\|))?|\}\}[ \t]*\n?/us";
            }
            if (preg_match_all($template_regexp, $text, $m, PREG_PATTERN_ORDER|PREG_OFFSET_CAPTURE))
            {
                $cut = -1;
                $open = 0;
                $shift = 0;
                foreach ($m[0] as $match)
                {
                    list($t, $p) = $match;
                    if ($cut < 0)
                    {
                        if ($t != '{{' && substr($t, 0, 2) != '}}')
                        {
                            $cut = $p;
                            $open = 1;
                        }
                    }
                    elseif ($cut >= 0)
                    {
                        $open += (substr($t, 0, 2) == '}}' ? -1 : 1);
                        if ($open <= 0)
                        {
                            $text = substr($text, 0, $cut-$shift) . substr($text, $p+strlen($t)-$shift);
                            $shift += $p+strlen($t)-$cut;
                            $cut = -1;
                        }
                    }
                }
            }
        }
        return true;
    }
}
