<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
  	require_once("db_mysqli.php");	

	function new_purchase_order($int_supplier_id) {
		
		$qry_str = "SELECT MAX(purchase_order_id) AS next_id
			FROM ".Yearalize('purchase_order');
		$qry_result = new Query($qry_str);

		// get the next purchase order number
		$int_poNumber = $qry_result->FieldByName('next_id') + 1;

		// save the new entry as a draft
		$qry_result->Query(
			"INSERT INTO ".Yearalize('purchase_order')."
				(purchase_status,
				date_created,
				purchase_order_ref,
				user_id,
				supplier_id,
				assigned_to_user_id,
				storeroom_id,
				single_supplier)
			VALUES(".PURCHASE_DRAFT.", '".
				date("Y-m-d H:i:s")."', '".
				$int_poNumber."', ".
				$_SESSION["int_user_id"].", ".
				$int_supplier_id.", ".
				$_SESSION["int_user_id"].", ".
				$_SESSION["int_current_storeroom"].",
				'Y')"
		);

        $int_po_id = $qry_result->getInsertedID();

		return $int_po_id;
	}
	
	function get_next_purchase_order_ref() {
		$int_id = '';
		
		$qry = new Query("
			SELECT MAX(purchase_order_id) AS purchase_order_id
			FROM ".Yearalize('purchase_order')."
			WHERE (storeroom_id = ".$_SESSION['int_current_storeroom'].")
		");
		if ($qry->RowCount() > 0)
			$int_id = $qry->FieldByName('purchase_order_id') + 1;
			
		return $int_id;
	}
	
	function load_purchase_order_details($int_id) {
		$str_retval = 'OK';

		$qry = new Query("
			SELECT *
			FROM ".Yearalize('purchase_order')."
			WHERE purchase_order_id = $int_id
		");
		if ($qry->RowCount() > 0) {
			$_SESSION['purchase_order_id'] = $int_id;
			$_SESSION['purchase_comment'] = $qry->FieldByName('comment');
			$_SESSION['purchase_status'] = $qry->FieldByName('purchase_status');
			$_SESSION['purchase_date_created'] = set_formatted_date($qry->FieldByName('date_created'), '-');
			$_SESSION['purchase_date_received'] = set_formatted_date($qry->FieldByName('date_received'), '-');
			$_SESSION['purchase_order_ref'] = $qry->FieldByName('purchase_order_ref');
			$_SESSION['purchase_user_id'] = $qry->FieldByName('user_id');
			$_SESSION['purchase_date_expected'] = set_formatted_date($qry->FieldByName('date_expected_delivery'), '-');
			$_SESSION['purchase_supplier_id'] = $qry->FieldByName('supplier_id');
			$_SESSION['purchase_assigned_to'] = $qry->FieldByName('assigned_to_user_id');
			$_SESSION['purchase_single_supplier'] = $qry->FieldByName('single_supplier');
			$_SESSION['purchase_discount'] = $qry->FieldByName('discount');
			$_SESSION['invoice_number'] = $qry->FieldByName('invoice_number');
			$_SESSION['invoice_date'] = set_formatted_date($qry->FieldByName('invoice_date'), '-');

			// load the items
			$qry_items = new Query("
				SELECT *
				FROM ".Yearalize('purchase_items')." pi, stock_product sp
				INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
				WHERE (pi.purchase_order_id = $int_id )
					AND (pi.product_id = sp.product_id)
			");
			for ($i=0;$i<$qry_items->RowCount();$i++) {
				$_SESSION["purchase_order_arr_items"][$i][0] = $qry_items->FieldByName('product_code'); 	// Code
				$_SESSION["purchase_order_arr_items"][$i][1] = $qry_items->FieldByName('quantity_ordered');	// Qty
				$_SESSION["purchase_order_arr_items"][$i][2] = $qry_items->FieldByName('product_description');	// Description
				$_SESSION["purchase_order_arr_items"][$i][3] = $qry_items->FieldByName('product_id');		// Product Id
				$_SESSION["purchase_order_arr_items"][$i][4] = $qry_items->FieldByName('selling_price');	// Price
				$_SESSION["purchase_order_arr_items"][$i][5] = $qry_items->FieldByName('supplier_id');		// Supplier Id
				$_SESSION["purchase_order_arr_items"][$i]['buying_price'] = $qry_items->FieldByName('buying_price');
				$_SESSION["purchase_order_arr_items"][$i]['is_decimal'] = $qry_items->FieldByName('is_decimal');
				$_SESSION["purchase_order_arr_items"][$i]['tax_id'] = $qry_items->FieldByName('tax_id');
				
				$qry_items->Next();
			}
		}
		else
			$str_retval = 'FALSE';
		
		return $str_retval;
	}
?>