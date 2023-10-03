<script language="javascript">

	function setBatchSplit(intID) {
		if (document.location.href.indexOf("?") < 0) {
			document.location = document.location.href + "?action=batch_split&batch_id="+intID;
		} else {
			document.location = document.location.href+"&action=batch_split&batch_id="+intID;
		}
	}

</script>

<?
	error_reporting(E_ALL);

	function fn_check_expiry($element_name, $element_value) {
		$arr_curdate = explode("/", date('d/m/Y',time()));
		$str_date = $element_value['Y'].$element_value['M'].$element_value['d'];
		$str_curdate = $arr_curdate[2].$arr_curdate[1].$arr_curdate[0];
		if ($str_date < $str_curdate)
			return false;

		return true;
	}

	// returns the date contained in the returned array "$arr_date" from the quickForm
	function get_date($arr_date) {
		$str_date = $arr_date['Y']."-".$arr_date['M']."-".$arr_date['d']." 00:00:00";
		return $str_date;
	}

	// returns the expiry date based on the date of manufacture (here $date_start) and
	// the shelf life of the product (here $int_increment)
	function get_expiry_date($date_start, $int_increment) {
		$str_date = mktime(0,0,0,$date_start['M'],$date_start['d'],$date_start['Y']);

		$str_increment = "+".$int_increment." days";

		return date("Y-m-d h:i:s", strtotime($str_increment, $str_date));
	}

	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");

	require_once 'HTML/QuickForm.php';

	$form =& new HTML_QuickForm('frmReceive', 'GET');


$groupLabel = <<<_HTML
<table border="0" cellpadding=5 cellspacing="0">
	<tr valign="top">
		<td align="right" width="250">
			<!-- BEGIN required --><span>*</span><!-- END required -->{label}
		</td>
		<td align="left">
			<!-- BEGIN error --><span>{error}</span><br/><!-- END error -->{element}
		</td>
	</tr>
</table>
_HTML;


// column header to display at the top of the form
$html_header = "<table border=\"0\" cellpadding=\"5\" cellspacing=\"0\" bgcolor=\"#D0D0D0\">
<tr style=\"font-family:Arial,Verdana,sans-serif;font-size:12px;font-weight:bold;\">
	<td width=\"250\" align=\"right\">Code - Description - Ordered</td>
	<td width=\"70\">Batch</td>
	<td width=\"75\">Received</td>
	<td width=\"50\">Bonus</td>
	<td width=\"55\">B.Price</td>
	<td width=\"50\">S.Price</td>
	<td width=\"180\">Date of Manufacture</td>
	<td width=\"95\">Supplier</td>
	<td width=\"40\">Active</td>
	<td width=\"40\">Receive</td>
	<td width=\"40\"></td>
</tr></table>";

	$renderer =& $form->defaultRenderer();

	// get "purchase order number" for the current purchase order
	$qry_supplierid = "SELECT purchase_order_number, supplier_id, assigned_to_user_id FROM ".Yearalize('purchase_order')." WHERE (purchase_order_id=".$_GET["id"].")";
	$result_supplierid = new Query($qry_supplierid);
	$str_purchase_order_number = $result_supplierid->FieldByName('purchase_order_number');

	// result set of items to receive, listing only those where the is_received
	// flag is still false
	$qry_receive = "SELECT *
		FROM ".Yearalize('purchase_items')." pi,
			stock_product sp
		WHERE (purchase_order_id=".$_GET["id"].") AND
			(pi.product_id=sp.product_id) AND
			(pi.is_received='N')";
	$result_receive = new Query($qry_receive);

	$form->addElement('hidden', 'id', $_GET["id"]);
	// holds the total number of rows
	$form->addElement('hidden', 'int_total_groups', $result_receive->RowCount());
	$form->addElement('hidden', 'int_assigned_to_id', $result_supplierid->FieldByName('assigned_to_user_id'));
	$form->addElement('hidden', 'str_po_number', $str_purchase_order_number);

	$form->addElement('static', 'form_header', $html_header);

	// not used now as the date of manufacture is entered instead of the date of expiry
	// the date of expiry is calculated based on the date of manufacture
	$form->registerRule('check_expiry','function','fn_check_expiry');

	if (empty($_SESSION["int_total_rows"]))
		$_SESSION["int_total_rows"] = $result_receive->RowCount();

	// check whether a batch has been selected for splitting and increment the totalrows accordingly
	if (IsSet($_GET["action"])) {
		if ($_GET["action"] == "batch_split") {
			$_SESSION["int_total_rows"] = $_SESSION["int_total_rows"] +1;
			echo $_GET["batch_id"].", ".$_SESSION["int_total_rows"];
		}
	}
	else
		$int_total_rows = 0;

	// create a quickForm group for each purchase order item to receive
	for ($i=0;$i<$result_receive->RowCount();$i++) {

		// list of suppliers for the given item
		$result_supplier = new Query("
			SELECT ss.supplier_name AS supplier_name1,
				ss2.supplier_name AS supplier_name2,
				ss3.supplier_name AS supplier_name3
			FROM
				stock_product sp,
				stock_supplier ss
			LEFT JOIN stock_supplier ss2 ON (sp.supplier2_id = ss2.supplier_id)
			LEFT JOIN stock_supplier ss3 ON (sp.supplier3_id = ss3.supplier_id)
			WHERE (sp.supplier_id = ss.supplier_id) AND
				(sp.product_id=".$result_receive->FieldByName('product_id').")");
		// load the list of suppliers in an array
		$arr_supplier[0] = $result_supplier->FieldByName('supplier_name1');
		if (!is_null($result_supplier->FieldByName('supplier_name2')))
			$arr_supplier[1] = $result_supplier->FieldByName('supplier_name2');
		if (!is_null($result_supplier->FieldByName('supplier_name3')))
			$arr_supplier[2] = $result_supplier->FieldByName('supplier_name3');

		// per item to receive, create a set of grouped elements
		// where each fieldname is unique
		$str_product_id			= "product_id_".$i;
		$str_batch 				= "batch_".$i;
		$str_ordered			= "ordered_".$i;
		$str_received			= "received_".$i;
		$str_bonus				= "bonus_".$i;
		$str_buying_price 		= "buying_price_".$i;
		$str_selling_price		= "selling_price_".$i;
		$str_date_manufacture	= "date_manufacture_".$i;
		$str_shelf_life			= "shelf_life_".$i;
		$str_active				= "active_".$i;
		$str_supplier			= "supplier_".$i;
		$str_is_received		= "is_received_".$i;
		$str_split_batch		= "split_batch_".$i;
		$product_id		= &HTML_QuickForm::createElement('hidden', $str_product_id);
		$batch			= &HTML_QuickForm::createElement('text', $str_batch, 'code', array('style' => 'width: 75px;'));
		$ordered		= &HTML_QuickForm::createElement('hidden', $str_ordered);
		$received 		= &HTML_QuickForm::createElement('text', $str_received, 'received', array('style' => 'width: 75px;'));
		$bonus			= &HTML_QuickForm::createElement('text', $str_bonus, null, array('style' => 'width: 50px;'));
		$buying_price	= &HTML_QuickForm::createElement('text', $str_buying_price, null, array('style' => 'width: 55px;'));
		$selling_price	= &HTML_QuickForm::createElement('text', $str_selling_price, null, array('style' => 'width: 55px;'));
		$manufacture	= &HTML_QuickForm::createElement('date', $str_date_manufacture, null, array('format'=>'d / M / Y'));
		$shelf_life		= &HTML_QuickForm::createElement('hidden', $str_shelf_life);
		$supplier		= &HTML_QuickForm::createElement('select', $str_supplier, null, $arr_supplier, array('style' => 'width: 100px;'));
		$active			= &HTML_QuickForm::createElement('checkbox', $str_active, null, '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', null, array('style' => 'width: 100px;'));
		$is_received	= &HTML_QuickForm::createElement('checkbox', $str_is_received, null, '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
		$split_batch	= &HTML_QuickForm::createElement('button', 'action', 'split', array('onclick' => "setBatchSplit(".$i.")"));

		// create the unique group
		$str_group_name = "items".$i;
		$form->addGroup(array($product_id, $batch, $ordered, $received, $bonus, $buying_price, $selling_price, $manufacture, $shelf_life, $supplier, $active, $is_received, $split_batch),
				$str_group_name,
				$result_receive->FieldByName('product_code')."  -  ".$result_receive->FieldByName('product_description')."  -  <b>".$result_receive->FieldByName('quantity_ordered')."</b>", '&nbsp;&nbsp;');

		$renderer->setElementTemplate($groupLabel, $str_group_name);

		// Rule for group's elements
		$form->addGroupRule($str_group_name, array(
			$str_received => array(
				array('invalid received value', 'required', null, 'client'),
				array('received is numbers only', 'numeric', null, 'client')
			),
			$str_buying_price => array(
				array('Buying price is numbers only', 'numeric', null, 'client'),
				array('Buying price is required', 'required', null, 'client'),
				array('price should be greater than zero', 'nonzero', null, 'client')
			),
			$str_selling_price => array(
				array('price is numbers only', 'numeric', null, 'client'),
				array('price is required', 'required', null, 'client'),
				array('price should be greater than zero', 'nonzero', null, 'client')
			)
		));

		// set default values for the items
		if (empty($_GET["action"])) {
			$product_id		->setValue($result_receive->FieldByName('product_id'));
			$ordered		->setValue($result_receive->FieldByName('quantity_ordered'));
			$received		->setValue($result_receive->FieldByName('quantity_ordered'));
			$bonus			->setValue('0');
			$buying_price	->setValue($result_receive->FieldByName('buying_price'));
			$selling_price	->setValue($result_receive->FieldByName('selling_price'));
			$manufacture	->setValue(time());
			$shelf_life		->setValue($result_receive->FieldByName('shelf_life'));
			$supplier		->setValue($result_receive->FieldByName('supplier_id'));
			$active			->setValue(true);
			$is_received	->setValue(true);
		}

		$result_receive->Next();
	}

	$form->addElement('static', '', "<hr align=\"left\" height=\"5\" width=\"1060\">");

	$buttons[] = &HTML_QuickForm::createElement('submit', 'action', 'save');
	$buttons[] = &HTML_QuickForm::createElement('button', 'action', 'cancel', array('onClick' => "window.close();"));
	$form->addGroup($buttons, null, null, '&nbsp;', false);


	if (IsSet($_GET["action"])) {
		if ($_GET["action"] == "save") {
			if ($form->validate()) {
			// Form is validated, then processes the data
				$form->freeze();
				$form->process('saveForm', false);
			}
		}
	}

	$form->display();

	// returns the number of items selected to process based on the "is_received" field
	function getSelectedRows() {
		$int_result=0;
		for ($i = 0; $i < $_GET["int_total_groups"]; $i++) {
			$item_values =  $_GET["items".$i];
			if (!empty($item_values["is_received_".$i]))
				$int_result = $int_result + 1;
		}
		return $int_result;
	}

	function saveForm($values) {

		// get the number of rows that have been selected
		$int_num_rows_selected = getSelectedRows();

		if ($int_num_rows_selected > 0) {

			// save all the selected rows...
			$qry_update = new Query("BEGIN");
			$bool_success = true;
			$str_message = "";

			for ($i=0;$i<$_GET["int_total_groups"];$i++) {

				$group_values =  $_GET["items".$i];

				if (!empty($group_values["is_received_".$i])) {

				// add an entry in stock_batch
					if (!empty($group_values["batch_".$i])) {
						$qry_update->Query("INSERT INTO stock_batch
								(batch_code,
								buying_price,
								selling_price,
								date_created,
								opening_balance,
								date_manufacture,
								date_expiry,
								is_active,
								status,
								user_id,
								buyer_id,
								supplier_id,
								product_id,
								storeroom_id)
							VALUES('".
								$group_values["batch_".$i]."', ".
								$group_values["buying_price_".$i].", ".
								$group_values["selling_price_".$i].", '".
								date("Y-m-d h:i:s")."', ".
								$group_values["received_".$i].", '".
								get_date($group_values["date_manufacture_".$i])."', '".
								get_expiry_date($group_values["date_manufacture_".$i], $group_values["shelf_life_".$i])."', ".
								"'Y', '".
								STATUS_COMPLETED."', ".
								$_SESSION["int_user_id"].", ".
								$_GET["int_assigned_to_id"].", ".
								$group_values["supplier_".$i].", ".
								$group_values["product_id_".$i].", ".
								$_SESSION["int_current_storeroom"]."
								)");
					}
					else {
						// don't save the batch code, and set it later to the autoincremental value of batch_id
						$qry_update->Query("INSERT INTO stock_batch
								(buying_price,
								selling_price,
								date_created,
								opening_balance,
								date_manufacture,
								date_expiry,
								is_active,
								status,
								user_id,
								buyer_id,
								supplier_id,
								product_id,
								storeroom_id)
							VALUES(".
								$group_values["buying_price_".$i].", ".
								$group_values["selling_price_".$i].", '".
								date("Y-m-d h:i:s")."', ".
								$group_values["received_".$i].", '".
								get_date($group_values["date_manufacture_".$i])."', '".
								get_expiry_date($group_values["date_manufacture_".$i], $group_values["shelf_life_".$i])."', ".
								"'Y', '".
								STATUS_COMPLETED."', ".
								$_SESSION["int_user_id"].", ".
								$_GET["int_assigned_to_id"].", ".
								$group_values["supplier_".$i].", ".
								$group_values["product_id_".$i].", ".
								$_SESSION["int_current_storeroom"]."
								)");
					}
					$int_batch_id = $qry_update->getInsertedID();
					if (empty($group_values["batch_".$i])) {
						// set the batch code to the autoincremental value of batch_id if it is not assigned
						$qry_update->Query("UPDATE stock_batch
							SET batch_code = '".$int_batch_id."'
							WHERE (batch_id=".$int_batch_id.")");
					}
					if ($qry_update->b_error == true) {
						$bool_success = false;
						$str_message = "error updating stock batch";
						exit;
					}

				// update stock_storeroom_product_year_month, updating fields stock_ordered and stock_current
					$qry_update->Query("UPDATE ".Monthalize('stock_storeroom_product')."
						SET stock_ordered = stock_ordered - ".$group_values["ordered_".$i].",
							stock_current = stock_current + ".$group_values["received_".$i]."
						WHERE (product_id=".$group_values["product_id_".$i].") AND
							(storeroom_id=".$_SESSION["int_current_storeroom"].")");

					if ($qry_update->b_error == true) {
						$bool_success = false;
						$str_message = "error updating stock storeroom product";
						exit;
					}

				// add an entry in stock_storeroom_batch_year_month
					$qry_update->Query("INSERT INTO ".Monthalize('stock_storeroom_batch')."
							(stock_available,
							shelf_id,
							batch_id,
							storeroom_id,
							product_id)
						VALUES (".$group_values["received_".$i].",
							0, ".
							$int_batch_id.", ".
							$_SESSION["int_current_storeroom"].", ".
							$group_values["product_id_".$i].")");
					if ($qry_update->b_error == true) {
						$bool_success = false;
						$str_message = "error updating stock storeroom batch";
						exit;
					}

				// update entry in stock_balance_year
					$qry_update->Query("UPDATE ".Yearalize('stock_balance')."
							SET stock_received = stock_received + ".$group_values["received_".$i]."
							WHERE (product_id=".$group_values["product_id_".$i].") AND
								(storeroom_id=".$_SESSION["int_current_storeroom"].") AND
								(balance_month=".$_SESSION["int_month_loaded"].") AND
								(balance_year=".$_SESSION["int_year_loaded"].")");
					if ($qry_update->b_error == true) {
						$bool_success = false;
						$str_message = "error updating stock balances";
						exit;
					}

				// add an entry in stock_transfer_year_month
					$qry_update->Query("INSERT INTO ".Monthalize('stock_transfer')."
							(transfer_quantity,
							transfer_description,
							date_created,
							module_id,
							user_id,
							storeroom_id_from,
							storeroom_id_to,
							product_id,
							batch_id,
							module_record_id,
							transfer_type,
							transfer_status,
							user_id_dispatched,
							user_id_received,
							is_deleted)
						VALUES(".
							$group_values["received_".$i].", '".
							"PURCHASE ORDER ".$_GET["str_po_number"]."', '".
							date("Y-m-d h:i:s")."', ".
							"3, ".
							$_SESSION["int_user_id"].", ".
							"0, ".
							$_SESSION["int_current_storeroom"].", ".
							$group_values["product_id_".$i].", ".
							$int_batch_id.", ".
							$_GET["id"].", ".
							TYPE_RECEIVED.", ".
							STATUS_COMPLETED.", ".
							$_GET["int_assigned_to_id"].", ".
							$_SESSION["int_user_id"].", ".
							"'N')");
					if ($qry_update->b_error == true) {
						$bool_success = false;
						$str_message = "error updating stock transfer";
						exit;
					}

				// update purchase_items_year, setting the quantity_received, quantity_bonus and batch_id field
					$qry_update->Query("UPDATE ".Yearalize('purchase_items')."
						SET quantity_received=".$group_values["received_".$i].",
							quantity_bonus=".$group_values["bonus_".$i].",
							is_received='Y',
							batch_id=".$int_batch_id."
						WHERE (purchase_order_id=".$_GET["id"].") AND
							(product_id=".$group_values["product_id_".$i].")");
					if ($qry_update->b_error == true) {
						$bool_success = false;
						$str_message = "error updating purchase items";
						exit;
					}

				} // end of "if selected"

			}	// end of loop that iterates through received items

			// set the purchase order to received only if selected rows = total rows
			if ($int_num_rows_selected == $_GET["int_total_groups"]) {
				// update purchase_order_year, setting purchase_status field
				$qry_update->Query("UPDATE ".Yearalize('purchase_order')."
					SET purchase_status=".PURCHASE_RECEIVED.",
						date_received='".date("Y-m-d h:i:s")."'
					WHERE (purchase_order_id=".$_GET["id"].")");
				if ($qry_update->b_error == true) {
					$bool_success = false;
					$str_message = "error updating purchase order";
					exit;
				}
			}

			if ($bool_success)
				$qry_update->Query("COMMIT");
			else
				$qry_update->Query("ROLLBACK");

			$qry_update->Free();

			echo "<script language=\"javascript\">\n";
			echo "window.opener.document.location=window.opener.document.location.href;\n";
			echo "window.close();\n";
			echo "</script>\n";

		} // end of "if selected > 0" statement

	}	// end function saveForm

?>