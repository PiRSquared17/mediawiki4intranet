/**
 * MediaWiki FavRate extension - javascript part
 * Copyright © 2010+ Vitaliy Filippov
 * License: LGPLv3 or later
 * http://wiki.4intra.net/FavRate
 */

(function()
{
    var favRateTimer = null;
    var favStatus = null;
    var favRateToggleCallback = function(request, btn, pageId, callback, add)
    {
        if (request.status != 200)
            return;
        var r = eval(request.responseText);
        if (!favStatus)
        {
            favStatus = document.createElement('div');
            favStatus.className = 'favstatus';
            document.body.appendChild(favStatus);
        }
        var p = $(btn).offset();
        var dw = $(document).width();
        if (p.left < dw-200)
        {
            favStatus.style.left = (p.left+8)+'px';
            favStatus.style.right = '';
        }
        else
        {
            favStatus.style.left = '';
            favStatus.style.right = (dw-p.left-8)+'px';
        }
        favStatus.style.top = (p.top+8)+'px';
        favStatus.className = 'favstatus favvisible';
        favStatus.innerHTML = r[1];
        if (r[0])
        {
            btn.className = btn.className.replace(add ? 'fav0' : 'fav1', add ? 'fav1' : 'fav0');
            btn.title = mw.msg(add ? 'favrate-remfav' : 'favrate-addfav');
        }
        if (callback)
            callback(add);
        clearTimeout(favRateTimer);
        favRateTimer = setTimeout(function() { favStatus.className = ''; }, 1500);
    };
    window.favRateToggleFavFor = function(btn, pageId, callback)
    {
        var u = mw.config ? mw.config.get('wgUserName') : wgUserName;
        var add = btn.className.indexOf('fav0') > -1 ? 1 : 0;
        if (pageId && u !== null)
        {
            sajax_do_call('efFavRateSet', [ pageId, add ],
                function(request) { favRateToggleCallback(request, btn, pageId, callback, add); });
        }
    };
    window.favRateToggleFav = function(btn)
    {
        var id = mw.config ? mw.config.get('wgArticleId') : wgArticleId;
        favRateToggleFavFor(btn, id);
    };
    window.favRateToggleFavWikilog = function(link, pageId)
    {
        favRateToggleFavFor(link.parentNode, pageId, function(add) {
            var inc = add ? 1 : -1;
            link.innerHTML = link.innerHTML.replace(/\d+/, function(m) { return parseInt(m[0])+inc; });
        });
    };
})();
