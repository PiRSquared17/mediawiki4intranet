<?php

/**
 * @copyright 2010 Brion Vibber <brion@pobox.com>
 * Backported into MediaWiki 1.14.1 -- Vitaliy Filippov <vitalif@mail.ru>
 *
 * todo:
 * JS code: load on File pages (ok)
 * JS add 'edit' button (ok)
 * JS edit button -> load svgedit (ok)
 * API point to store file data (ok: using api upload point)
 * hook save UI in the editor (ok)
 * UI to start editor with a new file (create)
 * API point to fetch file data (ok: using ApiSVGProxy extension)
 * hook load UI to browse local files
 * visual cleanup
 * Flash compat for IE?
 */

$wgHooks['BeforePageDisplay'][] = 'SVGEditHooks::beforePageDisplay';

$wgAutoloadClasses['SVGEditHooks'] = dirname( __FILE__ ) . '/SVGEdit.hooks.php';
