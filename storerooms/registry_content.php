<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");
    
    $str_code = '';
    if (IsSet($_GET['product_code']))
        $str_code = $_GET['product_code'];
    
    $str_query = "
        SELECT sp.product_id, sp.product_code, sp.product_description,
            ss.supplier_name,
            SUM(ssp.stock_current) AS current_stock,
            smu.measurement_unit
        FROM ".Monthalize('stock_storeroom_product')." ssp
        INNER JOIN stock_product sp ON (sp.product_id = ssp.product_id)
        INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
        INNER JOIN stock_supplier ss ON (ss.supplier_id = sp.supplier_id)
        WHERE sp.product_code = '".$str_code."'
        GROUP BY sp.product_id 
        ORDER BY sp.product_code";
    
    $qry = new Query($str_query);
    
?>

<html>
    <head>
        <link rel="stylesheet" type="text/css" href="../include/<?echo $str_css_filename;?>" />
    </head>
<body>

    <table width='100%'>
        <tr><td align='center'>
        
            <table border='1' cellpadding='7' cellspacing='0'>
                <tr>
                    <td align='right' class='<?echo $str_class_header?>'><b>Code:</b></td>
                    <td class='<?echo $str_class_header?>'><?echo $qry->FieldByName('product_code')?></td>
                </tr>
                <tr>
                    <td align='right' class='<?echo $str_class_header?>'><b>Description:</b></td>
                    <td class='<?echo $str_class_header?>'><?echo $qry->FieldByName('product_description')?></td>
                </tr>
                <tr>
                    <td align='right' class='<?echo $str_class_header?>'><b>Supplier:</b></td>
                    <td class='<?echo $str_class_header?>'><?echo $qry->FieldByName('supplier_name')?></td>
                </tr>
                <tr>
                    <td align='right' class='<?echo $str_class_header?>'><b>Stock:</b></td>
                    <td class='<?echo $str_class_header?>'><?echo number_format($qry->FieldByName('current_stock'), 2, '.', '')." ".$qry->FieldByName('measurement_unit')?></td>
                </tr>
            </table>
        
        </td></tr>
    </table>
    
</body>
</html>