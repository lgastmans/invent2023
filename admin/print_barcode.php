<?
//require("print_image.php");
//require("../common/php-barcode.php");
require_once("../include/const.inc.php");
require_once("../include/session.inc.php");
require_once("../include/db.inc.php");

if (IsSet($_GET['id'])) {
    $int_id = $_GET['id'];
	$str_printer = $_GET['selected_printer'];


    //=========================================
    // get the code, barcode, and supplier name
    //-----------------------------------------
    $qry = new Query("
        SELECT product_code, product_bar_code, supplier_name
        FROM stock_product sp
        LEFT JOIN stock_supplier ss ON (ss.supplier_id = sp.supplier_id)
        WHERE sp.product_id = $int_id
    ");
    if ($qry->RowCount() > 0) {
        $str_code = $qry->FieldByName('product_code');
        $str_barcode = $qry->FieldByName('product_bar_code');
        $str_supplier = $qry->FieldByName('supplier_name');
    }
    
    //======================
    // get the selling price
    //----------------------
    $flt_price = 0;
    $qry->Query("
        SELECT use_batch_price, sale_price
        FROM ".Monthalize('stock_storeroom_product')."
        WHERE product_id = $int_id
            AND storeroom_id = ".$_SESSION['int_current_storeroom']);
    if ($qry->RowCount() > 0) {
        if ($qry->FieldByName('use_batch_price') == 'N')
            $flt_price = number_format($qry->FieldByName('sale_price'),2,'.','');
        else {
            $qry->Query("
                SELECT sb.selling_price
                FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb
                WHERE (sb.product_id = ".$int_id.") AND
                        (sb.storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
                        (sb.status = ".STATUS_COMPLETED.") AND
                        (sb.deleted = 'N') AND
                        (ssb.product_id = sb.product_id) AND
                        (ssb.batch_id = sb.batch_id) AND
                        (ssb.storeroom_id = sb.storeroom_id) AND 
                        (ssb.is_active = 'Y')
                ORDER BY date_created DESC
                LIMIT 1
            ");
            if ($qry->RowCount() > 0)
                $flt_price = number_format($qry->FieldByName('selling_price'),2,'.','');
        }
    }

    $str_info = $str_code." ".$str_supplier." Rs.".$flt_price;

	require("php-barcode.php");
	barcode_print($str_barcode, 'ean', 2, 'jpg', $str_info);
//	barcode_print('0123456789123', 'ean', 2, 'jpg', $str_info);

    //print_image($str_printer, "");
}

?>
