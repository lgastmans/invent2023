<?php
	include("../include/const.inc.php");
	include("../include/session.inc.php");
	require_once("../include/db_mysqli.php");
	
	$error = false;
	$arr = array('msg'=>'SUCCESS');


	/**
	 * 
	 * check whether the selected database is in the current FY
	 * 
	 */
	$month_current = date('n');
	$month_start = 4;
	$year_current = date('Y');

	if ($month_current < 4) {
		$year_start = $year_current-1;
		$qry = $conn->query("SELECT * FROM bill_".$year_start."_4");
	}
	else {
		$year_start = $year_current;
		$qry = $conn->query("SELECT * FROM bill_".$year_start."_4");
	}

	if (!$qry) {
		$arr['msg'] = "The current financial year has to be selected";
		$error = true;
		echo json_encode($arr);die();
	}


	$supplier_id = 0;
	if (isset($_POST['supplier_id']))
		$supplier_id = $_POST['supplier_id'];

	$qry = $conn->query("
		SELECT supplier_id, supplier_name, supplier_phone
		FROM stock_supplier
		WHERE supplier_id = $supplier_id
	");	
	$obj = $qry->fetch_object();

	if (!$obj) {
		$arr = array("msg"=>"Error retrieving supplier");
		echo json_encode($arr);die();
	}


	if (!empty($obj->supplier_name)) {


		$qry = $conn->query("START TRANSACTION");


		/**
		 * stock_batch
		*/
		$qry = $conn->query("SELECT * FROM stock_batch_".$year_start);
		if (!$qry) {
			$arr['msg'][] = "Table stock_batch_".$year_start." : not found";
			//$error = true;
			//break;
		}
		else {
			$sql = "
				DELETE FROM stock_batch_".$year_start."
				WHERE supplier_id = ".$obj->supplier_id;
			$qry = $conn->query($sql);
			//$arr['action'][] = $sql;
			if (!$qry) {
				$arr['msg'] = "Table stock_batch : ".mysqli_error($conn);
				$error = true;
			}
		}

		/**
		 * stock_balance
		*/
		$qry = $conn->query("SELECT * FROM stock_balance_".$year_start);
		if (!$qry) {
			$arr['msg'] = "Table stock_balance_".$year_start." : not found";
			//$error = true;
			//break;
		}
		else {
			$sql = "
				DELETE sb.* FROM stock_balance_".$year_start." sb
				INNER JOIN stock_product sp ON (sp.supplier_id = ".$obj->supplier_id.")
				WHERE sb.product_id = sp.product_id";
			$qry = $conn->query($sql);
			//$arr['action'][] = $sql;
			if (!$qry) {
				$arr['msg'] = "Table stock_balance : ".mysqli_error($conn);
				$error = true;
			}
		}
			

		$bool_continue = true;
		while ($bool_continue) {

			/**
			 * stock_storeroom_product
			*/
			$qry = $conn->Query("SELECT * FROM stock_storeroom_product_".$year_start."_".$month_start);
			if (!$qry) {
				$arr['msg'] = "Table stock_storeroom_product_".$year_start."_".$month_start." : not found";
				//$error = true;				
				//break;
			}
			else {
				$sql = "
					DELETE ssp.* FROM stock_storeroom_product_".$year_start."_".$month_start." ssp
					INNER JOIN stock_product sp ON (sp.supplier_id = ".$obj->supplier_id.")
					WHERE ssp.product_id = sp.product_id";
				$qry = $conn->query($sql);
				//$arr['action'][] = $sql;
				if (!$qry) {
					$arr['msg'] = "Table stock_storeroom_product : ".mysqli_error($conn);
					$error = true;
				}
			}

			/**
			 * stock_storeroom_batch
			*/
			$qry = $conn->Query("SELECT * FROM stock_storeroom_batch_".$year_start."_".$month_start);
			if (!$qry) {
				$arr['msg'] = "Table stock_storeroom_batch_".$year_start."_".$month_start." : not found";
				//$error = true;				
				//break;
			}
			else {
				$sql = "
					DELETE ssb.* FROM stock_storeroom_batch_".$year_start."_".$month_start." ssb
					INNER JOIN stock_product sp ON (sp.supplier_id = ".$obj->supplier_id.")
					WHERE ssb.product_id = sp.product_id";
				$qry = $conn->query($sql);
				//$arr['action'][] = $sql;
				if (!$qry) {
					$arr['msg'] = "Table stock_storeroom_batch : ".mysqli_error($conn);
					$error = true;
				}
			}

			/**
			 * stock_transfer
			*/
			$qry = $conn->Query("SELECT * FROM stock_transfer_".$year_start."_".$month_start);
			if (!$qry) {
				$arr['msg'] = "Table stock_transfer_".$year_start."_".$month_start." : not found";
				//$error = true;				
				//break;
			}
			else {
				$sql = "
					DELETE st.* FROM stock_transfer_".$year_start."_".$month_start." st
					INNER JOIN stock_product sp ON (sp.supplier_id = ".$obj->supplier_id.")
					WHERE st.product_id = sp.product_id";
				$qry = $conn->query($sql);
				$arr['action'][] = $sql;
				if (!$qry) {
					$arr['msg'] = "Table stock_transfer : ".mysqli_error($conn);
					$error = true;
				}
			}


			if ($month_start == 12) {
				$month_start = 1;
				$year_start++; 
			}
			else {
				$month_start++;

				if ($month_start == 4)
					break;
			}
		}


		/**
		 * stock_product
		 */
		$sql = "
			DELETE FROM stock_product
			WHERE supplier_id = $supplier_id";
		$qry = $conn->query($sql);
		//$arr['action'][] = $sql;
		if (!$qry) {
			$arr['msg'] = "Table stock_product : ".mysqli_error($conn);
			$error = true;
		}


		/**
		 * supplier
		 */
		$sql = "
			DELETE FROM stock_supplier
			WHERE supplier_id = $supplier_id";
		$qry = $conn->query($sql);
		//$arr['action'][] = $sql;
		if (!$qry) {
			$arr['msg'] = "Table supplier : ".mysqli_error($conn);
			$error = true;
		}

		if (!$error) {

			$qry = $conn->query("COMMIT");

		}
		else {

			$qry = $conn->query("ROLLBACK");

		}

	}

	echo json_encode($arr);


?>