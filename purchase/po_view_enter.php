<?
/**
*
* @version 	$Id: po_view_enter.php,v 1.2 2006/02/15 06:56:17 cvs Exp $
* @copyright 	Cynergy Software 2005
* @author	Luc Gastmans
* @date		23 Nov 2005
* @module 	Purchase Order View - Enter frame
* @name  	po_view_enter.php
*
* The enter frame is part of the purchase_view_frameset.
* It is the frame that contains the fields for entering items,
* ie the code and quantity field
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
	}
	else if ($_SESSION['str_user_font_size'] == 'standard') {
		$str_class_input = "inputbox60";
		$str_class_header = "headertext";
		$str_class_list_box = "listbox";
		$str_class_list_header = "listheader";
		$str_class_total = "bill_total";
	}
	else if ($_SESSION['str_user_font_size'] == 'large') {
		$str_class_input = "inputbox60_large";
		$str_class_header = "headertext_large";
		$str_class_list_box = "listbox_large";
		$str_class_list_header = "listheader_large";
		$str_class_total = "bill_total_large";
	}
	else {
		$str_class_input = "inputbox60";
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
<head>
<link href="../include/<?echo $str_css_filename;?>" rel="stylesheet" type="text/css">
</head>

<body bgcolor="#D0D0D0" onunload="remLink()">
	<script language="javascript">

		function checkZero() {
			oTextbox = document.po_view_enter.qty;
			if (oTextbox.value == "0") {
				oTextbox.value = '_zero';
				document.po_view_enter.submit();
			}
		}
		function remLink() {
			if (window.searchProduct && window.searchProduct.open && !window.searchProduct.closed)
				window.searchProduct.opener = null;
		}

		function getCode() {
			if (document.po_view_enter.code.value != "")
				document.po_view_enter.submit();
		}

		function checkCode() {
			if (document.po_view_enter.code.value == "") {
				document.po_view_enter.submit();
			}
			else {
				document.po_view_enter.qty.focus();
				document.po_view_enter.qty.select();
			}
		}

		function openSearch() {
			myWin = window.open("../common/product_search.php?formname=po_view_enter&fieldname=code",'searchProduct','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=400,height=600,top=0');
			myWin.focus();
		}

		function setQtyBlank() {
			oTextBox = document.po_view_enter.qty;
			oTextBox.value = "";
		}
	</script>

<?

	// suggest quantity to order based on the current and ordered fields
	function assign_quantity($productId) {
		$result_minimum = new Query("SELECT stock_minimum, stock_current, stock_ordered
			FROM ".Monthalize('stock_storeroom_product')." ssp
			WHERE (ssp.product_id=".$productId.") AND
				(ssp.storeroom_id=".$_SESSION["int_current_storeroom"].")");
		if ($result_minimum->RowCount()>0) {
			if ($result_minimum->FieldByName('stock_current') + $result_minimum->FieldByName('stock_ordered') < $result_minimum->FieldByName('stock_minimum'))
				$str_qty = $result_minimum->FieldByName('stock_minimum') - ($result_minimum->FieldByName('stock_current') + $result_minimum->FieldByName('stock_ordered'));
			else
				$str_qty="1";
		}
		else
			$str_qty="1";
		return $str_qty;
	}


	// if the "code" has not been defined set all to blank, focus "code"
	if (!IsSet($_POST["code"])) {
		$str_code="";
		$str_description="";
		$str_qty="";
		$int_focus="0"; // focus the "code" field
	}
	else {
		// check that "code" is not empty, in which case set description and focus "qty"
		if (!empty($_POST["code"])) {
			$result_code = new Query("SELECT * FROM stock_product WHERE (product_code = ".$_POST["code"].")");
			$str_code=$_POST["code"];
			$int_product_id=$result_code->FieldByName('product_id');
			$str_description=$result_code->FieldByName('product_description');
			$int_focus="1"; // focus the "qty" field

			// get the prices for the given product
			$result_price = new Query("SELECT buying_price, selling_price 
				FROM ".Yearalize('stock_batch')." 
				WHERE (product_id = ".$result_code->FieldByName('product_id').") AND
					(storeroom_id = ".$_SESSION['int_current_storeroom'].")
			");
			$flt_buying_price = 0;
			$flt_selling_price = 0;
			if ($result_price->RowCount() > 0) {
				$flt_buying_price = $result_price->FieldByName('buying_price');
				$flt_selling_price = $result_price->FieldByName('selling_price');
			}

			// if code is already entered in the list, then remove from list, and remember quantity
			$code_found="N";
			$result_check = new Query("SELECT * FROM ".Yearalize('purchase_items')." pi
				WHERE (pi.product_id=".$int_product_id.") AND
					(pi.purchase_order_id=".$_GET["id"].")");
			if ($result_check->RowCount() > 0) {
				//set flag "code_found" true
				$code_found="Y";
				//remember the quantity
				$str_qty=$result_check->FieldByName('quantity_ordered');
				//remove from the list
				$result_check->ExecuteQuery("DELETE FROM ".Yearalize('purchase_items')."
					WHERE (product_id=".$int_product_id.") AND
					(purchase_order_id=".$_GET["id"].") LIMIT 1");
				// refresh list
				echo "<script language=\"javascript\">";
				echo "parent.frames[\"frame_details\"].document.location=\"po_view_list.php?id=".$_GET["id"]."\"\n";
				echo "</script>";
			}

			// if "qty" is not set, assign a value based on stock_storeroom_year_month.stock_minimum
			if (!IsSet($_POST["qty"])) {
				if ($code_found=="N")
					$str_qty=assign_quantity($int_product_id);
			}
			else {
				// "qty" is set and not empty, therefore save the entry and update the list
				if (!empty($_POST["qty"])) {
					if ($_POST["qty"] != "_zero") {
						$qry_new="INSERT INTO ".Yearalize('purchase_items')."
								(purchase_order_id,
									product_id,
									quantity_ordered,
									supplier_id,
									buying_price,
									selling_price)
								VALUES (".$_GET["id"].", ".
									$int_product_id.", ".
									$_POST["qty"].", ".
									$result_code->FieldByName('supplier_id').", ".
									$flt_buying_price.", ".
									$flt_selling_price.")"; 
						$result_new=new Query($qry_new);

						// refresh list
						echo "<script language=\"javascript\">";
						echo "parent.frames[\"frame_details\"].document.location=\"po_view_list.php?id=".$_GET["id"]."\"\n";
						echo "</script>";
					}

					//reset the fields for a new entry
					$str_code="";
					$str_description="";
					$str_qty="";
					$int_focus=0;
				}
				else {
					if ($code_found=="N")
						$str_qty=assign_quantity($int_product_id);
				}
			}
		}
		else {
			$str_code="";
			$str_description="";
			$str_qty="";
			$int_focus="0";
		}
	$result_check->free();
	$result_code->free();
	}
?>

<form name="po_view_enter" method="POST" action="">
	<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class='<? echo $str_class_header?>'>Code: <input type="text" class="<?echo $str_class_input?>" name="code" value="<?echo $str_code;?>" width="10" size="10" onblur="javascript:getCode()" onfocus="javascript:setQtyBlank()"></td>
			<td><a href="javascript:openSearch()"><img src="../images/findfree.gif" border="0" title="Search" alt="Search"></a></td>
			<td width="250"><? echo $str_description; ?></td>
			<td class='<? echo $str_class_header?>'>Quantity: <input type="text" class="<?echo $str_class_input?>" name="qty" id="qty" value="<?echo $str_qty;?>" width="10" size="10" onkeyup="javascript:checkZero()" onfocus="javasript:checkCode()" validchars="0123456789"></td>
			<td><input type="submit" name="action" value="save"></td>
			<td></td>
		</tr>
	</table>
	<hr>
<?
	if ($int_focus == "0") {
		echo "<script language=\"javascript\">";
		echo "document.po_view_enter.code.focus()";
		echo "</script>";
	}
	else
	if ($int_focus == "1") {
		echo "<script language=\"javascript\">";
		echo "document.po_view_enter.qty.focus()";
		echo "</script>";
	}
?>

</form>
</body>
</html>