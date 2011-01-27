var aclRightPrefix = 'Right';
var aclNsText = 'ACL';
var msgStartTyping = 'Start typing to display $1 list...';
var userNsRegexp = '(^|,\s*)Участник:';
var groupPrefixRegexp = '(^|,\s*)Group:';
var groupClosureCache = {};
var aclRights = {};
var aclClosure = {};
var user_hint;

userNsRegexp = userNsRegexp ? new RegExp(userNsRegexp, 'gi') : '';
groupPrefixRegexp = groupPrefixRegexp ? new RegExp(groupPrefixRegexp, 'gi') : '';

// change target ACL page name/type
function target_change()
{
    var isr = document.getElementById('acl_right').checked;
    document.getElementById('p_sd').className = isr ? 'inactive' : '';
    document.getElementById('p_right').className = isr ? '' : 'inactive';
    var what = isr ? aclRightPrefix : document.getElementById('acl_what').value;
    var name = document.getElementById(isr ? 'acl_right_name' : 'acl_name').value.trim();
    var pns = document.getElementById('acl_pns');
    if (name.length)
    {
        var pn = document.getElementById('acl_pn');
        var t = aclNsText+':'+what+'/'+name;
        pn.innerHTML = t;
        pn.href = wgScript+'/'+t;
        document.getElementById('wpTitle').value = t;
        pns.style.display = '';
    }
    else
        pns.style.display = 'none';
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
    var r = {}, i, j, k, h, act, ass;
    for (i in m)
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
                act = [ 'create', 'delete', 'edit', 'move', 'read' ];
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
    }
    // assigned to manage rights
    var m1 = t.match(/\{\{\s*\#manage\s+rights:\s*[^\}]*?\}\}/ig);
    for (i in m1)
    {
        ass = /[:\|]\s*assigned\s+to\s*=\s*([^\|\}]*)/i.exec(m1[i]);
        ass = (ass[1] || '').trim().split(/[,\s*],[,\s]*/);
        for (k in ass)
        {
            r[ass[k]] = r[ass[k]] || {};
            r[ass[k]]['manage'] = true;
        }
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
            if (i == 'create, delete, edit, move, read')
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
    var a = e.id.substr(4);
    var direct = aclRights[g_to] && aclRights[g_to][a];
    var grp = aclClosure[g_to] && aclClosure[g_to][a] || aclRights['#'] && aclRights['#'][a];
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
    var act = [ 'read', 'edit', 'create', 'delete', 'move', 'manage' ];
    var c, l, direct, grp;
    for (var a in act)
    {
        a = act[a];
        c = document.getElementById('act_'+a);
        l = document.getElementById('act_label_'+a);
        direct = g_to && aclRights[g_to] && aclRights[g_to][a];
        grp = g_to && (aclClosure[g_to] && aclClosure[g_to][a] || aclRights['#'] && aclRights['#'][a]);
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
function grant(g_to, g_act, g_yes)
{
    if (g_yes)
    {
        aclRights[g_to] = aclRights[g_to] || {};
        aclRights[g_to][g_act] = true;
    }
    else if (aclRights[g_to])
        delete aclRights[g_to][g_act];
    save_sd(aclRights);
    if (g_to.substr(0, 6) == 'Group/')
        fill_closure();
}

// create input autocompleter for users/groups and init to_name_change()
(function()
{
    var w = document.getElementById('to_type');
    user_hint = new SHint(
        'to_name', 'hacl', msgStartTyping.replace('$1', w.value),
        function (h, v) { sajax_do_call('haclUserHint', [ w.value, v ], function (request) { if (request.status == 200) h.change_ajax(request.responseText) }) });
    user_hint.change_old = user_hint.change;
    user_hint.change = function(ev)
    {
        // onchange for to_name
        user_hint.msg_hint = msgStartTyping.replace('$1', w.value);
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
}) ();
