<?
  // check whether we are allowed to delete the record
  
  function deleteRecord($f_record_id) {
  
      $delQuery = new Query("select * from stock_product where product_id=$f_record_id");

      if ($delQuery->RowCount()==0) 
            return "Cannot find record.";
      if ($delQuery->FieldByName('deleted')=='Y') 
            return "This record has already been deleted.";

      $strCode = $delQuery->FieldByName('product_code');
      
      //==================================================
      // check to see if there is something with a balance
      //--------------------------------------------------
      $delQuery->Query("
            SELECT * 
            FROM ".Yearalize("stock_balance")."
            WHERE balance_month=".$_SESSION['int_month_loaded']."
                  AND balance_year=".$_SESSION['int_year_loaded']."
                  AND stock_closing_balance<>0
                  AND product_id=$f_record_id");
      if ($delQuery->RowCount() > 0) {
            return "This product has stock.\\nPlease make sure stock balance is cleared first.";
      }

//      $bool_success == true;
//      $delQuery->Query("START TRANSACTION");
      
      $delQuery->ExecuteQuery("UPDATE stock_product SET deleted='Y' WHERE product_id = $f_record_id");
      $delQuery->ExecuteQuery("UPDATE stock_batch SET deleted='Y' WHERE product_id = $f_record_id");

      $delQuery->Free();
      
      return "Deleted product $strCode";
  } 
?>
