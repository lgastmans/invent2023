<?php
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");
	require_once("../../common/product_funcs.inc.php");	

	if ($_POST['action'] == 'batches') {

		$sql = "
			UPDATE ".Monthalize('stock_storeroom_batch')."
			SET is_active = 'Y'
			WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")
				AND (is_active = 'N') AND (stock_available > 0)
		";

		$qry = new Query($sql);

		echo "updated batches";

	}
	elseif ($_POST['action'] == 'stock') {

		foreach ($_POST['ids'] as $row) {

			$correct_stock = number_format(round((float)$row[1],3),3,'.','');

			$ret = stock_correct($row[0], $correct_stock);

		}

		echo $ret;
	}

?>