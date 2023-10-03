<?php
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");

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

			//$arr = explode(",", $row);

			$correct_stock = number_format(round((float)$row[1],3),3,'.','');

			$sql = "
				UPDATE ".Yearalize('stock_balance')."
				SET stock_closing_balance = ".$correct_stock.",
					stock_mismatch_addition = IF(stock_opening_balance > $correct_stock,  stock_opening_balance - $correct_stock, $correct_stock - stock_opening_balance)
				WHERE (product_id = ".$row[0].")
					AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
					AND (balance_month = ".$_SESSION['int_month_loaded'].")
					AND (balance_year = ".$_SESSION['int_year_loaded'].")
			";
			$qry = new Query($sql);

			$sql = "
				UPDATE ".Monthalize('stock_storeroom_product')."
				SET stock_adjusted = 0,
					stock_current = ".$correct_stock."
				WHERE (product_id = ".$row[0].")
					AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
			";
			$qry = new Query($sql);

			$sql = "
				INSERT INTO  ".Monthalize('stock_transfer')."
					(transfer_quantity,
					transfer_description,
					date_created,
					module_id,
					user_id,
					storeroom_id_from,
					storeroom_id_to,
					product_id,
					batch_id,
					module_record_id,
					transfer_type,
					transfer_status,
					user_id_dispatched,
					user_id_received,
					is_deleted)
				VALUES(".
					$correct_stock.", ". 
					"'Stock mismatch correction', ".
					"'".date('Y-m-d H:i:s')."', ".
					"4, ". 										// module_id 4 is "Admin"
					$_SESSION["int_user_id"].", ".
					$_SESSION["int_current_storeroom"].", ".
					"0, ".
					$row[0].", ".
					"0, ".
					"0, ".
					TYPE_CORRECTED.", ".
					STATUS_COMPLETED.", ".
					$_SESSION["int_user_id"].", ".
					"0, ".
					"'N')
			";
			$qry = new Query($sql);

		}

		echo "updated stock";
	}

?>