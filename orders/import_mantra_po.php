<?php
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");

    /*
        IMPORT CSV FILE
    */

    if (IsSet($_POST['action'])) {

        if ($_POST['action'] == 'Import') {
            

            /*
                MANTRA HACK
                customer has field "use_mrp" set to Y
            */
            $qry = new Query("
                SELECT *
                FROM customer
                WHERE use_mrp = 'Y'
            ");
            $int_customer_id = $qry->FieldByName('id');



            $file = fopen($_FILES['import_file']['tmp_name'], 'r');


            $po_number = '';
            $po_date = '';

            while (($line = fgetcsv($file,1000,"\t")) !== FALSE) {

                if (strpos($line[0], 'Purchase') !== false)
                    $po_number = $line[1];

                elseif (strpos($line[0], 'Date') !== false)
                    $po_date = set_mysql_date($line[1], '-');

                elseif (strpos($line[0], 'SN') !== false)
                    break;
            }


            /*
                check whether order with given $po_number was already imported
            */

            $qry_check = new Query("
                SELECT * 
                FROM ".Monthalize('orders')." 
                WHERE (order_reference = '$po_number') 
                    AND (CC_id = ".$int_customer_id.")
            ");

            if ($qry_check->RowCount() > 0) {

                //if (!confirm("Order with reference $po_number for customer ".$qry->FieldByName('company')." already exists, are you sure you want to import?"))

                    die("Order with reference $po_number for customer ".$qry->FieldByName('company')." already exists <br> Order not imported");
            }

            $items = array();
            $i=0;

            while (($line = fgetcsv($file,1000,"\t")) !== FALSE) {

                if (!empty($line[0])) {

                    $items[$i]['code'] = substr($line[1] ,3);
                    $items[$i]['quantity'] = floatval($line[5]);
                    

                    /*
                        get the mrp
                    */
                    $sql = "SELECT mrp FROM stock_product WHERE product_code = '".$items[$i]['code']."'";
                    $product = new Query($sql);

                    if ($product)
                        $items[$i]['price'] = number_format($product->FieldByName('mrp'),3,'.','');
                    else
                        $items[$i]['price'] = 0;

                    $i++;

                }
                else
                    break;


            }

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
                        '".$po_number."',
                        '".$po_date."'
                    )
                ");
                $int_order_id = $qry->getInsertedID();

                $flt_total = 0;
                foreach($items as $row) {
                    
                    $qry->Query("SELECT * FROM stock_product WHERE product_code = '".$row['code']."'");
                    
                    if ($qry->RowCount() > 0) {

                        $int_product_id = $qry->FieldByName('product_id');
                        $flt_total = $row['quantity'] * $row['price'];
                        
                        if ($row['quantity'] > 0) {

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
                                    ".$row['quantity'].",
                                    ".$row['quantity'].",
                                    ".$row['price'].",
                                    ".$int_product_id."
                                )
                            ");
                        }
                    }
                    else {
                        echo "Product with product_id ".$row['code']." was not found, and not entered into the order.<br>";
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


            fclose($file);

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
            <td height='15px'>Please select the Mantra purchase order to import...</td>
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