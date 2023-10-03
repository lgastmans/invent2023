<?
	require_once("../../include/db.inc.php");

	function getClient($client_id) {
		$str_res = 'ERROR|Not Found';
		
		$qry = new Query("
			SELECT address, city, zip, discount
			FROM customer
			WHERE id = ".$client_id
		);
		
		if ($qry->RowCount() > 0) {
			$str_res = "OK|".
				$qry->FieldByName('address')."|".
				$qry->FieldByName('city')." ".$qry->FieldByName('zip')."|".
				$qry->FieldByName('discount');
		}
		
		return $str_res;
	}


	if (!empty($_GET['live'])) {
		if (!empty($_GET['client_id'])) {
			echo getClient($_GET['client_id']);
			die();
		} else {
			die(0);
		}
	}
?>