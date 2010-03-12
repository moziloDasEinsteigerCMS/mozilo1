function cat_togglen(toggleid,iconShow,iconHide,noIconTextShow,noIconTextHide,klick) {

    var linkButton = "<input id=\"" + toggleid + "_button\" class=\"input_img_button\" type=\"image\" value=\"" + noIconTextShow + "\"  src=\"" + iconShow + "\" onclick=\"cat_togglen('" + toggleid + "','" + iconShow + "','" + iconHide + "','" + noIconTextShow + "','" + noIconTextHide + "','true');\">";

    if (document.getElementById(toggleid + '_linkBild')) {
        document.getElementById(toggleid + '_linkBild').innerHTML = linkButton;
    }

    /* ein hack damit f�r die css bei error display:block gesetzt werden kann */
    if (klick) {
        if (document.getElementById(toggleid).style.display == "none") {
            if (document.getElementById(toggleid + '_button').src) {
                document.getElementById(toggleid + '_button').src = iconHide;
                document.getElementById(toggleid + '_button').value = noIconTextHide;
            }
            document.getElementById(toggleid).style.display = "block";
        } else {
            if (document.getElementById(toggleid + '_button').src) {
                document.getElementById(toggleid + '_button').src = iconShow;
                document.getElementById(toggleid + '_button').value = noIconTextShow;
            }
            document.getElementById(toggleid).style.display = "none";
        }
    } else {
        if (document.getElementById(toggleid).style.display == "block") {
            if (document.getElementById(toggleid + '_button').src) {
                document.getElementById(toggleid + '_button').src = iconHide;
                document.getElementById(toggleid + '_button').value = noIconTextHide;
            }
        } else {
            document.getElementById(toggleid).style.display = "none";
        }
    }
}
