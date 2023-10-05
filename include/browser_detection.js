function browserType(){
var browser=navigator.appName;
var b_version=navigator.appVersion;
var version=parseFloat(b_version);


//alert(res[0]+'/'+b_version);
ns = (document.layers) ? true : false;
ie = (document.all) ? true : false;
ff = document.getElementById? true : false;
if (ie) return ieType(b_version);
else if (ff) return "ff";
else if (ns) return "ns";
else return "unk";
}
function ieType(b_version){
  var re = new RegExp("MSIE\\s([0-9]*\\.[0-9]*)");

    var m=re.exec(b_version);
if (m == null) {
   return "";
  } else {
    if (m[m.length-1]<7)
      return  'ie6';
    else   
      return  'ie7';
  }
}