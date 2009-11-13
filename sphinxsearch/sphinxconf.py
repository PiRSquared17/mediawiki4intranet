# -*- coding: utf-8 -*-

import os
import os.path
import glob
import fnmatch
import sys
import time
import codecs
from copy import copy
import re

reload(sys)
sys.setdefaultencoding('utf-8')

wikis = []

def print_wiki_conf(wiki):
  ls = """
source src_main_%(wikiname)s
{
    type           = mysql
    sql_host       = localhost
    sql_user       = %(sql_user)s
    sql_pass       = %(sql_pass)s
    sql_db         = %(sql_db)s
    sql_query_pre  = SET NAMES utf8
    sql_query      = SELECT page_id, REPLACE(page_title,'_',' ') page_title, page_namespace, old_id, old_text FROM page, revision, text WHERE rev_id=page_latest AND old_id=rev_text_id 
    sql_attr_uint  = page_namespace
    sql_attr_uint  = old_id
    sql_attr_multi = uint category from query; SELECT cl_from, page_id AS category FROM categorylinks, page WHERE page_title=cl_to AND page_namespace=14
    sql_query_info = SELECT REPLACE(page_title,'_',' ') page_title, page_namespace FROM page WHERE page_id=$id
}

source src_incremental_%(wikiname)s : src_main_%(wikiname)s
{
    sql_query = SELECT page_id, REPLACE(page_title,'_',' ') page_title, page_namespace, old_id, old_text FROM page, revision, text WHERE rev_id=page_latest AND old_id=rev_text_id AND page_touched>=DATE_FORMAT(CURDATE(), '%%Y%%m%%d050000')     
}

index main_%(wikiname)s
{
    source        = src_main_%(wikiname)s
    path          = /var/data/sphinx/main_%(wikiname)s
    docinfo       = extern
    morphology    = stem_ru
    #stopwords    = /var/data/sphinx/stopwords.txt
    min_word_len  = 2
    min_infix_len = 1
    enable_star   = 1
    charset_type  = utf-8
}

index inc_%(wikiname)s : main_%(wikiname)s
{
    path   = /var/data/sphinx/inc_%(wikiname)s
    source = src_incremental_%(wikiname)s
}

indexer
{
    mem_limit = 64M
}

searchd
{
    address      = 127.0.0.1
    port         = %(port)s
    log          = /var/log/sphinx/%(wikiname)s.log
    query_log    = /var/log/sphinx/query-%(wikiname)s.log
    read_timeout = 5
    max_children = 30
    pid_file     = /var/log/sphinx/searchd-%(wikiname)s.pid
    max_matches  = 1000
}
""" % wiki
  lf = open(wiki["wikiname"]+".conf","w")
  lf.write(ls)
  lf.close()

wikis += [{
    "wikiname" : "wiki",
    "sql_user" : "wiki",
    "sql_pass" : "wiki",
    "sql_db"   : "wiki13",
    "port"     : 3112,
  }]

wikis += [{
    "wikiname" : "smwiki",
    "sql_user" : "smwiki",
    "sql_pass" : "smwiki",
    "sql_db"   : "smwiki13",
    "port"     : 3113,
  }]

wikis += [{
    "wikiname" : "rdwiki",
    "sql_user" : "rdwiki",
    "sql_pass" : "rdwiki",
    "sql_db"   : "rdwiki13",
    "port"     : 3114,
  }]

wikis += [{
    "wikiname" : "sbwiki",
    "sql_user" : "sbwiki",
    "sql_pass" : "sbwiki",
    "sql_db"   : "sbwiki13",
    "port"     : 3115,
  }]

wikis += [{
    "wikiname" : "gzwiki",
    "sql_user" : "gzwiki",
    "sql_pass" : "gzwiki",
    "sql_db"   : "gzwiki13",
    "port"     : 3116,
  }]

wikis += [{
    "wikiname" : "dpwiki",
    "sql_user" : "dpwiki",
    "sql_pass" : "dpwiki",
    "sql_db"   : "dpwiki13",
    "port"     : 3117,
  }]

wikis += [{
    "wikiname" : "hrwiki",
    "sql_user" : "hrwiki",
    "sql_pass" : "hrwiki",
    "sql_db"   : "hrwiki13",
    "port"     : 3118,
  }]

wikis += [{
    "wikiname" : "cbwiki",
    "sql_user" : "cbwiki",
    "sql_pass" : "cbwiki",
    "sql_db"   : "cbwiki13",
    "port"     : 3119,
  }]

wikis += [{
    "wikiname" : "gzstable13",
    "sql_user" : "gzstable13",
    "sql_pass" : "gzstable13",
    "sql_db"   : "gzstable13",
    "port"     : 3120,
  }]

wikis += [{
    "wikiname" : "orwiki",
    "sql_user" : "orwiki13",
    "sql_pass" : "orwiki13",
    "sql_db"   : "orwiki13",
    "port"     : 3121,
  }]

for wiki in wikis:
  print_wiki_conf(wiki)

sphinxsearchd = ""
for wiki in wikis:
  sphinxsearchd += """
/usr/local/bin/searchd --config /etc/sphinxsearch/%(wikiname)s.conf &""" % wiki

sphinxsearchd="""#!/bin/sh

%s

exit 0
""" % sphinxsearchd

lf = open("sphinxsearchd","w")
lf.write(sphinxsearchd.replace("\r",""))
lf.close()
if os.name == "posix":
  cmd = "chmod a+x sphinxsearchd"
  os.system(cmd)

try:
  os.mkdir("cron.daily")
except:
  pass

try:
  os.mkdir("cron.hourly")
except:
  pass

for wiki in wikis:
  ls = "/usr/local/bin/indexer --config /etc/sphinxsearch/%(wikiname)s.conf main_%(wikiname)s --rotate > /dev/null" % wiki
  ls = """#!/bin/sh

%s
""" % ls
  scriptname = "cron.daily/reindex_%(wikiname)s" % wiki
  lf = open(scriptname,"w")
  lf.write(ls.replace("\r",""))
  lf.close()
  if os.name == "posix":
    cmd = "chmod a+x "+scriptname
    os.system(cmd)

  ls = "/usr/local/bin/indexer --config /etc/sphinxsearch/%(wikiname)s.conf inc_%(wikiname)s --rotate > /dev/null" % wiki
  ls = """#!/bin/sh

%s
""" % ls
  scriptname = "cron.hourly/reindex_%(wikiname)s" % wiki
  lf = open(scriptname,"w")
  lf.write(ls.replace("\r",""))
  lf.close()
  if os.name == "posix":
    cmd = "chmod a+x "+scriptname
    os.system(cmd)

ls = ""
for wiki in wikis:
  ls += "\n/usr/local/bin/indexer --config /etc/sphinxsearch/%(wikiname)s.conf main_%(wikiname)s --all " % wiki
ls = """#!/bin/sh

%s
""" % ls
scriptname = "reindex_all"
lf = open(scriptname,"w")
lf.write(ls.replace("\r",""))
lf.close()
if os.name == "posix":
  cmd = "chmod a+x "+scriptname
  os.system(cmd)
