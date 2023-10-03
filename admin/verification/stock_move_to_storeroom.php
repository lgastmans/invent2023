<?php
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");
	require_once("../../stock/transfers/direct_transfer_funcs.php");


	$int_storeroom_id = 0;
	if (isset($_GET['storeroom']))
		$int_storeroom_id = $_GET['storeroom'];

	if ($int_storeroom_id > 0)
	{

		$sql = "
			SELECT ssp.product_id, ssp.stock_current, sp.product_code, sp.product_description
			FROM ".Monthalize('stock_storeroom_product')." ssp
			LEFT JOIN stock_product sp ON (sp.product_id = ssp.product_id)
			WHERE ssp.storeroom_id = ".$_SESSION["int_current_storeroom"]."
			ORDER BY product_code";
		//die($sql);
		$qry = new Query($sql);

		if ($qry->RowCount() > 0) {


			for ($i=0;$i<=$qry->RowCount();$i++)
			{
			
				$int_product_id = $qry->FieldByName('product_id');
				$flt_quantity = number_format($qry->FieldByName('stock_current'), 2, '.', '');
				$int_day = date('n');
				$str_ref_num = 'move stock';
				$str_message = '';

//				echo $int_product_id.":".$flt_quantity.":".$int_storeroom_id.":".$int_day."<br>";
				
				/**
				 * START TRANSACTION
				 */
				$qry_transfer = new Query("START TRANSACTION");
				$bool_success = true;

				/**
				 * DEDUCT STOCK FROM SOURCE STOREROOM
				 */
				$str_retval = deduct_stock($int_product_id, $flt_quantity, $int_storeroom_id, $int_day, $str_ref_num);
				$arr_retval = explode('|', $str_retval);
				if ($arr_retval[0] == 'OK')
					$bool_success = true;
				else {
					$bool_success = false;
					$str_message = "Deduct stock: ".$arr_retval[1]."<br>";
				}
				
				/**
				 * ADD STOCK TO DESTINATION STOREROOM
				 */
				if ($bool_success) {
					$str_retval = add_stock($int_product_id, $int_storeroom_id, $flt_quantity, $int_day, $str_ref_num);
					$arr_retval = explode('|', $str_retval);
					if ($arr_retval[0] == 'OK') {
						$bool_success = true;
					}
					else {
						$bool_success = false;
						$str_message = "Add stock: ".$arr_retval[1]."<br>";
					}
				}

				/**
				 * FINALIZE TRANSACTION
				 */
				if ($bool_success) {
					$qry_transfer->Query("COMMIT");
					echo "$i Committed for ".$qry->FieldByName('product_code')." - ".$qry->FieldByName('product_description')."<br>";
				}
				else {
					$qry_transfer->Query("ROLLBACK");
					echo "Rolled back for ".$qry->FieldByName('product_code')." - ".$qry->FieldByName('product_description').", ".$str_message."<br>";
				}


				$qry->Next();
				
			} // while
		}
	}
?>