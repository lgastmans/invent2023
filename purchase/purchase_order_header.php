<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../include/purchase_funcs.inc.php");

	if ($_SESSION['purchase_order_id'] > -1) {
		$str_purchase_order_ref = $_SESSION['purchase_order_ref'];
		$int_assigned_to = $_SESSION['purchase_assigned_to'];
		$date_created = $_SESSION['purchase_date_expected'];
		$int_supplier_id = $_SESSION['purchase_supplier_id'];
		$invoice_number = $_SESSION['invoice_number'];
		$invoice_date = $_SESSION['invoice_date'];
	}
	else {
		$str_purchase_order_ref = get_next_purchase_order_ref();
		$int_assigned_to = $_SESSION['int_user_id'];
		$date_created = date('d-m-Y', time());
		$int_supplier_id = $_SESSION['purchase_supplier_id'];
		$invoice_number = '';
		$invoice_date = date('d-m-Y', time());
	}
	
	$qry_users = new Query("SELECT * FROM user ORDER BY username");
	$qry_suppliers = new Query("SELECT * FROM stock_supplier WHERE is_active = 'Y' ORDER BY supplier_name");
?>

<script language='javascript'>

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

	var requester_sessions = createRequest();

	function stateHandler_sessions() {
		if (requester_sessions.readyState == 4) {
			if (requester_sessions.status == 200)  {
				strRetVal = requester_sessions.responseText;
			}
			else {
				alert("failed to load page... please click the button again.");
			}
			requester_sessions = null;
			requester_sessions = createRequest();
		}
	}

	function set_session_variables() {
		var oTextReference = document.purchase_order_header.reference;
		var oSelectAssignedTo = document.purchase_order_header.select_assigned_to;
		var oTextDate = document.purchase_order_header.purchase_order_date;
		var oSelectSupplier = document.purchase_order_header.select_supplier;
//		var oCBSingleSupplier = document.purchase_order_header.cb_single_supplier;
		var oInvNo = document.purchase_order_header.po_inv_no;
		var oInvDt = document.purchase_order_header.po_inv_dt;
		
//		if (oCBSingleSupplier.checked)
			str_single_supplier = 'Y';
//		else
//			str_single_supplier = 'N';
		
		str_pass = "purchase_order_sessions.php?live=1"+
			"&order_reference="+oTextReference.value +
			"&order_assigned_to="+oSelectAssignedTo.value +
			"&order_date_expected="+oTextDate.value+
			"&order_supplier="+oSelectSupplier.value+
			"&order_single_supplier="+str_single_supplier+
			"&order_invoice_number="+oInvNo.value+
			"&order_invoice_date="+oInvDt.value;
			
		requester_sessions.onreadystatechange = stateHandler_sessions;
		requester_sessions.open("GET", str_pass);
		requester_sessions.send(null);
	}

	function set_supplier() {
//		var oCBSingleSupplier = document.purchase_order_header.cb_single_supplier;

//		if (oCBSingleSupplier.checked) {
			if (confirm("Clear list and change supplier ?")) {
				if (!set_session_variables())
 					top.document.location = "purchase_order_frameset.php?action=clear";			
			}
//		}
	}

	function setSupplierSelect() {

		var oSelectSupplier = document.purchase_order_header.select_supplier;
		var oCBSingleSupplier = document.purchase_order_header.cb_single_supplier;
		
		if (confirm("This action will clear the list. Continue ?")) {

			if (oCBSingleSupplier.checked) {
				oSelectSupplier.disabled = false;
			}
			else {
				oSelectSupplier.disabled = true;
			}

			if (!set_session_variables()) {
				console.log('done');
				top.document.location = "purchase_order_frameset.php?action=clear";			
			}

		}
	}

</script>

<html>
<head>
	<script language="javascript" src="../include/calendar1.js"></script>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
</head>
	
<body id='body_bgcolor' marginwidth="10" marginheight="10">

<form name="purchase_order_header" method="GET">

<table border='0' cellpadding='0' cellspacing='0' class="edit">
	<tr>
		<td align='center'>

			<table width="100%" height="100%" border="0" cellpadding="0" cellspacing="4">
				<tr>
					<td class='normaltext' align='right'>Ref :&nbsp;</td>
					<td><input type='text' name='reference' value='<? echo $str_purchase_order_ref; ?>' class='input_100' onblur='javascript:set_session_variables()'></td>
					
					<td class='normaltext' align='right'>Assigned to :&nbsp;</td>
					<td>
						<select name='select_assigned_to' class="select_100" onblur='javascript:set_session_variables()'>
						<?
							for ($i=0;$i<$qry_users->RowCount();$i++) {
								if ($int_assigned_to == $qry_users->FieldByName('user_id')) 
									echo "<option value=".$qry_users->FieldByName('user_id')." selected>".$qry_users->FieldByName('username');
								else
									echo "<option value=".$qry_users->FieldByName('user_id').">".$qry_users->FieldByName('username');
								$qry_users->Next();
							}
						?>
						</select>
					</td>
					
					<td class='normaltext' align='right'>Date Expected :&nbsp;</td>
					<td>
						<input type='text' name='purchase_order_date' value='<? echo $date_created; ?>' class='input_100' onblur='javascript:set_session_variables()'>
						<a href="javascript:cal1.popup();"><img src="../images/calendar/cal.gif" width="16" height="16" border="0" alt="Click here to select a date"></a>
					</td>
				</tr>
<!--
				<tr>
					<td colspan='5' class='normaltext'>
						<input type='checkbox' name='cb_single_supplier' onchange='javascript:setSupplierSelect()' <?if ($_SESSION['purchase_single_supplier'] == 'Y') echo 'checked'?>>
						Assign purchase order to the following supplier:
					</td>
					<td class='normaltext' align='right'>&nbsp;</td>
				</tr>
-->
				<tr>
					
					<td colspan='2'>
						<select name='select_supplier' class="select_300" onchange='javascript:set_supplier()' onblur='javascript:set_session_variables()' <?if ($_SESSION['purchase_single_supplier'] == 'N') echo 'disabled'?>>
						<?
							for ($i=0;$i<$qry_suppliers->RowCount();$i++) {
								if ($int_supplier_id == $qry_suppliers->FieldByName('supplier_id'))
									echo "<option value=".$qry_suppliers->FieldByName('supplier_id')." selected>".$qry_suppliers->FieldByName('supplier_name');
								else
									echo "<option value=".$qry_suppliers->FieldByName('supplier_id').">".$qry_suppliers->FieldByName('supplier_name');
								$qry_suppliers->Next();
							}
						?>
						</select>
					</td>
					<td class='normaltext' align='right'>Invoice No.:</td>
					<td class='normaltext' align='left'>
						<input type="text" name="po_inv_no" value="<? echo $invoice_number; ?>" class='input_100' onblur='javascript:set_session_variables()' />
					</td>
					<td class='normaltext' align='right'>Invoice Date:</td>
					<td class='normaltext' align='left'>
						<input type="text" name="po_inv_dt" value="<?php echo $invoice_date; ?>" class='input_100' onblur='javascript:set_session_variables()' />
						<a href="javascript:cal2.popup();"><img src="../images/calendar/cal.gif" width="16" height="16" border="0" alt="Click here to select a date"></a>
					</td>
				</tr>
			</table>

		</td>
	</tr>
</table>

</form>

<script language="javascript">
	var oTextDate = document.purchase_order_header.purchase_order_date;
	var oInvDt = document.purchase_order_header.po_inv_dt;

	var cal1 = new calendar1(oTextDate);
	cal1.year_scroll = true;
	cal1.time_comp = false;

	var cal2 = new calendar1(oInvDt);
	cal2.year_scroll = true;
	cal2.time_comp = false;

	set_session_variables();
</script>

</body>
</html>