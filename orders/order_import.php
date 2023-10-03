<?php
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


    /*
        IMPORT XML FILE
    */

    if (IsSet($_POST['action'])) {

        if ($_POST['action'] == 'Import') {
            

            $xmlstr = file_get_contents($_FILES['import_file']['tmp_name']);

            $data = new SimpleXMLElement($xmlstr);


            /*
                verify if customer exists
            */
            $qry = new Query("
                SELECT *
                FROM customer
                WHERE id = '".$data->order->CC_id."'"
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
                        ".ORDER_STATUS_PENDING.",
                        ".$_SESSION['int_current_storeroom'].",
                        ".$_SESSION['int_user_id'].",
                        '',
                        '".date('Y-m-d H:i:s', $data->order->order_date)."'
                    )
                ");
                $int_order_id = $qry->getInsertedID();

                $flt_total = 0;
                foreach($data->order_products->order_product as $row) {
                    
                    $qry->Query("SELECT * FROM stock_product WHERE product_id = '".$row->product_id."'");
                    
                    if ($qry->RowCount() > 0) {
                        $int_product_id = $qry->FieldByName('product_id');
                        $flt_price = get_price($int_product_id);
                        $flt_total = $row->quantity * $flt_price;
                        
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
                                ".$row->quantity.",
                                ".$row->quantity.",
                                ".$flt_price.",
                                ".$int_product_id."
                            )
                        ");
                    }
                    else {
                        echo "Product with product_id ".$row->product_id." was not found, and not entered into the order.<br>";
                    }
                }

                $qry->Query("
                    UPDATE ".Monthalize('orders')."
                    SET total_amount = ".$flt_total."
                    WHERE order_id = $int_order_id
                ");
                
                echo "Order imported successfully.";

                //echo "<script language='javascript'>";
                //echo "window.close()";
                //echo "</script>";
            }
            else {
                echo "Customer could not be found - order import not processed.";
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
            <td height='15px'>Please select the order file to import...</td>
        </tr>
        <tr>
            <td height='15px'><input type="file" name="import_file" size='50px'></td>
        </tr>
        <tr>
            <td valign='bottom' style="padding-bottom: 20px;">
                <input type='submit' name='action' value='Import'>
                &nbsp;
                <input type='button' name='action' value='Close' onclick='window.close()'>
            </td>
        </tr>
    </table>

</form>
</body>
</html>