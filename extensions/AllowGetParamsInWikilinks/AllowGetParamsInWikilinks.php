<?php

/**
 * Allow Get Params In WikiLinks
 *
 * Funcion que hace que se permita el paso de parametros "get" en un enlce tipo WikiLink,
 * de la forma [[destino?param1=valor1&param2=valor2]]
 *
 * Actualmente, un wikilink no permite esto y se traduce en una pagina entera con nombre
 * "destino?param1=valor1&param2=valor2". Esta extension arregla esto para que se le pasen
 * los parametros tipo "get" a la pagina "destino"
 *
 * Posibles BUGS conocidos: cuando un articulo tiene ? en su texto y va a haber otro que tenga
 * un titulo exactamente igual, pero mas largo. Mientras no existe, se tomaria como un paso de
 * parametros. Una vez definido el articulo, ya se llegaria a el sin problemas porque se
 * reconoceria como un articulo existente; sin embargo, no se podría pasar parametros al artículo
 * corto.
 *
 * @author Carlos A. <caralla76@gmail.com>
 */

$wgExtensionCredits['other'][] = array(
    'name'        => 'AllowGetParamsInWikilinks',
    'url'         => 'http://mediawiki.org/wiki/Extension:AllowGetParamsInWikilinks',
    'description' => 'Allows get parameters for known articles in wikilinks',
    'author'      => '[mailto:caralla76@gmail.com Carlos A.]',
);

$wgHooks['LinkBegin'][] = 'efAllowGetParamsInWikiLinks';
function efAllowGetParamsInWikiLinks($skin, $target, &$text, &$customAttribs, &$query, &$options, &$ret)
{
    if (!in_array('broken', $options))
    {
        /** known link */
        return true;
    }
    $direccion = parse_url($target->getText());

    if (!array_key_exists('query', $direccion))
    {
        /** it is not written in the form target?query */
        return true;
    }

    if (isset($direccion['user']) ||
        isset($direccion['pass']) ||
        isset($direccion['host']) ||
        isset($direccion['scheme']))
    {
        /** complex urls are not the target of this extension */
        return true;
    }

    /** patch for solving problems with Special pages */
    $pos = strpos($target->getPrefixedText(), "?");
    $direccion['path'] = substr($target->getPrefixedText(), 0, $pos);
    $tituloArticulo = Title::newFromText($direccion['path']);
    if (!$tituloArticulo->isKnown())
        return true;

    $nuevosArgumentos = array();
    foreach (explode('&', $direccion['query']) as $argumento)
    {
        $valores = split('=',$argumento,2);
        $nuevosArgumentos[$valores[0]] = $valores[1];
    }
    $query = array_merge($query, $nuevosArgumentos);

    /** customAttribs probably is not preloaded, but we use it in case it is */
    $myattribs = $customAttribs;
    $myattribs['href'] = $tituloArticulo->getLinkUrl();
    $myattribs['href'] = wfAppendQuery($myattribs['href'], wfArrayToCgi($query));

    /** Preparing the link text and the title */
    if (is_null($text))
        $text = htmlspecialchars($tituloArticulo->getPrefixedText());

    $myattribs['title'] = $tituloArticulo->getPrefixedText();

    /** Create the link */
    $ret = Xml::openElement('a', $myattribs) . $text . Xml::closeElement('a');
    return false;
}
