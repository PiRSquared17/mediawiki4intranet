/**
 * Author: Davide Ficano
 * Date  : 14-Mar-06
 * Date  : 01-Aug-06 added callbackObject
 * http://dafizilla.svn.sourceforge.net/viewvc/dafizilla/viewsourcewith/src/main/content/viewsourcewith/urldownloader.js?revision=1
 */

function UrlDownloader() {
    this.onFinish = null;
    this.count = 0;
    this.urls = [];
    this.types = [];
    this.outFiles = [];
    this.callbackObject = null;
}

UrlDownloader.prototype = {
    saveURIList : function(urls, outFiles) {
        if (!this.onFinish) {
            throw "UrlDownloader: the onFinish is not valid";
        }
        this.urls = urls;
        this.outFiles = outFiles;
        this.types = [];
        this.count = 0;

        for (var i = 0; i < urls.length; i++) {
            this.internalSaveURI(urls[i], outFiles[i]);
        }
    },

    onStateChange : function(webProgress, request, stateFlags, status) {
        const wpl = Components.interfaces.nsIWebProgressListener;
        if (stateFlags & (wpl.STATE_START | wpl.STATE_IS_REQUEST))
        {
            hc = request.QueryInterface(Components.interfaces.nsIHttpChannel);
            this.types[request.URI] = hc.getResponseHeader('Content-Type');
        }
        if (stateFlags & wpl.STATE_STOP)
        {
            // load finished
            ++this.count;
            if (this.count == this.outFiles.length)
                this.onFinish(this.urls, this.outFiles, this.callbackObject);
        }
    },

    QueryInterface : function(iid) {
        if (iid.equals(Components.interfaces.nsIWebProgressListener) ||
            iid.equals(Components.interfaces.nsISupportsWeakReference) ||
            iid.equals(Components.interfaces.nsISupports)) {
            return this;
        }

        throw Components.results.NS_NOINTERFACE;
    },

    internalSaveURI : function(url, outFile) {
        const nsIWBP = Components.interfaces.nsIWebBrowserPersist;
        var persist = Components
            .classes["@mozilla.org/embedding/browser/nsWebBrowserPersist;1"]
            .createInstance(Components.interfaces.nsIWebBrowserPersist);

        try
        {
            persist.progressListener = this;
            persist.persistFlags = nsIWBP.PERSIST_FLAGS_REPLACE_EXISTING_FILES;// | nsIWBP.PERSIST_FLAGS_FROM_CACHE;
        }
        catch(err)
        {
            alert(err);
        }

        var referrer = null;//ViewSourceWithBrowserHelper.getReferrer(document);
        var postData = null;//ViewSourceWithBrowserHelper.getPostData();

        var uri = Components
            .classes["@mozilla.org/network/io-service;1"]
            .getService(Components.interfaces.nsIIOService)
            .newURI(url, null, null);
        if(!outFile.exists())
            outFile.create(0x00,0644);
        persist.saveURI(uri, null, referrer, postData, null, outFile);
    },

    onStatusChange : function(webProgress, request, status, message) {},
    onLocationChange : function(webProgress, request, location) {},
    onProgressChange : function(webProgress, request, curSelfProgress, maxSelfProgress, curTotalProgress, maxTotalProgress) {},
    onSecurityChange : function(webProgress, request, state) {}
};
