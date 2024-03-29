<script language="javascript">

// GLOBAL VARIABLES

var bTCPresent = true;  //Is TConnector installed (refer to body onload event)

var myData = '';

// GLOBAL FUNCTIONS

//////////////////////////////////////
//
//////////////////////////////////////
function writedata()
{
var nBytes;
var strData = new String();
var strDev  = new String();
var TC2     = window.TC2;
   
   strData	= unescape(window.document.printerForm.output.value);
//   strData	= html_entity_decode(window.document.printerForm.output.value);
   
   if (!bTCPresent) 		//check, if we have an instance of TConnector available
   {
		StatClear();
		StatOut('\nNo TConnector2 Object available...\n');
		return;
	}
	
	// only for informational purpose
	switch (TC2.IOType)
	{
		case 0 : strDev = 'None'; break;
		case 1 : strDev = 'Null'; break;
		case 2 : strDev = 'File'; break;
		case 5 : strDev = 'Tcp: ' + TC2.Host; break;
		default: strDev = TC2.Device;
	}

	try
	{
		StatOut( '\nOpening device ' + strDev );
		TC2.Open();  // open device, must be called before any operation
	}
	catch (error)
	{
		StatOut( '\nCouldn\'t open device...' );
		StatOut( '\nSysErr: ' + TC2.TranslateErrorNo(error.number) );
		return;
	}
	
	try
	{
	var nBytesToWrite = strData.length;
	var nBytesWritten = 0;

		StatOut( '\nTry to write ' + nBytesToWrite + ' Bytes, Data=' + strData );
		myData = strData;
		// write specified amount of data within the specified timeout (3sec = 3000)
		nBytesWritten = TC2.Write (nBytesToWrite, 6000000, strData); 
		
		//display amount of data that has been written
		StatOut( '\nOK... ' + nBytesWritten + ' Bytes written to ' + strDev );
		bOK=true;
		
	}
	catch (error)
	{
		bOK = false;
		StatOut( '\nCouldn\'t write data within specified timeout...' );
		StatOut( '\n' + TC2.TranslateErrorNo(error.number) );
		StatOut( '\n' + error.description );
	}
		
	try
	{
		StatOut( '\nClosing device ' + strDev );
		TC2.Close();  // Close device, must be called at the end, otherwise the port will be blocked
	}
	catch (error)
	{
		StatOut( '\nCouldn\'t close device...' );
		StatOut( '\n' + TC2.TranslateErrorNo(error.number) );
	}
	if (bOK) window.close();
}

//////////////////////////////////////
//
//////////////////////////////////////
function StatOut(strStatLine)
{
	var status = window.document.printerForm.printerStatus;

	status.value = status.value + strStatLine;
}

//////////////////////////////////////
//
//////////////////////////////////////
function StatClear()
{
var status = window.document.printerForm.printerStatus;

	status.value = '';
}

//////////////////////////////////////
//
//////////////////////////////////////
function CheckTC()
{
var TCInstance;

	try
	{
	  // if the object creation isn't successfully an exception is generated
		TCInstance = new ActiveXObject("TConnector2.TConnector2");
	}
	catch (exception)
	{
	    // TConnector2.ProgID was not created
		 alert('Can\'t create TConnector2 ActiveX - make sure it is installed on this system!');
		 window.open("../res/TConnector2_setup.exe","download","");
//		 document.printerForm.configure.disabled = true;
		 bTCPresent = false;
	}
	TCInstance = null;
}

//////////////////////////////////////
//js html entities
//////////////////////////////////////
function htmlentities (string, quote_style) {
    // http://kevin.vanzonneveld.net
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: nobbler
    // +    tweaked by: Jack
    // +   bugfixed by: Onno Marsman
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // -    depends on: get_html_translation_table
    // *     example 1: htmlentities('Kevin & van Zonneveld');
    // *     returns 1: 'Kevin &amp; van Zonneveld'
    // *     example 2: htmlentities("foo'bar","ENT_QUOTES");
    // *     returns 2: 'foo&#039;bar'
    if (!string)
      return string;
    
    var histogram = {}, symbol = '', tmp_str = '', entity = '';
    tmp_str = string.toString();
    
    if (false === (histogram = this.get_html_translation_table('HTML_ENTITIES', quote_style))) {
        return false;
    }
    
    for (symbol in histogram) {
        entity = histogram[symbol];
        tmp_str = tmp_str.split(symbol).join(entity);
    }
    
    return tmp_str;
}
function html_entity_decode( string, quote_style ) {
    // Convert all HTML entities to their applicable characters  
    // 
    // version: 905.1002
    // discuss at: http://phpjs.org/functions/html_entity_decode
    // +   original by: john (http://www.jd-tech.net)
    // +      input by: ger
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Onno Marsman
    // +   improved by: marc andreu
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // -    depends on: get_html_translation_table
    // *     example 1: html_entity_decode('Kevin &amp; van Zonneveld');
    // *     returns 1: 'Kevin & van Zonneveld'
    // *     example 2: html_entity_decode('&amp;lt;');
    // *     returns 2: '&lt;'
 if (!string)
      return string;
    var histogram = {}, symbol = '', tmp_str = '', entity = '';
    tmp_str = string.toString();
    
    if (false === (histogram = this.get_html_translation_table('HTML_ENTITIES', quote_style))) {
        return false;
    }

    // &amp; must be the last character when decoding!
    delete(histogram['&']);
    histogram['&'] = '&amp;';

    for (symbol in histogram) {
        entity = histogram[symbol];
        tmp_str = tmp_str.split(entity).join(symbol);
    }
    
    return tmp_str;
}

function get_html_translation_table(table, quote_style) {
    // Returns the internal translation table used by htmlspecialchars and htmlentities  
    // 
    // version: 905.3122
    // discuss at: http://phpjs.org/functions/get_html_translation_table
    // +   original by: Philip Peterson
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: noname
    // +   bugfixed by: Alex
    // +   bugfixed by: Marco
    // +   bugfixed by: madipta
    // +   improved by: KELAN
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // %          note: It has been decided that we're not going to add global
    // %          note: dependencies to php.js. Meaning the constants are not
    // %          note: real constants, but strings instead. integers are also supported if someone
    // %          note: chooses to create the constants themselves.
    // *     example 1: get_html_translation_table('HTML_SPECIALCHARS');
    // *     returns 1: {'"': '&quot;', '&': '&amp;', '<': '&lt;', '>': '&gt;'}
    
    var entities = {}, histogram = {}, decimal = 0, symbol = '';
    var constMappingTable = {}, constMappingQuoteStyle = {};
    var useTable = {}, useQuoteStyle = {};
    
    // Translate arguments
    constMappingTable[0]      = 'HTML_SPECIALCHARS';
    constMappingTable[1]      = 'HTML_ENTITIES';
    constMappingQuoteStyle[0] = 'ENT_NOQUOTES';
    constMappingQuoteStyle[2] = 'ENT_COMPAT';
    constMappingQuoteStyle[3] = 'ENT_QUOTES';

    useTable       = !isNaN(table) ? constMappingTable[table] : table ? table.toUpperCase() : 'HTML_SPECIALCHARS';
    useQuoteStyle = !isNaN(quote_style) ? constMappingQuoteStyle[quote_style] : quote_style ? quote_style.toUpperCase() : 'ENT_COMPAT';

    if (useTable !== 'HTML_SPECIALCHARS' && useTable !== 'HTML_ENTITIES') {
        throw new Error("Table: "+useTable+' not supported');
        // return false;
    }

    // ascii decimals for better compatibility
    entities['38'] = '&amp;';
    if (useQuoteStyle !== 'ENT_NOQUOTES') {
        entities['34'] = '&quot;';
    }
    if (useQuoteStyle === 'ENT_QUOTES') {
        entities['39'] = '&#039;';
    }
    entities['60'] = '&lt;';
    entities['62'] = '&gt;';

    if (useTable === 'HTML_ENTITIES') {
        entities['160'] = '&nbsp;';
        entities['161'] = '&iexcl;';
        entities['162'] = '&cent;';
        entities['163'] = '&pound;';
        entities['164'] = '&curren;';
        entities['165'] = '&yen;';
        entities['166'] = '&brvbar;';
        entities['167'] = '&sect;';
        entities['168'] = '&uml;';
        entities['169'] = '&copy;';
        entities['170'] = '&ordf;';
        entities['171'] = '&laquo;';
        entities['172'] = '&not;';
        entities['173'] = '&shy;';
        entities['174'] = '&reg;';
        entities['175'] = '&macr;';
        entities['176'] = '&deg;';
        entities['177'] = '&plusmn;';
        entities['178'] = '&sup2;';
        entities['179'] = '&sup3;';
        entities['180'] = '&acute;';
        entities['181'] = '&micro;';
        entities['182'] = '&para;';
        entities['183'] = '&middot;';
        entities['184'] = '&cedil;';
        entities['185'] = '&sup1;';
        entities['186'] = '&ordm;';
        entities['187'] = '&raquo;';
        entities['188'] = '&frac14;';
        entities['189'] = '&frac12;';
        entities['190'] = '&frac34;';
        entities['191'] = '&iquest;';
        entities['192'] = '&Agrave;';
        entities['193'] = '&Aacute;';
        entities['194'] = '&Acirc;';
        entities['195'] = '&Atilde;';
        entities['196'] = '&Auml;';
        entities['197'] = '&Aring;';
        entities['198'] = '&AElig;';
        entities['199'] = '&Ccedil;';
        entities['200'] = '&Egrave;';
        entities['201'] = '&Eacute;';
        entities['202'] = '&Ecirc;';
        entities['203'] = '&Euml;';
        entities['204'] = '&Igrave;';
        entities['205'] = '&Iacute;';
        entities['206'] = '&Icirc;';
        entities['207'] = '&Iuml;';
        entities['208'] = '&ETH;';
        entities['209'] = '&Ntilde;';
        entities['210'] = '&Ograve;';
        entities['211'] = '&Oacute;';
        entities['212'] = '&Ocirc;';
        entities['213'] = '&Otilde;';
        entities['214'] = '&Ouml;';
        entities['215'] = '&times;';
        entities['216'] = '&Oslash;';
        entities['217'] = '&Ugrave;';
        entities['218'] = '&Uacute;';
        entities['219'] = '&Ucirc;';
        entities['220'] = '&Uuml;';
        entities['221'] = '&Yacute;';
        entities['222'] = '&THORN;';
        entities['223'] = '&szlig;';
        entities['224'] = '&agrave;';
        entities['225'] = '&aacute;';
        entities['226'] = '&acirc;';
        entities['227'] = '&atilde;';
        entities['228'] = '&auml;';
        entities['229'] = '&aring;';
        entities['230'] = '&aelig;';
        entities['231'] = '&ccedil;';
        entities['232'] = '&egrave;';
        entities['233'] = '&eacute;';
        entities['234'] = '&ecirc;';
        entities['235'] = '&euml;';
        entities['236'] = '&igrave;';
        entities['237'] = '&iacute;';
        entities['238'] = '&icirc;';
        entities['239'] = '&iuml;';
        entities['240'] = '&eth;';
        entities['241'] = '&ntilde;';
        entities['242'] = '&ograve;';
        entities['243'] = '&oacute;';
        entities['244'] = '&ocirc;';
        entities['245'] = '&otilde;';
        entities['246'] = '&ouml;';
        entities['247'] = '&divide;';
        entities['248'] = '&oslash;';
        entities['249'] = '&ugrave;';
        entities['250'] = '&uacute;';
        entities['251'] = '&ucirc;';
        entities['252'] = '&uuml;';
        entities['253'] = '&yacute;';
        entities['254'] = '&thorn;';
        entities['255'] = '&yuml;';
    }
    
    // ascii decimals to real symbols
    for (decimal in entities) {
        symbol = String.fromCharCode(decimal);
        histogram[symbol] = entities[decimal];
    }
    
    return histogram;
}


</script>

<? 

function replaceSpecialCharacters($aString) {
  $boldSwitch = '';
  $condensedSwitch = '';
  $normalSwitch= '';
  $doubleSwitch='';
  $wideSwitch= '';
  $underlinedSwitch= '';
  $ejectSwitch = '';
 
/*
  $boldSwitch = '%1B%47';
  $condensedSwitch = '%1B%0F';
  $normalSwitch= '%1B%2D%00%1B%46%1B%48%1B%57%00%12';
  $doubleSwitch='%1b%45';
  $wideSwitch= '%1b%57%01';
  $underlinedSwitch= '%1b%2d%01';
  $ejectSwitch = '%1B%0C';
*/

  $aString=str_replace("%b",$boldSwitch,$aString);
  $aString=str_replace("%n",$normalSwitch,$aString);
  $aString=str_replace("%d",$doubleSwitch,$aString);
  $aString=str_replace("%w",$wideSwitch,$aString);
  $aString=str_replace("%u",$underlinedSwitch,$aString);
  $aString=str_replace("%c",$condensedSwitch,$aString);
  $aString=str_replace("%e",$ejectSwitch,$aString);

  return $aString;
}

//require_once "Words.php";

function ExpandAmountRs_old($amount) {

// create object
  $nw = new Numbers_Words();
  
  if (strpos(($amount+''),'.') >0) {
    $numwords=explode('.',($amount+''));
    $res = 'Rupees '.$nw->toWords($numwords[0]).' and '.$nw->toWords($numwords[1]).' paisa only';
  } else $res = 'Rupees '.$nw->toWords($amount).' only';
  return $res;
}

function ExpandAmount($amount) {

// create object
  $nw = new Numbers_Words();
  
  if (strpos(($amount+''),'.') >0) {
    $numwords=explode('.',($amount+''));
    $res = $nw->toWords($numwords[0]).' and '.$nw->toWords($numwords[1]).'  only';
  } else $res = $nw->toWords($amount);
  return $res;
}



?>
<!--The next tag contains the ActiveX object that is used for input/output.
You can create an instance of this object also with JScript:
newObj = new ActiveXObject(servername.typename[, location])
<PARAM NAME="IOType" VALUE="4"><PARAM NAME="Device" VALUE="LPT1"><PARAM NAME="IsOpen" VALUE="0">-->
<OBJECT classid=clsid:126C289A-607B-4251-BF31-1555A5951948 id=TC2 VIEWASTEXT>
<? if ($_SESSION['int_user_printing_type'] == 1) { ?>
	<PARAM NAME="IOType" VALUE="4"><PARAM NAME="Device" VALUE="LPT1">
<? } else { ?>
	<PARAM NAME="IOType" VALUE="5"><PARAM NAME="Host" VALUE="127.0.0.1">
<? } ?>

<PARAM NAME="Service" VALUE="3000"></OBJECT>


