<?
	error_reporting(E_ERROR);

	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");


	function productBatches($strProductCode) {
		// default return string
		// this string gets returned to a javascript function in "billing_enter.php"
		$strBatches = "nil";

		if ($strProductCode != 'nil') {
			// locate the product for the given code and save the id
			$result_set = new Query("
				SELECT product_id
				FROM stock_product
				WHERE (product_code = '".$strProductCode."')
					AND (deleted = 'N')
			");
			$int_product_id = $result_set->FieldByName('product_id');

			// get the batches and quantities available for the given product
			$sql = "
				SELECT sb.batch_id, sb.batch_code, sb.buying_price, sb.selling_price, sb.tax_id, st.tax_description, ssb.stock_available,
					str.module_id, po.invoice_number, po.invoice_date, po.date_received
				FROM ".Yearalize('stock_batch')." sb
				LEFT JOIN ".Monthalize('stock_tax_links')." stl ON (stl.tax_id = sb.tax_id)
				INNER JOIN ".Monthalize('stock_tax')." st ON (st.tax_id = stl.tax_id)
				INNER JOIN ".Monthalize('stock_tax_definition')." std ON (std.definition_id = stl.tax_definition_id)
				INNER JOIN ".Monthalize('stock_storeroom_batch')." ssb ON (ssb.product_id = sb.product_id)
					AND (ssb.batch_id = sb.batch_id)
					AND	(ssb.storeroom_id = sb.storeroom_id)
				LEFT JOIN ".Monthalize('stock_transfer')." str ON (str.batch_id = sb.batch_id)	AND (transfer_type = 5)
				LEFT JOIN ".Yearalize('purchase_items')." pi ON (pi.batch_id = sb.batch_id)
				LEFT JOIN ".Yearalize('purchase_order')." po ON (po.purchase_order_id = pi.purchase_order_id)
				WHERE (sb.product_id = ".$int_product_id.")
					AND (sb.storeroom_id = ".$_SESSION["int_current_storeroom"].")
					AND (sb.is_active = 'Y')
					AND (sb.status = ".STATUS_COMPLETED.")
					AND (sb.deleted = 'N')
					AND (sb.supplier_id = ".$_SESSION["current_supplier_id"].")
					AND (ssb.is_active = 'Y')
				ORDER BY sb.date_created";
//echo $sql;				
			$result_set->Query($sql);



			if ($result_set->RowCount() > 0) {

				// save the batch code and quantity in the session array
				unset($_SESSION["arr_item_batches"]);

				for ($i=0; $i<$result_set->RowCount(); $i++) {
					
					$_SESSION["arr_item_batches"][$i][0] = $result_set->FieldByName('batch_code');
					$_SESSION["arr_item_batches"][$i][1] = number_format($result_set->FieldByName('stock_available'),3,'.','');
					$_SESSION["arr_item_batches"][$i][2] = $result_set->FieldByName('batch_id');
					$_SESSION["arr_item_batches"][$i]['invno'] = $result_set->FieldByName('invoice_number');
					$_SESSION["arr_item_batches"][$i]['invdt'] = $result_set->FieldByName('invoice_date');
					//$_SESSION["arr_item_batches"][$i]['batch_id'] = $result_set->FieldByName('batch_id');

					$result_set->Next();
				}

				// this string is for javascript, in order to populate the list of batches
				$result_set->First();

				$strBatches = "";

				for ($i=0; $i<$result_set->RowCount(); $i++) {

					$info = 'DR';
					if (!is_null($result_set->FieldByName('date_received')))
						$info = "PO&".$result_set->FieldByName('invoice_number')."&".FormatDate($result_set->FieldByName('invoice_date'),'.')."&".FormatDate($result_set->FieldByName('date_received'),'.');

					if ($i == $result_set->RowCount()-1)
						$strBatches .= $result_set->FieldByName('batch_code')."&".number_format($result_set->FieldByName('stock_available'),3,'.','')."&".$result_set->FieldByName('tax_description')."&".$info;
					else
						$strBatches .= $result_set->FieldByName('batch_code')."&".number_format($result_set->FieldByName('stock_available'),3,'.','')."&".$result_set->FieldByName('tax_description')."&".$info."|";

					$result_set->Next();
				}
			}
			else
				unset($_SESSION["arr_item_batches"]);
		}

		// return
		return $strBatches;
	}

	if (!empty($_GET['live'])) {
		if (!empty($_GET['product_code'])) {
			echo productBatches($_GET['product_code']);
			die();
		}
		else {
			die("nil");
		}
	}
?>