rm chrome/wikihelper.jar
rm WikiHelper.xpi
zip -r chrome/wikihelper.jar content locale -x '*/.svn/*'
zip -r WikiHelper.xpi COPYING chrome.manifest install.rdf chrome -x '*/.svn/*'
