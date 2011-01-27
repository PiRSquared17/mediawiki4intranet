/* Подсказки в текстовых полях ввода. Использование:
   var hint = new SHint(input, style_prefix, msg_hint, fill_handler);
   SHint.init();
   Параметры конструктора:
   input - текстовое поле ввода, для которого делаем подсказку (ID или само)
   style_prefix - префикс стилей, используются
       P_tip (стиль окошка подсказки),
       P_tt (стиль простого текста в нём),
       P_ti (стиль элемента подсказки),
       P_ti_a (стиль выбранного элемента подсказки)
   msg_hint - текст предложения ко вводу
   fill_handler(Hint, value) - функция, по значению value дёргающая
       загрузку подсказок и передающая HTML-код в Hint.change_ajax()
       все элементы с классом P_ti в HTML-коде считаются подсказками
       значение берётся из их атрибута title=""
*/
var SHint = function(input, style_prefix, msg_hint, fill_handler)
{
    var sl = this;
    sl.style_prefix = style_prefix;
    if (typeof(input) == 'string')
        input = document.getElementById(input);
    sl.element = input;
    sl.msg_hint = msg_hint;
    sl.fill_handler = fill_handler;
    sl.focus = function(f)
    {
        sl.tip_div.style.display = f || sl.nodefocus ? '' : 'none';
        sl.nodefocus = undefined;
    };
    sl.change_highlight = function(ev, e)
    {
        var c;
        if (typeof(e) != 'object' && (!sl.current ||
            !(e = document.getElementById(sl.current.replace(/\d+/,function(m){return ''+(parseInt(m)+e)})))))
            return false;
        if (sl.current && (c = document.getElementById(sl.current)))
            c.className = sl.style_prefix+'_ti';
        sl.current = e.id;
        e.className = sl.style_prefix+'_ti_a';
        return false;
    };
    sl.keyup = function(ev, e)
    {
        if (ev.keyCode != 10 && ev.keyCode != 13)
            sl.focus(true);
        if (ev.keyCode == 38 || ev.keyCode == 40 || ev.keyCode == 10 || ev.keyCode == 13)
            return 2;
        sl.change(ev);
        return 0;
    };
    sl.keypress = function(ev, e)
    {
        return ev.keyCode == 10 || ev.keyCode == 13 ? 2 : 0;
    };
    sl.keydown = function(ev, e)
    {
        if (ev.keyCode == 38) // up
            sl.change_highlight(ev, -1);
        else if (ev.keyCode == 40) // down
            sl.change_highlight(ev, 1);
        else if (ev.keyCode == 10 || ev.keyCode == 13) // enter
        {
            var x;
            if (x = document.getElementById(sl.current))
                sl.set(null, x);
        }
        else
            return 0;
        return 2;
    };
    sl.change_ajax = function(text)
    {
        sl.current = '';
        sl.tip_div.innerHTML = text;
        sl.find_attach(sl.tip_div);
    };
    sl.find_attach = function(e)
    {
        for (var i in e.childNodes)
        {
            if (e.childNodes[i].className &&
                e.childNodes[i].className.indexOf(sl.style_prefix+'_ti') >= 0)
            {
                exAttach(e.childNodes[i], 'mouseover', sl.change_highlight);
                exAttach(e.childNodes[i], 'click', sl.set);
                if (!sl.current)
                    sl.change_highlight(null, e.childNodes[i]);
            }
            else
                sl.find_attach(e.childNodes[i]);
        }
    };
    sl.change = function(ev)
    {
        var t = sl.tip_div;
        var n = sl.element;
        var v = n.value.trim();
        if (!v.length)
            t.innerHTML = '<div class="'+sl.style_prefix+'_tt">'+sl.msg_hint+'</div>';
        else
            sl.fill_handler(sl, v);
    };
    sl.set = function(ev, e)
    {
        sl.element.value = e.title;
        sl.focus(false);
        if (sl.onset)
            sl.onset(ev, e);
    };
    sl.h_focus = function() { sl.focus(true); return 1; };
    sl.h_blur = function() { sl.focus(false); return 1; };
    sl.t_mousedown = function() { sl.nodefocus = true; return 1; };
    sl.d_mousedown = function() { sl.focus(false); return 0; };
    sl.e_mousedown = function() { return 1; };
    sl.init = function()
    {
        var e = sl.element;
        var p = getOffset(e);
        var t = sl.tip_div = document.createElement('div');
        t.className = sl.style_prefix + '_tip';
        t.style.display = 'none';
        t.style.position = 'absolute';
        t.style.top = (p.top+e.offsetHeight) + 'px';
        t.style.left = p.left + 'px';
        document.body.appendChild(t);
        exAttach(document, 'mousedown', sl.d_mousedown);
        exAttach(t, 'mousedown', sl.t_mousedown);
        exAttach(e, 'mousedown', sl.e_mousedown);
        exAttach(e, 'keydown', sl.keydown);
        exAttach(e, 'keypress', sl.keypress);
        exAttach(e, 'keyup', sl.keyup);
        exAttach(e, 'change', sl.change);
        exAttach(e, 'focus', sl.h_focus);
        exAttach(e, 'blur', sl.h_blur);
        sl.change(null);
    };
};
