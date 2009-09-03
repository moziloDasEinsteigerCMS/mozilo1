/* 
* 
* $Revision: 19 $
* $LastChangedDate: 2008-03-12 18:06:54 +0100 (Mi, 12 Mrz 2008) $
* $Author: arvid $
*
*/
var ttCounter = 0;
if (navigator.appName == "Microsoft Internet Explorer") var ie = true;  //ie

function htmlOverlopen(domStart){
	var htmlElements = domStart.childNodes;
	for(var i = 0; i < htmlElements.length; i++){
	
		if(htmlElements[i].childNodes.length > 0){
			if(htmlElements[i].getAttribute("accesskey")) {
				checkTag(htmlElements[i]);
			}
			htmlOverlopen(htmlElements[i]);
		}  // einde if
		
	} // einde for
	
} // einde function

function checkTag(htmlTag){
	var dirTag = htmlTag.getAttribute("accesskey");
	if(dirTag.substr(0,8) == "tooltip:"){
		tagValue = htmlTag.getAttribute("accesskey").substr(8,htmlTag.getAttribute("accesskey").length);
		var arrTooltip = tagValue.split(";");
		var title = arrTooltip[0];
		var message = arrTooltip[1];
		var width = arrTooltip[2];
		var border = arrTooltip[3];
		var trans = arrTooltip[4];
		var delay = arrTooltip[5];
		createTooltip(htmlTag, title, message, width, border, trans, delay);
	}
	
}

function createTooltip(htmltag, title, message, width, border, trans, delay){
	ttCounter++;
	if(width == "")	width = 100;
	
	
	var ttDiv = document.createElement("div");
	var ttDivId = document.createAttribute("id");
	ttDivId.value = "tooltip" + ttCounter;
	ttDiv.setAttributeNode(ttDivId);

	var ttDivWidth = document.createAttribute("width");
	
	
	ttDivWidth.value = width;
	ttDiv.setAttributeNode(ttDivWidth);

	var ttTable = document.createElement("table");
	var ttTbody = document.createElement("tbody");
	
	if(title != ""){
		var ttTr = document.createElement("tr");
		var ttTd = document.createElement("td");
	}
	
	if(message != ""){
		var ttTr2 = document.createElement("tr");
		var ttTd2 = document.createElement("td");
	}
	
	ttDiv.appendChild(ttTable);
	ttTable.appendChild(ttTbody);

	ttTableWidth = document.createAttribute("width");
	ttTableWidth.value = width;
	ttTable.setAttributeNode(ttTableWidth);
	
	ttTableHeight= document.createAttribute("height");
	ttTableHeight.value = "10";
	ttTable.setAttributeNode(ttTableHeight);
	
	ttTable.style.backgroundColor = "#ffffdd";			// TOOLTIP BGCOLOR
	ttTable.style.border = "solid";
	ttTable.style.borderWidth = border+"px";
	ttTable.style.borderColor = "#000000";
	
	if(ie){
		ttTable.style.filter = "alpha(opacity:"+trans+")";
	} else {
		ttTable.style.opacity = (trans/100);
	}
	if(title != ""){
	ttTbody.appendChild(ttTr);
	ttTr.appendChild(ttTd);
	ttTd.style.fontFamily = "arial";		// TOOLTIP TITLE STYLE!!!!
	ttTd.style.fontSize = 11 + "px";
	ttTd.style.fontWeight = "bold";
	ttTd.innerHTML = title;
	}
	
	if(message != ""){
		ttTbody.appendChild(ttTr2);
		ttTr2.appendChild(ttTd2);
		ttTd2.style.fontFamily = "arial";		// TOOLTIP BODY STYLE!
		ttTd2.style.fontSize = 11 + "px";
		ttTd2.style.color = "#000000";		//"#2222ff";
		ttTd2.innerHTML = message;
	}
	
	document.body.appendChild(ttDiv);
	
	ttDiv.style.position = "absolute";
	ttDiv.style.display = "none";
	
	htmltag.onmouseover = function eventhandler(){ setTimeout("showDiv('" + ttDiv.id + "')",0 /*delay*/) };
	htmltag.onmouseout = function eventhandler2(){ setTimeout("hideDiv('" + ttDiv.id + "')",0) };
	htmltag.onmousemove = function eventhandler3(e){ followMouse(e,ttDiv,20); };
}

function showDiv(divObject){
	divObject = document.getElementById(divObject);
	divObject.style.display = "block";
}

function hideDiv(divObject){
	divObject = document.getElementById(divObject);
	divObject.style.display = "none";
}

function followMouse(e, divObject, space){
	if (ie) {  //ie
		tempX = (event.clientX + space) + document.body.scrollLeft;
		tempY = (event.clientY + space) + document.documentElement.scrollTop;
	}
	else {  // firefox
		tempX = (e.pageX + space) + "px";
		tempY = (e.pageY + space) + "px";
	}  
	if (tempX < 0){tempX = 0;}
	if (tempY < 0){tempY = 0;}  
	
	divObject.style.left = tempX;
	divObject.style.top = tempY;
}

document.body.onload = function bodyloadEvent(){ htmlOverlopen(document.documentElement,0); };
