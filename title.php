<?php


/**
*
* @version 	$Id: title.php,v 1.2 2006/02/14 09:55:35 cvs Exp $
* @copyright 	Cynergy Software 2005
* @author	Luk Gastmans
* @date		12 Oct 2005
* @module 	Title Bar Navigation
* @name  	title.php
*
* The title bar navigation is used for the entire site.  It lets you jump to
* the various modules that you have access to as well as switch months using
* the Previous/Next buttons.
*/



$bool_root_folder = true;


require_once("include/const.inc.php");
require_once("session.inc.php");
require_once("db.inc.php");
require_once("module.inc.php");
require_once('db_params.php');
require_once("db_funcs.inc.php");



?>

<html>
<head>
	
	<!-- <link href="include/bootstrap-3.3.4-dist/css/bootstrap.min.css" rel="stylesheet"> -->

	<link href="include/styles.css" rel="stylesheet" type="text/css">

	<style type="text/css">
		body {
			margin:0;
			padding:0;
		}
	</style>
	
	<link rel="stylesheet" type="text/css" href="yui2.7.0/build/container/assets/skins/sam/container.css" />
	<link rel="stylesheet" type="text/css" href="yui2.7.0/build/fonts/fonts-min.css" />
	<link rel="stylesheet" type="text/css" href="yui2.7.0/build/button/assets/skins/sam/button.css" />
	
	<script type="text/javascript" src="yui2.7.0/build/yahoo-dom-event/yahoo-dom-event.js"></script>
	<script type="text/javascript" src="yui2.7.0/build/element/element-min.js"></script>
	<script type="text/javascript" src="yui2.7.0/build/container/container-min.js"></script>
	<script type="text/javascript" src="yui2.7.0/build/button/button-min.js"></script>

	<script type="text/javascript" src="yui2.7.0/build/yahoo/yahoo-min.js"></script>
	<script type="text/javascript" src="yui2.7.0/build/event/event-min.js"></script>
	<script type="text/javascript" src="yui2.7.0/build/connection/connection-min.js"></script>
	<script type="text/javascript" src="yui2.7.0/build/json/json-min.js"></script>

</head>


<body class="yui-skin-sam" leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 background='images/mainmenu_background.png'>


<div id="frame_title_loader">
<?

	
	$arr_db = explode("_", $_SESSION['invent_database_loaded']); //$arr_invent_config['database']['invent_database']);
	$db_db = $_SESSION['invent_database_loaded'];
	$db_server =  $arr_invent_config['database']['invent_server'];
	$db_login = $arr_invent_config['database']['invent_login'];
	$db_password = $arr_invent_config['database']['invent_password'];

	
	if (IsSet($_GET['action'])) {

		if ($_GET['action'] == 'select') {

			//require_once("Config.php");
			$arr_period = explode("-", $_GET['database']);
//			$db_db = $arr_db[0]."_".$arr_period[0]."_".$arr_period[1];
			$db_db = $arr_invent_config['database']['invent_database']."_".$arr_period[0]."_".$arr_period[1];
			
			/*
				check whether selected db exists
			*/
			if (((date('n') >= 4) && ($arr_period[0] == date('Y'))) || ((date('n') <= 3) && ($arr_period[1] == date('Y')))) {
//				$dsn_check = "mysqli://$db_login:$db_password@$db_server/$arr_db[0]";
				$dsn_check = "mysqli://$db_login:$db_password@$db_server/".$arr_invent_config['database']['invent_database'];

				$conn_check = new mysqli($db_server, $db_login, $db_password, $arr_invent_config['database']['invent_database']);				
			}
			else {
				$dsn_check = "mysqli://$db_login:$db_password@$db_server/$db_db";

				$conn_check = new mysqli($db_server, $db_login, $db_password, $db_db);
			}

			//$conn_check =& MDB2::connect($dsn_check);

			$bool_found = false;
			$bool_db_error = false;

			//if (MDB2::isError($conn_check)) {
			if ($conn->connect_errno) {
				/*
					does not exist, so check whether
					it could be a new financial year
				*/
				$bool_db_error = true;
//				$bool_found = check_current_db($arr_db[0], $db_server, $db_login, $db_password);
				$bool_found = check_current_db($arr_invent_config['database']['invent_database'], $db_server, $db_login, $db_password);
			}
			
			if ($bool_db_error == false) {
				/*
				$c = new Config();
				$root =& $c->parseConfig($str_root."include/config.ini", "IniFile");
				$databaseSection =& $root->getItem("section", "database");
				$databaseDirective =& $databaseSection->getItem("directive", "invent_database");
				
				if (((date('n') >= 4) && ($arr_period[0] == date('Y'))) || ((date('n') <= 3) && ($arr_period[1] == date('Y'))))
					$databaseDirective->setContent($arr_db[0]);
				else
					$databaseDirective->setContent($arr_db[0]."_".$arr_period[0]."_".$arr_period[1]);
				$c->writeConfig();
				*/
				if (((date('n') >= 4) && ($arr_period[0] == date('Y'))) || ((date('n') <= 3) && ($arr_period[1] == date('Y')))) {
//					$_SESSION['invent_database_loaded'] = $arr_db[0];
					$_SESSION['invent_database_loaded'] = $arr_invent_config['database']['invent_database'];
					$_SESSION['int_month_loaded'] = 4;
					$_SESSION['int_year_loaded'] = $arr_period[0];
				}
				else {
//					$_SESSION['invent_database_loaded'] = $arr_db[0]."_".$arr_period[0]."_".$arr_period[1];
					$_SESSION['invent_database_loaded'] = $arr_invent_config['database']['invent_database']."_".$arr_period[0]."_".$arr_period[1];
					$_SESSION['int_month_loaded'] = 3;
					$_SESSION['int_year_loaded'] = $arr_period[1];
				}
				
				//die($_GET['int_module_selected']."::".$_SESSION['int_module_selected']);
				echo "<script language='javascript'>\n";
				echo "document.location='title.php?int_module_selected=".$_SESSION['int_module_selected']."';\n";
				echo "</script>";
			}
			else {
				if ($bool_found == true) {
					$_SESSION['invent_is_new_financial_year'] = true;
					
					$_SESSION['int_month_loaded'] = 3;
					$_SESSION['int_year_loaded'] = $arr_period[1];
?>
					<script language="javascript">
						alert('For the new financial year, go to Admin | Database Tools, and create the new month');
					</script>
<?				}
				else {
?>
					<script language="javascript">
						alert('Selected financial year not found');
					</script>
<?				}
			}
		}
	}

if (!empty($_GET['int_set_month']))
	$int_new_month = $_GET['int_set_month'];
else 
	$int_new_month = $_SESSION["int_month_loaded"];

if (!empty($_GET['int_module_selected'])) {
	$_SESSION['int_module_selected'] = $_GET['int_module_selected'];
}
else {
	if (!IsSet($_SESSION['int_module_selected'])) {
		$_SESSION['int_module_selected'] = 1;
	}
}

if (!empty($_GET['int_set_storeroom'])) {
	$_SESSION['int_current_storeroom'] = $_GET['int_set_storeroom'];
}

if (!empty($_GET['int_set_year'])) 
	$int_new_year = $_GET['int_set_year'];
else 
	$int_new_year = $_SESSION["int_year_loaded"];

$bool_new_month_loaded = false;
$bool_month_error = false;

/*
	if the month or year changed, reload
*/
if (($int_new_year <> $_SESSION["int_year_loaded"]) || ($int_new_month <> $_SESSION["int_year_loaded"])) {
    $_SESSION["int_year_loaded"] = $int_new_year;
    $_SESSION["int_month_loaded"] = intval($int_new_month);
} else {
    $int_new_month = $_SESSION["int_month_loaded"];
    $int_new_year =  $_SESSION["int_year_loaded"];
}


$int_connect_mode = @$_REQUEST['connect_mode'];
if ($int_connect_mode <> '') {
	$_SESSION["connect_mode"] = $int_connect_mode;
} elseif ($int_connect_mode=='') {
	$int_connect_mode = @$_SESSION["connect_mode"];
	if (empty($int_connect_mode)) {
		$int_connect_mode=CONNECT_METHOD;
		
		$_SESSION["connect_mode"] = intval($int_connect_mode);
	}
}

/*
	if not the current month/year is selected
	then the background color of the table
	should be red
*/
if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y')))
	$bg_color = "#8BA8D3";
else
	$bg_color = "red";

/*
	check current month/year against loaded month/year
*/
$arrtemp = explode("_", $_SESSION['invent_database_loaded']);
if (
	((count($arrtemp) > 0) && (is_numeric($arrtemp[count($arrtemp)-1])))
	|| (IsSet($_SESSION['invent_is_new_financial_year']) && ($_SESSION['invent_is_new_financial_year'] == true))
) {
//echo "1";
	if ($_SESSION['int_month_loaded'] <= 3) {
		$start_year = $_SESSION['int_year_loaded']-1;
		$end_year = $_SESSION['int_year_loaded'];
	}
	else {
		$start_year = $_SESSION['int_year_loaded'];
		$end_year = $_SESSION['int_year_loaded']+1;
	}

	$str_prev_fs_year = ($start_year-1)."-".$start_year;
	$str_financial_year = $start_year."-".$end_year;
	$str_next_fs_year = ($start_year+1)."-".($start_year+2);

	$arr_months = getFSMonths();
//	print_r($arr_months);
	/*
	$arr_months = array(
		"4_".$start_year => "April ".$start_year,
		"5_".$start_year => "May ".$start_year,
		"6_".$start_year => "June ".$start_year,
		"7_".$start_year => "July ".$start_year,
		"8_".$start_year => "August ".$start_year,
		"9_".$start_year => "September ".$start_year,
		"10_".$start_year => "October ".$start_year,
		"11_".$start_year => "November ".$start_year,
		"12_".$start_year => "December ".$start_year,
		"1_".$end_year => "January ".$end_year,
		"2_".$end_year => "February ".$end_year,
		"3_".$end_year => "March ".$end_year
	);
	*/
}
else {
//echo "2";
	$is_new_fs = check_current_db($arr_db[0], $db_server, $db_login, $db_password);
	$arr_months = array();
	
	if ($is_new_fs) {
		//echo "yes";
		if ($_SESSION['int_month_loaded'] >= 4) {
			$cur_year = date('Y')+1;
			$cur_month = date('n');
		}
		else {
			$cur_year = date('Y');
			$cur_month = date('n');
		}
		
		$str_prev_fs_year = ($cur_year-2)."-".($cur_year-1);
		$str_financial_year = ($cur_year-1)."-".$cur_year;
		$str_next_fs_year = $cur_year."-".($cur_year+1);
	}
	else {
		//echo "no";
		$cur_year = date('Y');
		$cur_month = date('n');
		
		$int_month = 4;
		if ($cur_month < 4)
			$int_year = $cur_year-1;
		else
			$int_year = $cur_year;

		while (true) {
			$arr_months[$int_month."_".$int_year] = getMonthName($int_month)." ".$int_year;
			
			if ($int_month == $cur_month)
				break;
			else if ($int_month == 12) {
				$int_month = 1;
				$int_year++;
			}
			else
				$int_month++;
		}
		
		if ($cur_month < 4) {
			$cur_year = date('Y');
			$str_prev_fs_year = ($cur_year-2)."-".($cur_year-1);
			$str_financial_year = ($cur_year-1)."-".$cur_year;
			$str_next_fs_year = $cur_year."-".($cur_year+1);
		}
		else {
			$cur_year = date('Y')+1;
			$str_prev_fs_year = ($cur_year-2)."-".($cur_year-1);
			$str_financial_year = ($cur_year-1)."-".$cur_year;
			$str_next_fs_year = $cur_year."-".($cur_year+1);
		}
	}
}

?>

<script language=JavaScript>
	<? if ($bool_month_error==false) { ?> 
		parent.frames["content"].document.location = parent.frames["content"].document.location.href;
	<? } else { ?>
		alert("The month/year you are trying to load could not be found!");
	<? } ?>
	
	var curImage;
	var curImageFile;
	
	function selectImage(anObject,anImage) {
		if (curImage) {
			curImage.src='images/'+curImageFile+".gif";
		}
		curImage=anObject;
		curImageFile=anImage;
		curImage.src='images/'+curImageFile+"down.gif";
	}
	
	function hilite(int_id) {
		var oSubmenuFrame = document.getElementById('submenu');
		var oDoc = oSubmenuFrame.contentWindow || oSubmenuFrame.contentDocument;
		
		for (i=0; i < oDoc.int_num_submenus; i++) {
			element=oDoc.document.getElementById('submenu'+(i+1));
			element.className = 'tab';
		}
		element=oDoc.document.getElementById('submenu'+int_id);
		element.className = 'tabdown';
	}
	
	function selectMonth(strPeriod) {
		arrPeriod = strPeriod.split("_");
		document.location = "title.php?int_set_year="+arrPeriod[1]+"&int_set_month="+arrPeriod[0];
	}
	
	function loadDatabase(strPeriod, moduleSelected) {
		var strURL = "title.php?action=select&database="+strPeriod;
		document.location = strURL;
	}
	
	(function () {
		
		function createRequest() {
			try {
				var requester = new XMLHttpRequest();
			}
			catch (error) {
				try {
					requester = new ActiveXObject("Microsoft.XMLHTTP");
				}
				catch (error) {
					return false;
				}
			}
			return requester;
		}

		var requester = createRequest();
	
		function stateHandler() {
			if (requester.readyState == 4) {
				if (requester.status == 200)  {
					str_retval = requester.responseText;
					parent.frames["content"].document.location = str_retval;
				}
				else {
					alert("failed to update list... please try again.");
				}
				requester = null;
				requester = createRequest();
			}
		}
		
		/*
			logout ajax handler
		*/
		var handleSuccess = function(o) {
			var r = YAHOO.lang.JSON.parse(o.responseText);
			
			if (r.replyStatus=='Ok')
				window.location = "logout.php";
		}
		
		var handleFailure = function(o){
			alert('failed to logout');
		}
		var callback = {
			success:handleSuccess,
			failure:handleFailure
		};
		
		/*
			"checkedButtonChange" event handler for each ButtonGroup instance
		*/
		function onCheckedButtonChange(oEvent) {
			if (oEvent.newValue.get("value") == 0) {
				YAHOO.util.Connect.asyncRequest('POST', 'logout.php', callback);
				//top.window.close();
			}
			else if (oEvent.newValue.get("value") == 'h') {
				var helpWin = window.open('help/index.php','helpWin');
				helpWin.focus();
			}
			else {
				var oSubmenuFrame = document.getElementById('submenu');
				oSubmenuFrame.src = "submenu.php?selected="+oEvent.newValue.get("value");
				
				requester.onreadystatechange = stateHandler;
				requester.open("GET", "get_active_link.php?live=1&module_id="+oEvent.newValue.get("value"));
				requester.send(null);
			}
		}
		
		// Create a ButtonGroup without using existing markup
		var oButtonGroup3 = new YAHOO.widget.ButtonGroup({
			id:"buttongroup3",
			name:"radiofield3",
			container: "mainmenu"
		});

		<?
			$int_counter = 0;
			$arr_menu = array();
			for ($i=0; $i<count($_SESSION['arr_modules']);$i++) {
				if (intval(getModuleAccessLevel($_SESSION['arr_modules'][$i]->str_module_name)) > 0) {
					$arr_menu[$int_counter]['label'] = $_SESSION['arr_modules'][$i]->str_module_name;
					$arr_menu[$int_counter]['value'] = $_SESSION['arr_modules'][$i]->int_module_id;
					$arr_menu[$int_counter]['id'] = $_SESSION['arr_modules'][$i]->str_active_link;
					
					if ($_SESSION['int_module_selected'] == $_SESSION['arr_modules'][$i]->int_module_id)
						$arr_menu[$int_counter]['checked'] = true;
					else
						$arr_menu[$int_counter]['checked'] = false;
					
					$int_counter++;
				}
			}
			
			/*
			$arr_menu[$int_counter]['label'] = "Help";
			$arr_menu[$int_counter]['value'] = 'h';
			$int_counter++;
			*/
			$arr_menu[$int_counter]['label'] = "Logout";
			$arr_menu[$int_counter]['value'] = 0;
			
			require_once('JSON.php');
			$json = new Services_JSON();
			$json_menu = $json->encode($arr_menu);
		?>
		
		oButtonGroup3.addButtons(eval(<?echo $json_menu;?>));

		oButtonGroup3.on("checkedButtonChange", onCheckedButtonChange);
	} () );

</script>

<form id="button-example-form" method='get'>

<table height="100%" width="100%" cellspacing=0 border=0 cellpadding=0>
	<tr height="50%">

		<!-- MAIN MENU -->

		<td align='left' valign='middle'>
<!-- 			ADDED NEW -->
			<div id="mainmenu"></div>
<!-- 			END ADDED NEW -->
		</td>
		
		
		<td align='right' class='normaltext'>
			<table border='0' cellpadding='4' cellspacing='0' bgcolor='<?echo $bg_color; ?>'>
				<tr>
					<td class='normaltext'>
						&nbsp;<? echo "Logged in as <b>". $_SESSION['str_user_name']."</b>"; ?>
					</td>
					<td rowspan='2'>
						<table border=0>
							<tr>
								<td>
									<table border='0'>
									<tr>
										<TD colspan="3" align="center">
											Database:<b><?echo $str_financial_year;?></b>
										</TD>
									</tr>
									<tr>
<td>
	<a href="javascript:loadDatabase('<?echo $str_prev_fs_year?>')"><img border=0 src='images/db-first.png'></a>
</td>
<td>
	<select name="select_month" onchange="javascript:selectMonth(this.value)" class='normaltext'>
	<?
		foreach($arr_months as $key=>$value) {
			$str_key = $int_new_month."_".$int_new_year;
			if ($str_key == $key)
				echo "<option value='$key' selected>$value</option>\n";
			else
				echo "<option value='$key'>$value</option>\n";
		}
	?>
	</select>
</td>
<td>
	<a href="javascript:loadDatabase('<?echo $str_next_fs_year?>')"><img border=0 src='images/db-last.png'></a>
</td>
									</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td class='normaltext'>
						&nbsp;Storeroom:
						<select name='int_set_storeroom' onchange="this.form.submit();" class='select_storeroom'>
<?				
							$arr_storeroom_list = getStoreroomList();		
							foreach ($arr_storeroom_list as $key=>$value) {
								$sel=($key==$_SESSION['int_current_storeroom']?'selected':'');
								if ($key==1)
									echo "<option value='$key' $sel>$value</option>\n";
								else
									echo "<option value='$key' $sel>$value</option>\n";
							}
?>
						</select>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	
	<tr height="50%" valign='bottom'>
		<td valign='bottom' colspan='3'>
			<iframe src="submenu.php?selected=<?echo $_SESSION['int_module_selected'];?>" width="100%" height="18px" frameborder="0" id="submenu" name="submenu" allowtransparency="true" background-color="transparent" marginheight="0" marginwidth="0" scrolling="no"></iframe>
		</td>
	</tr>
</table>
</form>

<style>
	#tt1 {
		font-size:14px;
	}
</style>

<script language="JavaScript">
	function forceResize(isOpen) {
		if (top.frames["help"].innerWidth) {
			frameWidth = top.frames["help"].innerWidth;
			frameHeight = top.frames["help"].innerHeight;
		}
		else if (top.frames["help"].document.documentElement && top.frames["help"].document.documentElement.clientWidth) {
			frameWidth = top.frames["help"].document.documentElement.clientWidth;
			frameHeight = top.frames["help"].document.documentElement.clientHeight;
		}
		else if (top.frames["help"].document.body) {
			frameWidth = top.frames["help"].document.body.clientWidth;
			frameHeight = top.frames["help"].document.body.clientHeight;
		}
	
	
		if (isOpen==1) {
			top.document.body.cols="*,250";
		}
		else {
			if (frameWidth > 1) {
				top.document.body.cols="*,1";
			}
			else
				top.document.body.cols="*,250";
		}
		top.frames["help"].location.href='help/index.php';
	}
</script>
</div>


<script src="include/js/jquery-3.2.1.min.js"></script>
<script src="include/bootstrap-3.3.4-dist/js/bootstrap.min.js"></script>

<script>

    $( document ).ready(function() {


    	
    });

</script>


</body>
</html>