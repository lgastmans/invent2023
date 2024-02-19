<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db_mysqli.php");
	require_once("../common/tax.php");
	require_once("../common/product_funcs.inc.php");	
	require_once("statement_totals_func.php");

	$_SESSION["int_bills_menu_selected"] = 9;


	$consignment = 'N'; // BN is direct sales , Y is consignment sales
	if (IsSet($_GET["supplier_type"]))
		$consignment = $_GET["supplier_type"];


	$str_include_tax = 'Y';

	$display_gstin = "Y";
	if (isset($_GET['display_gstin']))
		$display_gstin = $_GET['display_gstin'];


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

			<?php if ($display_gstin == 'Y') { ?>
				<th>Account No.<br>GSTIN</th>
			<?php } else { ?>
				<th>Account No.</th>
			<?php } ?>

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

	    	if (isset($_GET['load']))
	    		echo display_data();

	    ?>

	</table>
	<br>

	</font>

</body>

</html>
