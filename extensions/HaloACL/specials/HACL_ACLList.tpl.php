<p>Filter by name: <input type="text" id="acl_filter" onchange="change_filter()" onkeyup="change_filter()" style="width: 400px" /></p>

<p><a href="<?= $wgScript . '?title=Special:HaloACL&action=acl' ?>"><?= wfMsg('hacl_acllist_create_acl') ?></a></p>

<div id="acl_list" style="border: 1px solid gray; width: 500px; height: 500px; padding: 5px; overflow-y: scroll; overflow: -moz-scrollbars-vertical"></div>

<script language="JavaScript" src="<?= $haclgHaloScriptPath ?>/scripts/exAttach.js"></script>
<script language="JavaScript">
function change_filter()
{
    sajax_do_call('haclAcllist', [ '', document.getElementById('acl_filter').value ],
        function(request) { document.getElementById('acl_list').innerHTML = request.responseText; }
    );
}
exAttach(window, 'load', change_filter);
</script>
