<?
	include("../../include/const.inc.php");
	include("../../include/session.inc.php");
	require_once("../../include/db_mysqli.php");
	require_once("../../common/product_funcs.inc.php");
	
	$supplier_id = 0;
	if (isset($_GET['supplier']))
		$supplier_id = $_GET['supplier'];

	$qry = $conn->query("
		SELECT supplier_id, supplier_name, commission_percent, commission_percent_2, commission_percent_3
		FROM stock_supplier
		WHERE supplier_id = $supplier_id
	");	
	$obj = $qry->fetch_object();


	$error_str = '';
	$error = false;

	if (!empty($obj->supplier_name)) {


		$sql = "SELECT * FROM stock_product WHERE supplier_id = ".$obj->supplier_id;
		$qry_products = $conn->query($sql);


		$commission = $obj->commission_percent + $obj->commission_percent_2 + $obj->commission_percent_3;

		echo $obj->supplier_name." commission: ".$commission."<br><br>";

		$qry = $conn->query("START TRANSACTION");


		while ($product = $qry_products->fetch_object()) {

			$bprice = getBuyingPrice($product->product_id);
			$sprice = number_format(round((float)($bprice / ((100 - $commission) / 100)),2),2,'.','');


			$sql ="
				UPDATE ".Monthalize('stock_storeroom_product')."
				SET 
					sale_price = ".$sprice.",
					use_batch_price = 'N'
				WHERE product_id=".$product->product_id."
					AND storeroom_id=".$_SESSION['int_current_storeroom'];
			$qry = $conn->query($sql);

			if (!$qry) {
				$error_str .= $obj->product_code.": ".mysqli_error($conn)."<br>";
				$error = true;
			}

			echo $product->product_code.",".$product->product_description.",".$bprice.",".$sprice."<br>";

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

			echo "<br>Selling prices updated for ".$obj->supplier_name;

		}
		else
			echo $error_str;


	?>

    <br><br>

    <input type='button' name='action' value='Back' class='settings_button' onclick='javascript:goBack()'>
    
    <br><br>

</body>
</html>