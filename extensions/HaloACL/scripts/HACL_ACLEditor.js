var allActions = [ 'create', 'delete', 'edit', 'move', 'read' ];
var groupClosureCache = {};
var aclRights = {};
var aclClosure = {};
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

// add ACL inclusion
function include_acl()
{
    var t = document.getElementById('acl_def').value;
    var inc = document.getElementById('inc_acl').value;
    t = t.replace(/\{\{\s*#predefined\s+right\s*:\s*([^\}]*)\}\}\s*/ig, function(m, m1) { return m1.trim() == inc ? '' : m });
    t = t + "{{#predefined right: "+inc+"}}\n";
    document.getElementById('acl_def').value = t;
}

// parse definition text from textbox
function parse_sd()
{
    var t = document.getElementById('acl_def').value;
    var m = t.match(/\{\{\s*\#access:\s*[^\}]*?\}\}/ig);
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
    var m1 = t.match(/\{\{\s*\#manage\s+rights:\s*[^\}]*?\}\}/ig);
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
    return r;
}

// fill in aclRights with closure data
function closure_ajax(request)
{
    if (request.status != 200)
        return;
    var d = eval('('+request.responseText+')'); // JSON parse
    if (d)
        for (var g in d)
            groupClosureCache[g] = d[g];
}

// modify closure, append d = [ group1, group2, ... ]
function closure_groups(d)
{
    var w;
    for (var g in d)
    {
        g = d[g];
        for (var u in (groupClosureCache[g] || {})['user'])
        {
            for (var r in aclRights[g])
            {
                w = 'User:'+groupClosureCache[g]['user'][u].user_name;
                aclClosure[w] = aclClosure[w] || {};
                aclClosure[w][r] = true;
            }
        }
        for (var u in (groupClosureCache[g] || {})['group'])
        {
            for (var r in aclRights[g])
            {
                w = 'Group/'+groupClosureCache[g]['group'][u].group_name;
                aclClosure[w] = aclClosure[w] || {};
                aclClosure[w][r] = true;
            }
        }
    }
}

// parse ACL and re-fill closure
function parse_make_closure()
{
    aclRights = parse_sd();
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
    if (fetch.length)
        sajax_do_call('haclGroupClosure', [ fetch.join(',') ], function(request) { closure_ajax(request); closure_groups(g); });
    else if (g.length)
        closure_groups(g);
}

// save definition text into textbox
function save_sd(rights)
{
    var r, i, j, k, m, h, man;
    // remove old definitions
    var t = document.getElementById('acl_def').value;
    t = t.replace(/\{\{\s*\#(access|manage\s+rights):\s*[^\}]*?\}\}\s*/ig, '');
    // build {{#manage rights: }}
    m = [];
    r = rights;
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
    // add definitions
    t = t + r + man;
    document.getElementById('acl_def').value = t;
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
    save_sd(aclRights);
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
        return '<div class="hacl_tt">'+msgStartTyping[tt]+'</div>';
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
