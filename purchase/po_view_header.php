<?
/**
*
* @version 	$Id: po_view_header.php,v 1.2 2006/02/15 06:56:17 cvs Exp $
* @copyright 	Cynergy Software 2005
* @author	Luk Gastmans
* @date		23 Nov 2005
* @module 	Purchase Order View - Header frame
* @name  	po_view_header.php
*
* The header frame is part of the purchase_view_frameset.
* It is the frame that contains the fields for entering the purchase order's supplier,
* assigned to and expected date of delivery
*
*/

	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");

	if ($_SESSION['str_user_font_size'] == 'small') {
		$str_class_input = "inputbox60_small";
		$str_class_header = "headertext_small";
		$str_class_list_box = "listbox_small";
		$str_class_list_header = "listheader_small";
		$str_class_total = "bill_total_small";
        	$str_class_select = "select100_small";
	}
	else if ($_SESSION['str_user_font_size'] == 'standard') {
		$str_class_input = "inputbox60";
		$str_class_header = "headertext";
		$str_class_list_box = "listbox";
		$str_class_list_header = "listheader";
		$str_class_total = "bill_total";
        	$str_class_select = "select100";
	}
	else if ($_SESSION['str_user_font_size'] == 'large') {
		$str_class_input = "inputbox60_large";
		$str_class_header = "headertext_large";
		$str_class_list_box = "listbox_large";
		$str_class_list_header = "listheader_large";
		$str_class_total = "bill_total_large";
        	$str_class_select = "select100_large";
	}
	else {
		$str_class_input = "inputbox60";
		$str_class_header = "headertext";
		$str_class_list_box = "listbox";
		$str_class_list_header = "listheader";
		$str_class_total = "bill_total";
        	$str_class_select = "select100";
	}

	if ($_SESSION['str_user_color_scheme'] == 'standard')
		$str_css_filename = 'bill_styles.css';
	else if ($_SESSION['str_user_color_scheme'] == 'blue')
		$str_css_filename = 'bill_styles_blue.css';
	else if ($_SESSION['str_user_color_scheme'] == 'purple')
		$str_css_filename = 'bill_styles_purple.css';
	else if ($_SESSION['str_user_color_scheme'] == 'green')
		$str_css_filename = 'bill_styles_green.css';
	else
		$str_css_filename = 'bill_styles.css';
?>

<html>
<head><TITLE></TITLE>
<link href="../include/<?echo $str_css_filename;?>" rel="stylesheet" type="text/css">
</head>

<body bgcolor="#D0D0D0">

<?

	if (!IsSet($_GET["id"]))
		$int_po_id = 1;
	else
		$int_po_id = $_GET["id"];

	// get the purchase order number
	$qry_po = "SELECT *
		FROM ".Yearalize('purchase_order')."
		WHERE (purchase_order_id=".$int_po_id.")
			AND (storeroom_id=".$_SESSION["int_current_storeroom"].")";
	$result_po = new Query($qry_po);

	// get "supplier id"  and "assigned to" for the current purchase order
	// and save in the session variables for the "action" and "enter" frame
	$qry_current_ids = "SELECT po.supplier_id, po.assigned_to_user_id FROM ".Yearalize('purchase_order')." po WHERE (po.purchase_order_id = ".$_GET["id"].")";
	$result_current_ids = new Query($qry_current_ids);
	$_SESSION["int_current_supplier_id"] = $result_current_ids->FieldByName('supplier_id');
	$_SESSION["int_current_assigned_id"] = $result_current_ids->FieldByName('assigned_to_user_id');

	// list of users for the "assigned to" combo
	$qry_str_users = "SELECT user_id, username FROM user ORDER BY username";
	$qry_result_users = new Query($qry_str_users);

	// list of suppliers for the "supplier" combo
	$qry_str_supplier = "SELECT supplier_id, supplier_name FROM stock_supplier ORDER BY supplier_name";
	$qry_result_supplier = new Query($qry_str_supplier);

	// send the id to the "entry" and "details" frame
	echo "<script language=\"javascript\">";
	echo "parent.frames[\"frame_entry\"].document.location=\"po_view_enter.php?id=".$int_po_id."\"\n";
	echo "parent.frames[\"frame_details\"].document.location=\"po_view_list.php?id=".$int_po_id."\"\n";
	echo "parent.frames[\"frame_action\"].document.location=\"po_view_action.php?id=".$int_po_id."\"\n";
	echo "</script>";
?>

<script language="javascript">

	function isValidDate(sText){
		var reDateFormat = /(?:19|20\d{2})-(?:0[1-9]|1[0-2])-(?:0[1-9]|[12][0-9]|3[01])/;

		var boolReturn=false;

		if (!reDateFormat.test(sText))
			alert("Invalid Date.")
		else {
			var str_year	= sText.split("-")[0];
			var str_month	= sText.split("-")[1];
			var str_day	= sText.split("-")[2];
			var date_test	= new Date(str_year, str_month-1, str_day);

			if ((date_test.getMonth()+1 != str_month) || (date_test.getDate() != str_day)||(date_test.getFullYear() != str_year))
				alert("Invalid Date.")
			else
				boolReturn=true
		}
		if (boolReturn==false)
			input.select();
		return boolReturn;
	}

	function update_data(id) {
		oSelectSupplier = document.po_view_header.supplier;

		oSelectAssigned = document.po_view_header.assigned_to;

		oTxtDay = document.po_view_header.str_day.value;
		oTxtMonth = document.po_view_header.str_month.value;
		oTxtYear = document.po_view_header.str_year.value;
		str_date = oTxtYear+'-'+oTxtMonth+'-'+oTxtDay;
		if (!isValidDate(str_date)) {
			alert('Invalid date entered for field expected');
		}

		parent.frames["frame_action"].document.location="po_view_action.php?id="+id+"&assigned_id="+oSelectAssigned.options[oSelectAssigned.selectedIndex].value+"&supplier_id="+oSelectSupplier.options[oSelectSupplier.selectedIndex].value+"&expected_date="+str_date;
	}

</script>

<form name="po_view_header" method="POST">
	<table width="100%" height="20" border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td width="175" class='<?echo $str_class_header;?>'><?echo "P.O. No.: <b>".$result_po->FieldByName('purchase_order_number')."</b>"?></td>
			<td width="130" align="right" class='<?echo $str_class_header;?>'><?echo "Assigned to :&nbsp";?></td>
			<td width="120">
				<select name="assigned_to" class='<?echo $str_class_select?>' onchange="javascript:update_data(<?echo $int_po_id;?>)">
					<?
						for ($i=0;$i<$qry_result_users->RowCount();$i++) {
							if ($qry_result_users->FieldByName('user_id') == $_SESSION["int_current_assigned_id"])
								echo "<option value=".$qry_result_users->FieldByName('user_id')." selected=\"selected\">".$qry_result_users->FieldByName('username');
							else
								echo "<option value=".$qry_result_users->FieldByName('user_id').">".$qry_result_users->FieldByName('username');
							$qry_result_users->Next();
						}
					?>
				</select>
			</td>
			<td width="95" class='<?echo $str_class_header;?>' align="right"><?echo "Supplier :&nbsp";?></td>
			<td width="120">
				<select name="supplier" class='<?echo $str_class_select?>' onchange="javascript:update_data(<?echo $int_po_id;?>)">
					<?
						for ($i=0;$i<$qry_result_supplier->RowCount();$i++) {
							if ($qry_result_supplier->FieldByName('supplier_id') == $_SESSION["int_current_supplier_id"])
								echo "<option value=".$qry_result_supplier->FieldByName('supplier_id')." selected=\"selected\">".$qry_result_supplier->FieldByName('supplier_name');
							else
								echo "<option value=".$qry_result_supplier->FieldByName('supplier_id').">".$qry_result_supplier->FieldByName('supplier_name');
							$qry_result_supplier->Next();
						}
					?>
				</select>
			</td>
			<td width="105" class='<?echo $str_class_header;?>' align="right"><?echo "Expected :&nbsp";?></td>
			<td>
				<input type="text" name="str_day" value="<?echo date("d")?>" class="<?echo $str_class_input?>" onchange="javascript:update_data(<?echo $int_po_id;?>)">-
				<input type="text" name="str_month" value="<?echo date("m") ?>" class="<?echo $str_class_input?>" onchange="javascript:update_data(<?echo $int_po_id;?>)">-
				<input type="text" name="str_year" value="<?echo date("Y")?>" class="<?echo $str_class_input?>" onchange="javascript:update_data(<?echo $int_po_id;?>)">
			</td>
			<td>&nbsp;</td>
		</tr>
	</table>
</form>
</body>
</<html>