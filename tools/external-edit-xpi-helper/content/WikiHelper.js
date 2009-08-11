function WikiHelper()
{
    this.hnd = new WikiContentHandler();
    Components
        .classes["@mozilla.org/uriloader;1"]
        .getService(Components.interfaces.nsIURILoader)
        .registerContentListener(this);
}

WikiHelper.prototype =
{
    QueryInterface: function(iid)
    {
        if (!iid.equals(Components.interfaces.nsIURIContentListener) &&
            !iid.equals(Components.interfaces.nsISupports))
            return Components.results.NS_ERROR_NO_INTERFACE;
        return this;
    },
    loadCookie: null,
    parentContentListener: null,
    hnd: null,
    canHandleContent: function(aContentType, aIsContentPreferred, aDesiredContentType)
    {
        if (!this.hnd.indialog && aContentType.substr(0,29) == 'application/x-external-editor')
            return true;
        return false;
    },
    isPreferred: function(aContentType, aDesiredContentType)
    {
        if (!this.hnd.indialog && aContentType.substr(0,29) == 'application/x-external-editor')
            return true;
        return false;
    },
    onStartURIOpen: function(aURI)
    {
        return false;
    },
    doContent: function(aContentType, aIsContentPreferred, aRequest, aContentHandler)
    {
        aContentHandler.value = this.hnd.indialog ? null : this.hnd;
        return false;
    },
};

function WikiContentHandler()
{
    this.ud = new UrlDownloader();
    this.ud.callbackObject = this;
    this.ud.onFinish =
        function(urls, outfiles, obj)
        {
            obj.finish(urls, outfiles);
        };
}

WikiContentHandler.prototype =
{
    QueryInterface: function(iid)
    {
        if (!iid.equals(Components.interfaces.nsIStreamListener) &&
            !iid.equals(Components.interfaces.nsIRequestObserver) &&
            !iid.equals(Components.interfaces.nsIObserver) &&
            !iid.equals(Components.interfaces.nsIDOMEventListener) &&
            !iid.equals(Components.interfaces.nsISupports))
            return Components.results.NS_ERROR_NO_INTERFACE;
        return this;
    },
    data: '',
    ud: null,
    url: '',
    ext: '',
    script: '',
    outfile: null,
    post: null,
    indialog: false,
    onStartRequest: function(aRequest, aContext)
    {
        this.data = '';
        this.url = '';
        this.ext = '';
        this.script = '';
    },
    onDataAvailable: function(aRequest, aContext, aInputStream, aOffset, aCount)
    {
        var c = '';
        try
        {
            if ('data' in aInputStream)
                c = aInputStream.data;
            else
                aInputStream.read(c, aCount);
        }
        catch (err) {}
        this.data += c;
    },
    onStopRequest: function(aRequest, aContext, aStatusCode)
    {
        var d = this.data.split("\n");
        var re = /^\s*(.*?)\s*=\s*(.*?)\s*$/i;
        var v;
        for (var i = 0; i < d.length; i++)
        {
            if (v = d[i].match(re))
            {
                if (v[1] == 'Script')
                    this.script = v[2];
                else if (v[1] == 'Extension')
                    this.ext = v[2];
                else if (v[1] == 'URL')
                    this.url = v[2];
            }
        }
        if (this.url == '' || this.script == '')
        {
            alert(wgWikiHelperStrings['error']);
            return;
        }
        var f = Components
            .classes["@mozilla.org/file/local;1"]
            .createInstance(Components.interfaces.nsILocalFile);
        f.initWithPath(this.getTmpDir());
        f.appendRelativePath("wikihelper-" + hex_md5(this.url) + "." + this.ext);
        this.ud.saveURIList([this.url], [f]);
    },
    getTmpDir: function()
    {
        return Components
            .classes['@mozilla.org/file/directory_service;1']
            .getService(Components.interfaces.nsIProperties)
            .get("UChrm",Components.interfaces.nsILocalFile)
            .path;
    },
    i: 0,
    handleEvent: function(st)
    {
        this.i = this.i+1;
        if (this.post.readyState == 4)
        {
            Application.activeWindow.activeTab.load(Application.activeWindow.activeTab.uri);
            this.postbodyfp.close();
            this.postbodyfp = null;
            this.postbodyfn.remove();
            this.postbodyfn = null;
        }
    },
    observe: function(aSubject, aTopic, aData)
    {
        if (aTopic != 'process-finished' && aTopic != 'process-finished-pre-3.5')
            return;
        // показываем окно для ввода описания изменений
        this.indialog = true;
        openDialog('chrome://wikihelper/content/entercomment.xul', 'entercomment', 'chrome,centerscreen', this);
    },
    save: function(comment)
    {
        try
        {
            if (comment == null || comment == '')
                return;
            // конвертируем комментарий в байты utf-8
            var conv = Components
                .classes["@mozilla.org/intl/scriptableunicodeconverter"]
                .createInstance(Components.interfaces.nsIScriptableUnicodeConverter);
            conv.charset = 'UTF-8';
            comment = conv.ConvertFromUnicode(comment);
            // заливаем файл
            var filename = /([^\/]*)$/i;
            filename = this.url.match(filename);
            filename = filename[1];
            var post = Components
                .classes["@mozilla.org/xmlextras/xmlhttprequest;1"]
                .createInstance(Components.interfaces.nsIXMLHttpRequest);
            this.post = post;
            post.open('POST', this.script+'?title=Special:Upload&wpSourceType=file&wpIgnoreWarning=true&wpDestFile='+filename, true);
            post.withCredentials = true;
            post.onreadystatechange = this;
            this.postbodyfn = Components
                .classes["@mozilla.org/file/local;1"]
                .createInstance(Components.interfaces.nsILocalFile);
            this.postbodyfn.initWithPath(this.getTmpDir());
            this.postbodyfn.appendRelativePath("wikihelper-POST-" + hex_md5(this.url) + ".multipart");
            this.postbodyfp = Components
                .classes["@mozilla.org/network/file-output-stream;1"]
                .createInstance(Components.interfaces.nsIFileOutputStream);
            this.postbodyfp.init(this.postbodyfn, -1, -1, null);
            var is = Components
                .classes["@mozilla.org/network/file-input-stream;1"]
                .createInstance(Components.interfaces.nsIFileInputStream);
            is.init(this.outfile, 0x01, null, null);
            var bis = Components
                .classes["@mozilla.org/binaryinputstream;1"]
                .createInstance(Components.interfaces.nsIBinaryInputStream);
            bis.setInputStream(is);
            var sz = is.available();
            var data =
                "----upload0962783\n"+
                "Content-Disposition: form-data; name=\"wpUploadDescription\"\n\n"+
                comment+"\n"+
                "----upload0962783\n"+
                "Content-Disposition: form-data; name=\"wpUpload\"\n\n"+
                "Upload file\n"+
                "----upload0962783\n"+
                "Content-Disposition: form-data; name=\"wpUploadFile\"; filename=\""+this.outfile.path+"\"\n"+
                "Content-Length: "+sz+"\n\n";
            this.postbodyfp.write(data, data.length);
            var left;
            while ((left = is.available()) > 0)
            {
                data = bis.readBytes(left > 65536 ? 65536 : left);
                this.postbodyfp.write(data, data.length);
            }
            is.close();
            data = "\n----upload0962783--\n";
            this.postbodyfp.write(data, data.length);
            this.postbodyfp.close();
            this.postbodyfp = Components
                .classes["@mozilla.org/network/file-input-stream;1"]
                .createInstance(Components.interfaces.nsIFileInputStream);
            this.postbodyfp.init(this.postbodyfn, 0x01, null, null);
            post.setRequestHeader('Content-Type', 'multipart/form-data; boundary=--upload0962783');
            post.send(this.postbodyfp);
            this.delayed = true;
            var timer = Components
                .classes["@mozilla.org/timer;1"]
                .createInstance(Components.interfaces.nsITimer);
            timer.initWithCallback({
                target: this,
                notify: function (tim) { this.target.delayed = false; },
            }, 1000, 0);
            var thread = Components
                .classes["@mozilla.org/thread-manager;1"]
                .getService(Components.interfaces.nsIThreadManager)
                .currentThread;
            while (this.delayed)
                thread.processNextEvent(true);
        }
        catch(err)
        {
            alert("Exception in save(): "+err);
        }
        this.indialog = false;
        return true;
    },
    finish: function(urls, outfiles)
    {
        var mimeservice = Components
            .classes["@mozilla.org/mime;1"]
            .getService(Components.interfaces.nsIMIMEService);
        var type;
        for (var i in this.ud.types)
            type = this.ud.types[i];
        var ed = '';
        try
        {
            var mime = mimeservice.getFromTypeAndExtension(type, '.'+this.ext);
            ed = mime.preferredApplicationHandler;
            ed = ed.QueryInterface(Components.interfaces.nsILocalHandlerApp);
            ed = ed.executable.path;
        }
        catch(err) { ed = ''; }
        ed = prompt(wgWikiHelperStrings['enterpath'], ed);
        if (!ed)
            return;
        // запускаем редактор
        var ef = Components
            .classes["@mozilla.org/file/local;1"]
            .createInstance(Components.interfaces.nsILocalFile);
        ef.initWithPath(ed);
        if (!ef.exists())
        {
            alert(wgWikiHelperStrings['editornotfound'].replace('XXX', ef.path));
            return;
        }
        if (!ef.isExecutable())
        {
            alert(wgWikiHelperStrings['notexecutable'].replace('XXX', ef.path));
            return;
        }
        this.outfile = outfiles[0];
        var process = Components
            .classes["@mozilla.org/process/util;1"]
            .createInstance(Components.interfaces.nsIProcess);
        process.init(ef);
        var args = [outfiles[0].path];
        try
        {
            var process2 = process.QueryInterface(Components.interfaces.nsIProcess2);
            process2.runAsync(args, args.length, this, false);
            this.indialog = true;
        }
        catch (err)
        {
            // runAsync, вероятно, недоступен (версия Firefox < 3.5)
            process.run(false, args, args.length);
            this.observe(process, 'process-finished-pre-3.5', null);
        }
    },
};
