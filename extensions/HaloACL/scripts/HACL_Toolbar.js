var hacl_change_toolbar_goto = function(e, msg)
{
  var l = document.getElementById('hacl_toolbar_goto');
  var t = e.options[e.selectedIndex].title;
  l.style.display = t ? '' : 'none';
  l.href = t ? wgScript+'?title='+encodeURI(t) : '';
  l.title = msg.replace('$1', t);
};
