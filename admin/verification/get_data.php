<?php

	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");
	require_once("../../common/tax.php");

	header("Content-Type: application/json;charset=utf-8");

	$vdata = array();

	$discrepancies = 0;

	$message = '';
	$action  = '';

	/*
		First check whether there are batches that have stock and that are
		flagged as inactive
	*/

	$sql = "
		SELECT sp.product_id, sp.product_code, sp.product_description, ssb.stock_available
		FROM ".Monthalize('stock_storeroom_batch')." ssb
		INNER JOIN stock_product sp ON (sp.product_id = ssb.product_id)
		WHERE (ssb.product_id = sp.product_id)
			AND (ssb.storeroom_id = ".$_SESSION["int_current_storeroom"].")
			AND (ssb.is_active = 'N') AND (ssb.stock_available > 0)
	";

	$qry = new Query($sql);

	if ($qry->RowCount() > 0) {

		for ($i=0;$i<$qry->RowCount();$i++) {

			$vdata[$discrepancies][0] = $qry->FieldByName('product_id'); //(string)$discrepancies;
			$vdata[$discrepancies][1] = $qry->FieldByName('product_code');
			$vdata[$discrepancies][2] = $qry->FieldByName('product_description');
			$vdata[$discrepancies][3] = 0;
			$vdata[$discrepancies][4] = 0;
			$vdata[$discrepancies][5] = number_format($qry->FieldByName('stock_available'),3,'.','');
			$vdata[$discrepancies][6] = 0;

			$discrepancies++;

			$qry->Next();
		}

		$message = "Incorrect inactive batches!<br><br>There are batches that are set as inactive and that have stock.<br>Click the 'Correct Inconsistencies' button to reset these batches as active. ";

		$action = "batches";

	}
	else {

		/*
			If all the inactive batches are set correctly,
			check whether the balance entries across the three tables are synchronized
		*/

		$sql = "
			SELECT
				sp.product_id,
				sp.product_code,
				sp.product_description,
				ssp.stock_adjusted,
				ssp.stock_current,
				stock_closing_balance,
				ROUND((
					SELECT ROUND(SUM(stock_available),3) AS stock_available
					FROM ".Monthalize('stock_storeroom_batch')." ssb
					WHERE (ssb.product_id = sp.product_id)
						AND (ssb.storeroom_id = ".$_SESSION["int_current_storeroom"].")
						AND (ssb.is_active = 'Y')
				),3)
				AS total_batch_stock

			FROM ".Yearalize('stock_balance')." sb

			INNER JOIN stock_product sp ON (sp.product_id = sb.product_id)

			LEFT JOIN ".Monthalize('stock_storeroom_product')." ssp ON (ssp.product_id = sb.product_id)
				AND (ssp.storeroom_id = ".$_SESSION['int_current_storeroom'].")

			WHERE (sb.storeroom_id = ".$_SESSION['int_current_storeroom'].")
				AND (sb.balance_month = ".$_SESSION['int_month_loaded'].")
				AND (sb.balance_year = ".$_SESSION['int_year_loaded'].")

			ORDER BY sp.product_code+0
		";

		$qry = new Query($sql);


		for ($i=0;$i<$qry->RowCount();$i++) {

			$total_batch_stock = 0;
			if (!is_null($qry->FieldByName('total_batch_stock')))
				$total_batch_stock = number_format(round((float)$qry->FieldByName('total_batch_stock'),3),3,'.','');

			$stock_current = number_format(round((float)$qry->FieldByName('stock_current'),3),3,'.','');
			$stock_closing_balance = number_format(round((float)$qry->FieldByName('stock_closing_balance'),3),3,'.','');

			if (	
				($stock_current <> $stock_closing_balance) ||
				($stock_current <> $total_batch_stock) ||
				($stock_closing_balance <> $total_batch_stock)
				) {

				$vdata[$discrepancies][0] = $qry->FieldByName('product_id'); //(string)$discrepancies;
				$vdata[$discrepancies][1] = $qry->FieldByName('product_code');
				$vdata[$discrepancies][2] = $qry->FieldByName('product_description');
				$vdata[$discrepancies][3] = $stock_closing_balance;
				$vdata[$discrepancies][4] = $stock_current;
				$vdata[$discrepancies][5] = $total_batch_stock;
				$vdata[$discrepancies][6] = number_format($qry->FieldByName('stock_adjusted'),3,'.','');

				$discrepancies++;
				
			}

			$qry->Next();
		}

		$message = "Stock mismatches found!!<br><br>Update mismatched stock by entering the actual stock in the 'Batches Stock' column.
		<br>Adjusted stock will be set to zero.";

		$action = "stock";
	}


	$ret = array("data"=>$vdata, "discrepancies"=>$discrepancies, "message"=>$message, "action"=>$action);

	echo json_encode($ret);
?>