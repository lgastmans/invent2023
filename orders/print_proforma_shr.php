<?php
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");
    require_once("../common/tax.php");
    require_once("../Numbers/Words.php");
    require_once("../common/number_to_words.php");

    if (IsSet($_GET['order_id']))
        $int_id = $_GET['order_id'];
    
    if (IsSet($_GET['is_bill_id'])) {
        $qry = new Query("SELECT module_record_id FROM ".Monthalize('bill')." WHERE bill_id = $int_id");
        $int_id = $qry->FieldByName('module_record_id');
        $qry->Free();
    }
    
    
    function ExpandAmount($amount) {
        $nw = new Numbers_Words();
        if (strpos($amount,'.') !== false) {
            $numwords = explode('.',$amount);
            if (intval($numwords[1]) > 0)
                $res = $nw->toWords($numwords[0]).' and paise '.$nw->toWords($numwords[1]).' only';
            else
                $res = $nw->toWords($numwords[0]).' only';
        }
        else  {
            $res = $nw->toWords($amount);
        }
        return $res;
    }

    $qry = new Query("SELECT * FROM templates WHERE template_type = ".TEMPLATE_ORDER_PROFORMA." AND is_default = 'Y'");
    if ($qry->RowCount() == 0)
        die('No template found');

    //======================================
    // get the tax details for the storeroom
    //--------------------------------------
    $qry_tax = new Query("
            SELECT is_taxed, is_cash_taxed, is_account_taxed
            FROM stock_storeroom
            WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")"
    );
    $is_taxed = 'Y';
    $is_cash_taxed = 'Y';
    $is_account_taxed = 'Y';
    if ($qry_tax->RowCount() > 0) {
            $is_taxed = $qry_tax->FieldByName('is_taxed');
            $is_cash_taxed = $qry_tax->FieldByName('is_cash_taxed');
            $is_account_taxed = $qry_tax->FieldByName('is_account_taxed');
    }

    $str_title = stripslashes($qry->FieldByName('title'));
    $str_header = stripslashes($qry->FieldByName('header'));
    $str_content = stripslashes($qry->FieldByName('content'));
    $str_footer = stripslashes($qry->FieldByName('footer'));
    
    $qry_order = new Query("
        SELECT *
        FROM ".Monthalize('orders')."
        WHERE order_id = ".$int_id
    );
    
    $qry_items = new Query("
        SELECT *
        FROM ".Monthalize('order_items')." oi
        LEFT JOIN stock_product sp ON (sp.product_id = oi.product_id)
		LEFT JOIN stock_category sc ON (sc.category_id = sp.category_id)
        WHERE order_id = ".$qry_order->FieldByName('order_id')
    );
    
    $qry_customer = new Query("
        SELECT *
        FROM customer
        WHERE id = ".$qry_order->FieldByName('CC_id'));

	/*
		get the customer's price_increase field
	*/
	$flt_price_increase = 0;
	if ($qry_customer->RowCount() > 0)
		$flt_price_increase = 1 + ($qry_customer->FieldByName('price_increase') / 100);
	/*
		---
	*/
    $qry_tax = new Query("
        SELECT *
        FROM ".Monthalize('stock_tax')."
        WHERE tax_id = ".$qry_customer->FieldByName('tax_id')
    );
    $str_tax_description = $qry_tax->FieldByName('tax_description');

    function get_date($str_mysql_date) {
        $str_date = substr($str_mysql_date, 0, 10);
        $arr_date = explode("-", $str_date);
        return $arr_date[2]."-".$arr_date[1]."-".$arr_date[0];
    }
    
    //========================================================
    // header
    //--------------------------------------------------------
    /*
	$str_header = str_replace('$customer', $qry_customer->FieldByName('company'), $str_header);
    $str_header = str_replace('$address', $qry_customer->FieldByName('address'), $str_header);
    $str_header = str_replace('$city', $qry_customer->FieldByName('city')." ".$qry_customer->FieldByName('zip'), $str_header);
    $str_header = str_replace('$order_date', get_date($qry_order->FieldByName('order_date')), $str_header);
	*/
	$customer = $qry_customer->FieldByName('company');
	$address = $qry_customer->FieldByName('address');
	$address2 = $qry_customer->FieldByName('address2');
	$city = $qry_customer->FieldByName('city')." ".$qry_customer->FieldByName('zip');
	$state = $qry_customer->FieldByName('state');
	$state_code = $qry_customer->FieldByName('state_code');
	$gstin = $qry_customer->FieldByName('gstin');
    
    //========================================================
    // content
    //--------------------------------------------------------
    $str_items = '';
    $flt_total = 0;
	$tax_total = 0;
	$taxable_value = 0;
	
    for ($i=0; $i<$qry_items->RowCount(); $i++) {
        
        $item_total = 0;
        
        $calculate_tax = $is_taxed;
        // calculate the tax and the total cost per item billed
        if ($is_taxed == 'Y') {
            if ($qry_order->FieldByName('payment_type') == BILL_CASH) {
                if ($is_cash_taxed == 'Y')
                    $calculate_tax = 'Y';
                else
                    $calculate_tax = 'N';
            }
            else if ($qry_order->FieldByName('payment_type') == BILL_ACCOUNT) {
                if ($is_account_taxed == 'Y')
                    $calculate_tax = 'Y';
                else
                    $calculate_tax = 'N';
            }
        }
        else
            $calculate_tax = 'N';
        
        //$total_quantity = $qry_items->FieldByName('quantity_ordered');
        $total_quantity = $qry_items->FieldByName('quantity_delivered');
        
        $flt_price = $qry_items->FieldByName('price') * $flt_price_increase;
		$flt_discount_price = $flt_price * (1 - ($qry_order->FieldByName('discount')/100));
		
        if ($calculate_tax == 'Y') {
			if ($qry_items->FieldByName('tax_id')==NULL)
				$int_tax_id = 0;
			else
				$int_tax_id = $qry_items->FieldByName('tax_id');
            $tax_amount = calculateTax($flt_price , $int_tax_id);
			
            //$flt_price = $qry_items->FieldByName('price'); //$qry_items->FieldByName('price') + $tax_amount;
            $item_total = $total_quantity * $flt_discount_price; //($total_quantity * ($qry_items->FieldByName('price') + $tax_amount));
        }
        else {
            $tax_amount = 0;
            //$flt_price = $qry_items->FieldByName('price');
            $item_total = $total_quantity * $flt_discount_price;
        }
        
        $qry_tax->Query("
			SELECT * 
			FROM ".Monthalize('stock_tax')." st
			LEFT JOIN ".Monthalize('stock_tax_links')." stl ON (stl.tax_id = st.tax_id)
			LEFT JOIN ".Monthalize('stock_tax_definition')." std ON (std.definition_id = stl.tax_definition_id)
			WHERE st.tax_id = ".$qry_items->FieldByName('tax_id')
		);
			
        $flt_total += $item_total;

        if ($qry_customer->FieldByName('is_other_state')=='N') {
		
			$tax = $qry_tax->FieldByName('definition_percent');

			$tax_rate = $tax / 2;
			$tax_amount = $item_total * ($tax_rate / 100);
			$tax_total += ($tax_amount * 2);
			
			$str_items .= 
				"<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'>".
					"<td>".$qry_items->FieldByName('product_code')."</td>".
					"<td>".$qry_items->FieldByName('product_description')."</td>".
					"<td>".$qry_items->FieldByName('hsn')."</td>".
					"<td>".number_format($total_quantity, 0, '.', '')."</td>".
					"<td>".number_format($flt_price, 2, '.', '')."</td>".
					"<td>".$qry_order->FieldByName('discount')."%</td>".
					"<td>".number_format(($flt_discount_price * $total_quantity), 2, '.', ',')."</td>".
					"<td><table width='100%'><tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'><td width='50px'>".number_format($tax_rate, 2, '.', '')."%</td>".
						"<td>".number_format($tax_amount, 2, '.', '')."</td></tr></table>".
					"<td><table width='100%'><tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'><td width='50px'>".number_format($tax_rate, 2, '.', '')."%</td>".
						"<td>".number_format($tax_amount, 2, '.', '')."</td></tr></table>".
				"</tr>";
		} else {

			$tax = $qry_tax->FieldByName('definition_percent');

			$tax_rate = $tax;
			$tax_amount = ($item_total * ($tax_rate / 100));
			$tax_total += $tax_amount;

			$str_items .= 
				"<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'>".
					"<td>".$qry_items->FieldByName('product_code')."</td>".
					"<td>".$qry_items->FieldByName('product_description')."</td>".
					"<td>".$qry_items->FieldByName('hsn')."</td>".
					"<td>".number_format($total_quantity, 0, '.', '')."</td>".
					"<td>".number_format($flt_price, 2, '.', '')."</td>".
					"<td>".$qry_order->FieldByName('discount')."%</td>".
					"<td>".number_format(($flt_discount_price * $total_quantity), 2, '.', ',')."</td>".
					"<td><table width='100%'><tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'><td width='50px' align='right'>".number_format($tax_rate, 2, '.', '')."%</td>".
						"<td align='right'>".number_format($tax_amount, 2, '.', '')."</td></tr></table>".
				"</tr>";
		}
        $taxable_value += $tax_amount;
		
        $qry_items->Next();
    }

	$flt_total = $flt_total + ($tax_total);
	
    //$flt_discount = $flt_total * $qry_customer->FieldByName('discount') / 100;
    //$flt_tax = calculateTax(($flt_total - $flt_discount), $qry_customer->FieldByName('tax_id'));
    $flt_handling = $qry_order->FieldByName('handling_charge');
    $flt_handling_tax = $flt_handling * 0.12;
    $flt_grand_total = $flt_total + $flt_handling + $flt_handling_tax;
    $flt_total_due = $flt_grand_total - ($qry_order->FieldByName('advance_paid'));
    $flt_total_due = round($flt_total_due);
    $str_amount = ExpandAmount(number_format($flt_total_due,2,'.',''));

    $str_content = str_replace('$items', $str_items, $str_content);
    
    $str_content = str_replace('$subtotal', number_format($flt_total, 2, '.', ','), $str_content);
    $str_content = str_replace('$discount_percentage', $qry_order->FieldByName('discount')."%", $str_content);
    $str_content = str_replace('$discount', number_format($flt_discount, 2, '.', ','), $str_content);
    $str_content = str_replace('$tax_percentage', $str_tax_description , $str_content);
    $str_content = str_replace('$salestax', number_format($flt_tax, 2, '.', ','), $str_content);
    $str_content = str_replace('$handling', number_format($flt_handling, 2, '.', ','), $str_content);
    $str_content = str_replace('$total', number_format($flt_grand_total, 2, '.', ','), $str_content);
    $str_content = str_replace('$paid', number_format($qry_order->FieldByName('advance_paid'), 2, '.', ','), $str_content);
    $str_content = str_replace('$due', number_format($flt_total_due, 2, '.', ','), $str_content);
    $str_content = str_replace('$str_amount_words', $str_amount, $str_content);

    $str_template = $str_title.$str_header.$str_content.$str_footer;
    
//    echo $str_template;

?>

<html><body>
<table width='100%' border='0'>
<tr><td valign='top' height='60px'>

<table width='100%' border='0'>
	<tr>
		<td align='center' colspan='2'>
			<font style='font-family:Arial,sans-serif;font-size:18px;font-weight:bold;'>PROFORMA</br>
			TEAM TRUST</font></br></br>
		</td>
	</tr>
	<tr>
		<td align='left'>
			<img src='../settings/images/Invoice_Header.jpg' width="200px"><br>
			<font style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'><br>
			
			Shakti Auroville 605 101 Tamil Nadu<br>
			Tel:(0413) 2623104<br>
			Email: sales@worktree.in<br></font>
		</td>
	</tr>
</table>

<br><br>

</td></tr>


<!-- HEADER -->


<tr><td valign='top' height='100px'>

<table width='100%' frame="none" cellspacing='0'>

	<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'>
		<td align='right' width='250px'>GSTIN:</td>
		<td width='150px'>33AAATA0037B41L</td>
		<td width='150px'>Transportation Mode:</td>
		<td></td>
	</tr>
	<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'>
		<td align='right'>Tax Is Payable On Reverse Charge:</td>
		<td>N</td>
		<td>Veh. No.:</td>
		<td></td>
	</tr>
	<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'>
		<td align='right'>Proforma No.:</td>
		<td><?php echo $qry_order->FieldByName('order_reference'); ?></td>
		<td>Date & Time of Supply:</td>
		<td></td>
	</tr>
	<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'>
		<td align='right'>Date:</td>
		<td><?php echo $qry_order->FieldByName('order_date'); ?></td>
		<td>Place of Supply:</td>
		<td></td>
	</tr>

</table>

</br>

<table width='100%' frame="none" cellspacing='0'>
	<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:bold;'>
		<td style='border-width:0px;border-color:black;border-style:solid;' colspan='2' align='center'>Details of Receiver (Billed To)</td>
		<!--<td style='border-width:0px;border-color:black;border-style:solid;' colspan='2' align='center'>Details of Consignee (Shipped To)</td>-->
	</tr>

	<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'>
		<td align='right' width='150px' style='font-weight:bold;'>Name:</td>
		<td width='550px'><?php echo $customer;?></td>
		<td align='right' width='120px' style='font-weight:bold;'></td>
		<td width='550px'></td>
	</tr>

	<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'>
		<td align='right' style='font-weight:bold;'>Address:</td>
		<td><?php
			 echo $address; 
			 if (!empty($address2))
			 	echo ", ".$address2;
			 ?>
		</td>
		<td align='right' style='font-weight:bold;'></td>
		<td></td>
	</tr>

	<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'>
		<td align='right' style='font-weight:bold;'>State:</td>
		<td><?php echo $city.", ".$state;?></td>
		<td align='right' style='font-weight:bold;'></td>
		<td></td>
	</tr>
	
	<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'>
		<td align='right' style='font-weight:bold;'>State Code:</td>
		<td><?php echo $state_code;?></td>
		<td align='right' style='font-weight:bold;'></td>
		<td></td>
	</tr>

	<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'>
		<td align='right' style='font-weight:bold;'>GSTIN:</td>
		<td><?php echo $gstin;?></td>
		<td align='right' style='font-weight:bold;'></td>
		<td></td>
	</tr>

</table>

<br>

</td></tr>


<!-- CONTENT -->


<tr><td height="100%" valign='top'>

<table width='100%'>
<tr><td align='center'>

<table width='100%' border='1' cellpadding='5' cellspacing='0' page-break-after="always">
<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:bold;'>
	<td width='60px'><b>Code</b></td>
	<td width='150px'><b>Description</b></td>
	<td width='60px' align='center'><b>HSN</br>Code</b></td>
	<td width='20px' align='center'><b>Qty</b></td>
	<td width='60px' align='center'><b>Price</b></td>
	<td width='40px' align='center'><b>Discount</b></td>
	<td width='40px' align='center'><b>Taxable</br>Value</b></td>
	<?php if ($qry_customer->FieldByName('is_other_state')=='Y') { ?>
		<td width='100px'>
			<table width='100%' >
				<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:bold;'>
					<td width='60px' colspan='2' align='center'>IGST</td>
				</tr>
				<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:bold;'>
					<td>Rate</td><td>Amount</td>
				</tr>
			</table>
		</td>
	<?php } else { ?>
		<td width='100px' cellpadding='0'>
			<table width='100%' border='0' cellspacing='0' >
				<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:bold;'>
					<td colspan='2' align='center'>CGST</td>
				</tr>
				<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:bold;'>
					<td>Rate</td><td>Amount</td>
				</tr>
			</table>
		</td>
		<td width='100px' cellpadding='0'>
			<table width='100%' border='0' cellspacing='0' >
				<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:bold;'>
					<td colspan='2' align='center'>SGST</td>
				</tr>
				<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:bold;'>
					<td>Rate</td><td>Amount</td>
				</tr>
			</table>
		</td>
	<?php } ?>
</tr>

<?php 
	echo $str_items;
?>

<?php if ($qry_customer->FieldByName('is_other_state')=='Y') { ?>
	<tr  style='font-family:Arial,sans-serif;font-size:12px;font-weight:bold;'>
		<td colspan='7'></td>
		<td width='100px'>
			<table width='100%' >
				<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:bold;'>
					<td width='50px'></td>
					<td align='right'><?php echo number_format(($tax_total),2); ?></td>
				</tr>
			</table>
		</td>
	</tr>
<?php } else { ?>
	<tr  style='font-family:Arial,sans-serif;font-size:12px;font-weight:bold;'>
		<td colspan='7'></td>
		<td width='100px' cellpadding='0'>
			<table width='100%' border='0' cellspacing='0' >
				<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:bold;'>
					<td width='50px'></td>
					<td><?php echo number_format(($tax_total/2),2); ?></td>
				</tr>
			</table>
		</td>
		<td width='100px' cellpadding='0'>
			<table width='100%' border='0' cellspacing='0' >
				<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:bold;'>
					<td width='50px'></td>
					<td><?php echo number_format(($tax_total/2),2); ?></td>
				</tr>
			</table>
		</td>
	</tr>
<?php } ?>

<?php 
	if ($qry_customer->FieldByName('is_other_state')=='Y')
		$colspan = 7;
	else
		$colspan = 8;

?>

<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'>
	<td align='right' colspan='<?php echo $colspan;?>' style='border-bottom:none;border-top:none;border-right:none;'>
		<b>Subtotal:</b></td>
		<td align='right' width='75px'><?php echo number_format($flt_total, 2, '.', ','); ?>
	</td>
</tr>

<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'>
	<td align='right' colspan='<?php echo $colspan;?>' style='border-bottom:none;border-top:none;border-right:none;'>
		<b>Handling:</b>
	</td>
	<td align='right'><?php echo number_format($flt_handling, 2, '.', ','); ?></td>
</tr>

<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'>
	<td align='right' colspan='<?php echo $colspan;?>' style='border-bottom:none;border-top:none;border-right:none;'>
		<b>Handling GST 12%:</b>
	</td>
	<td align='right'><?php echo number_format($flt_handling_tax, 2, '.', ','); ?></td>
</tr>

<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'>
	<td align='right' colspan='<?php echo $colspan;?>' style='border-bottom:none;border-top:none;border-right:none;'>
		<b>Total:</b>
	</td>
	<td align='right'><?php echo number_format($flt_grand_total, 2, '.', ','); ?></td>
</tr>

<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'>
	<td align='right' colspan='<?php echo $colspan;?>' style='border-bottom:none;border-top:none;border-right:none;'>
		<b>Amount Paid:</b>
	</td>
	<td align='right'><?php echo number_format($qry_order->FieldByName('advance_paid'), 2, '.', ','); ?></td>
</tr>

<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'>
	<td align='right' colspan='<?php echo $colspan;?>' style='border-top:none;border-right:none;'>
		<b>Amount Due:</b>
	</td>
	<td align='right'><b><?php echo number_format($flt_total_due, 2, '.', ','); ?></b></td>
</tr>

<tr style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'>
	<td colspan='<?php echo $colspan+1;?>'>
		<b>Amount:</b> Rupees <?php echo  $str_amount; ?>.
	</td>
</tr>

</table>

</td></tr>
</table>

<table width='75%'>
<tr><td align='right'>

</td></tr>
</table>

</td></tr>


<!-- FOOTER -->


<tr><td valign='bottom'>

<table width='100%' border='0'>
	<tr>
		<td align='right' valign='bottom'>
			<font style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'>
			Authorized Signatory</br></br></br>
			for Worktree
			</font>
		</td>
	</tr>
</table>


<table width='100%' border='0'>
<tr>
<td align='center' valign='bottom'><font style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'>
Bankers: State Bank of India, Auroville Branch
</font></td>
</tr>
</table>

</td></tr>
</table>
</body></html>
