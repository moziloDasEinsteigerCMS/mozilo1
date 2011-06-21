function insert(aTag, eTag, keepSelectedText) {
    var toggle_status = true
    if((typeof eAs != "undefined" && eAs[meditorID]["displayed"]) || (typeof editAreas != "undefined" && editAreas[meditorID]["displayed"])) {
        toggle_status = false
        editAreaLoader.toggle(meditorID);
    }
    var input = document.forms['form'].elements['pagecontent'];
    var scrolltop = input.scrollTop;
    input.focus();
    /* für Internet Explorer */
    if(typeof document.selection != 'undefined') {
        /* Einfügen des Formatierungscodes */
        var range = document.selection.createRange();
        var insText = range.text;
        if (keepSelectedText == true) {
            range.text = aTag + insText + eTag;
        } else {
            range.text = aTag + eTag;
        }
        /* Anpassen der Cursorposition */
        range = document.selection.createRange();
        if ((insText.length == 0) || (keepSelectedText == false)) {
            range.move('character', -eTag.length);
        } else {
            range.moveStart('character', aTag.length + insText.length + eTag.length);
        }
        range.select();
    }
    /* für neuere auf Gecko basierende Browser */
    else if(typeof input.selectionStart != 'undefined')
    {
        /* Einfügen des Formatierungscodes */
        var start = input.selectionStart;
        var end = input.selectionEnd;
        var insText = input.value.substring(start, end);
        if (keepSelectedText == true) {
            input.value = input.value.substr(0, start) + aTag + insText + eTag + input.value.substr(end);
        } else {
            input.value = input.value.substr(0, start) + aTag + eTag + input.value.substr(end);
        }
        /* Anpassen der Cursorposition */
        var pos;
        if ((insText.length == 0) || (keepSelectedText == false)) {
            pos = start + aTag.length;
        } else {
            pos = start + aTag.length + insText.length + eTag.length;
        }
        input.selectionStart = pos;
        input.selectionEnd = pos;
    }
    /* für die Übrigen Browser */
    else
    {
        /* Abfrage der Einfügeposition */
        var pos;
        var re = new RegExp('^[0-9]{0,3}$');
        while(!re.test(pos)) {
            pos = prompt("Einfügen an Position (0.." + input.value.length + "):", "0");
        }
        if(pos > input.value.length) {
            pos = input.value.length;
        }
        /* Einfügen des Formatierungscodes */
        var insText = prompt("Bitte geben Sie den zu formatierenden Text ein:");
        input.value = input.value.substr(0, pos) + aTag + insText + eTag + input.value.substr(pos);
    }
    input.scrollTop = scrolltop;
    if(toggle_status == false) {
        editAreaLoader.toggle(meditorID);
    }
}

function insertAndResetSelectbox(selectbox) {
    if (selectbox.selectedIndex > 0) {
        insert(selectbox.options[selectbox.selectedIndex].value, '', false);
        selectbox.selectedIndex = 0;
    }
}

function insertPluginAndResetSelectbox(selectbox) {
    if (selectbox.selectedIndex > 0) {
        var currentValue = selectbox.options[selectbox.selectedIndex].value;
        // {PLUGIN|}
        if (currentValue.search(/\|\}/) != -1) {
            insert(currentValue.substring(0, currentValue.length-1), '}', true);
        }
        // {PLUGIN|wert}
        else if (currentValue.search(/\|/) != -1) {
            insert(currentValue, '', false);
        } 
        // {PLUGIN}
        else {
            insert(currentValue, '', false);
        }
        selectbox.selectedIndex = 0;
    }
}

function insertTagAndResetSelectbox(selectbox) {
    if (selectbox.selectedIndex > 0) {
        insert('['+selectbox.options[selectbox.selectedIndex].value+'|', ']', true);
        selectbox.selectedIndex = 0;
    }
}