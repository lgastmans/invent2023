<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");

	function getMySQLDate($str_date) {
		if ($str_date == '')
			$str_date = date('d-m-Y');
		$arr_date = explode('-', $str_date);
		return sprintf("%04d-%02d-%02d", $arr_date[2], $arr_date[1], $arr_date[0]);
	}

	if (IsSet($_GET['action'])) {

		if ($_GET['action'] == 'save') {

			if (IsSet($_GET['status'])) {
				if ($_GET['status'] == 'draft')
					$int_status = PURCHASE_DRAFT;
				else if ($_GET['status'] == 'sent')
					$int_status = PURCHASE_SENT;
				else
					$int_status = PURCHASE_DRAFT;
			}

			$bool_success = true;

			$qry = new Query("START TRANSACTION");

//================================
// EDIT ORDER
//================================
			if ($_SESSION['purchase_order_id'] > -1) {
				$str_date_expected_delivery = $_SESSION['purchase_date_expected'];
				$qry->Query("
					UPDATE ".Yearalize('purchase_order')."
					SET 
						purchase_status = ".$int_status.",
						purchase_order_ref = '".addslashes($_SESSION['purchase_order_ref'])."',
						user_id = ".$_SESSION['int_user_id'].",
						date_expected_delivery = '".addslashes($str_date_expected_delivery)."',
						supplier_id = ".$_SESSION['purchase_supplier_id'].",
						assigned_to_user_id = ".$_SESSION['purchase_assigned_to'].",
						storeroom_id = ".$_SESSION['int_current_storeroom'].",
						single_supplier = '".$_SESSION['purchase_single_supplier']."',
						invoice_number = '".$_SESSION['purchase_invoice_number']."',
						invoice_date = '".$_SESSION['purchase_invoice_date']."'
					WHERE 
						purchase_order_id = ".$_SESSION['purchase_order_id']."
				");
				if ($qry->b_error == true) {
					$bool_success = false;
					$str_message = "ERROR|Error updating purchase order table";
				}

				$qry->Query("
					DELETE FROM ".Yearalize('purchase_items')."
					WHERE 
						purchase_order_id = ".$_SESSION['purchase_order_id']."
				");

				for ($i=0;$i<count($_SESSION['purchase_order_arr_items']);$i++) {
					if ($_SESSION['purchase_single_supplier'] == 'Y')
						$int_supplier_id = $_SESSION['purchase_supplier_id'];
					else
						$int_supplier_id = $_SESSION['purchase_order_arr_items'][$i][5];
					
					$qry->Query("
						INSERT INTO ".Yearalize('purchase_items')."
						(
							purchase_order_id,
							product_id,
							buying_price,
							selling_price,
							quantity_ordered,
							supplier_id
						)
						VALUES (
							".$_SESSION['purchase_order_id'].",
							".$_SESSION['purchase_order_arr_items'][$i][3].",
							".$_SESSION['purchase_order_arr_items'][$i]['buying_price'].",
							".$_SESSION['purchase_order_arr_items'][$i][4].",
							".$_SESSION['purchase_order_arr_items'][$i][1].",
							".$int_supplier_id."
						)
					");
					if ($qry->b_error == true) {
						$bool_success = false;
						$str_message = 'ERROR|Error updating purchase items table';
					}
				}

			}
//================================
// NEW ORDER
//================================
			else {
				$str_date_expected_delivery = $_SESSION['purchase_date_expected'];
				

				$qry->Query("
					INSERT INTO ".Yearalize('purchase_order')."
					(
						purchase_status,
						date_created,
						purchase_order_ref,
						user_id,
						date_expected_delivery,
						supplier_id,
						assigned_to_user_id,
						storeroom_id,
						single_supplier,
						comment,
						invoice_number,
						invoice_date
					)
					VALUES (
						".$int_status.",
						'".date("Y-m-d H:i:s")."',
						'".addslashes($_SESSION['purchase_order_ref'])."',
						".$_SESSION['int_user_id'].",
						'".$str_date_expected_delivery."',
						".$_SESSION['purchase_supplier_id'].",
						".$_SESSION['purchase_assigned_to'].",
						".$_SESSION['int_current_storeroom'].",
						'".$_SESSION['purchase_single_supplier']."',
						'',
						'".$_SESSION['purchase_invoice_number']."',
						'".$_SESSION['purchase_invoice_date']."'
					)
				");
				if ($qry->b_error == true) {
					$bool_success = false;
					$str_message = "ERROR|Error inserting into purchase order table";
				}
				$int_purchase_order_id = $qry->getInsertedID();
#echo "<script language='javascript'>";
#echo "alert('".$int_purchase_order_id."');";
#echo "alert('".$str_date_expected_delivery."-".$_SESSION['purchase_date_expected']."');";
#echo "</script>";
				for ($i=0;$i<count($_SESSION['purchase_order_arr_items']);$i++) {
					if ($_SESSION['purchase_single_supplier'] == 'Y')
						$int_supplier_id = $_SESSION['purchase_supplier_id'];
					else
						$int_supplier_id = $_SESSION['purchase_order_arr_items'][$i][5];
						
					$str_query = "
						INSERT INTO ".Yearalize('purchase_items')."
						(
							purchase_order_id,
							product_id,
							buying_price,
							selling_price,
							quantity_ordered,
							supplier_id
						)
						VALUES (
							".$int_purchase_order_id.",
							".$_SESSION['purchase_order_arr_items'][$i][3].",
							".$_SESSION['purchase_order_arr_items'][$i]['buying_price'].",
							".$_SESSION['purchase_order_arr_items'][$i][4].",
							".$_SESSION['purchase_order_arr_items'][$i][1].",
							".$int_supplier_id."
						)";
					$qry->Query($str_query);
					if ($qry->b_error == true) {
						$bool_success = false;
						$str_message = 'ERROR|Error inserting into purchase items table';
					}
				}
			}
			
			if ($int_status == PURCHASE_SENT) {
				for ($i=0;$i<count($_SESSION['purchase_order_arr_items']);$i++) {
					updateStoreroomProduct(
						$_SESSION["int_current_storeroom"],
						$_SESSION['purchase_order_arr_items'][$i][3],
						0, 
						0, 
						$_SESSION['purchase_order_arr_items'][$i][1]
					);
				}
			}

			if ($bool_success == true) {
				$qry->Query("COMMIT");
				echo "<script language=\"javascript\">\n";
				echo "alert('Purchase Order saved successfully.');\n";
				echo "top.document.location = 'purchase_order_frameset.php?action=clear';\n";
				echo "</script>\n";
			}
			else {
				$qry->Query("ROLLBACK");
				echo "<script language=\"javascript\">\n";
				echo "alert('".$str_message."');\n";
				echo "</script>\n";
			}
		}
	}

?>


<script language='javascript'>

	function saveOrder(str_status) {
		if (str_status == 'S')
			parent.frames['frame_action'].document.location = "purchase_order_action.php?action=save&status=sent";
		else
			parent.frames['frame_action'].document.location = "purchase_order_action.php?action=save&status=draft";
	}
	
	function cancelOrder() {
 		if (confirm("Are you sure?"))
 			top.document.location = "purchase_order_frameset.php?action=clear";
	}
	
	function CloseWindow() {
		/*
		var oList = parent.frames["frame_list"].document.purchase_order_list.item_list;

		if (oList.options.length > 0) {
			if (confirm('Items have been entered. \n Exit anyway?')) {
				if (top.window.opener)
					top.window.opener.document.location=top.window.opener.document.location.href;
				top.window.close();
			}
		}
		*/
		
		if (top.window.opener)
			top.window.opener.document.location=top.window.opener.document.location.href;
		top.window.close();
		
	}

</script>


<html>
<head><TITLE></TITLE>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
</head>

<body id='body_bgcolor' leftmargin='40px' topmargin=5>

<form name="purchase_order_action" method="GET">

	<input type="button" class="settings_button" name="button_save" value="Save as Draft" onclick="javascript:saveOrder('N')">
	<input type="button" class="settings_button" name="button_save" value="Save as Sent" onclick="javascript:saveOrder('S')">
	<input type="button" class="settings_button" name="button_cancel" value="Cancel" onclick="javascript:cancelOrder()">
	<input type="button" name="button_close" value="Close" class="settings_button" onclick="CloseWindow()">

</form>
</body>
</html>

