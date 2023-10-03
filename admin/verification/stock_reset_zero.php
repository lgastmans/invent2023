<?
	include("../../include/const.inc.php");
	include("../../include/session.inc.php");
	require_once("../../include/db_mysqli.php");
	
	$supplier_id = 0;
	if (isset($_GET['supplier']))
		$supplier_id = $_GET['supplier'];

	$qry = $conn->query("
		SELECT supplier_id, supplier_name, supplier_phone
		FROM stock_supplier
		WHERE supplier_id = $supplier_id
	");	
	$obj = $qry->fetch_object();



	$error = false;

	if (!empty($obj->supplier_name)) {


		$qry = $conn->query("START TRANSACTION");


		/*
			stock_balance
		*/
		$qry = $conn->query("
			UPDATE ".Yearalize('stock_balance')." sb
			INNER JOIN stock_product sp ON (sp.supplier_id = ".$obj->supplier_id.")
			SET stock_opening_balance = 0,
				stock_closing_balance = 0,
				stock_in = 0,
				stock_out = 0,
				stock_sold = 0,
				stock_damaged = 0,
				stock_wasted = 0,
				stock_returned = 0,
				stock_received = 0,
				stock_mismatch_addition = 0,
				stock_mismatch_deduction = 0,
				stock_cancelled = 0
			WHERE sb.product_id = sp.product_id
				AND (sb.storeroom_id = ".$_SESSION["int_current_storeroom"].")
		");
		if (!$qry) {
			echo "stock_balance: ".mysqli_error($conn)."<br>";
			$error = true;
		}

		/*
			stock_batch
		*/
		$qry = $conn->query("
			UPDATE ".Yearalize('stock_batch')."
			SET opening_balance = 0
			WHERE supplier_id = ".$obj->supplier_id."
				AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
		");
		if (!$qry) {
			echo "stock_batch: ".mysqli_error($conn)."<br>";
			$error = true;
		}


		/*
			stock_storeroom_batch
		*/
		$qry = $conn->query("
			UPDATE ".Monthalize('stock_storeroom_batch')." ssb
			INNER JOIN stock_product sp ON (sp.supplier_id = ".$obj->supplier_id.")
			SET stock_available = 0,
				stock_reserved = 0,
				bill_reserved = 0,
				stock_ordered = 0
			WHERE ssb.product_id = sp.product_id
				AND (ssb.storeroom_id = ".$_SESSION["int_current_storeroom"].")
		");
		if (!$qry) {
			echo "stock_storeroom_batch: ".mysqli_error($conn)."<br>";
			$error = true;
		}


		/*
			stock_storeroom_product
		*/
		$qry = $conn->query("
			UPDATE ".Monthalize('stock_storeroom_product')." ssp
			INNER JOIN stock_product sp ON (sp.supplier_id = ".$obj->supplier_id.")
			SET stock_current = 0,
				stock_reserved = 0,
				stock_ordered = 0,
				stock_adjusted = 0
			WHERE ssp.product_id = sp.product_id
				AND (ssp.storeroom_id = ".$_SESSION["int_current_storeroom"].")
		");
		if (!$qry) {
			echo "stock_storeroom_product: ".mysqli_error($conn)."<br>";
			$error = true;
		}


		if (!$error) {

			$qry = $conn->query("COMMIT");

		}
		else {

			$qry = $conn->query("ROLLBACK");

		}

	}



?>
<html>
<head>
    <script language='javascript'>
        function goBack() {
            document.location = '../index_verification_tools.php';
        }
    </script>
</head>

<body leftmargin='20px' rightmargin='20px' topmargin='20px' bottommargin='20px'>

	<?php

		if (!$error) {

			echo "All stock reset to zero.";

		}


	?>

    <br><br>

    <input type='button' name='action' value='Back' class='settings_button' onclick='javascript:goBack()'>
    
    <br><br>

</body>
</html>