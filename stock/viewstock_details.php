<?php
	require_once("../include/db.inc.php");
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	
	$arr=array('zero results');

	if (isset($_POST['product_code'])) {

		$strProductCode = $_POST['product_code'];

		// check whether the code exists
		$result_search = new Query("
			SELECT sp.product_id, sp.product_code, sp.product_description, sp.tax_id, sp.is_available, 
				sp.margin_percent, smu.measurement_unit, smu.is_decimal
			FROM stock_product sp
				INNER JOIN stock_measurement_unit smu ON (sp.measurement_unit_id = smu.measurement_unit_id)
			WHERE ((product_code = '".$strProductCode."') OR (product_bar_code = '".$strProductCode."'))
				AND (deleted = 'N')");

		if ($result_search->GetErrorMessage()<>"") die ($result_search->GetErrorMessage());

		$description = '__NOT_FOUND';
		$buying_price = 0;
		$selling_price = 0;
		$tax_id = 0;
		$current_stock = 0;
		$adjusted_stock = 0;
		
		if ($result_search->RowCount() > 0) {

			$qry = new Query("
				SELECT * 
				FROM ".Monthalize('stock_storeroom_product')."
				WHERE (product_id = ".$result_search->FieldByName('product_id').") 
				AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
			");

			$buying_price = $qry->FieldByName('buying_price');
			$selling_price = $qry->FieldByName('sale_price');
			
			$description = $result_search->FieldByName('product_description');
		}

		

		$arr['description'] = $description;
		$arr['buying_price'] = $buying_price;
		$arr['selling_price'] = $selling_price;
		$arr['product_id'] = $result_search->FieldByName('product_id');
		$arr['use_batch_price'] = $qry->FieldByName('use_batch_price');
		$arr['minimum_quantity'] = $qry->FieldByName('stock_minimum');
	}


	echo json_encode($arr);

	
?>