<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");

    if (IsSet($_GET["supplier_id"]))
        $int_supplier_id = $_GET["supplier_id"];
    else
        $int_supplier_id = 0;

    if (IsSet($_GET["include_tax"]))
        $str_include_tax = $_GET["include_tax"];
    else
        $str_include_tax = 'Y';
    
    if (IsSet($_GET["include_value"]))
        $str_include_value = $_GET["include_value"];
    else
        $str_include_value = 'Y';

    if (IsSet($_GET['display_stock']))
        $str_display_stock = $_GET['display_stock'];
    else
        $str_display_stock = 'All';

    if (IsSet($_GET['include_bprice']))
        $str_include_bprice = $_GET['include_bprice'];
    else
        $str_include_bprice = 'N';

    $str_is_filtered = 'N';
    if (IsSet($_GET['is_filtered']))
        $str_is_filtered = $_GET['is_filtered'];

    if ($str_is_filtered == 'Y') {
        $str_filter_field = $_GET['filter_field'];
        $str_filter_text = $_GET['filter_text'];
        $str_where = '';
        if ($str_filter_field == 'code')
            $str_where = "AND (sp.product_code = '".$str_filter_text."')";
        else if ($str_filter_field == 'description')
            $str_where = "AND (sp.product_description LIKE '".$str_filter_text."%')";
    }
    else {
        $str_filter_field = '';
        $str_filter_text = '';
        $str_where = '';
    }

    $qry_supplier = new Query("
        SELECT *
        FROM stock_supplier
        WHERE supplier_id = $int_supplier_id
    ");
    
    $qry_products = new Query("
        SELECT *
        FROM stock_product
        WHERE supplier_id = $int_supplier_id
    ");
    
    $str_listed = "
        SELECT *
        FROM stock_product sp
        LEFT JOIN ".Yearalize('stock_batch')." sb ON (sb.product_id = sp.product_id)
                AND (sb.status = ".STATUS_COMPLETED.")
                AND (sb.deleted = 'N')
                AND (sb.is_active = 'Y')
                AND (sb.storeroom_id = ".$_SESSION['int_current_storeroom'].")
        INNER JOIN ".Monthalize('stock_storeroom_batch')." ssb ON (ssb.batch_id = sb.batch_id)
                AND (ssb.storeroom_id = ".$_SESSION['int_current_storeroom'].")
                AND (ssb.is_active = 'Y')
        WHERE (sp.supplier_id = ".$int_supplier_id.")
                AND (sp.deleted = 'N')
                ".$str_where."
        GROUP BY sp.product_id";
        
    $qry_listed = new Query($str_listed);
?>

<html>
    <head>
	<link rel="stylesheet" type="text/css" href="../include/sweetTitles.css" />
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	<script type="text/javascript" src="../include/addEvent.js"></script>
	<script type="text/javascript" src="../include/sweetTitles.js"></script>
    </head>
<body id='body_bgcolor'>
	<font class='normaltext'>
	
	<table width='100%' border='0' cellpadding='0' cellspacing='0'>
	<tr><td align='left'>
            
            <table border='0' cellpadding='2' cellspacing='0'>
                <tr>
                    <td class='normaltext_bold' align='right' width='100px'><b>Contact Person</b></td>
                    <td class='normaltext' width='500px'><?echo $qry_supplier->FieldByName('contact_person')?></td>
                    <td class='normaltext_bold' align='right' width='100px'><a class='normaltext' href='#' title='The total number of products defined for this supplier. Products for which there are no batches will not be listed below.'>Total Products</a></td>
                    <td class='normaltext'><?echo $qry_products->RowCount()?></td>
                </tr>
                <tr>
                    <td class='normaltext_bold' align='right'><b>Address</b></td>
                    <td class='normaltext' ><?echo $qry_supplier->FieldByName('supplier_address')?></td>
                    <td class='normaltext_bold' align='right' width='100px'>Listed Below</td>
                    <td class='normaltext'><?echo $qry_listed->RowCount()?></td>
                </tr>
                <tr>
                    <td class='normaltext_bold' align='right'><b>Phone</b></td>
                    <td class='normaltext' ><?echo $qry_supplier->FieldByName('supplier_phone')?></td>
                </tr>
                <tr>
                    <td class='normaltext_bold' align='right'><b>Cell</b></td>
                    <td class='normaltext' ><?echo $qry_supplier->FieldByName('supplier_cell')?></td>
                </tr>
            </table>
                
            <table border=1 cellpadding=7 cellspacing=0 class='normaltext_bold'>
                <tr bgcolor='lightgrey'>
                    <td width='50px'>Code</td>
                    <td width='250px'>Description</td>
                    <td width="100px">Category</td>
                    <? if ($str_include_bprice == 'Y') { ?>
                        <td width='100px'>Buying Price</td>
                    <? } ?>
                    <td width='100px'>Selling Price</td>
                    <? if ($str_include_tax == 'Y') { ?>
                        <td width='100px'>S Price/Tax</td>
                        <td width='100px'>M.R.P.</td>
                        <td width='50px'>Tax%</td>
                    <? } else { ?>
                        <td width='100px'>M.R.P.</td>
                    <? } ?>
                    <td width='100px'>Current Stock</td>
                    <? if ($str_include_value == 'Y') { ?>
                        <? if ($str_include_bprice == 'Y') { ?>
                            <td width='100px'>Buying Value</td>
                        <? } ?>
                        <td width='100px'>Selling Value</td>
                    <? } ?>
                </tr>
            </table>





	</td></tr>
	</table>
	</font>
</body>
</html>