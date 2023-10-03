<?
  // check whether we are allowed to delete the record
  
  function completeRecord($f_record_id) {
	// make sure the record exists and we have permission to complete it
  	$str_query="		SELECT 
					st.transfer_id,
					st.transfer_status,
					st.batch_id,
					st.user_id,
					st.product_id,
					ssb.stock_available,
					ssb.stock_reserved,
					st.storeroom_id_from,
					st.storeroom_id_to,
					st.transfer_quantity
				FROM ".Monthalize("stock_transfer")." st
				INNER JOIN
					".Monthalize('stock_storeroom_batch')." ssb
				ON	ssb.batch_id = st.batch_id 
				AND 
					ssb.storeroom_id=".$_SESSION['int_current_storeroom']."
				WHERE st.transfer_id=$f_record_id 
				AND 
					st.transfer_status=".STATUS_DISPATCHED."
				AND 	st.storeroom_id_to=".$_SESSION['int_current_storeroom'];

  	$delQuery = new Query($str_query);
	if ($delQuery->b_error) die($delQuery->GetErrorMessage().$str_query);
	if ($delQuery->RowCount()==0) 
		return "You do not have permission to complete this transfer";
	
	$qty = $delQuery->FieldByName('transfer_quantity');
	$int_batch_id = $delQuery->FieldByName('batch_id');
	$int_storeroom_id_from = $delQuery->FieldByName('storeroom_id_from');
	$int_product_id = $delQuery->FieldByName('product_id');

	$delQuery->Query("BEGIN");	

	$res = updateStoreroomBatch($_SESSION['int_current_storeroom'], $int_batch_id, $qty, 0, -$qty);
	$res .= updateStoreroomProduct($_SESSION['int_current_storeroom'], $int_product_id, $qty, 0, -$qty);

	$res .= updateStoreroomBatch($int_storeroom_id_from, $int_batch_id, -$qty, -$qty,0);
	$res .= updateStoreroomProduct($int_storeroom_id_from, $int_product_id, -$qty, -$qty, 0);



	$delQuery->ExecuteQuery("UPDATE ".Monthalize("stock_transfer")." SET transfer_status=".STATUS_COMPLETED." , user_id_received=".$_SESSION['int_user_id']." where transfer_id=$f_record_id");
	if ($delQuery->b_error) $res.="Error setting status";
	if (!empty($res)) {
		$delQuery->Query("ROLLBACK");
		return $res;

	} else $delQuery->Query("COMMIT");

	$delQuery->Free();
	
	return "Completed transfer $f_record_id";
  } 

  function deleteRecord($f_record_id) {
  	$str_query="SELECT 
					st.transfer_id,
					st.transfer_status,
					st.product_id,
					st.batch_id,
					st.user_id,
					ssb.stock_available,
					ssb.stock_reserved,
					st.transfer_quantity,
					st.storeroom_id_from,
					st.storeroom_id_to
				FROM ".Monthalize("stock_transfer")." st
				INNER JOIN
					".Monthalize('stock_storeroom_batch')." ssb
				ON	ssb.batch_id = st.batch_id 
				AND 
					ssb.storeroom_id=".$_SESSION['int_current_storeroom']."
				WHERE st.transfer_id=$f_record_id
				AND
					st.storeroom_id_from = ".$_SESSION['int_current_storeroom'];
  	$delQuery = new Query($str_query);
	if ($delQuery->b_error) die($delQuery->GetErrorMessage().$str_query);
	if ($delQuery->FieldByName('transfer_id')<>$f_record_id) 
		return "You do not have permission to delete this transfer.";
	
	$qty = $delQuery->FieldByName('transfer_quantity');
	$int_batch_id = $delQuery->FieldByName('batch_id');
	$int_storeroom_id_from = $delQuery->FieldByName('storeroom_id_from');
	$int_storeroom_id_to = $delQuery->FieldByName('storeroom_id_to');

	$int_product_id = $delQuery->FieldByName('product_id');

	$delQuery->Query("BEGIN");	

	$res = updateStoreroomBatch($int_storeroom_id_from, $int_batch_id, 0, -$qty, 0);
	$res .= updateStoreroomProduct($int_storeroom_id_from, $int_product_id, 0, -$qty, 0);
	$res .= updateStoreroomBatch($int_storeroom_id_to, $int_batch_id , 0, 0, -$qty);
	$res .= updateStoreroomProduct($int_storeroom_id_to, $int_product_id, 0, 0, -$qty);
	

	$delQuery->ExecuteQuery("UPDATE ".Monthalize("stock_transfer")." SET is_deleted='Y' and user_id_received=".$_SESSION['int_user_id']." where transfer_id=$f_record_id");

	if ($delQuery->b_error) $res.="Error setting status";
	if (!empty($res)) {
		$delQuery->Query("ROLLBACK");
		return $res;
	} else $delQuery->Query("COMMIT");

	$delQuery->Free();
	
	return "Deleted transfer request #$f_record_id";
  } 


?>
