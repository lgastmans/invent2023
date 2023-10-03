<?php
	require_once("../../include/const.inc.php");
	//require_once("../../common/product_funcs.inc.php");
	require_once("xml_parser_class.php");
	require_once("DB.php");
	require_once("db_params.php");

	function cleanData($str) { 
	 	// $str = preg_replace("/\t/", "\\t", $str); 
	 	// $str = preg_replace("/\r?\n/", "\\n", $str); 
	 	// if(strstr($str, '"')) 
	 	// 	$str = '"' . str_replace('"', '""', $str) . '"'; 
	 	return $str;
	}

	$filename = "idoya_data_" . date('Y-m-d') . ".xls";

	//header("Content-Type: application/text; name=".$filename);
	header("Content-Type: application/vnd.ms-excel");
	header("Content-Transfer-Encoding: binary");
	header("Content-Disposition: attachment; filename=".$filename);
	header("Expires: 0");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");

	$qry =& $conn->query("
		SELECT 
			b.bill_number, 
			b.date_created,
			
			c.company, 
			c.city,

			bi.product_description, 
			sp.product_code,
			sc.category_description,
			sp.mrp,
			(bi.quantity + bi.adjusted_quantity) AS quantity, 
			bi.price,
			bi.discount

		FROM ".Monthalize('bill')." b

		INNER JOIN ".Monthalize('bill_items')." bi ON (bi.bill_id = b.bill_id)
		INNER JOIN `stock_product` sp ON (sp.product_id = bi.product_id)
		LEFT JOIN `customer` c ON (c.id = b.CC_id)
		LEFT JOIN `stock_category` sc ON (sc.category_id = sp.category_id)

		
	");

	while ($obj =& $qry->fetchRow()) {

		$str = 
			$obj->bill_number . "\t".
			$obj->date_created . "\t".
			$obj->company . "\t".
			$obj->city . "\t".
			$obj->product_description . "\t".
			$obj->product_code . "\t".
			$obj->category_description . "\t".
			$obj->mrp . "\t".
			$obj->quantity . "\t".
			$obj->price . "\t".
			$obj->discount . "\t".
			"\r\n";

		echo $str;

	}

?>