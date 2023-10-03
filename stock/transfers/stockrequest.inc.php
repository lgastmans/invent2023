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
					st.transfer_quantity,
					st.is_deleted
				FROM ".Monthalize("stock_transfer")." st
				INNER JOIN
					".Monthalize('stock_storeroom_batch')." ssb
				ON	ssb.batch_id = st.batch_id 
				AND 
					ssb.storeroom_id=".$_SESSION['int_current_storeroom']."
				WHERE st.is_deleted='N' and st.transfer_id=$f_record_id";
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
  
  //					ssb.stock_available,
  //					ssb.stock_reserved,
//				LEFT JOIN
//					".Monthalize('stock_storeroom_batch')." ssb
//				ON	ssb.batch_id = st.batch_id 
//				AND 
//					ssb.storeroom_id=".$_SESSION['int_current_storeroom']."

  	$str_query="SELECT 
					st.transfer_id,
					st.transfer_status,
					st.batch_id,
					st.product_id,
					st.user_id,
					st.transfer_quantity,
					st.storeroom_id_from,
					st.storeroom_id_to

				FROM ".Monthalize("stock_transfer")." st
				WHERE st.transfer_id=$f_record_id";
				
//		die($str_query);
 	$delQuery = new Query($str_query);
	if ($delQuery->b_error) die($delQuery->GetErrorMessage().$str_query);
	
	if ($delQuery->RowCount()==0) 
		return " Cannot find record.";
	
	$qty = 0-$delQuery->FieldByName('transfer_quantity');
	
	if ($delQuery->FieldByName('transfer_status')==1) {
	$msg = updateStoreroomProduct($delQuery->FieldByName('storeroom_id_to'),$delQuery->FieldByName('product_id'),0,0,$qty);	
	
//	$msg = //updateStoreroomProduct($delQuery->FieldByName('storeroom_id_from'),$delQuery->FieldByName('product_id'),0,$qty,0);	
	
//	$res = UpdateStockBatch($_SESSION['int_current_storeroom'], $delQuery->FieldByName('batch_id'), 0, $qty, 0);
//	if (!empty($res)) return $res;

	$delQuery->ExecuteQuery("UPDATE ".Monthalize("stock_transfer")." SET is_deleted='Y', user_id_dispatched=".$_SESSION['int_user_id']." where transfer_id=$f_record_id");
  }
  else {
  	return "This record is already being processed.  You cannot delete it.";  
  }
	$delQuery->Free();
	
	return "Deleted transfer request #$f_record_id";
	
  } 


?>
