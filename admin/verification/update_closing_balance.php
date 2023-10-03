<?
	if (file_exists('../include/const.inc.php'))
		require_once('../include/const.inc.php');
	else if (file_exists('../../include/const.inc.php'))
		require_once('../../include/const.inc.php');
	require_once('session.inc.php');
	require_once('db_params.php');
	
	$str_query = "
		SELECT *
		FROM ".Monthalize('stock_storeroom_product')." ssp
		WHERE (ssp.storeroom_id = ".$_SESSION['int_current_storeroom'].")
	";
	$qry_verify = $conn->query($str_query);
	if(PEAR::isError($qry_verify)) {
		die('Error message : '.$qry_verify->getMessage()."::".$qry_verify->getDebugInfo());
	}
	
?>
<html>
<head>
	<link href="../../include/styles.css" rel="stylesheet" type="text/css">
	<script language='javascript'>
		function goBack() {
			document.location = '../index_verification_tools.php';
		}
		function applyCorrections() {
			if (confirm('Are you sure?'))
				document.location = 'stock_closing_balance.php?action=apply_corrections';
		}
	</script>
</head>

<body leftmargin='20px' rightmargin='20px' topmargin='20px' bottommargin='20px'>

<?
	while ($obj =& $qry_verify->fetchRow()) {
		$tmp = "
			SELECT *
			FROM ".Yearalize('stock_balance')."
			WHERE (product_id = ".$obj->product_id.")
				AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
				AND (balance_month = ".$_SESSION["int_month_loaded"].")
				AND (balance_year = ".$_SESSION["int_year_loaded"].")
		";
		$qry = $conn->query($tmp);
		
		/*
			get the closing balance from the month before
		*/
		$qry_balance = $conn->query("
			SELECT *
			FROM ".Yearalize('stock_balance')."
			WHERE (product_id = ".$obj->product_id.")
				AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
				AND (balance_month = ".($_SESSION["int_month_loaded"] -1).")
				AND (balance_year = ".$_SESSION["int_year_loaded"].")
		");
		$opening_balance = 0;
		if ($qry_balance->numRows() > 0) {
			$obj_balance = $qry_balance->fetchRow();
			$opening_balance = $obj_balance->stock_closing_balance;
		}
		
		if ($qry->numRows() == 0) {
			$str = "
				INSERT INTO ".Yearalize('stock_balance')."
				(
					product_id,
					storeroom_id,
					balance_month,
					balance_year,
					stock_opening_balance,
					stock_closing_balance
				)
				VALUES(
					".$obj->product_id.",
					".$_SESSION['int_current_storeroom'].",
					".$_SESSION['int_month_loaded'].",
					".$_SESSION['int_year_loaded'].",
					".$opening_balance.",
					".$obj->stock_current."
				)
			";
		}
		else {
			$str = "
				UPDATE ".Yearalize('stock_balance')."
				SET stock_opening_balance = ".$opening_balance.",
					stock_closing_balance = ".$obj->stock_current."
				WHERE (product_id = ".$obj->product_id.")
					AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
					AND (balance_month = ".$_SESSION['int_month_loaded'].")
					AND (balance_year = ".$_SESSION['int_year_loaded'].")
			";
		}
		
		$update = $conn->query($str);
		if(PEAR::isError($update)) {
			die('Error message (2) : '.$update->getMessage()."::".$update->getDebugInfo());
		}
		
	}
?>

</body>
</html>