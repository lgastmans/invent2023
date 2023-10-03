<?
  // check whether we are allowed to delete the record
  
  function dispatchRecord($f_record_id) {
  	$str_query="SELECT 
					st.transfer_id,
					st.transfer_status,
					st.batch_id,
					st.user_id,
					ssb.stock_available,
					ssb.stock_reserved,
					st.transfer_quantity
				FROM ".Monthalize("stock_transfer")." st
				INNER JOIN
					".Monthalize('stock_storeroom_batch')." ssb
				ON	ssb.batch_id = st.batch_id 
				AND 
					ssb.storeroom_id=".$_SESSION['int_current_storeroom']."
				WHERE st.transfer_id=$f_record_id";
  	$delQuery = new Query($str_query);
	if ($delQuery->b_error) die($delQuery->GetErrorMessage().$str_query);
	if ($delQuery->RowCount()==0) 
		return "Cannot find record.";
	
	$qty_reserved = -$delQuery->FieldByName('transfer_quantity');
	$qty_available = -$delQuery->FieldByName('transfer_quantity');
	
	$res = UpdateStockBatch($_SESSION['int_current_storeroom'], $delQuery->FieldByName('batch_id'), $qty_available, $qty_reserved, 0);
	if (!empty($res)) return $res;

	$delQuery->ExecuteQuery("UPDATE ".Monthalize("stock_transfer")." SET transfer_status=".STATUS_DISPATCHED." and user_id_dispatched=".$_SESSION['int_user_id']." where transfer_id=$f_record_id");

	$delQuery->Free();
	
	return "Dispatched transfer $f_record_id";
  } 

  function deleteRecord($f_record_id) {
  	$str_query="SELECT 
					st.transfer_id,
					st.transfer_status,
					st.batch_id,
					st.user_id,
					ssb.stock_available,
					ssb.stock_reserved,
					st.transfer_quantity
				FROM ".Monthalize("stock_transfer")." st
				INNER JOIN
					".Monthalize('stock_storeroom_batch')." ssb
				ON	ssb.batch_id = st.batch_id 
				AND 
					ssb.storeroom_id=".$_SESSION['int_current_storeroom']."
				WHERE st.transfer_id=$f_record_id";
  	$delQuery = new Query($str_query);
	if ($delQuery->b_error) die($delQuery->GetErrorMessage().$str_query);
	if ($delQuery->RowCount()==0) 
		return "Cannot find record.";
	
	$qty = $delQuery->FieldByName('transfer_quantity');
	
	$res = UpdateStockBatch($_SESSION['int_current_storeroom'], $delQuery->FieldByName('batch_id'), 0, $qty, 0);
	if (!empty($res)) return $res;

	$delQuery->ExecuteQuery("UPDATE ".Monthalize("stock_transfer")." SET is_deleted='Y' and user_id_dispatched=".$_SESSION['int_user_id']." where transfer_id=$f_record_id");

	$delQuery->Free();
	
	return "Delted transfer request #$f_record_id";
  } 


?>
