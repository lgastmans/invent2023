<?
    include("../../include/const.inc.php");
    include("../../include/session.inc.php");
    include("../../include/db.inc.php");
    
    //======================================
    // reset the ordered and reserved fields
    //--------------------------------------
    $str_reset = "
        UPDATE ".Monthalize('stock_storeroom_product')." ssp
        SET stock_ordered = 0,
            stock_reserved = 0
        WHERE storeroom_id = ".$_SESSION['int_current_storeroom'];
    
    $qry = new Query($str_reset);
    
    //=============================================================
    // get the products for which there is a positive ordered value
    //-------------------------------------------------------------
    $str_query = "
        SELECT pi.product_id, SUM(pi.quantity_ordered) AS quantity_ordered
        FROM ".Yearalize('purchase_order')." po
        INNER JOIN ".Yearalize('purchase_items')." pi ON (pi.purchase_order_id = po.purchase_order_id)
        WHERE (po.purchase_status = ".PURCHASE_SENT.")
            AND (po.storeroom_id = ".$_SESSION['int_current_storeroom'].")
        GROUP BY pi.product_id
    ";
    $qry_update = new Query($str_query);
    
    //=================================================================
    // update the stock_storeroom_product table to reflect these values
    //-----------------------------------------------------------------
    for ($i=0;$i<$qry_update->RowCount();$i++) {
        $qry->Query("
            UPDATE ".Monthalize('stock_storeroom_product')."
            SET stock_ordered = ".$qry_update->FieldByName('quantity_ordered')."
            WHERE (product_id = ".$qry_update->FieldByName('product_id').")
                AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
        ");
        $qry_update->Next();
    }
    
    //==============================================================
    // get the products for which there is a positive reserved value
    //--------------------------------------------------------------
    $str_query = "
        SELECT bi.product_id, SUM(bi.quantity_ordered) AS quantity_ordered
        FROM ".Monthalize('bill')." b
        INNER JOIN ".Monthalize('bill_items')." bi ON (bi.bill_id = b.bill_id)
        WHERE (bill_status = ".BILL_STATUS_UNRESOLVED.")
            AND (is_pending = 'Y')
            AND (module_id = 7)
            AND (is_debit_bill = 'N')
            AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
        GROUP BY bi.product_id
    ";
    $qry_update = new Query($str_query);

    //=================================================================
    // update the stock_storeroom_product table to reflect these values
    //-----------------------------------------------------------------
    for ($i=0;$i<$qry_update->RowCount();$i++) {
        $qry->Query("
            UPDATE ".Monthalize('stock_storeroom_product')."
            SET stock_reserved = ".$qry_update->FieldByName('quantity_ordered')."
            WHERE (product_id = ".$qry_update->FieldByName('product_id').")
                AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
        ");
        $qry_update->Next();
    }
?>
<html>
<head>
    <link href="../../include/styles.css" rel="stylesheet" type="text/css">
    <script language='javascript'>
        function goBack() {
            document.location = '../index_verification_tools.php';
        }
    </script>
</head>

<body leftmargin='20px' rightmargin='20px' topmargin='20px' bottommargin='20px'>

<?
    boundingBoxStart("800", "../../images/blank.gif");
?>
    <br>
    <div class='normaltext'>Successfully updated the 'ordered' and 'reserved' fields.</div>
    <br>
    <input type='button' name='action' value='Back' class='settings_button' onclick='javascript:goBack()'>
    <br><br>
<?
    boundingBoxEnd("800", "../../images/blank.gif");
?>

</body>
</html>