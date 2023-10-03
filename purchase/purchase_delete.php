<?

// assuming that const.inc.php is included already

function deleteRecord($f_record_id, $f_status) {

  	$result_delete = new Query("SELECT *
		FROM ".Yearalize('purchase_order')."
		WHERE (purchase_order_id=".$f_record_id.") AND
			(purchase_status=".$f_status.")");

	if ($result_delete->RowCount()==0)
		return "Cannot find record.";

	if ($f_status == PURCHASE_DRAFT) {
		// delete the purchase order
		$result_delete->ExecuteQuery("DELETE
			FROM ".Yearalize('purchase_order')."
			WHERE (purchase_order_id=".$f_record_id.")");

		// delete the corresponding purchase order items
		$result_items = new Query("DELETE
			FROM ".Yearalize('purchase_items')."
			WHERE (purchase_order_id=".$f_record_id.")");
	}
	else {
		// cancel the purchase order
		$result_delete->Query("UPDATE ".Yearalize('purchase_order')."
			SET purchase_status = ".PURCHASE_CANCELLED."
			WHERE (purchase_order_id=".$f_record_id.")");

		// update table stock_storeroom_product_year_month,
		// deducting the field stock_ordered for each corresponding purchase order item
		$qry_items="SELECT * FROM ".Yearalize('purchase_items')." pi
			WHERE (pi.purchase_order_id = ".$f_record_id.")";
		$result_items=new Query($qry_items);

		if ($result_items->RowCount() > 0) {
			for ($i=0;$i<$result_items->RowCount();$i++) {
				$qry_ordered="UPDATE ".Monthalize('stock_storeroom_product')."
					SET stock_ordered = stock_ordered - ".$result_items->FieldByName('quantity_ordered')."
					WHERE (product_id = ".$result_items->FieldByName('product_id').")
						AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")";
				$result_ordered = new Query($qry_ordered);

				$result_items->next();
			}
			$result_ordered->Free();
		}
		$result_items->Free();
	}

	$result_delete->Free();

	return "Deleted record $f_record_id";
}

?>
