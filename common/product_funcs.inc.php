<?
if (file_exists('../include/const.inc.php')) {
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
}
else if (file_exists('../../include/const.inc.php')) {
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");
}
else if (file_exists('../../../include/const.inc.php')) {
	require_once("../../../include/const.inc.php");
	require_once("../../../include/session.inc.php");
	require_once("../../../include/db.inc.php");
}



/*
	return in an array the months across which
	to retrieve the quantity sold per item
*/
function get_current_months() {

	$arr = explode("_", $_SESSION['invent_database_loaded']);

	$period = array();
	
	if (count($arr) <= 1) { 

		if (date('n') <= 3) {

			if ($_SESSION['int_month_loaded'] <= 3) {
				/*
					current year, and selected month is between Jan and Mar
				*/
				$year = date('Y');
				for ($i=1;$i<=$_SESSION['int_month_loaded'];$i++)
					$period[$i] = $year."_".$i;
				$year--;
				for ($i=4;$i<=12;$i++)
					$period[$i] = $year."_".$i;
			}
			else {
				/*
					current year, but selected month is between Apr and Dec
				*/
				$year = date('Y')-1;
				for ($i=4;$i<=$_SESSION['int_month_loaded'];$i++)
					$period[$i] = $year."_".$i;
			}
		}
		else {
			
			$year = date('Y');
			for ($i=4;$i<=$_SESSION['int_month_loaded'];$i++)
				$period[$i] = $year."_".$i;
		}
	}
	else {
		/*
			previous financial year
		*/
		if ($_SESSION['int_month_loaded'] <= 3) {

			$year = intval($arr[2]);
			for ($i=1;$i<=$_SESSION['int_month_loaded'];$i++)
				$period[$i] = $year."_".$i;
			$year--;
			for ($i=4;$i<=12;$i++)
				$period[$i] = $year."_".$i;

		}
		else {

			$year = intval($arr[1]);
			for ($i=4;$i<=12;$i++)
				$period[$i] = $year."_".$i;

		}
		
	}

	return $period;
}


//=================================================
// return a products buying price
// in case the product's use_batch_price is enabled
// the price of the most recent batch is retured,
// or, if the batch is given, the price of the batch
// else
// the global price is returned
//-------------------------------------------------
function getBuyingPrice($int_product_id, $batch_id=0 ) {
	$flt_buying_price = 0;
	
	if ($batch_id > 0 ) {
		$sql = "
			SELECT IF(ssp.use_batch_price = 'Y', sb.buying_price, ssp.buying_price) AS buying_price
			FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb, ".Monthalize('stock_storeroom_product')." ssp
			WHERE (sb.product_id = ".$int_product_id.")
				AND (sb.storeroom_id = ".$_SESSION["int_current_storeroom"].")
				AND (ssp.product_id = ".$int_product_id.")
				AND (ssp.storeroom_id =".$_SESSION["int_current_storeroom"].")
				AND (sb.status = ".STATUS_COMPLETED.")
				AND (sb.deleted = 'N')
				AND (ssb.product_id = sb.product_id)
				AND (sb.batch_id = $batch_id)
				AND (ssb.batch_id = sb.batch_id)
				AND (ssb.storeroom_id = sb.storeroom_id)
			ORDER BY date_created DESC
		";
		//echo $sql."<br><br>";
		$qry = new Query($sql);
	}
	else
		$qry = new Query("
			SELECT IF(ssp.use_batch_price = 'Y', sb.buying_price, ssp.buying_price) AS buying_price
			FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb, ".Monthalize('stock_storeroom_product')." ssp
			WHERE (sb.product_id = ".$int_product_id.")
				AND (sb.storeroom_id = ".$_SESSION["int_current_storeroom"].")
				AND (ssp.product_id = ".$int_product_id.")
				AND (ssp.storeroom_id =".$_SESSION["int_current_storeroom"].")
				AND (sb.status = ".STATUS_COMPLETED.")
				AND (sb.deleted = 'N')
				AND (ssb.product_id = sb.product_id)
				AND (ssb.batch_id = sb.batch_id)
				AND (ssb.storeroom_id = sb.storeroom_id)
			ORDER BY date_created DESC
		");
	
	if ($qry->RowCount() > 0)
		$flt_buying_price = $qry->FieldByName('buying_price');
	
	return number_format($flt_buying_price,2,'.','');
}

/*
	return a product's selling price
	--------------------------------
	if the product's use_batch_price is enabled
			the price of the most recent batch is retured
		OR
			the price of the specified batch id is returned
	else
		the global price is returned
*/ 
function getSellingPrice($int_product_id, $int_batch_id=0) {
	$flt_selling_price = 0;
	
	if ($int_batch_id > 0) {
		$sql = "
			SELECT IF(ssp.use_batch_price = 'Y', sb.selling_price, ssp.sale_price) AS selling_price
			FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb, ".Monthalize('stock_storeroom_product')." ssp
			WHERE (sb.product_id = ".$int_product_id.")
				AND (sb.storeroom_id = ".$_SESSION["int_current_storeroom"].")
				AND (ssp.product_id = ".$int_product_id.")
				AND (ssp.storeroom_id =".$_SESSION["int_current_storeroom"].")
				AND (sb.status = ".STATUS_COMPLETED.")
				AND (sb.deleted = 'N')
				AND (ssb.product_id = sb.product_id)
				AND (ssb.batch_id = sb.batch_id)
				AND (ssb.storeroom_id = sb.storeroom_id)
				
				AND (sb.batch_id = $int_batch_id)
			ORDER BY date_created DESC
		";
		//echo $sql."<br>";
		$qry = new Query($sql);
	} else
		$qry = new Query("
			SELECT IF(ssp.use_batch_price = 'Y', sb.selling_price, ssp.sale_price) AS selling_price
			FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb, ".Monthalize('stock_storeroom_product')." ssp
			WHERE (sb.product_id = ".$int_product_id.")
				AND (sb.storeroom_id = ".$_SESSION["int_current_storeroom"].")
				AND (ssp.product_id = ".$int_product_id.")
				AND (ssp.storeroom_id =".$_SESSION["int_current_storeroom"].")
				AND (sb.status = ".STATUS_COMPLETED.")
				AND (sb.deleted = 'N')
				AND (ssb.product_id = sb.product_id)
				AND (ssb.batch_id = sb.batch_id)
				AND (ssb.storeroom_id = sb.storeroom_id)
				AND (ssb.is_active = 'Y')
			ORDER BY date_created DESC
		");
	if ($qry->RowCount() > 0)
		$flt_selling_price = $qry->FieldByName('selling_price');
	
	return number_format($flt_selling_price,2,'.','');
}

//===================================================================
// returns an array containing the active batches for a given product
// 		0 - batch id
// 		1 - batch code
// 		2 - quantity
//-------------------------------------------------------------------
function get_active_batches($int_product_id=0, $str_product_code='', $is_bar_code='N') {
	$arr_batches = array();
	
	// query initialization
	$qry = new Query("SELECT * FROM stock_product LIMIT 1");
	
	if ($int_product_id == 0) {
		//***
		// locate the product for the given code and save the id
		//***
		if ($is_bar_code == 'Y')
			$qry->Query("
				SELECT product_id
				FROM stock_product
				WHERE (product_bar_code = '".$str_product_code."')"
			);
		else
			$qry->Query("
				SELECT product_id
				FROM stock_product
				WHERE (product_code = '".$str_product_code."')"
			);
		
		$int_product_id = $qry->FieldByName('product_id');
	}
	

	if ($str_product_code != 'nil') {
		if ($int_product_id > 0) {
			//***
			// make sure there is at least one active batch
			//***
			$qry->Query("
				SELECT *
				FROM ".Monthalize('stock_storeroom_batch')."
				WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].") 
					AND (product_id = ".$int_product_id.")
					AND (is_active = 'Y')
			");
			
			if ($qry->RowCount() == 0) {
				//***
				// make the most recent batch active
				//***
				$qry->Query("
					SELECT ssb.stock_storeroom_batch_id
					FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb
					WHERE (sb.product_id = ".$int_product_id.") AND
						(sb.storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
						(sb.status = ".STATUS_COMPLETED.") AND
						(sb.deleted = 'N') AND
						(ssb.product_id = sb.product_id) AND
						(ssb.batch_id = sb.batch_id) AND
						(ssb.storeroom_id = sb.storeroom_id) AND
						(ssb.stock_available <= 0)
					ORDER BY date_created DESC
					LIMIT 1
				");
				if ($qry->RowCount() > 0) {
					$qry->First();
					$int_ssb_batch_id = $qry->FieldByName('stock_storeroom_batch_id');
					
					$qry->Query("
						UPDATE ".Monthalize('stock_storeroom_batch')."
						SET is_active = 'Y'
						WHERE (stock_storeroom_batch_id = ".$int_ssb_batch_id.")
							AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
					");
				}
			}
			
			//***
			// double check:
			// make sure is_active is false when positive stock batches are available
			//***
			$arr_result = check_active_batches($int_product_id);
			
			if (count($arr_result) > 0) {
				for ($i=0;$i<count($arr_result);$i++) {
					$str_update = "
						UPDATE ".Monthalize('stock_storeroom_batch')."
						SET is_active = 'N'
						WHERE (stock_storeroom_batch_id = ".$arr_result[$i].")
							AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
						LIMIT 1
					";
					$qry->Query($str_update);
				}
			}
		
			//***
			// get the batches and quantities available for the given product
			//***
			$qry->Query("
				SELECT sb.batch_id, sb.batch_code, ssb.stock_available
				FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb
				WHERE (sb.product_id = ".$int_product_id.") AND
					(sb.storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
					(sb.status = ".STATUS_COMPLETED.") AND
					(sb.deleted = 'N') AND
					(ssb.product_id = sb.product_id) AND
					(ssb.batch_id = sb.batch_id) AND
					(ssb.storeroom_id = sb.storeroom_id) AND 
					(ssb.is_active = 'Y')
				ORDER BY date_created
			");
		
			if ($qry->RowCount() > 0) {
				//***
				// save the batch id, code and quantity
				//***
				for ($i=0; $i<$qry->RowCount(); $i++) {
					$arr_batches[$i][0] = $qry->FieldByName('batch_code');
					$arr_batches[$i][1] = $qry->FieldByName('batch_id');
					$arr_batches[$i][2] = $qry->FieldByName('stock_available');
					
					$qry->Next();
				}
			}
		}
	}
	
	return $arr_batches;
}

//====================================
// function used by get_active_batches
//------------------------------------
function check_active_batches($int_id) {
	$str_batches = "
		SELECT stock_storeroom_batch_id, batch_id, @cur_id := product_id AS product_id,
			(SELECT 
				COUNT(batch_id) 
				FROM ".Monthalize('stock_storeroom_batch')."
				WHERE stock_available  > 0 
					AND is_active = 'Y' 
					AND product_id = @cur_id
					AND storeroom_id = ".$_SESSION['int_current_storeroom']."
			) AS counter
		FROM ".Monthalize('stock_storeroom_batch')."
		WHERE is_active = 'Y'
			AND stock_available = 0
			AND product_id = $int_id
			AND storeroom_id = ".$_SESSION['int_current_storeroom'];
	$qry_batches = new Query($str_batches);
	
	$arr_batches = array();
	
	for ($i=0; $i < $qry_batches->RowCount(); $i++) {
		if ($qry_batches->FieldByName('counter') > 0)
			$arr_batches[] = $qry_batches->FieldByName('stock_storeroom_batch_id');
		
		$qry_batches->Next();
	}
	
	return $arr_batches;
}

?>
