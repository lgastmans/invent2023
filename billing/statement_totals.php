<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db_mysqli.php");
	require_once("../common/tax.php");
	require_once("../common/product_funcs.inc.php");	


	$_SESSION["int_bills_menu_selected"] = 9;


	$consignment = 'N'; // BN is direct sales , Y is consignment sales
	if (IsSet($_GET["supplier_type"]))
		$consignment = $_GET["supplier_type"];


	$str_include_tax = 'Y';


	/*
		company details
	*/
	$qry_company = $conn->Query("SELECT * FROM company WHERE 1");	
	$company = $qry_company->fetch_object();


	/*
		check whether to display commissions
	*/
	$display_commissions = false;
	$sql = "
		SELECT SUM(commission_percent + commission_percent_2 + commission_percent_3) AS commissions
		FROM stock_supplier
		WHERE (is_supplier_delivering='Y')
			AND (is_active = 'Y')
	";
	$qry_commission = $conn->Query($sql);
	if ($qry_commission->num_rows>0){
		$commissions = $qry_commission->fetch_object();
		$display_commissions = ($commissions->commissions>0);
	}


	/*
		fetch all suppliers
	*/
	$sql = "
		SELECT *
		FROM stock_supplier ss 
		WHERE (ss.is_supplier_delivering = '$consignment')
			AND (is_active = 'Y')
		ORDER BY ss.supplier_name
	";

	$qry_supplier = $conn->Query($sql);


	/*
		Direct Sales

			Discount on Selling Price, not on Buying Price
			Profit is difference on both selling prices 
			The tax filed by retailer is difference on both taxes

		Consignment Sales

			Buying Price ignored
			Discount on Selling Price
			Commissions calculated on Buying Price (before discount)
			Profit is the sum of commissions
	*/

?>

<html>
<head>
	<style>
		table {
			font-size:10pt;
		}
		th {
			min-width:100px;
		}
		td {
			text-align: right;
		}
		.white {
			background-color: rgb(255, 255, 255);
		}
		.colored {
			background-color: rgb(245, 245, 245);
		}
	</style>
</head>
<body>
	<font style="font-family:Verdana,sans-serif;">

	<table border=1 cellpadding=7 cellspacing=0>

		<tr bgcolor=#dfdfdf>

			<th colspan="2">Supplier</th>

			<th colspan="5">Selling Price</th>

			<?php if ($consignment=='N')  { ?>

				<th colspan="3">Buying Price</th>

			<?php } else if (($consignment=='Y') && ($display_commissions)) { ?>

				<th colspan="3">Commissions</th>

			<?php } ?>

			<?php if ($consignment=='Y') { ?>
				<th></th>
			<?php } ?>

			<th></th>
		</tr>

		<tr  bgcolor=#dfdfdf>

			<th>Name</th>

			<th>Account No.</th>

			<th>S. Price<br>Amount</th>
			<th>Discount<br>Amount</th>
			<th>Taxable<br>Amount</th>
			<th>S. Price<br>GST</th>
			<th>S. Price<br>Total</th>

			<?php if ($consignment=='N')  { ?>

				<th>B. Price<br>Amount</th>
				<th>B. Price<br>GST</th>
				<th>B. Price<br>Total<br>( Given )</th>

			<?php } else if (($consignment=='Y') && ($display_commissions)) { ?>

				<th>Commission<br>1</th>
				<th>Commission<br>2</th>
				<th>Commission<br>3</th>

			<?php } ?>

			<?php if ($consignment=='Y') { ?>
				<th>Given</th>
			<?php } ?>

			<th>Profit</th>
			<!-- <th>Tax to<br>File</th> -->

    	</tr>

    <?php

			$gtotal_sprice_amount = 0;
			$gtotal_sprice_discount = 0;
			$gtotal_sprice_gst = 0;

    		$gtotal_bprice_amount = 0;
    		$gtotal_bprice_discount = 0;
    		$gtotal_bprice_gst = 0;

			$gtotal_commission1 = 0;
			$gtotal_commission2 = 0;
			$gtotal_commission3 = 0;

    		$gtotal_profit = 0;
    		$gtotal_tax = 0;

			$i=0;
			
			while ($supplier = $qry_supplier->fetch_object()) {

				if ($i % 2 == 1) 
					$bgcolor = "colored";
				else 
					$bgcolor = "white"; 


				echo "<tr class='".$bgcolor."'>";

				
				if ($supplier->is_active == 'Y')
					echo "<td>".$supplier->supplier_name."</td>";
				else
					echo "<td><font color='red'>".$supplier->supplier_name."</font></td>";
				echo "<td align=right>".$supplier->account_number." ".$supplier->gstin."</td>";
				

				/*
					generate the total of all billed items for current supplier
				*/
				$sql = "
					SELECT bi.*
					FROM 
						".Monthalize('bill')." b, 
						".Monthalize('bill_items')." bi, 
						".Yearalize('stock_batch')." sb
					WHERE (b.bill_id = bi.bill_id)
						AND (b.bill_status = ".BILL_STATUS_RESOLVED.")
						AND (sb.supplier_id = ".$supplier->supplier_id.")
						AND (sb.batch_id = bi.batch_id)
						AND (sb.product_id = bi.product_id)

				";

				$qry_items = $conn->Query($sql);

				if (!$qry_items)
					die(mysqli_error($conn));

				
				$sprice_amount = 0;
				$sprice_discount = 0;
				$sprice_gst = 0;
				$bprice_amount = 0;
				$bprice_discount = 0;
				$bprice_gst = 0;
				$commission1_total = 0;
				$commission2_total = 0;
				$commission3_total = 0;

				$str_include_tax = 'Y';
				if ($company->gstin == $supplier->gstin)
				 	$str_include_tax = 'N';


				while ($item = $qry_items->fetch_object()) {


					$quantity = $item->quantity + $item->adjusted_quantity;


					$commission1 = 0;
					$commission2 = 0;
					$commission3 = 0;
					$tax_amount = 0;

					/*
						selling price
					*/
					if ($str_include_tax == 'Y') {

						if ($item->discount > 0) {

							$discount_price = round(($item->price * (1 - ($item->discount/100))), 3);
							$discount = round(($quantity * $item->price) * ($item->discount/100), 3);
							$tax_amount = calculateTax($quantity * $discount_price, $item->tax_id);
							$amount = round(($quantity * $item->price), 3);

						}

						else {

							$discount = 0;
							$tax_amount = calculateTax($item->price * $quantity, $item->tax_id);
							$amount = round(($quantity * $item->price), 3);

						}

					}
					else {

						if ($item->discount > 0) {

							$discount_price = round(($item->price * (1 - ($item->discount/100))), 3);
							$discount = round(($quantity * $item->price) * ($item->discount/100), 3);
							$tax_amount = 0;
							$amount = round(($quantity * $item->price), 3);


						}
						else {

							$amount = ($item->price * $quantity);
							$tax_amount = 0;
							$discount = 0;

						}
					}

					/*
						Consignment Sales
						Commissions calculated on Selling Price (before discount)
					*/
					if (($consignment=='Y') && ($display_commissions)) {
						$commission1 = round(($item->price * $quantity) * ($supplier->commission_percent/100),3);
						$commission2 = round(($item->price * $quantity) * ($supplier->commission_percent_2/100),3);
						$commission3 = round(($item->price * $quantity) * ($supplier->commission_percent_3/100),3);
					}

					$sprice_amount += $amount;
					$sprice_discount += $discount;
					$sprice_gst += $tax_amount;

		    		$gtotal_sprice_amount += $amount;
		    		$gtotal_sprice_discount += $discount;
		    		$gtotal_sprice_gst += $tax_amount;




					/*
						buying price
					*/
					
					$bprice = $item->bprice;

					if ($item->bprice == 0) 
						$bprice = getBuyingPrice($item->product_id, $item->batch_id);


					if ($str_include_tax == 'Y') {

						/*
							
							Discounts ignored for Consignment sales


						if (($item->discount > 0) && ($consignment=='Y')) {

							$discount_price = round(($item->bprice * (1 - ($item->discount/100))), 3);
							$discount = round(($quantity * $item->bprice) * ($item->discount/100), 3);
							$tax_amount = calculateTax($quantity * $discount_price, $item->tax_id);
							$amount = round(($quantity * $item->bprice), 3);


						}

						else {
						*/
							$tax_amount = calculateTax($bprice * $quantity, $item->tax_id);
							$amount = round(($quantity * $bprice), 3);
							$discount = 0;

						//}

					}
					else {

						/*
						if ($item->discount > 0) {

							$amount = ($item->bprice * (1 - ($item->discount/100)) * $quantity);
							$discount =  round(($item->bprice * $quantity) * ($item->discount/100), 3);

						}
						else {
						*/
							$tax_amount = 0;
							$amount = ($bprice * $quantity);
							$discount = 0;

						//}
					}

					$bprice_amount += $amount;
					$bprice_discount += $discount;
					$bprice_gst += $tax_amount;

					$gtotal_bprice_amount += $amount;
					$gtotal_bprice_discount += $discount;
					$gtotal_bprice_gst += $tax_amount;


					$commission1_total += $commission1;
					$commission2_total += $commission2;
					$commission3_total += $commission3;

					$gtotal_commission1 += $commission1;
					$gtotal_commission2 += $commission2;
					$gtotal_commission3 += $commission3;


				} // end while ($item = ... )



				echo "<td>".number_format($sprice_amount, 2, '.', ',')."</td>";
				echo "<td>".number_format(($sprice_discount), 2, '.', ',')."</td>";
				echo "<td>".number_format(($sprice_amount - $sprice_discount), 2, '.', ',')."</td>";
				echo "<td>".number_format($sprice_gst, 2, '.', ',')."</td>";
				echo "<td>".number_format((($sprice_amount - $sprice_discount) + $sprice_gst), 2, '.', ',')."</td>";


				if ($consignment=='N') { 
					echo "<td>".number_format($bprice_amount, 2, '.', ',')."</td>";
					echo "<td>".number_format($bprice_gst, 2, '.', ',')."</td>";
					echo "<td>".number_format(($bprice_amount + $bprice_gst), 2, '.', ',')."</td>";
				}

				if (($consignment=='Y') && ($display_commissions)) {
					echo "<td>".number_format($commission1_total, 2, '.', ',')."</td>";
					echo "<td>".number_format($commission2_total, 2, '.', ',')."</td>";
					echo "<td>".number_format($commission3_total, 2, '.', ',')."</td>";
				}


				/*
					Consignment - amount given to supplier
				*/
				if ($consignment=='Y') { 
					$given = ($sprice_amount - $sprice_discount) - ($commission1_total + $commission2_total + $commission3_total);
					echo "<td>".number_format($given, 2, '.', ',')."</td>";
				}


				/*
					Profit
						Difference in discounted prices
						minus
						Difference in tax payable

				*/
				$profit = 0;

				if ($consignment=='N') {

					$profit = ($sprice_amount - $sprice_discount) - ($bprice_amount - $bprice_discount);
					$gtotal_profit += $profit;

				} 
				else {
					
					$profit = ($commission1_total + $commission2_total + $commission3_total);
					$gtotal_profit += $profit;

				}

				echo "<td>".number_format($profit, 2, '.', ',')."</td>";

				echo "<tr>";


				$i++;

			} // end while ($supplier = ... )


    		echo "<tr style='font-weight:bold;'>";
			echo "<td colspan=2>TOTALS</td>";
			echo "<td>".number_format($gtotal_sprice_amount, 2, '.', ',')."</td>";
			echo "<td>".number_format($gtotal_sprice_discount, 2, '.', ',')."</td>";
			echo "<td>".number_format(($gtotal_sprice_amount - $gtotal_sprice_discount), 2, '.', ',')."</td>";
			echo "<td>".number_format($gtotal_sprice_gst, 2, '.', ',')."</td>";
			echo "<td>".number_format((($gtotal_sprice_amount - $gtotal_sprice_discount) + $gtotal_sprice_gst), 2, '.', ',')."</td>";


			if ($consignment=='N') { 
				echo "<td>".number_format($gtotal_bprice_amount, 2, '.', ',')."</td>";
				echo "<td>".number_format($gtotal_bprice_gst, 2, '.', ',')."</td>";
				echo "<td>".number_format(($gtotal_bprice_amount + $gtotal_bprice_gst), 2, '.', ',')."</td>";
			}

			if (($consignment=='Y') && ($display_commissions)) {
				echo "<td>".number_format($gtotal_commission1, 2, '.', ',')."</td>";
				echo "<td>".number_format($gtotal_commission2, 2, '.', ',')."</td>";
				echo "<td>".number_format($gtotal_commission3, 2, '.', ',')."</td>";
			}

			if ($consignment=='Y') { 
				/*
					if consignment supplier, the buying price is irrelevant
					the given amount is the total selling price amount
						minus discount
						minus commission
				*/
				$given = ($gtotal_sprice_amount - $gtotal_sprice_discount) - ($gtotal_commission1 + $gtotal_commission2 + $gtotal_commission3);
				echo "<td>".number_format($given, 2, '.', ',')."</td>";
			}

			echo "<td>".number_format(($gtotal_profit), 2, '.', ',')."</td>";

			echo "</tr>";

    ?>

  </table>
  <br>
  </font>

</body>
</html>