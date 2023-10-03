<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");

    function get_price($int_product_id) {
        $result_set = new Query("
            SELECT is_taxed, is_cash_taxed, is_account_taxed
            FROM stock_storeroom
            WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")"
        );
        $is_taxed = 'Y';
        $is_cash_taxed = 'Y';
        $is_account_taxed = 'Y';
        if ($result_set->RowCount() > 0) {
            $is_taxed = $result_set->FieldByName('is_taxed');
            $is_cash_taxed = $result_set->FieldByName('is_cash_taxed');
            $is_account_taxed = $result_set->FieldByName('is_account_taxed');
        }
        
        // check whether the item should use the batch price or the storeroom price
        // in case the storeroom price is to be used, then the tax should still be
        // taken from the batch
        $result_set->Query("
            SELECT sale_price, point_price, use_batch_price, discount_qty, discount_percent
            FROM ".Monthalize('stock_storeroom_product')."
            WHERE (product_id = ".$int_product_id.") AND
                (storeroom_id = ".$_SESSION["int_current_storeroom"].")"
        );
        $sale_price = 0;
        $point_price = 0;
        $use_batch_price = 'Y';
        $discount_qty = 0;
        $discount_percent = 0;
        if ($result_set->RowCount() > 0) {
            $sale_price = $result_set->FieldByName('sale_price');
            $point_price = $result_set->FieldByName('point_price');
            $use_batch_price = $result_set->FieldByName('use_batch_price');
            $discount_qty = $result_set->FieldByName('discount_qty');
            $discount_percent = $result_set->FieldByName('discount_percent');
        }
        
        // get the price from the last batch and tax_id
        $result_set->Query("
            SELECT sb.selling_price, sb.tax_id, sb.batch_id
            FROM ".Yearalize('stock_batch')." sb
            WHERE (sb.product_id = ".$int_product_id.") AND
                (sb.storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
                (sb.is_active = 'Y') AND
                (sb.status = ".STATUS_COMPLETED.") AND
                (sb.deleted = 'N')
            ORDER BY date_created
        ");
        $selling_price = 0;
        $tax_id = 0;
        if ($result_set->RowCount() > 0) {
            $selling_price = $result_set->FieldByName('selling_price');
            $tax_id = $result_set->FieldByName('tax_id');
            $batch_id = $result_set->FieldByName('batch_id');
        }
        
        if ($use_batch_price == 'Y')
            return number_format(round($selling_price,3),2,'.','');
        else
            return round($sale_price,3);
    }

    if (IsSet($_POST['action'])) {

        if ($_POST['action'] == 'Import') {
            
            $arr_lines = file($_FILES['import_file']['tmp_name']);
       
            for($i=0; $i<count($arr_lines); $i++) { 
                $line = trim($arr_lines[$i]); 
                $arr = explode("\t", $line);
                
                for ($j=0; $j<count($arr);$j++) {
                    
                    $pos = strpos($arr[$j], 'Item');
                    if ($pos !== false) 
                        $int_items_start = $i;
                    
                    $pos = strpos($arr[$j], 'SUBTOTAL');
                    if ($pos !== false) 
                        $int_items_end = $i;
                    
                    $pos = strpos($arr[$j], 'Customer');
                    if ($pos !== false) 
                        $str_customer = substr($arr[$j], 13, strlen($arr[$j]));
                    
                    $pos = strpos($arr[$j], 'Date');
                    if ($pos !== false) 
                        $str_date = substr($arr[$j], 6, strlen($arr[$j]));
                        
                    $pos = strpos($arr[$j], 'ORDER');
                    if ($pos !== false) 
                        $str_reference = $arr[$j+1];
                    
                    $pos = strpos($arr[$j], 'Company');
                    if ($pos !== false) 
                        $str_company = substr($arr[$j], 9, strlen($arr[$j]));
                    
                    $pos = strpos($arr[$j], 'City');
                    if ($pos !== false) 
                        $str_city = substr($arr[$j], 5, strlen($arr[$j]));
                        
                    $pos = strpos($arr[$j], 'Address');
                    if ($pos !== false) 
                        $str_address = substr($arr[$j], 9, strlen($arr[$j]));
                    
                    $pos = strpos($arr[$j], 'Phone');
                    if ($pos !== false) 
                        $str_phone = substr($arr[$j], 6, strlen($arr[$j]));
                    
                    $pos = strpos($arr[$j], 'Fax');
                    if ($pos !== false) 
                        $str_fax = substr($arr[$j], 4, strlen($arr[$j]));
                    
                    $pos = strpos($arr[$j], 'Email');
                    if ($pos !== false) 
                        $str_email = substr($arr[$j], 6, strlen($arr[$j]));
                    
                    $pos = strpos($arr[$j], 'Contact');
                    if ($pos !== false) 
                        $str_contact = substr($arr[$j], 15, strlen($arr[$j]));
                    
                    $pos = strpos($arr[$j], 'Sales tax number');
                    if ($pos !== false) 
                        $str_sales_tax_no = substr($arr[$j], 17, strlen($arr[$j]));
                    
                    $pos = strpos($arr[$j], 'Sales tax');
                    if ($pos !== false) 
                        $str_sales_tax = substr($arr[$j], 11, strlen($arr[$j]));
                    
                }
            }
            
            $arr_items = array();
            $int_counter = 0;
            for ($i=($int_items_start+1); $i<$int_items_end; $i++) {
                $line = trim($arr_lines[$i]); 
                $arr = explode("\t", $line);
                
                for ($j=0; $j<count($arr); $j++) {
                    $arr_items[$int_counter][$j] = $arr[$j];
                }
                $int_counter++;
            }
            
            $qry = new Query("
                SELECT *
                FROM customer
                WHERE customer_id = '".$str_customer."'"
            );
            $int_customer_id = $qry->FieldByName('id');
            
            if ($qry->RowCount() > 0) {
                
                $qry->Query("
                    INSERT INTO ".Monthalize('orders')."
                    (
                        CC_id,
                        order_type,
                        total_amount,
                        payment_type,
                        order_status,
                        storeroom_id,
                        user_id,
                        order_reference,
                        order_date
                    )
                    VALUES(
                        ".$int_customer_id.",
                        ".ORDER_TYPE_DAILY.",
                        0,
                        ".BILL_CASH.",
                        ".ORDER_STATUS_ACTIVE.",
                        ".$_SESSION['int_current_storeroom'].",
                        ".$_SESSION['int_user_id'].",
                        '".$str_reference."',
                        '".set_mysql_date($str_date,'-')."'
                    )
                ");
                $int_order_id = $qry->getInsertedID();
                
                $flt_total = 0;
                for ($i=0;$i<count($arr_items);$i++) {
                    
                    $qry->Query("SELECT * FROM stock_product WHERE product_code = '".$arr_items[$i][4]."'");
                    
                    if ($qry->RowCount() > 0) {
                        $int_product_id = $qry->FieldByName('product_id');
                        $flt_price = get_price($int_product_id);
                        $flt_total = $arr_items[$i][7] * $flt_price;
                        
                        $qry->Query("
                            INSERT INTO ".Monthalize('order_items')."
                            (
                                order_id,
                                quantity_ordered,
                                quantity_delivered,
                                price,
                                product_id
                            )
                            VALUES (
                                ".$int_order_id.",
                                ".$arr_items[$i][7].",
                                ".$arr_items[$i][7].",
                                ".$flt_price.",
                                ".$int_product_id."
                            )
                        ");
                    }
                    else {
                        echo "Product with code ".$arr_items[$i][4]." was not found, and not entered into the order!<br>";
                    }
                }
                
                $qry->Query("
                    UPDATE ".Monthalize('orders')."
                    SET total_amount = ".$flt_total."
                    WHERE order_id = $int_order_id
                ");
                
                //echo "<script language='javascript'>";
                //echo "window.close()";
                //echo "</script>";
            }
            else {
                echo "Customer ".$str_company." could not be found.";
            }
        }
    }
?>

<html>
<head>
    <link rel="stylesheet" type="text/css" href="../include/<?echo $str_css_filename;?>" />
</head>

<body>

<form enctype='multipart/form-data' name='order_import' method='POST'>

    <table width='100%' height='100%' border='0'>
        <tr>
            <td height='15px'>Please specify a tab delimited file to import</td>
        </tr>
        <tr>
            <td height='15px'><input type="file" name="import_file" size='50px'></td>
        </tr>
        <tr>
            <td valign='bottom'>
                <input type='submit' name='action' value='Import'>
                &nbsp;
                <input type='button' name='action' value='Close' onclick='window.close()'>
            </td>
        </tr>
    </table>

</form>
</body>
</html>