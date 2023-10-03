<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");
    
    $int_id = -1;
    if (IsSet($_GET['id'])) {
        $int_id = $_GET['id'];
    }
    
    $qry_bill = new Query("
        SELECT *
        FROM ".Monthalize('bill')."
        WHERE bill_id = $int_id
    ");
    
    $qry_items = new Query("
        SELECT *
        FROM ".Monthalize('bill_items')." bi
        LEFT JOIN stock_product sp ON (sp.product_id = bi.product_id)
        WHERE bi.bill_id = $int_id
    ");

    $qry_order = new Query("
        SELECT *
        FROM ".Monthalize('orders')."
        WHERE order_id = ".$qry_bill->FieldByName('module_record_id'));
    
    $qry_customer = new Query("
        SELECT *
        FROM customer
        WHERE id = ".$qry_order->FieldByName('CC_id'));
    
?>

<html>
<body>

<table width='100%' border='0'>
<tr>
<td width='110.2px' height='42.5px'><img src='../settings/images/Shradhanjali_flower.jpg'></td>
<td align='center' valign='center' style="padding-right:100px;"><img src='../settings/images/shradhanjali_text.jpg'><br><font style='font-family:Arial,sans-serif;font-size:22px;font-weight:bold;text-align:center'>Shradhanjali</font><font style='font-family:Arial,sans-serif;font-size:16px;font-weight:normal;'><br>Auroshilpam Auroville 605 101 Tamil Nadu<br>Tel:(0413) 2622172 Fax:(0413) 2622062<br>Email: shradhan@auroville.org.in<br><br><b>DELIVERY CHALLAN</b><br></font></td>
</tr>
</table>

    <table width='100%' cellpadding='2' cellspacing='2'>
        <tr style='font-family:Arial,sans-serif;font-size:11px;font-weight:normal;'>
            <td align='right' width='100px'><b>Internal Ref:</b></td>
            <td><? echo $qry_order->FieldByName('note'); ?></td>
        </tr>
        <tr style='font-family:Arial,sans-serif;font-size:11px;font-weight:normal;'>
            <td align='right'><b>Order Ref:</b></td>
            <td><? echo $qry_order->FieldByName('order_reference'); ?></td>
        </tr>
        <tr style='font-family:Arial,sans-serif;font-size:11px;font-weight:normal;'>
            <td align='right'><b>Order Date:</b></td>
            <td><? echo set_formatted_date($qry_order->FieldByName('order_date'), '-'); ?></td>
        </tr>
        <tr style='font-family:Arial,sans-serif;font-size:11px;font-weight:normal;'>
            <td align='right' valign='top'><b>Customer:</b></td>
            <td><?echo $qry_customer->FieldByName('company')."<br>".$qry_customer->FieldByName('city')." ".$qry_customer->FieldByName('zip')?></td>
        </tr>
        <tr style='font-family:Arial,sans-serif;font-size:11px;font-weight:normal;'>
            <td align='right' valign='top'><b>D.C. #:</b></td>
            <td><?echo $qry_order->FieldByName('order_id');?></td>
        </tr>
        <tr style='font-family:Arial,sans-serif;font-size:11px;font-weight:normal;'>
            <td align='right' valign='top'><b>D.C. Date:</b></td>
            <td><?echo set_formatted_date($qry_order->FieldByName('order_date'), '-');?></td>
        </tr>
    </table>
    <br>
    <table border='2' cellpadding='5' cellspacing='0'>
        <tr style='font-family:Arial,sans-serif;font-size:11px;font-weight:bold;'>
            <td width='75px'><b>Code</b></td>
            <td width='250px'><b>Description</b></td>
            <td width='60px' align='right'><b>Price</b></td>
            <td width='60px' align='right'><b>Qty</b></td>
            <td width='60px' align='right'><b>Total</b></td>
        </tr>
        <tr>
            <?
                for ($i=0;$i<$qry_items->RowCount();$i++) {
                    $total_quantity = $qry_items->FieldByName('quantity') + $qry_items->FieldByName('adjusted_quantity');
                    
                    echo "<tr style='font-family:Arial,sans-serif;font-size:11px;font-weight:normal;'>";
                    echo "<td>".$qry_items->FieldByName('product_code')."</td>";
                    echo "<td>".$qry_items->FieldByName('product_description')."</td>";
                    echo "<td align='right'>".$qry_items->FieldByName('price')."</td>";
                    echo "<td align='right'>".$total_quantity."</td>";
                    echo "<td align='right'>".($qry_items->FieldByName('price') * $total_quantity)."</td>";
                    echo "</td>";
                    $qry_items->Next();
                }
            ?>
        </tr>
    </table>

	
<table width='100%' border='0'>
<tr>
<td align='center' valign='bottom'><font style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'>
Bankers: State Bank of India, Auroville - ICICI Bank, Pondicherry<br>VAT Tin No. 33144720859 - CST No. 399043 dt.23.04.93
</font></td>
</tr>
</table>

	
	
</body>
</html>