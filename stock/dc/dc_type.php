<?php
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");
	require_once("get_dc_number.php");
	require_once("Config.php");
	
	$config = new Config();
	$arrConfig =& $config->parseConfig($str_root."include/config.ini", "IniFile");
	
	if (IsSet($_GET["action"])) {
		if ($_GET["action"] == 'cancel') {
			unset($_SESSION["arr_item_batches"]);	
			unset($_SESSION["arr_total_qty"]);
			$_SESSION['dc_total'] = 0;
			$_SESSION['dc_client_id'] = 0;

			echo "<script language=\"javascript\">;";
			echo "parent.frames[\"frame_list\"].document.location = \"dc_list.php\";";
			echo "</script>";
		}
	}
	
	$int_access_level = (getModuleAccessLevel('Stock'));
	if ($_SESSION["int_user_type"] > 1) {
		$int_access_level = ACCESS_ADMIN;
	}

	if (IsSet($_SESSION['current_dc_day'])) {
		$current_dc_day = $_SESSION['current_dc_day'];
	}
	else
		$current_dc_day = date('j');

	/*
		get the next dc number
	*/
	$dc_number = get_dc_number_no_update($_SESSION['current_bill_type']);

	/*
		get clients
	*/
	$str_query = "
		SELECT id, company
		FROM customer
		ORDER BY company
	";
	$qry_clients = new Query($str_query);
?>
<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../../yui2.7.0/build/fonts/fonts-min.css" />
		<script type="text/javascript" src="../../yui2.7.0/build/yahoo/yahoo-min.js"></script>
		<script type="text/javascript" src="../../yui2.7.0/build/event/event-min.js"></script>
		<script type="text/javascript" src="../../yui2.7.0/build/connection/connection-min.js"></script>
		
		<script language="javascript">
			var handleSuccess = function(o) {
				//alert(o.responseText);
			}
			var handleFailure = function(o){
				alert('An error occurred saving settings');
			}
			var callback = {
				success:handleSuccess,
				failure:handleFailure
			};
			function setSessionVars() {
				var oClient = document.getElementById('select_client');
				var oDay = document.getElementById('dc_day');
				
				YAHOO.util.Connect.asyncRequest('GET', 'dc_sessions.php?live=1&client_id='+oClient.value+"&dc_day="+oDay.value, callback);
			}
			
			function stopRKey(evt) {
				var evt = (evt) ? evt : ((event) ? event : null); 
				var node = (evt.target) ? evt.target : ((evt.srcElement) ? evt.srcElement : null); 
				if ((evt.keyCode == 13) && (node.type=="text"))  {return false;} 
			}
			
		</script>
		
		<link rel="stylesheet" type="text/css" href="../../include/<?echo $str_css_filename;?>" />
		
	</head>
	
<body class="yui-skin-sam" OnKeyPress="return stopRKey(event)">

<form name="billing_type" method="GET">

	<table width="100%" height="100%" border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td width="450px">
				<font class='headertext'>Client:&nbsp;</font>
				<select name="select_client" id="select_client" onchange="javascript:setSessionVars();" class="<?echo $str_class_select?>" <?if ($_SESSION['dc_id'] > -1) echo "disabled";?> >
					<option value="0">--select--</option>
					<?php
						for ($i=0;$i<$qry_clients->RowCount();$i++) {
							if ($_SESSION['dc_client_id'] == $qry_clients->FieldByName('id'))
								echo "<option value=\"".$qry_clients->FieldByName('id')."\" \"selected\">".$qry_clients->FieldByName('company')."</option>\n";
							else
								echo "<option value=\"".$qry_clients->FieldByName('id')."\">".$qry_clients->FieldByName('company')."</option>\n";
							$qry_clients->Next();
						}
					?>
				</select>
			</td>
		
			<? if ($_SESSION['dc_id'] > -1) { ?>
				<td>
					<font class="<?echo $str_class_header?>">DC:&nbsp;<b><?echo $_SESSION['dc_number']?></b></font>
				</td>
			<? } else { ?>
				<td align="left">
					<span id="bill_number" class="<?echo $str_class_header?>">DC:&nbsp <?echo $dc_number;?></span>
				</td>
			<? } ?>
		
			<td align="right" class="headertext">
			
				<select name="dc_day" id="dc_day" width='50px' class="<?echo $str_class_select?>" <?if ($_SESSION['dc_id'] > -1) echo "disabled";?> onchange="javascript:setSessionVars()" <? if ($_SESSION['str_user_can_change_bill_date'] == 'N') { echo "disabled";} ?> >
				<?
					$int_num_days = DaysInMonth2($_SESSION["int_month_loaded"], $_SESSION["int_year_loaded"]);
					for ($i=1; $i<=date('j'); $i++) {
						if ($i == $current_dc_day)
							echo "<option value=".$i." selected=\"selected\">".$i;
						else
							echo "<option value=".$i.">".$i;
					}
				?>
				</select>

				<font style="font-size:16px;font-weight:bold;">
				<? echo "&nbsp;".getMonthName($_SESSION["int_month_loaded"])."&nbsp;&nbsp;".$_SESSION["int_year_loaded"]."&nbsp;"; ?>
				</font>
			</td>
		</tr>
	</table>

</form>
</body>
</html>
