<?php

	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");
	require_once("../../common/tax.php");


	$int_decimals = 2;
	
	$sql_settings = new Query("
		SELECT *
		FROM user_settings
		WHERE storeroom_id = ".$_SESSION['int_current_storeroom']
	);
	if ($sql_settings->RowCount() > 0)
		$int_decimals = $sql_settings->FieldByName('bill_decimal_places');



	if (IsSet($_GET["del"])) {

		/*
			get the number of entries found in the session array arr_total_qty for the given product code
		*/
		$int_TotalRows = 0;
		for ($i=0; $i<count($_SESSION["arr_total_qty"]); $i++) {
			if ($_SESSION["arr_total_qty"][$i][0] == $_SESSION['current_code'])
				$int_TotalRows = $int_TotalRows + 1;
		}

		/*
			remove the row from session array arr_total_qty
		*/
		$_SESSION["arr_total_qty"] = array_delete($_SESSION["arr_total_qty"], $_GET["atIndex"]);
	}

	
	/*
		iterate through the session array arr_total_qty
	*/
	$flt_total = 0;

	for ($i=0; $i<count($_SESSION["arr_total_qty"]); $i++) {

		$adjusted = 0;
		if (isset($_SESSION["arr_total_qty"][$i][5]))
			$adjusted = $_SESSION["arr_total_qty"][$i][5];

		$tmp_qty = number_format($_SESSION["arr_total_qty"][$i][2] + $adjusted, 3,'.','');
		$tmp_price = $_SESSION["arr_total_qty"][$i][6];

		$flt_price_total = round(($tmp_qty * $tmp_price), 2);

		/*
			save the total per item 
		*/
		$_SESSION["arr_total_qty"][$i][10] = round($flt_price_total,2);

		$billdata[$i]['id'] 		= 2;
		$billdata[$i]['code'] 		= $_SESSION["arr_total_qty"][$i][0];
		$billdata[$i]['batch'] 		= $_SESSION["arr_total_qty"][$i][1];
		$billdata[$i]['invno'] 		= (isset($_SESSION["arr_total_qty"][$i]['invno'])) ? $_SESSION["arr_total_qty"][$i]['invno'] : "";
		$billdata[$i]['invdt'] 		= (isset($_SESSION["arr_total_qty"][$i]['invdt'])) ? $_SESSION["arr_total_qty"][$i]['invdt'] : "";
		$billdata[$i]['description'] = $_SESSION["arr_total_qty"][$i][12];
		$billdata[$i]['quantity'] 	= $_SESSION["arr_total_qty"][$i][2];
		$billdata[$i]['bprice'] 	= (isset($_SESSION["arr_total_qty"][$i]['buying_price'])) ? number_format($_SESSION["arr_total_qty"][$i]['buying_price'], $int_decimals,'.','') : "";
		$billdata[$i]['sprice'] 	= number_format($_SESSION["arr_total_qty"][$i][6], $int_decimals,'.','');
		$billdata[$i]['tax'] 		= (isset($_SESSION["arr_total_qty"][$i][8])) ? $_SESSION["arr_total_qty"][$i][8] : "";
		$billdata[$i]['total'] 		= number_format($_SESSION["arr_total_qty"][$i][10], $int_decimals,'.','');


		/*
			in the case of a manual transfer of extra stock, show this in the bill
			as another line. The price, however, is included in the line above
		*/
		if ( (isset($_SESSION["arr_total_qty"][$i][5])) && ($_SESSION["arr_total_qty"][$i][5] > 0) ) {

			$strList = StuffWithBlank("", 6)." ".
			StuffWithBlank("", 10)." ".
			PadWithBlank($_SESSION["arr_total_qty"][$i][12], 30)." ".
			StuffWithBlank($_SESSION["arr_total_qty"][$i][5], 5);

			echo "<option value=\"".$i."\">".$strList;
		}

		/*
			calculate the bill total
		*/
		$flt_total += $_SESSION["arr_total_qty"][$i][10];

	}


	if (empty($_SESSION['arr_total_qty'])) {

		$billdata = array();

	}

	$billdata = array_reverse($billdata);

	//$fmt = new NumberFormatter( 'en_IN', NumberFormatter::CURRENCY );
	//$flt_total = $fmt->formatCurrency($flt_total, "Rs");

	$_SESSION['bill_total'] = number_format($flt_total,2,'.','');

	//$flt_total = money_format('%i', $flt_total);

	$ret = array("data"=>$billdata, "billtotal" => number_format($flt_total,2,'.',''), "num_rows" => count($billdata));

	echo json_encode($ret);

?>