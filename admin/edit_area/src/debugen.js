var debugennummer=1;
function debugen(inhalt,komentar) {
    if(debug == "true") {//document.getElementById("debug").innerHTML
        var texttmp = "";
        if(document.getElementById("debug").innerHTML.length > 3) {
            texttmp = document.getElementById("debug").innerHTML;
        };
        if(typeof inhalt == "object") {
//            inhalt = inhalt.toSource();
            inhalt = serialize(inhalt);
        };
        inhalt = "<b>" + debugennummer + " --------------- " + komentar + " -------------</b><br>" + inhalt;
        document.getElementById("debug").innerHTML = texttmp + inhalt + "<br>";
        debugennummer++;
    };
}

function serialize(_obj) {
   // Let Gecko browsers do this the easy way
   if (typeof _obj.toSource !== 'undefined' && typeof _obj.callee === 'undefined') {
      return _obj.toSource();
   }
   // Other browsers must do it the hard way
   switch (typeof _obj) {
      // numbers, booleans, and functions are trivial:
      // just return the object itself since its default .toString()
      // gives us exactly what we want
      case 'number':
      case 'boolean':
      case 'function':
         return _obj;
         break;

      // for JSON format, strings need to be wrapped in double-quotes
      case 'string':
         return '\'' + _obj + '\'';
         break;

      case 'object':
         var str;
         if (_obj.constructor === Array || typeof _obj.callee !== 'undefined') {
            str = '[';
            var i, len = _obj.length;
            for (i = 0; i < len-1; i++) { str += serialize(_obj[i]) + ','; }
            str += serialize(_obj[i]) + ']';
         } else {
            str = '{';
            var key;
            for (key in _obj) { str += key + ':' + serialize(_obj[key]) + ','; }
            str = str.replace(/\,$/, '') + '}';
         }
         return str;
         break;

      default:
         return 'UNKNOWN';
         break;
   }
}
