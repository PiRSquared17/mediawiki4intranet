// FIXME HACL_GroupEditor.js uses a JS class, but this does not :(

/* This script requires global variables:
   aclNsText: HACL_NS_ACL namespace name
   msgStartTyping: { page =>, user =>, group =>, category => }
   msgEditSave
   msgEditCreate
   msgAffected: { user =>, group => }
   userNsRegexp: regexp matching (,|^)+localised user namespace name
   groupPrefixRegexp: same for group prefix
   petPrefix: { PET_XX => prefix } from haclgContLang */

var allActions = [ 'create', 'delete', 'edit', 'move', 'read' ];
var groupClosureCache = {};
var predefCache = {};
var aclRights = {};
var aclClosure = {};
var aclPredefined = {};
var aclLastTarget = {};
var user_hint, target_hint, inc_hint, last_target = '', last_target_name = '';

userNsRegexp = userNsRegexp ? new RegExp(userNsRegexp, 'gi') : '';
groupPrefixRegexp = groupPrefixRegexp ? new RegExp(groupPrefixRegexp, 'gi') : '';

if (!String.prototype.trim)
    String.prototype.trim = function() { return this.replace(/^\s*/, '').replace(/\s*$/, ''); }

// target ACL page name/type change
// total_change==true only when onchange is fired (element loses focus)
function target_change(total_change)
{
    var what = document.getElementById('acl_what').value;
    var an = document.getElementById('acl_name');
    // check if target type changed
    if (last_target != what)
    {
        if (last_target)
        {
            // remember name for each type separately
            aclLastTarget[last_target] = an.value;
            if (aclLastTarget[what])
                an.value = aclLastTarget[what];
            else if (what == 'template')
                an.value = wgUserName;
            else
                an.value = '';
            last_target_name = an.value;
            target_hint.change();
        }
        last_target = what;
    }
    var name = an.value.trim();
    if (last_target_name.length && last_target_name == name && !total_change)
        return;
    last_target_name = name;
    if (name.length)
    {
        var pn = document.getElementById('acl_pn');
        var t = aclNsText+':'+petPrefix[what]+'/'+name;
        pn.innerHTML = t;
        pn.href = wgScript+'/'+t;
        document.getElementById('wpTitle').value = t;
        document.getElementById('acl_delete_link').href = wgScript + '?title=' + encodeURI(t) + '&action=delete';
        if (total_change)
            sajax_do_call('haclSDExists', [ what, name ], pe_exists_ajax);
    }
    if (!name.length || !total_change)
        document.getElementById('acl_exists_hint').style.display = 'none';
    document.getElementById('acl_delete_link').style.display = name.length ? '' : 'none';
    document.getElementById('acl_pns').style.display = name.length ? '' : 'none';
    document.getElementById('acl_pnhint').style.display = name.length ? 'none' : '';
}

// react to item existence check
function pe_exists_ajax(request)
{
    if (request.status != 200)
        return;
    var exists = eval('('+request.responseText+')'); // json parse
    if (exists)
        document.getElementById('acl_exists_hint').style.display = '';
    else
        document.getElementById('acl_delete_link').style.display = 'none';
    document.getElementById('wpSave').value = exists ? msgEditSave : msgEditCreate;
}

// add predefined ACL inclusion
function include_acl()
{
    var inc = document.getElementById('inc_acl').value;
    aclPredefined[inc] = true;
    save_sd();
}

// parse definition text from textbox
function parse_sd()
{
    var t = document.getElementById('acl_def').value;
    var m = t.match(/\{\{\s*\#access\s*:\s*[^\}]*?\}\}/ig) || [];
    var r = {}, i = 0, j, k, h, act, ass;
    while (m[i])
    {
        ass = /[:\|]\s*assigned\s+to\s*=\s*([^\|\}]*)/i.exec(m[i]);
        ass = (ass[1] || '');
        if (userNsRegexp)
            ass = ass.replace(userNsRegexp, '$1User:');
        if (groupPrefixRegexp)
            ass = ass.replace(groupPrefixRegexp, '$1Group/');
        ass = ass.trim().split(/[,\s*]*,[,\s]*/);
        act = /[:\|]\s*actions\s*=\s*([^\|\}]*)/i.exec(m[i]);
        act = (act[1] || '').trim().toLowerCase().split(/[,\s*]*,[,\s]*/);
        h = {};
        for (j = act.length-1; j >= 0; j--)
        {
            if (act[j] == '*')
            {
                act = allActions;
                break;
            }
            else if (h[act[j]])
            {
                act.splice(j, 1);
                j++;
            }
            else
                h[act[j]] = true;
        }
        for (j in act)
        {
            for (k in ass)
            {
                r[ass[k]] = r[ass[k]] || {};
                r[ass[k]][act[j]] = true;
            }
        }
        i++;
    }
    // assigned to manage rights
    var m1 = t.match(/\{\{\s*\#manage\s+rights\s*:\s*[^\}]*?\}\}/ig) || [];
    i = 0;
    while (m1[i])
    {
        ass = /[:\|]\s*assigned\s+to\s*=\s*([^\|\}]*)/i.exec(m1[i]);
        ass = (ass[1] || '').trim().split(/[,\s*]*,[,\s]*/);
        for (k in ass)
        {
            r[ass[k]] = r[ass[k]] || {};
            r[ass[k]]['manage'] = true;
        }
        i++;
    }
    // ACL inclusions
    var m2 = t.match(/\{\{\s*\#predefined\s+right\s*:\s*[^\}]*?\}\}/ig) || [];
    i = 0;
    aclPredefined = {};
    while (m2[i])
    {
        ass = /[:\|]\s*rights\s*=\s*([^\|\}]*)/i.exec(m2[i]);
        ass = (ass[1] || '').trim().split(/[,\s*]*,[,\s]*/);
        for (k in ass)
            aclPredefined[ass[k]] = true;
        i++;
    }
    // Save aclRights
    aclRights = r;
    check_errors();
}

// Check for errors (now: at least 1 manager defined, at least 1 action defined)
function check_errors()
{
    var has_managers = false, has_rights = false;
    for (var m in aclRights)
    {
        for (var a in aclRights[m])
        {
            if (a == 'manage')
                has_managers = true;
            else
                has_rights = true;
        }
        if (has_rights && has_managers)
            break;
    }
    for (var m in aclPredefined)
    {
        has_rights = true;
        break;
    }
    document.getElementById('acl_define_rights').style.display = has_rights ? 'none' : '';
    document.getElementById('acl_define_manager').style.display = has_managers ? 'none' : '';
}

// fill in aclRights with closure data
function closure_ajax(request)
{
    if (request.status != 200)
        return;
    var d = eval('('+request.responseText+')'); // JSON parse
    if (d && d['groups'])
        for (var g in d['groups'])
            groupClosureCache[g] = d['groups'][g];
    if (d && d['rights'])
        for (var g in d['rights'])
            predefCache[g] = d['rights'][g];
}

// modify closure, append d = [ group1, group2, ... ],
// append predefined rights sd = [ right1, ... ]
function closure_groups_sd(d, sd)
{
    var c = false;
    // Groups
    for (var g in d)
    {
        c = true;
        g = d[g];
        if (groupClosureCache[g])
        {
            for (var m in groupClosureCache[g])
            {
                m = groupClosureCache[g][m];
                aclClosure[m] = aclClosure[m] || {};
                for (var a in aclRights[g])
                    aclClosure[m][a] = true;
            }
        }
    }
    // Predefined rights
    for (var r in sd)
    {
        c = true;
        r = sd[r];
        if (predefCache[r])
        {
            for (var m in predefCache[r])
            {
                aclClosure[m] = aclClosure[m] || {};
                for (var a in predefCache[r][m])
                    aclClosure[m][a] = true;
            }
        }
    }
    // refresh hint
    if (c && !user_hint.element.value.trim().length)
        user_hint.change_ajax(get_empty_hint());
}

// parse ACL and re-fill closure
function parse_make_closure()
{
    parse_sd();
    fill_closure();
}

// re-fill aclClosure
function fill_closure()
{
    var g = [];
    var fetch = [];
    aclClosure = {};
    for (var k in aclRights)
    {
        if (k.substr(0, 6) == 'Group/')
        {
            g.push(k);
            if (!groupClosureCache[k])
                fetch.push(k);
        }
    }
    var sd = [];
    var fetch_sd = [];
    for (var k in aclPredefined)
    {
        sd.push(k);
        if (!predefCache[k])
            fetch_sd.push(k);
    }
    if (fetch.length || fetch_sd.length)
    {
        sajax_do_call(
            'haclGroupClosure',
            [ fetch.join(','), fetch_sd.join('[') ],
            function(request) { closure_ajax(request); closure_groups_sd(g, sd); }
        );
    }
    else if (g.length || sd.length)
        closure_groups_sd(g, sd);
}

// save definition text into textbox
function save_sd()
{
    var r, i, j, k, m, h, man;
    // remove old definitions
    var t = document.getElementById('acl_def').value;
    t = t.replace(/\{\{\s*\#(access|manage\s+rights|predefined\s*right):\s*[^\}]*?\}\}\s*/ig, '');
    // build {{#manage rights: }}
    m = [];
    r = aclRights;
    for (j in r)
        if (r[j]['manage'])
            m.push(j);
    man = m.length ? '{{#manage rights: assigned to = '+m.join(", ")+"}}\n" : '';
    // build {{#access: }} rights
    m = {};
    for (j in r)
    {
        h = r[j];
        i = [];
        for (var k in h)
            if (k != 'manage')
                i.push(k);
        if (i.length)
        {
            i = i.sort().join(', ');
            if (i == allActions.join(', '))
                i = '*';
            m[i] = m[i] || [];
            m[i].push(j);
        }
    }
    r = '';
    for (j in m)
        r += '{{#access: assigned to = '+m[j].join(", ")+' | actions = '+j+"}}\n";
    // include predefined rights
    var predef = [];
    for (var i in aclPredefined)
        predef.push(i);
    // add definitions
    t = t + r + man;
    t = t + "{{#predefined right: rights="+predef.join(", ")+"}}\n";
    document.getElementById('acl_def').value = t;
    check_errors();
}

// onchange for action checkboxes
function act_change(e)
{
    if (e.disabled)
        return;
    var g_to = get_grant_to();
    var a = e.id.substr(4), direct, grp;
    if (a == 'all')
    {
        var act = allActions;
        direct = grp = true;
        for (var i in act)
        {
            i = act[i];
            direct = direct && aclRights[g_to] && aclRights[g_to][i];
            grp = grp &&
                (aclClosure[g_to] && aclClosure[g_to][i] ||
                aclRights['#'] && aclRights['#'][i]);
        }
    }
    else
    {
        direct = aclRights[g_to] && aclRights[g_to][a];
        grp = aclClosure[g_to] && aclClosure[g_to][a] ||
            aclRights['#'] && aclRights['#'][a];
    }
    // grant if not yet
    if (e.checked && !direct && !grp)
        grant(g_to, a, true);
    // if right is granted through some group, we can't revoke
    else if (!e.checked && direct && !grp)
        grant(g_to, a, false);
}

// onchange for to_type
function to_type_change()
{
    document.getElementById('to_name').value = '';
    to_name_change();
    user_hint.change();
}

// additional onchange for to_name - load to_name's rights from aclClosure
function to_name_change()
{
    var g_to = get_grant_to();
    var act = allActions.slice(0);
    act.push('manage', 'all');
    var all_direct = true, all_grp = true;
    var c, l, direct, grp;
    for (var a in act)
    {
        a = act[a];
        c = document.getElementById('act_'+a);
        l = document.getElementById('act_label_'+a);
        direct = g_to && aclRights[g_to] && aclRights[g_to][a];
        grp = g_to && (aclClosure[g_to] && aclClosure[g_to][a] || aclRights['#'] && aclRights['#'][a]);
        // all = all except manage
        if (a == 'all')
        {
            direct = all_direct;
            grp = all_grp;
        }
        else if (a != 'manage')
        {
            all_direct = all_direct && direct;
            all_grp = all_grp && grp;
        }
        c.checked = direct || grp;
        // disable checkbox if right is granted through some group
        c.disabled = !g_to || grp;
        l.className = !g_to || grp ? 'act_disabled' : '';
    }
}

// get grant subject (*, #, User:X, Group/X)
function get_grant_to()
{
    var g_to = document.getElementById('to_type').value;
    if (g_to == '*' || g_to == '#')
        return g_to;
    var n = document.getElementById('to_name').value.trim();
    if (!n)
        return '';
    if (g_to == 'group')
        g_to = 'Group/' + n;
    else if (g_to == 'user')
        g_to = 'User:' + n;
    return g_to;
}

// grant/revoke g_act to/from g_to and update textbox with definition
// g_act is: one of allActions, or 'manage', or 'all'
function grant(g_to, g_act, g_yes)
{
    if (g_act == 'all')
        act = allActions;
    else
        act = [ g_act ];
    if (g_yes)
    {
        for (var a in act)
        {
            aclRights[g_to] = aclRights[g_to] || {};
            aclRights[g_to][act[a]] = true;
        }
    }
    else
    {
        for (var a in act)
            if (aclRights[g_to])
                delete aclRights[g_to][act[a]];
    }
    if (g_act == 'all')
        for (var a in allActions)
            document.getElementById('act_'+allActions[a]).checked = g_yes;
    else
    {
        var c = aclRights[g_to];
        if (c)
            for (var a in allActions)
                c = c && aclRights[g_to][allActions[a]];
        document.getElementById('act_all').checked = c;
    }
    save_sd();
    if (g_to.substr(0, 6) == 'Group/')
        fill_closure();
}

// escape &<>"'
function htmlspecialchars(s)
{
    var r = { '&' : '&amp;', '<' : '&lt;', '>' : '&gt;', '"' : '&quot;', '\'' : '&apos;' };
    for (var i in r)
        s = s.replace(i, r[i]);
    return s;
}

// get autocomplete html code for the case when to_name is empty
function get_empty_hint()
{
    var tt = document.getElementById('to_type').value;
    var involved = [], n, j = 0;
    if (tt == 'group' || tt == 'user')
    {
        var x = {};
        for (n in aclRights)
            for (j in aclRights[n])
            {
                x[n] = true;
                break;
            }
        for (n in aclClosure)
            for (j in aclClosure[n])
            {
                x[n] = true;
                break;
            }
        j = 0;
        for (var n in x)
        {
            if ((tt == 'group') == (n.substr(0, 6) == 'Group/'))
            {
                n = htmlspecialchars(n.replace(/^User:|^Group\//, ''));
                involved.push('<div id="hi_'+(++j)+'" class="hacl_ti" title="'+n+'">'+n+'</div>');
            }
        }
    }
    if (!involved.length)
        return '<div class="hacl_tt">'+msgAffected['no'+tt]+' '+msgStartTyping[tt]+'</div>';
    return '<div class="hacl_tt">'+msgAffected[tt]+'</div>'+involved.join('');
}

// initialize ACL editor
function acl_init_editor()
{
    // create autocompleter for user/group name
    var w = document.getElementById('to_type');
    user_hint = new SHint('to_name', 'hacl',
        function (h, v)
        {
            if (!v.length)
                h.change_ajax(get_empty_hint());
            else
                sajax_do_call('haclAutocomplete', [ w.value, v ],
                    function (request) { if (request.status == 200) h.change_ajax(request.responseText) })
        });
    user_hint.change_old = user_hint.change;
    user_hint.change = function(ev)
    {
        // onchange for to_name
        if (!user_hint.element.value.trim())
            user_hint.msg_hint = get_empty_hint();
        user_hint.element.style.display = w.value == '*' || w.value == '#' ? 'none' : '';
        if (w.value == '*' || w.value == '#')
            user_hint.focus(false);
        else
            user_hint.change_old();
    };
    user_hint.onset = to_name_change;
    user_hint.init();
    exAttach('to_name', 'change', to_name_change);
    to_name_change();
    // init protection target
    target_change();
    // create autocompleter for protection target
    var w1 = document.getElementById('acl_what');
    target_hint = new SHint('acl_name', 'hacl',
        function (h, v)
        {
            var wv = w1.value;
            if (wv == 'right' || wv == 'template')
                return;
            if (wv != 'namespace' && !v.length)
                h.tip_div.innerHTML = '<div class="hacl_tt">'+msgStartTyping[wv]+'</div>';
            else
                sajax_do_call('haclAutocomplete', [ wv, v ],
                    function (request) { if (request.status == 200) h.change_ajax(request.responseText) })
        });
    // do not hint template and right targets
    target_hint.focus = function(f)
    {
        target_hint.tip_div.style.display = w1.value != 'template' && w1.value != 'right' && (f || target_hint.nodefocus) ? '' : 'none';
        target_hint.nodefocus = undefined;
    };
    target_hint.onset = target_change;
    target_hint.max_height = 400;
    target_hint.init();
    // create autocompleter for ACL inclusion
    inc_hint = new SHint('inc_acl', 'hacl',
        function (h, v) {
            sajax_do_call('haclAutocomplete', [ 'sd/right', v ],
                function (request) { if (request.status == 200) h.change_ajax(request.responseText) })
        });
    inc_hint.init();
}
