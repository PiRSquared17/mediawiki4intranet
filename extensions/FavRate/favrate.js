/**
 * MediaWiki FavRate extension - javascript part
 * Copyright © 2010-2012 Vitaliy Filippov
 * License: LGPLv3 or later
 * http://wiki.4intra.net/FavRate
 */

(function()
{
    var favRateTimer = null;
    window.favRateToggleFav = function()
    {
        var b = document.getElementById('favtogglebtn');
        var id = mw.config ? mw.config.get('wgArticleId') : wgArticleId;
        var u = mw.config ? mw.config.get('wgUserName') : wgUserName;
        var add = b.className == 'fav0' ? 1 : 0;
        if (id && u !== null)
        {
            sajax_do_call('efFavRateSet', [ id, add ], function(request)
            {
                if (request.status == 200)
                {
                    var r = eval(request.responseText);
                    var d = document.getElementById('favstatus');
                    d.className = 'favvisible';
                    d.innerHTML = r[1];
                    if (r[0])
                    {
                        b.className = add ? 'fav1' : 'fav0';
                        b.title = favRateMsg[add ? 'remfav' : 'addfav'];
                    }
                    clearTimeout(favRateTimer);
                    favRateTimer = setTimeout(function() { d.className = ''; }, 1500);
                }
            });
        }
    };
})();
