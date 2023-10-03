<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("get_bill_number.php");
	require_once("Config.php");
	
	$config = new Config();
	$arrConfig =& $config->parseConfig($str_root."include/config.ini", "IniFile");
	
	$templateSection = $arrConfig->getItem("section", 'billing');
	/*
		if the "billing" section does not exist in the config.ini file
		set to default values
	*/
	if ($templateSection === false) {
		$int_fs_account_discount = 0;
	}
	else {
		/*
			if the section exists, but the directive does not,
			create it
		*/
		$account_discount_directive =& $templateSection->getItem("directive", "fs_account_discount");
		if ($account_discount_directive === false) {
			$templateSection->createDirective("fs_account_discount", '0');
			$account_discount_directive =& $templateSection->getItem("directive", "fs_account_discount");
			$config->writeConfig($str_root."include/config.ini", "IniFile");
		}
		$int_fs_account_discount = $account_discount_directive->getContent();
	}
	
	if (IsSet($_GET["action"])) {
		if ($_GET["action"] == 'cancel') {
			unset($_SESSION["arr_item_batches"]);	
			unset($_SESSION["arr_total_qty"]);
			if (IsSet($_GET["type"]))
				$_SESSION['current_bill_type'] = $_GET["type"];
			else
				$_SESSION['current_bill_type'] = 1;
//			$_SESSION['current_bill_day'] = date('j');
			$_SESSION['current_account_number'] = "";
			$_SESSION['bill_total'] = 0;
			$_SESSION['sales_promotion'] = 0;

			echo "<script language=\"javascript\">;";
			echo "parent.frames[\"frame_list\"].document.location = \"billing_list.php\";";
			echo "</script>";
		}
	}
	
	$int_access_level = (getModuleAccessLevel('Billing'));
	if ($_SESSION["int_user_type"] > 1) {
		$int_access_level = ACCESS_ADMIN;
	}

	if (IsSet($_SESSION['current_bill_day'])) {
		$int_current_bill_day = $_SESSION['current_bill_day'];
	}
	else
		$int_current_bill_day = date('j');

	// get which types that can be billed
	$qry = new Query("
		SELECT can_bill_cash, can_bill_creditcard, can_bill_fs_account, can_bill_pt_account, can_bill_aurocard
		FROM stock_storeroom
		WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")
	");
	$bool_cash = false;
	$bool_fs = false;
	$bool_pt = false;
	$bool_credit = false;
	$bool_aurocard = false;
	if ($qry->FieldByName('can_bill_cash') == 'Y')
		$bool_cash = true;
	if ($qry->FieldByName('can_bill_creditcard') == 'Y')
		$bool_credit = true;
	if ($qry->FieldByName('can_bill_fs_account') == 'Y')
		$bool_fs = true;
	if ($qry->FieldByName('can_bill_pt_account') == 'Y')
		$bool_pt = true;
	if ($qry->FieldByName('can_bill_aurocard') == 'Y')
		$bool_aurocard = true;

	// get the next bill number
        $int_bill_number = get_bill_number_no_update($_SESSION['current_bill_type']);

	/*
		get the salespersons
	*/
	$str = "SELECT * FROM salespersons ORDER BY first";
	$salespersons = new Query($str);
?>
<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../yui2.7.0/build/fonts/fonts-min.css" />
		<script type="text/javascript" src="../yui2.7.0/build/yahoo/yahoo-min.js"></script>
		<script type="text/javascript" src="../yui2.7.0/build/event/event-min.js"></script>
		<script type="text/javascript" src="../yui2.7.0/build/connection/connection-min.js"></script>

		
		<script language="javascript">
			
			var handleSuccessAccountType = function(o) {
				oSpanBillNumber = document.getElementById('bill_number');
				str_retval = o.responseText;
				oSpanBillNumber.innerHTML = 'Bill: ' + str_retval;
			}
			var handleFailureAccountType = function(o){
				alert('An error occurred getting the bill number');
			}
			var callbackAccountType = {
				success:handleSuccessAccountType,
				failure:handleFailureAccountType
			};
			
			function setAccountType() {
				var oTextBoxType = document.billing_type.bill_type;
				var oTextBoxDay = document.billing_type.bill_day;
				var oDivDiscount = document.getElementById('fs_default_discount');
				
				/*
					cash
				*/
				if (oTextBoxType.value == 1) {
					setTimeout("parent.frames['frame_enter'].document.billing_enter.code.focus();", 500);
					
					parent.document.body.rows="45,0,0,0,80,*,75,40";
					oListBox = parent.frames["frame_list"].document.billing_list.item_list;
					oListBox.size = 20;
					
					oDivDiscount.style.visibility = "hidden";
				}
				/*
					account OR PT account OR transfer
				*/
				else if ((oTextBoxType.value == 2) || (oTextBoxType.value == 3) || (oTextBoxType.value == 6))  { 
					setTimeout("parent.frames['frame_account'].document.billing_account.account_number.focus();", 500);
					
					parent.document.body.rows="45,80,0,0,80,*,75,40";
					oListBox = parent.frames["frame_list"].document.billing_list.item_list;
					oListBox.size = 15;
					
					if (oTextBoxType.value == 2)
						oDivDiscount.style.visibility = "visible";
					else
						oDivDiscount.style.visibility = "hidden";
				}
				/*
					Credit Card
				*/
				else if (oTextBoxType.value == 4)  {
					setTimeout("parent.frames['frame_creditcard'].document.billing_creditcard.card_name.focus();", 500);

					parent.document.body.rows="45,0,80,0,80,*,75,40";
					oListBox = parent.frames["frame_list"].document.billing_list.item_list;
					oListBox.size = 15;
					
					oDivDiscount.style.visibility = "hidden";
				}
				/*
					Aurocard
				*/
				else if (oTextBoxType.value == 7)  {
					setTimeout("parent.frames['frame_aurocard'].document.billing_aurocard.aurocard_number.focus();", 500);
					
					parent.document.body.rows="45,0,0,80,80,*,75,40";
					oListBox = parent.frames["frame_list"].document.billing_list.item_list;
					oListBox.size = 15;
				}
				
				parent.frames["frame_account"].document.location = "billing_account.php?action=set_type&bill_type=" + oTextBoxType.value+"&bill_day="+oTextBoxDay.value;
				parent.frames["frame_action"].document.location = "billing_action.php?action=set_type&bill_type=" + oTextBoxType.value+"&bill_day="+oTextBoxDay.value;
				parent.frames["frame_list"].document.location = "billing_list.php?action=set_type&bill_type=" + oTextBoxType.value+"&bill_day="+oTextBoxDay.value;
				parent.frames["frame_enter"].document.location = "billing_enter.php";
				
				YAHOO.util.Connect.asyncRequest('GET', 'get_bill_number.php?live=2&bill_type='+oTextBoxType.value, callbackAccountType);
			}

			function setBillDay() {
				var oTextBoxType = document.billing_type.bill_type;
				var oTextBoxDay = document.billing_type.bill_day;
				var oTextAccountName = parent.frames['frame_account'].document.getElementById('account_name');

				parent.frames["frame_account"].document.location = "billing_account.php?action=set_type&bill_type=" + oTextBoxType.value+"&bill_day="+oTextBoxDay.value+"&account_name="+oTextAccountName.innerHTML;
				parent.frames["frame_action"].document.location = "billing_action.php?action=set_type&bill_type=" + oTextBoxType.value+"&bill_day="+oTextBoxDay.value;
				parent.frames["frame_list"].document.location = "billing_list.php?action=set_type&bill_type=" + oTextBoxType.value+"&bill_day="+oTextBoxDay.value;
			}
			
			function setSalesperson() {
				oSalesperson = document.getElementById('salesperson');
				parent.frames["frame_action"].document.location = "billing_action.php?action=set_salesperson&salesperson="+oSalesperson.value;
			}

			var handleSuccessConnect = function(o) {
				var oSpanStatus = document.getElementById('connect_status');
				var oButtonStatus = document.billing_type.connect_method;
				
				str_retval = o.responseText;
				
				if (str_retval == 2) { 		// CONNECT_OFFLINE_LIMITED_ACCESS
					oSpanStatus.innerHTML = '<b>[ offline ]&nbsp;</b>';
					oButtonStatus.value = 'go online';
				}
				else if (str_retval == 3) {	// CONNECT_ONLINE
					oSpanStatus.innerHTML = '<b>[ online ]&nbsp;</b>';
					oButtonStatus.value = 'go offline';
				}
			}
			var handleFailureConnect = function(o){
				alert('An error occurred setting the connection');
			}
			var callbackConnect = {
				success:handleSuccessConnect,
				failure:handleFailureConnect
			};
			
			function set_connect_method() {
				var oButtonStatus = document.billing_type.connect_method;

				if (oButtonStatus.value == 'go online')
					connect_method = 3;
				else
					connect_method = 2;
					
				YAHOO.util.Connect.asyncRequest('GET', "set_connect_method.php?live=1&connect_method="+connect_method, callbackConnect);
			}
			
			var handleSuccess = function(o) {
				//var oTextBoxType = document.billing_type.bill_type;
				//var oTextBoxDay = document.billing_type.bill_day;
				//var oDivDiscount = document.getElementById('fs_default_discount');
				
				parent.frames["frame_enter"].document.location = "billing_enter.php";
				
			}
			var handleFailure = function(o){
				alert('An error occurred updating the discount');
			}
			var callback = {
				success:handleSuccess,
				failure:handleFailure
			};
			
			function setDefaultDiscount(evt) {
				evt = (evt) ? evt : event;
				var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
				
				var oDivDiscount = document.getElementById('fs_default_discount');
				var oValue = document.getElementById('fs_default_discount_value');
				
				if (charCode == 13) {
					YAHOO.util.Connect.asyncRequest('GET', 'setFSDiscount.php?discount='+oValue.value, callback);
				}
				else
					return true;
			}
			
			
			function stopRKey(evt) { 
				var evt = (evt) ? evt : ((event) ? event : null); 
				var node = (evt.target) ? evt.target : ((evt.srcElement) ? evt.srcElement : null); 
				if ((evt.keyCode == 13) && (node.type=="text"))  {return false;} 
			}



			
		</script>
		
		<link rel="stylesheet" type="text/css" href="../include/<?echo $str_css_filename;?>" />
		
	</head>
	
<body class="yui-skin-sam" OnKeyPress="return stopRKey(event)">

<form name="billing_type" method="GET">

  <table width="100%" height="100%" border="0" cellpadding="0" cellspacing="0">
	  <tr>
		  <td width="175px">
			<font class='headertext'>Type:&nbsp;</font>
			<select name="bill_type" class="<?echo $str_class_select?>" <?if ($_SESSION['bill_id'] > -1) echo "disabled";?> onchange="javascript:setAccountType()">
				<? if ($bool_cash == true) { ?>
					<option value="1" <? if ($_SESSION['current_bill_type'] == BILL_CASH) echo "selected=\"selected\""?>>Cash</option>
				<? } ?>
				<? if ($bool_credit == true) { ?>
					<option value="4" <? if ($_SESSION['current_bill_type'] == BILL_CREDIT_CARD) echo "selected=\"selected\""?>>Credit Card</option>
				<? } ?>
				<? if ($bool_fs == true) { ?>
					<option value="2" <? if ($_SESSION['current_bill_type'] == BILL_ACCOUNT) echo "selected=\"selected\""?>>Account</option>
				<? } ?>
				<? if ($bool_pt == true) { ?>
					<option value="3" <? if ($_SESSION['current_bill_type'] == BILL_PT_ACCOUNT) echo "selected=\"selected\""?>>Pour Tous</option>
				<? } ?>
				<? if ($bool_aurocard == true) { ?>
					<option value="7" <? if ($_SESSION['current_bill_type'] == BILL_AUROCARD) echo "selected=\"selected\""?>>Aurocard</option>
				<? } ?>
				<? if (CAN_BILL_TRANSFER_GOOD === 1) { ?>
					<option value="6" <? if ($_SESSION['current_bill_type'] == BILL_TRANSFER_GOOD) echo "selected=\"selected\""?>>Transfer of Goods</option>
				<? } ?>
			</select>
			</td>
			
			<td width="200px">
				<div id="fs_default_discount" style="visibility:<? if ($_SESSION['current_bill_type'] == BILL_ACCOUNT) echo "visible"; else echo "hidden"; ?>">
					<font class='<?echo $str_class_header?>'>default discount</font>
					<input type="text" id="fs_default_discount_value" name="fs_default_discount" value="<?echo $int_fs_account_discount?>" onkeypress="setDefaultDiscount(event)" style="width:50px">
				</div>
			</td>
			
			<td>
				<font class='<?echo $str_class_header?>'>Salesperson</font>
				<select name="salesperson" id="salesperson" onchange="javascript:setSalesperson()" class="<?echo $str_class_select?>" >
					<?php
						for ($i=0;$i<$salespersons->RowCount();$i++) {
							if ($salespersons->FieldByName('id') == $_SESSION['bill_salesperson'])
								echo "<option value='".$salespersons->FieldByName('id')."' selected>".$salespersons->FieldByName('first')."</option>\n";
							else
								echo "<option value='".$salespersons->FieldByName('id')."'>".$salespersons->FieldByName('first')."</option>\n";
							$salespersons->Next();
						}
					?>
				</select>
			</td>

			<? if ($_SESSION['bill_id'] > -1) { ?>
			<td>
				<font class='<?echo $str_class_header?>'>Bill:&nbsp;<b><?echo $_SESSION['bill_number']?></b></font>
			</td>
			<? } else { ?>
			<td align='left'>
				<span id='bill_number' class='<?echo $str_class_header?>'>Bill:&nbsp <?echo $int_bill_number;?></span>
			</td>
			<? } ?>
			
			<td>
				<font class="<?echo $str_class_header?>">
				<?
					if ($bool_pt == false) {
						if ($_SESSION['connect_method'] == CONNECT_ONLINE) { ?>
							<span id='connect_status'><b>[ online ]</b>&nbsp;</span><input type='button' name='connect_method' value='go offline' onclick='set_connect_method()'>
						<? } else { ?>
							<span id='connect_status'><b>[ offline ]</b>&nbsp;</span><input type='button' name='connect_method' value='go online' onclick='set_connect_method()'>
				<? 		}
					} 
				?>
				</font>
			</td>

			<td align="right" class="headertext">
			
				<select name="bill_day" width='50px' class="<?echo $str_class_select?>" <?if ($_SESSION['bill_id'] > -1) echo "disabled";?> onchange="javascript:setBillDay()" <? if ($_SESSION['str_user_can_change_bill_date'] == 'N') { echo "disabled";} ?> >
				<?
					$int_num_days = DaysInMonth2($_SESSION["int_month_loaded"], $_SESSION["int_year_loaded"]);
					for ($i=1; $i<=date('j'); $i++) {
					if ($i == $int_current_bill_day)
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

	<script language='javascript'>
		var oTextBoxType = document.billing_type.bill_type;
		if (oTextBoxType.value == 1)
			setTimeout("parent.frames['frame_enter'].document.billing_enter.code.focus();", 500);
		else if ((oTextBoxType.value == 2) || (oTextBoxType.value == 3))
			parent.frames['frame_account'].document.billing_account.account_number.focus();
		else if (oTextBoxType.value == 4)
			parent.frames['frame_creditcard'].document.billing_creditcard.card_name.focus();
	</script>



</form>
</body>

		<link type="text/css" rel="stylesheet" href="../include/autocomplete/jquery.autocomplete.css"/>
		<script src="../include/js/jquery-3.2.1.min.js"></script>
		<script src="../include/autocomplete/jquery.autocomplete.js"></script>

</html>
