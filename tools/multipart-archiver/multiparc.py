#!/usr/bin/python

import sys
import time

if len(sys.argv) > 1:
    boundary = '--' + str(int(time.time()))
    sys.stdout.write("Content-Type: multipart/related; boundary="+boundary+"\n"+boundary+"\nContent-Type: text/xml\nContent-ID: Revisions\n\n");
    sys.stdout.write('<?xml version=\"1.0\" ?>\n<mediawiki xmlns="http://www.mediawiki.org/xml/export-0.3/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.mediawiki.org/xml/export-0.3/ http://www.mediawiki.org/xml/export-0.3.xsd" version="0.3" xml:lang="ru"></mediawiki>\n')
    for arg in sys.argv[1:]:
        try:
            fp = open(arg, "rb")
            fp.seek(0, 2)
            l = fp.tell()
            fp.seek(0, 0)
            sys.stdout.write(boundary+"\nContent-Type: application/binary\nContent-Transfer-Encoding: Little-Endian\nContent-ID: "+arg+"\nContent-Length: "+str(l)+"\n\n")
            sys.stdout.write(fp.read(l))
            fp.close()
        except: pass
else:
    sys.stderr.write("USAGE: python multiparc.py FILE1 FILE2 FILE3 > OUTFILE\n")
