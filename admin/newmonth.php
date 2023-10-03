<?
$str_cur_module='Admin';
require_once("../include/const.inc.php");
require_once("../include/session.inc.php");

$int_access_level = (getModuleAccessLevel('Admin'));

if ($_SESSION["int_user_type"]>1) {
	$int_access_level = ACCESS_ADMIN;
}

$int_admin_selected=6;

$_SESSION['newmonth_reset_adjusted'] = 'N';

$module_stock = getModule('Stock');

$int_year = date('Y');
$int_month = date('n');

?>
<html>
<head>
	<link href="../include/styles.css" rel="stylesheet" type="text/css">
	<style type="text/css"> 
	body {
		margin:20;
		padding:20;
	}
	</style>
	<link rel="stylesheet" type="text/css" href="../yui2.7.0/build/fonts/fonts-min.css" /> 
	<link rel="stylesheet" type="text/css" href="../yui2.7.0/build/container/assets/skins/sam/container.css" />
	<script type="text/javascript" src="../yui2.7.0/build/yahoo-dom-event/yahoo-dom-event.js"></script>
	<script type="text/javascript" src="../yui2.7.0/build/connection/connection-min.js"></script>
	<script type="text/javascript" src="../yui2.7.0/build/animation/animation-min.js"></script>
	<script type="text/javascript" src="../yui2.7.0/build/dragdrop/dragdrop-min.js"></script>
	<script type="text/javascript" src="../yui2.7.0/build/container/container-min.js"></script>
</head>

<body class="yui-skin-sam">
		
	<div id="frame_content_loader">
	
		<font class='title'>Create New Month</font>
		<br><br>

		<? boundingBoxStart("600", '../images/blank.gif', '250'); ?>

		<font class='normaltext'>
		<br>
		Use this tool to open the databases for the new month.  This will carry over all balances
		from existing batches as well as tax information and other applicable data.  <br>
		You will be able to view the previous month's data at any time but you will <b>not</b> be able to
		make changes in a previous month after you open the new month, so please <b>make sure</b> that you
		have completed all transactions pertaining that month before completing it.<br>
		<b>
			<label>
				<input type='checkbox' id="reset_adjusted" name='reset_adjusted' <?if ($_SESSION['newmonth_reset_adjusted']=='Y') echo "checked";?>>Reset all 'adjusted' stock to zero.</b>
			</label>

		<br><br>

		<?
		   if (!($module_stock->monthExists($int_month, $int_year))) {  ?>

			<form method=post>
				You will now be closing <? echo getMonthName($_SESSION['int_month_loaded'])." ".$_SESSION['int_year_loaded']; ?>
				<br><br>
				<input type='hidden' name='month' value='<? echo $int_month; ?>'>
				<input type='hidden' name='year' value='<? echo $int_year; ?>'>
				<input type='button' class='mainmenu_button' id="panelbutton" name='openmonth' value='Close Current Month And Open New Month'>
			</form>
		<? } else { ?>
			<font color='red'>The month information for <? echo getMonthName($int_month)." ".$int_year; ?> already exists.</font>
		<? } ?>

		<? boundingBoxEnd("600", '../images/blank.gif', '250'); ?>

	</div>


<script type="text/javascript"> 
 
	YAHOO.namespace("example.container");

	function init() {
		var content = top.frames['title'].document.getElementById("frame_title_loader");
		var content2 = top.frames['content'].document.getElementById("frame_content_loader");
		
		var oReset = document.getElementById("reset_adjusted");
		var reset_adjusted = 'N';
		if (oReset.checked)
			reset_adjusted = 'Y';
		
		var str = content.innerHTML;
		content.innerHTML = '';
		
		if (!YAHOO.example.container.wait) {
			// Initialize the temporary Panel to display while waiting for external content to load
			YAHOO.example.container.wait =
					new YAHOO.widget.Panel("wait",
						{
							width: "350px",
							fixedcenter: true,
							close: false,
							draggable: false,
							zindex:4,
							modal: true,
							visible: false
						}
					);
			YAHOO.example.container.wait.setHeader("Please wait - processing data...");
			YAHOO.example.container.wait.setBody("<img src=\"../images/rel_interstitial_loading.gif\"/>");
			YAHOO.example.container.wait.render(document.body);
		}

		/**
			Define the callback object for Connection Manager that will set
			the body of our content area when the content has loaded
		*/
		var callback = {
			success : function(o) {
				content.innerHTML = str;
				content2.innerHTML = o.responseText; //"Successfully created new month";
				content.style.visibility = "visible";
				YAHOO.example.container.wait.hide();
			},
			failure : function(o) {
				content.innerHTML = str;
				content.style.visibility = "visible";
				content2.innerHTML = "CONNECTION FAILED!";
				YAHOO.example.container.wait.hide();
			}
		}
		
		YAHOO.example.container.wait.show();
		
		// Connect to our data source and load the data
		var conn = YAHOO.util.Connect.asyncRequest("GET", "newmonth_func.php?openmonth=Y&reset_adjusted="+reset_adjusted, callback);
	}

	YAHOO.util.Event.on("panelbutton", "click", init);

</script> 


</body>
</html>