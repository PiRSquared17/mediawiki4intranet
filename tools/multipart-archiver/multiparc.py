#!/usr/bin/python
# Multipart-"архиватор" для заливки многих изображений на сервер разом через импорт
# USAGE: ./multiparc.py file1 file2 file3 > outfile

import os, sys, time, hashlib
from stat import *
from xml.sax.saxutils import escape

if len(sys.argv) > 1:
    boundary = '--' + str(int(time.time()))
    sys.stdout.write("Content-Type: multipart/related; boundary="+boundary+"\n"+boundary+"\nContent-Type: text/xml\nContent-ID: Revisions\n\n");
    sys.stdout.write('<?xml version="1.0" ?>\n<mediawiki xmlns="http://www.mediawiki.org/xml/export-0.3/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.mediawiki.org/xml/export-0.3/ http://www.mediawiki.org/xml/export-0.3.xsd" version="0.3" xml:lang="ru">\n')
    sizes = {}
    for arg in sys.argv[1:]:
        try:
            st = os.stat(arg)
            sizes[arg] = st[ST_SIZE]
            fp = open(arg, "rb")
            s = hashlib.sha1()
            s.update(fp.read(sizes[arg]))
            sha1 = s.hexdigest()
            t = arg
            p = t.rfind('/')
            if p >= 0:
                t = t.substr(p+1)
            t = t.replace(':', '-')
            sys.stdout.write(
'''<page>
 <title>Image:%s</title>
 <upload>
  <timestamp>%s</timestamp>
  <filename>%s</filename>
  <src sha1="%s">multipart://%s</src>
  <size>%d</size>
 </upload>
</page>\n''' % (escape(t), time.strftime('%Y-%m-%dT%H:%M:%SZ', time.localtime(st[ST_MTIME])), escape(arg), escape(sha1), escape(arg), sizes[arg]))
            fp.close()
        except:
            sys.stderr.write('Failed to add %s\n' % (arg))
    sys.stdout.write('</mediawiki>\n')
    for arg in sys.argv[1:]:
        try:
            fp = open(arg, "rb")
            sys.stdout.write(boundary+"\nContent-Type: application/binary\nContent-Transfer-Encoding: Little-Endian\nContent-ID: "+arg+"\nContent-Length: "+str(sizes[arg])+"\n\n")
            sys.stdout.write(fp.read(sizes[arg]))
            fp.close()
        except:
            sys.stderr.write('Failed to add %s\n' % (arg))
else:
    sys.stderr.write("USAGE: python multiparc.py FILE1 FILE2 FILE3 > OUTFILE\n")
