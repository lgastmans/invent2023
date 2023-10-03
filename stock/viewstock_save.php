<?php

	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");

	$arr = array();
//print_r($_POST);
	if (IsSet($_POST['product_id'])) {

		$int_id = $_POST["product_id"];

		$str_use_batch_price = $_POST['use_batch_price'];
			
		if ($str_use_batch_price == 'Y')
			$sql ="
				UPDATE ".Monthalize('stock_storeroom_product')."
				SET 
					stock_minimum = ".$_POST['minimum_quantity'].",
					use_batch_price = '".$str_use_batch_price."'
				WHERE product_id=".$int_id."
					AND storeroom_id=".$_SESSION['int_current_storeroom'];
		else
			$sql ="
				UPDATE ".Monthalize('stock_storeroom_product')."
				SET 
					stock_minimum = ".$_POST['minimum_quantity'].",
					buying_price = ".$_POST['buying_price'].",
					sale_price = ".$_POST['selling_price'].",
					use_batch_price = '".$str_use_batch_price."'
				WHERE product_id=".$int_id."
					AND storeroom_id=".$_SESSION['int_current_storeroom'];


		$qry_save = new Query($sql);
		
		if ($qry_save->b_error == true) {
			$arr['msg'] = "error updating: ".mysql_error();
		}
		else {
			$arr['msg'] = "successfully updated";
		}
	}

	echo json_encode($arr);

?>