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

def wiki_conf(wiki):
  return """### %(wikiname)s ###

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
    charset_table = 0..9, A..Z->a..z, _, -, a..z, U+410..U+42F->U+430..U+44F, U+430..U+44F
}

index inc_%(wikiname)s : main_%(wikiname)s
{
    path   = /var/data/sphinx/inc_%(wikiname)s
    source = src_incremental_%(wikiname)s
}

""" % wiki

wikis_srv = [
  {
    "wikiname" : "wiki",
    "sql_user" : "wiki",
    "sql_pass" : "wiki",
    "sql_db"   : "wiki13",
  },
  {
    "wikiname" : "mmwiki",
    "sql_user" : "mmwiki",
    "sql_pass" : "mmwiki",
    "sql_db"   : "mmwiki",
  },
  {
    "wikiname" : "uml2wiki",
    "sql_user" : "uml2wiki",
    "sql_pass" : "uml2wiki",
    "sql_db"   : "uml2wiki",
  },
]

wikis_goblin = [
  {
    "wikiname" : "wiki",
    "sql_user" : "wiki",
    "sql_pass" : "wiki",
    "sql_db"   : "wiki13",
  },
  {
    "wikiname" : "smwiki",
    "sql_user" : "smwiki",
    "sql_pass" : "smwiki",
    "sql_db"   : "smwiki13",
  },
  {
    "wikiname" : "rdwiki",
    "sql_user" : "rdwiki",
    "sql_pass" : "rdwiki",
    "sql_db"   : "rdwiki13",
  },
  {
    "wikiname" : "sbwiki",
    "sql_user" : "sbwiki",
    "sql_pass" : "sbwiki",
    "sql_db"   : "sbwiki13",
  },
  {
    "wikiname" : "gzwiki",
    "sql_user" : "gzwiki",
    "sql_pass" : "gzwiki",
    "sql_db"   : "gzwiki13",
  },
  {
    "wikiname" : "dpwiki",
    "sql_user" : "dpwiki",
    "sql_pass" : "dpwiki",
    "sql_db"   : "dpwiki13",
  },
  {
    "wikiname" : "hrwiki",
    "sql_user" : "hrwiki",
    "sql_pass" : "hrwiki",
    "sql_db"   : "hrwiki13",
  },
  {
    "wikiname" : "cbwiki",
    "sql_user" : "cbwiki",
    "sql_pass" : "cbwiki",
    "sql_db"   : "cbwiki13",
  },
  {
    "wikiname" : "gzstable13",
    "sql_user" : "gzstable13",
    "sql_pass" : "gzstable13",
    "sql_db"   : "gzstable13",
  },
  {
    "wikiname" : "orwiki",
    "sql_user" : "orwiki13",
    "sql_pass" : "orwiki13",
    "sql_db"   : "orwiki13",
  }
]

wikis = wikis_goblin
for i in range(1, len(sys.argv)):
  if sys.argv[i] == '--srv':
    wikis = wikis_srv
  elif sys.argv[i] == '-h' or sys.argv[i] == '--help':
    print "USAGE: python sphinxconf.py [--srv]"
    quit()

if os.path.exists("sphinx.conf"):
  print "sphinx.conf already exists, delete or backup it before reconfiguring"
  quit()

lf = open("sphinx.conf", "w")
for wiki in wikis:
  lf.write(wiki_conf(wiki))
lf.write("""### General configuration ###

indexer
{
    mem_limit = 128M
}

searchd
{
    listen       = 127.0.0.1:3112
    log          = /var/log/sphinx/sphinx.log
    query_log    = /var/log/sphinx/query.log
    read_timeout = 5
    max_children = 30
    pid_file     = /var/run/searchd.pid
    max_matches  = 1000
}
""")
lf.close()
