var isGe = navigator.userAgent.indexOf('Gecko') > -1 && navigator.userAgent.indexOf('Safari') < 1 ? 1 : 0;

function contentScale(cont, hSize, vSize, initialFontSize)
{
	var fontSize = cont._lastFontSize || initialFontSize;
	var cw, ch, aspect, img, w, h;
	var sumSize = 0, sumCount = 0;
	var t;
	if (document.body.style.transform !== undefined)
		t = 't';
	else if (document.body.style.OTransform !== undefined)
		t = 'OT';
	else if (document.body.style.MozTransform !== undefined)
		t = 'MozT';
	else if (document.body.style.WebkitTransform !== undefined)
		t = 'WebkitT';
	for (var i = 0; i < 10; i++)
	{
		cw = cont.scrollWidth;
		ch = cont.scrollHeight;
		aspect = vSize/ch;
		if (aspect >= 0.95 && aspect < 1.11)
			break;
		sumSize += fontSize*aspect;
		sumCount++;
		fontSize = Math.round(sumSize*100/sumCount)/100;
		// Scale font:
		setFontSize('#'+cont.id, fontSize+'px', initialFontSize+'px');
		cont._lastFontSize = fontSize;
		// Scale images:
		var is = cont.getElementsByTagName('img');
		for (var j = 0; j < is.length; j++)
		{
			img = is[j];
			w = img.scrollWidth;
			h = img.scrollHeight;
			img.style.width = Math.round(w*aspect)+'px';
			img.style.height = Math.round(h*aspect)+'px';
		}
		// Scale SVG images:
		is = cont.getElementsByTagName('object');
		for (var j = 0; j < is.length; j++)
		{
			img = is[j];
			if (img.type != 'image/svg+xml')
				continue;
			if (!img.origWidth)
				img.origWidth = img.width;
			w = Math.round(img.width*aspect);
			h = Math.round(img.height*aspect);
			var svg = img.contentDocument.documentElement;
			svg.setAttribute('width', w);
			svg.setAttribute('height', h);
			// Move SVG contents into a layer
			if (svg.childNodes.length > 1 || svg.childNodes[0].id != '_gsc')
			{
				var g = img.contentDocument.createElementNS('http://www.w3.org/2000/svg', 'g');
				g.id = '_gsc';
				for (var e = 0; e < svg.childNodes.length; e++)
					g.appendChild(svg.childNodes[e]);
				svg.appendChild(g);
			}
			// Scale SVG contents
			svg = svg.childNodes[0];
			var sc = w/img.origWidth;
			svg.setAttribute('transform', 'scale('+sc+' '+sc+')');
			img.width = w;
			img.height = h;
		}
		// Scale class="scaled" elements using CSS3
		if (t && cont.getElementsByClassName)
		{
			is = cont.getElementsByClassName('scaled');
			cont.style.width = 'auto';
			reflowHack();
			if (hSize/cw < aspect)
				aspect = hSize/cw;
			for (var j = 0; j < is.length; j++)
			{
				img = is[j];
				if (!img.origWidth)
					img.origWidth = img.scrollWidth;
				img.style[t+'ransformOrigin'] = '0 0';
				img.style[t+'ransform'] = 'scale('+(img.scrollWidth*aspect/img.origWidth)+')';
			}
			cont.style.width = '';
		}
		reflowHack();
	}
}

function reflowHack()
{
	if (isGe)
	{  // hack to counter incremental reflow bugs
		var obj = document.getElementsByTagName('body')[0];
		obj.style.display = 'none';
		obj.style.display = 'block';
	}
}

function s5ss_addRule(target, rule, replace)
{
	if (!(s5ss = document.getElementById('s5ss')))
	{
		if (!document.createStyleSheet)
		{
			document.getElementsByTagName('head')[0].appendChild(s5ss = document.createElement('style'));
			s5ss.setAttribute('media','screen, projection');
			s5ss.setAttribute('id','s5ss');
		}
		else
		{
			document.createStyleSheet();
			document.s5ss = document.styleSheets[document.styleSheets.length - 1];
		}
	}
	if (!(document.s5ss && document.s5ss.addRule))
	{
		if (replace)
		{
			var c;
			for (var i = s5ss.childNodes.length-1; i >= 0; i--)
			{
				c = s5ss.childNodes[i];
				if (c.nodeValue.substr(0, target.length+1) == target+' ')
					s5ss.removeChild(c);
			}
		}
		s5ss.appendChild(document.createTextNode(target+' {'+rule+'}'));
	}
	else
		document.s5ss.addRule(target, rule);
}

// Force font size of all 'target' children be 'value',
// except for children which have class='scaled': set 'undoValue' for them
function setFontSize(target, value, undoValue)
{
	s5ss_addRule(target, 'font-size: ' + value + ' !important;', true);
}
