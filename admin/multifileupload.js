/* 
* 
* $Revision$
* $LastChangedDate$
* $Author$
*
*/
// Multiple file selector by Stickman -- http://www.the-stickman.com 
// with thanks to: [for Safari fixes] Luis Torrefranca -- http://www.law.pitt.edu and Shawn Parker & John Pennypacker -- http://www.fuzzycoconut.com [for duplicate name bug] 'neal'

// um mehrere inputs erwietert stefanbe
var countgesamt = 0;
var elementearray = new Array();

function MultiSelector( list_target, max, buttoncaption ) {
	this.list_target = list_target;
	this.count = 0;
	this.iid = 0;
	if( max ) {
		this.max = max;
	} else {
		this.max = -1;
	};
	this.buttoncaption = buttoncaption;
	this.addElement = function( element, pos ) {
		if( element.tagName == 'INPUT' && element.type == 'file' ) {
			if(element.id) {
				if (elementearray.join(" ").search(element.id) == -1) {
					elementearray[elementearray.length] = element.id;
				};
			};
			element.name = 'file_' + pos + '_' + this.iid++;
			element.multi_selector = this;
			element.onchange = function() {
				var new_element = document.createElement( 'input' );
				new_element.type = 'file';
				new_element.id = element.id;
				new_element.className = element.className;
				this.parentNode.insertBefore( new_element, this );
				this.multi_selector.addElement( new_element, pos );
				this.multi_selector.addListRow( this );
				this.style.position = 'absolute';
				this.style.left = '-1000px';
			};
			countgesamt++;
			if( this.max != -1 && this.count >= this.max ) {
				element.disabled = true;
			};
			if(countgesamt - elementearray.length >= this.max) {
				element.disabled = true;
				for (var i = 0; i < elementearray.length; ++i) {
					if(document.getElementById(elementearray[i])) {
						document.getElementById(elementearray[i]).disabled = true;
					};
				};
			};
			this.count++;
			this.current_element = element;
		} else {
			alert( 'Error: not a file input element' );
		};
	};
	this.addListRow = function( element ) {
		var new_row = document.createElement( 'div' );
		new_row.className = 'deleteuploadfilediv';

		var new_row_button = document.createElement( 'input' );
		new_row_button.type = 'button';
		new_row_button.value = this.buttoncaption;
		new_row_button.className = 'deleteuploadfilebutton';
//		new_row_button.id = 'deleteuploadfilebutton';
		new_row.element = element;
		new_row_button.onclick= function() {
			this.parentNode.element.parentNode.removeChild( this.parentNode.element );
			this.parentNode.parentNode.removeChild( this.parentNode );
			this.parentNode.element.multi_selector.count--;
			this.parentNode.element.multi_selector.current_element.id = this.parentNode.element.id;
			this.parentNode.element.multi_selector.current_element.disabled = false;
			countgesamt--;
			for (var i = 0; i < elementearray.length; ++i) {
				if(document.getElementById(elementearray[i])) {
					document.getElementById(elementearray[i]).disabled = false;
				};
			};
			return false;
		};
		element_work = element.value;
		element_tab = element_work.split("\\");
		if (element_work.search(/\\.+/) != -1) { // windows system
			element_tab = element_work.split("\\");
		} 
		if (element_work.search(/\/.+/) != -1) { // anderes system
			element_tab = element_work.split("/");
		}
		nbr_elements = element_tab.length;
		new_row.innerHTML = element_tab[nbr_elements-1];
		new_row.appendChild( new_row_button );
		this.list_target.appendChild( new_row );
	};
};