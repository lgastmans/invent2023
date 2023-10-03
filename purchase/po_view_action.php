<?
/**
*
* @version 	$Id: po_view_action.php,v 1.1.1.1 2006/02/14 05:03:59 cvs Exp $
* @copyright 	Cynergy Software 2005
* @author	Luc Gastmans
* @date		23 Nov 2005
* @module 	Purchase Order View - Action frame
* @name  	po_view_action.php
*
* The action frame is part of the purchase_view_frameset.
* It is the frame that contains the "draft" and "sent" buttons
*/

	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");

	if ($_SESSION['str_user_font_size'] == 'small') {
	    $str_class_header = "headertext_small";
	    $str_class_list_box = "listbox_small";
	    $str_class_list_header = "listheader_small";
	    $str_class_total = "bill_total_small";
	}
	else if ($_SESSION['str_user_font_size'] == 'standard') {
	    $str_class_header = "headertext";
	    $str_class_list_box = "listbox";
	    $str_class_list_header = "listheader";
	    $str_class_total = "bill_total";
	}
	else if ($_SESSION['str_user_font_size'] == 'large') {
	    $str_class_header = "headertext_large";
	    $str_class_list_box = "listbox_large";
	    $str_class_list_header = "listheader_large";
	    $str_class_total = "bill_total_large";
	}
	else {
	    $str_class_header = "headertext";
	    $str_class_list_box = "listbox";
	    $str_class_list_header = "listheader";
	    $str_class_total = "bill_total";
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
<link href="../include/<?echo $str_css_filename?>" rel="stylesheet" type="text/css">
</head>

<?
	// int_current_assigned_id and int_current_supplier_id point to the
	// user and supplier selected in the po_view_header.php file

	if (!empty($_GET["supplier_id"]))
		$_SESSION["int_current_supplier_id"] = $_GET["supplier_id"];

	if (!empty($_GET["assigned_id"]))
		$_SESSION["int_current_assigned_id"] = $_GET["assigned_id"];

	if (empty($_GET["expected_date"]))
		$expected_date=date("Y-m-d");
	else
		$expected_date=$_GET["expected_date"];

	if (IsSet($_POST["action"])) {
		$set_status=1;
		if ($_POST["action"] == "sent") {
			// set the status flag to 'Sent'
			$set_status=PURCHASE_SENT;

			// update table stock_storeroom_product_year_month, setting the field stock_ordered
			// for each product entered
			$qry_items="SELECT *
				FROM ".Yearalize('purchase_items')." pi
				WHERE (pi.purchase_order_id = ".$_GET["id"].")";
			$result_items=new Query($qry_items);

			if ($result_items->RowCount() > 0) {
				for ($i=0;$i<$result_items->RowCount();$i++) {

					// check whether the given product exists for the currently selected storeroom
					$qry_ordered = "SELECT *
						FROM ".Monthalize('stock_storeroom_product')."
						WHERE (product_id = ".$result_items->FieldByName('product_id').")
							AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")";
					$result_ordered = new Query($qry_ordered);

					// if it is not found, create an entry for the given product
					if ($result_ordered->RowCount()==0) {
						$result_ordered->ExecuteQuery("INSERT INTO ".Monthalize('stock_storeroom_product')."
								(product_id,
								storeroom_id)
							VALUES(".
								$result_items->FieldByName('product_id').", ".
								$_SESSION["int_current_storeroom"].")");
					}

					// and update the quantity_ordered
					updateStoreroomProduct($_SESSION["int_current_storeroom"],
						$result_items->FieldByName('product_id'),
						0, 0, $result_items->FieldByName('quantity_ordered'));

					$result_items->next();
				}
			}
		}

		// save the common purchase order fields, ie user_id, supplier_id, assigned_to_id
		$qry_po = "UPDATE ".Yearalize("purchase_order")."
			SET supplier_id=".$_SESSION["int_current_supplier_id"].",
				assigned_to_user_id=".$_SESSION["int_current_assigned_id"].",
				purchase_status=".$set_status.",
				date_expected_delivery='".$expected_date."'
			WHERE (purchase_order_id=".$_GET["id"].")";
		$result_po = new Query($qry_po);

		echo "<script language=\"javascript\">\n";
		echo "if (top.window.opener) {\n";
		echo "top.window.opener.document.location=top.window.opener.document.location.href;\n";
		echo "}\n";
		echo "top.window.close();\n";
		echo "</script>\n";
	}
?>

<body>

<form name="po_view_action" method="POST">
	<hr>
	<table width="100%" height="30" border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td width="50"><input type="submit" name="action" value="draft" class="v3button"></td>
			<? // check whether the table stock_storeroom_product exists for the current year and month
				$module_purchase = getModule('Purchase');
				if ($module_purchase->monthExists($_SESSION['int_month_loaded'], $_SESSION['int_year_loaded'])) { ?>
					<td width="50"><input type="submit" name="action" value="sent" class="v3button"></td>
			<? } ?>
			<td>&nbsp;</td>
		</tr>
	</table>
</form>

</body>
</html>