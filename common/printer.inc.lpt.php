<script language="javascript">

// GLOBAL VARIABLES

var bTCPresent = true;  //Is TConnector installed (refer to body onload event)


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
   
   strData			= unescape(window.document.printerForm.output.value);
   
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
		case 5 :	strDev = 'Tcp: ' + TC2.Host; break;
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
		
		// write specified amount of data within the specified timeout (3sec = 3000)
		nBytesWritten = TC2.Write (nBytesToWrite, 90000, strData); 
		
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

</script>

<? 

function replaceSpecialCharacters($aString) {
  $boldSwitch = '%1B%47';
  $condensedSwitch = '%0F';
  $normalSwitch= '%1B%2D%00%1B%46%1B%48%1B%57%00%12';
  $doubleSwitch='%1b%45';
  $wideSwitch= '%1b%57%01';
  $underlinedSwitch= '%1b%2d%01';
  
  $aString=str_replace("%b",$boldSwitch,$aString);
  $aString=str_replace("%n",$normalSwitch,$aString);
  $aString=str_replace("%d",$doubleSwitch,$aString);
  $aString=str_replace("%w",$wideSwitch,$aString);      
  $aString=str_replace("%u",$underlinedSwitch,$aString);  
  $aString=str_replace("%c",$condensedSwitch,$aString);        
  return $aString;
}

require_once "Numbers/Words.php";

function ExpandAmountRs($amount) {

// create object
  $nw = new Numbers_Words();
  
  if (strpos(($amount+''),'.') >0) {
    $numwords=explode('.',($amount+''));
    $res = 'Rupees '.$nw->toWords($numwords[0]).' and '.$nw->toWords($numwords[1]).' paisa only';
  } else $res = 'Rupees '.$nw->toWords($amount).' only';
  return $res;
}




?>
<!--The next tag contains the ActiveX object that is used for input/output.
You can create an instance of this object also with JScript:
newObj = new ActiveXObject(servername.typename[, location])-->
<OBJECT classid=clsid:126C289A-607B-4251-BF31-1555A5951948 id=TC2 VIEWASTEXT>
<PARAM NAME="IOType" VALUE="4"><PARAM NAME="Device" VALUE="LPT1"><PARAM NAME="IsOpen" VALUE="0"></OBJECT>
