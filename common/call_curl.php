<?php 
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	
if($_GET['action']=="weight"){
	$qry = "
		SELECT smu.is_decimal
		FROM stock_product sp
			INNER JOIN stock_measurement_unit smu ON (sp.measurement_unit_id = smu.measurement_unit_id)
		WHERE (product_code = '".$_GET['product']."')
			AND (deleted = 'N') AND (smu.is_decimal = \"Y\")
		";
	//echo $qry;
	$result_search = new Query($qry);
	if($result_search->RowCount() > 0){
		$weight = cURL("http://".$weight_clients[$_GET['ip']]."/getWeight.php/");
		echo floatval($weight);
	}
	else
		echo 1;
}


function cURL($url){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);		//The URL to fetch
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);	//return the transfer as a string of the return value instead of outputting it out directly.
	curl_setopt($ch, CURLOPT_HEADER, FALSE);	//do not include the header in the output. 
	curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);	//force the use of a new connection instead of a cached one
	curl_setopt($ch, CURLOPT_POST, TRUE);		//do a regular HTTP POST
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	$returnVal = curl_exec($ch);
	curl_close($ch);
	return ($returnVal);
}
?>