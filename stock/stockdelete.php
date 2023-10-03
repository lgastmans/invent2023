<?
  // check whether we are allowed to delete the record
  
  function deleteRecord($f_record_id) {
  
  	$delQuery = new Query("select * from ".Monthalize("stock_storeroom_product")." where product_id=$f_record_id");
	if ($delQuery->RowCount()==0) 
		return "Cannot find record.";
	
	if (($delQuery->FieldByName('stock_current')<>0) || ($delQuery->FieldByName('stock_reserved')<>0) ||
 	  	($delQuery->FieldByName('stock_ordered')<>0)) 
		return "Please make sure that balances are all 0 before attempting to delete this item.";

	$delQuery->ExecuteQuery("DELETE from ".Monthalize("stock_storeroom_product")." where product_id=$f_record_id");

	$delQuery->Free();
	
	return "Deleted record $f_record_id";
  } 
?>
