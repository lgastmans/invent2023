<?
    function verify_stock($str_include_cb) {
        
        $qry_products = new Query("
            SELECT *
            FROM stock_product
            WHERE deleted = 'N' AND is_available = 'Y'
            ORDER BY product_code
        ");
        
        $qry_stock_balance = new Query("SELECT * FROM ".Yearalize('stock_balance')." LIMIT 1");
        $qry_stock_storeroom_batch = new Query("SELECT * FROM ".Monthalize('stock_storeroom_batch')." LIMIT 1");
        $qry_stock_storeroom_product = new Query("SELECT * FROM ".Monthalize('stock_storeroom_product')." LIMIT 1");
        
        
        $int_discrepancies = 0;
        $arr_products = array();
        
        for ($i = 0; $i < $qry_products->RowCount(); $i++) {
            
            $str_stock_balance = "
                SELECT *
                FROM ".Yearalize('stock_balance')."
                WHERE (product_id = ".$qry_products->FieldByName('product_id').")
                    AND (balance_month = ".$_SESSION['int_month_loaded'].")
                    AND (balance_year = ".$_SESSION['int_year_loaded'].")";
            $qry_stock_balance->Query($str_stock_balance);
            $stock_opening_balance = 0;
            $stock_balance_amount = 0;
            if ($qry_stock_balance->RowCount() > 0) {
                $stock_opening_balance = number_format($qry_stock_balance->FieldByName('stock_opening_balance'),3,'.','');
                $stock_balance_amount = number_format($qry_stock_balance->FieldByName('stock_closing_balance'),3,'.','');
            }
            
           
            $str_stock_storeroom_batch = "
                SELECT *
                FROM ".Monthalize('stock_storeroom_batch')."
                WHERE product_id = ".$qry_products->FieldByName('product_id')."
                    AND (is_active = 'Y')";
            $qry_stock_storeroom_batch->Query($str_stock_storeroom_batch);
            $stock_storeroom_batch_amount = 0;
            for ($j=0;$j<$qry_stock_storeroom_batch->RowCount();$j++) {
                $stock_storeroom_batch_amount = $stock_storeroom_batch_amount + number_format($qry_stock_storeroom_batch->FieldByName('stock_available'),3,'.','');
                $qry_stock_storeroom_batch->Next();
            }
            $stock_storeroom_batch_amount = number_format($stock_storeroom_batch_amount,3,'.','');
            

            $old_month = date('n')-1;
            $old_year = date('Y');
            if ($old_month==0) { 
                    $old_year--; 
                    $old_month=12; 
            }
            $str_stock_storeroom_batch = "
                SELECT *
                FROM stock_storeroom_batch_".$old_year."_".$old_month."
                WHERE product_id = ".$qry_products->FieldByName('product_id')."
                    AND (is_active = 'Y')";
            $qry_stock_storeroom_batch->Query($str_stock_storeroom_batch);
//            echo $str_stock_storeroom_batch;
            $stock_storeroom_batch_previous_amount = 0;
            for ($j=0;$j<$qry_stock_storeroom_batch->RowCount();$j++) {
                $stock_storeroom_batch_previous_amount  = $stock_storeroom_batch_previous_amount  + number_format($qry_stock_storeroom_batch->FieldByName('stock_available'),3,'.','');
                $qry_stock_storeroom_batch->Next();
            }
            $stock_storeroom_batch_previous_amount  = number_format($stock_storeroom_batch_previous_amount,3,'.','');
            
           
            $str_stock_storeroom_product = "
                SELECT *
                FROM ".Monthalize('stock_storeroom_product')."
                WHERE product_id = ".$qry_products->FieldByName('product_id');
            $qry_stock_storeroom_product->Query($str_stock_storeroom_product);
            $stock_storeroom_product_amount = 0;
            if ($qry_stock_storeroom_product->RowCount() > 0)
                $stock_storeroom_product_amount = number_format($qry_stock_storeroom_product->FieldByName('stock_current'),3,'.','');
            
            if ($str_include_cb == 'Y') {
                if (
                    ($stock_balance_amount <> $stock_storeroom_product_amount) ||
                    ($stock_balance_amount <> $stock_storeroom_batch_amount) ||
                    ($stock_storeroom_batch_amount <> $stock_storeroom_product_amount) ||
                    ($stock_opening_balance <> $stock_storeroom_batch_previous_amount)) {
                        
                        $arr_products[$int_discrepancies][0] = $qry_products->FieldByName('product_id');
                        $arr_products[$int_discrepancies][1] = $qry_products->FieldByName('product_code');
                        $arr_products[$int_discrepancies][2] = $qry_products->FieldByName('product_description');
                        $arr_products[$int_discrepancies][3] = $stock_balance_amount;
                        $arr_products[$int_discrepancies][4] = $stock_storeroom_batch_amount;
                        $arr_products[$int_discrepancies][5] = $stock_storeroom_product_amount;
                        $arr_products[$int_discrepancies][6] = $stock_storeroom_batch_previous_amount;
                        $arr_products[$int_discrepancies][7] = $stock_opening_balance;
                        
                        $int_discrepancies++;
                }
            }
            else {
                if (
                    ($stock_storeroom_batch_amount <> $stock_storeroom_product_amount)) {
                        
                        $arr_products[$int_discrepancies][0] = $qry_products->FieldByName('product_id');
                        $arr_products[$int_discrepancies][1] = $qry_products->FieldByName('product_code');
                        $arr_products[$int_discrepancies][2] = $qry_products->FieldByName('product_description');
                        $arr_products[$int_discrepancies][3] = $stock_balance_amount;
                        $arr_products[$int_discrepancies][4] = $stock_storeroom_batch_amount;
                        $arr_products[$int_discrepancies][5] = $stock_storeroom_product_amount;
                        $arr_products[$int_discrepancies][6] = $stock_storeroom_batch_previous_amount;
                        $arr_products[$int_discrepancies][7] = $stock_opening_balance;
                        
                        $int_discrepancies++;
                }
            }
            
            $qry_products->Next();
        }
       
       return $arr_products;
    }
    
    
    
    
    function correct_closing_balances() {
        // first make sure that there is an entry in the stock_balance table
        // for each product
        $qry_products = new Query("
            SELECT *
            FROM stock_product
            WHERE deleted = 'N' AND is_available = 'Y'
            ORDER BY product_code
        ");
        
        $qry_stock_balance = new Query("SELECT * FROM ".Yearalize('stock_balance')." LIMIT 1");

        for ($i = 0; $i < $qry_products->RowCount(); $i++) {
            
            $qry_stock_balance->Query("
                SELECT *
                FROM ".Yearalize('stock_balance')."
                WHERE (product_id = ".$qry_products->FieldByName('product_id').")
                    AND (balance_month = ".$_SESSION['int_month_loaded'].")
                    AND (balance_year = ".$_SESSION['int_year_loaded'].")            
            ");
            
            if ($qry_stock_balance->RowCount() == 0) {
                // create an entry
                $qry_stock_balance->Query("
                    INSERT INTO ".Yearalize('stock_balance')."
                    (
                        balance_month,
                        balance_year,
                        product_id,
                        storeroom_id
                    )
                    VALUES (
                        ".$_SESSION['int_month_loaded'].",
                        ".$_SESSION['int_year_loaded'].",
                        ".$qry_products->FieldByName('product_id').",
                        ".$_SESSION['int_current_storeroom']."
                    )
                ");
            }
            
            $qry_products->Next();
        }
        
        // then set the closing_balance to equal the current stock
        $str_correct = "
            UPDATE ".Yearalize('stock_balance')." sb, ".Monthalize('stock_storeroom_product')." ssp
            SET sb.stock_closing_balance = ssp.stock_current
            WHERE (sb.product_id = ssp.product_id)
                AND (sb.balance_month = ".$_SESSION['int_month_loaded'].")
                AND (sb.balance_year = ".$_SESSION['int_year_loaded'].")            
        ";
        
        $qry_correct = new Query($str_correct);
        
        if ($qry_correct->b_error == false)
            echo "completed correction of closing balances successfully<br>";
        else
            echo "an error occurred updating the closing balances<br>";
        
        return true;
    }
    
    
    
    function correct_discrepancies() {
        
        // update the current stock to reflect the stock
        // available across active batches for those entries
        // that are faulty
        $arr_products = verify_stock('Y');
        
        $qry_batches = new Query("SELECT * FROM ".Yearalize('stock_batch')." LIMIT 1");
        $qry_update = new Query("SELECT * FROM ".Yearalize('stock_batch')." LIMIT 1");
        
        for ($i=0; $i<count($arr_products); $i++) {
            
            $str_batches = "
                    SELECT ssb.stock_available
                    FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb
                    WHERE (sb.product_id = ".$arr_products[$i][0].") AND
                            (sb.storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
                            (sb.is_active = 'Y') AND
                            (sb.status = ".STATUS_COMPLETED.") AND
                            (sb.deleted = 'N') AND
                            (ssb.product_id = sb.product_id) AND
                            (ssb.batch_id = sb.batch_id) AND
                            (ssb.storeroom_id = sb.storeroom_id) AND 
                            (ssb.is_active = 'Y')
                    ORDER BY date_created";
            $qry_batches->Query($str_batches);
            
            $flt_total_stock = 0;
            for ($j=0;$j<$qry_batches->RowCount();$j++) {
                
                $flt_total_stock = $flt_total_stock + number_format($qry_batches->FieldByName('stock_available'), 3, '.', '');
                
                $qry_batches->Next();
            }
            
            // update the current stock entry in stock_storeroom_product
            $qry_update->Query("
                UPDATE ".Monthalize('stock_storeroom_product')."
                SET stock_current = ".$flt_total_stock."
                WHERE product_id = ".$arr_products[$i][0]."
            ");
            
            // update the closing balance in stock_balance
            $qry_update->Query("
                UPDATE ".Yearalize('stock_balance')."
                SET stock_closing_balance = ".$flt_total_stock.",
                    stock_opening_balance = ".$arr_products[$i][6]."
                WHERE (product_id = ".$arr_products[$i][0].")
                    AND (balance_month = ".$_SESSION['int_month_loaded'].")
                    AND (balance_year = ".$_SESSION['int_year_loaded'].")
            ");
        }
        
        correct_closing_balances();
        
        return count($arr_products);
    }
    
    
?>