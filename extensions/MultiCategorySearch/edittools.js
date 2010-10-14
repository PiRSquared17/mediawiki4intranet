// Drop down box for the Special character menu in [[MediaWiki:Edittools]]
// Adapted from: http://commons.wikimedia.org/wiki/MediaWiki:Edittools.js

function addCharSubsetMenu() {
	var specialchars = document.getElementById('specialchars');

	if (specialchars) {
		var menu = "<select style=\"display:inline\" onChange=\"chooseCharSubset(selectedIndex)\">";
		menu += "<option>Standard</option>";
		menu += "<option>Latin</option>";
		menu += "<option>Greek</option>";
		menu += "<option>Cyrillic</option>";
		menu += "<option>IPA</option>";
		menu += "<option>Arabic</option>";
		menu += "<option>Catalan</option>";
		menu += "<option>Czech</option>";
		menu += "<option>Devanāgarī</option>";
		menu += "<option>Esperanto</option>";
		menu += "<option>Estonian</option>";
		menu += "<option>French</option>";
		menu += "<option>German</option>";
		menu += "<option>Hawaiian</option>";
		menu += "<option>Hebrew</option>";
		menu += "<option>Hungarian</option>";
		menu += "<option>Icelandic</option>";
		menu += "<option>Italian</option>";
		menu += "<option>Latvian</option>";
		menu += "<option>Lithuanian</option>";
		menu += "<option>Maltese</option>";
		menu += "<option>Old English</option>";
		menu += "<option>Pinyin</option>";
		menu += "<option>Polish</option>";
		menu += "<option>Portuguese</option>";
		menu += "<option>Romaji</option>";
		menu += "<option>Romanian</option>";
		menu += "<option>Scandinavian</option>";
		menu += "<option>Serbian</option>";
		menu += "<option>Spanish</option>";
		menu += "<option>Turkish</option>";
		menu += "<option>Vietnamese</option>";
		menu += "<option>Welsh</option>";
		menu += "<option>Yiddish</option>";
		menu += "</select>";
		specialchars.innerHTML = menu + specialchars.innerHTML;

		// Standard-CharSubset
		chooseCharSubset(0);
	}
}

// CharSubset selection
function chooseCharSubset(s) {
	var l = document.getElementById('specialchars').getElementsByTagName('p');
	for (var i = 0; i < l.length ; i++) {
		l[i].style.display = i == s ? 'inline' : 'none';
		// l[i].style.visibility = i == s ? 'visible' : 'hidden';
	}
}

// apply tagOpen/tagClose to selection in textarea,
// use sampleText instead of selection if there is none
function insertTags(tagOpen, tagClose, sampleText) {
	var txtarea;
	if (document.editform) {
		txtarea = document.editform.wpTextbox1;
	} else {
		// some alternate form? take the first one we can find
		var areas = document.getElementsByTagName('textarea');
		txtarea = areas[0];
	}
	txtarea = document.getElementById("MultiCategorySearch");
	var selText, isSample = false;

	if (document.selection  && document.selection.createRange) { // IE/Opera

		//save window scroll position
		if (document.documentElement && document.documentElement.scrollTop)
		var winScroll = document.documentElement.scrollTop
		else if (document.body)
		var winScroll = document.body.scrollTop;
		//get current selection  
		txtarea.focus();
		var range = document.selection.createRange();
		selText = range.text;
		//insert tags
		checkSelectedText();
		range.text = tagOpen + selText + tagClose;
		//mark sample text as selected
		if (isSample && range.moveStart) {
			if (window.opera)
			tagClose = tagClose.replace(/\n/g,'');
			range.moveStart('character', - tagClose.length - selText.length); 
			range.moveEnd('character', - tagClose.length); 
		}
		range.select();   
		//restore window scroll position
		if (document.documentElement && document.documentElement.scrollTop)
		document.documentElement.scrollTop = winScroll
		else if (document.body)
		document.body.scrollTop = winScroll;

	} else if (txtarea.selectionStart || txtarea.selectionStart == '0') { // Mozilla

		//save textarea scroll position
		var textScroll = txtarea.scrollTop;
		//get current selection
		txtarea.focus();
		var startPos = txtarea.selectionStart;
		var endPos = txtarea.selectionEnd;
		selText = txtarea.value.substring(startPos, endPos);
		//insert tags
		checkSelectedText();
		txtarea.value = txtarea.value.substring(0, startPos)
		+ tagOpen + selText + tagClose
		+ txtarea.value.substring(endPos, txtarea.value.length);
		//set new selection
		if (isSample) {
			txtarea.selectionStart = startPos + tagOpen.length;
			txtarea.selectionEnd = startPos + tagOpen.length + selText.length;
		} else {
			txtarea.selectionStart = startPos + tagOpen.length + selText.length + tagClose.length;
			txtarea.selectionEnd = txtarea.selectionStart;
		}
		//restore textarea scroll position
		txtarea.scrollTop = textScroll;
	} 

	function checkSelectedText(){
		if (!selText) {
			selText = sampleText;
			isSample = true;
		} else if (selText.charAt(selText.length - 1) == ' ') { //exclude ending space char
			selText = selText.substring(0, selText.length - 1);
			tagClose += ' ';
		} 
	}
}

// Menu insertion
if (window.addEventListener) 
	window.addEventListener("load", addCharSubsetMenu, false);
else if (window.attachEvent) 
	window.attachEvent("onload", addCharSubsetMenu);