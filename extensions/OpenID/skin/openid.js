/* jQuery-free version of OpenID javascript */

var openid = {
	current: 'openid',
	chclass: function(c, a) {
		var p;
		if ((p = c.indexOf(' '+a)) >= 0)
			return c.substr(0, p) + c.substr(p+a.length+1);
		return c + ' ' + a;
	},
	choidclass: function(i) {
		i.className = openid.chclass(i.className, 'openid_selected');
	},
	show: function(provider) {
		var i;
		if (i = document.getElementById('provider_form_' + openid.current))
			i.style.display = 'none';
		if (i = document.getElementById('provider_form_' + provider))
			i.style.display = '';
		if (i = document.getElementById('openid_provider_' + openid.current + '_icon'))
			openid.choidclass(i);
		if (i = document.getElementById('openid_provider_' + openid.current + '_link'))
			openid.choidclass(i);
		if (i = document.getElementById('openid_provider_' + provider + '_icon'))
			openid.choidclass(i);
		if (i = document.getElementById('openid_provider_' + provider + '_link'))
			openid.choidclass(i);
		openid.current = provider;
	},
	update: function() {
		// root is root of all articles (e.g. empty article name)
		var root = wgArticlePath;
		root = root.replace('$1', '');

		var exp = (new Date((new Date()).getTime()+365*86400)).toGMTString();
		document.cookie = 'openidprovider='+escape(openid.current)+";path="+escape(root)+";expires="+exp;

		if (openid.current !== 'openid') {
			var param = document.getElementById('openid_provider_param_' + openid.current).value;
			document.cookie = 'openidparam='+escape(param?param:'')+';path='+escape(root)+';expires='+exp;

			document.getElementById('openid_url').value = document.getElementById('openid_provider_url_' + openid.current).value.replace(/{.*}/, param);
		}
	},
	init: function() {
		var provider = openid.getCookie('openidprovider');
		if (provider !== null) {
			openid.show(provider);
			document.getElementById('openid_provider_param_' + openid.current).value = openid.getCookie('openidparam');
		}
	},
	addListener: function(obj, ev, handler) {
		if (obj.addEventListener)
			obj.addEventListener(ev, handler, false);
		else if (obj.attachEvent)
			obj.attachEvent(ev, handler);
		else
			obj["on"+ev] = handler;
	},
	getCookie: function(name)
	{
		var cookie = " " + document.cookie;
		var search = " " + name + "=";
		var setStr = null;
		var offset = 0;
		var end = 0;
		if (cookie.length > 0) {
			offset = cookie.indexOf(search);
			if (offset != -1) {
				offset += search.length;
				end = cookie.indexOf(";", offset)
				if (end == -1) {
					end = cookie.length;
				}
				setStr = unescape(cookie.substring(offset, end));
			}
		}
		return setStr;
	},
};

openid.addListener(window, 'load', openid.init);
