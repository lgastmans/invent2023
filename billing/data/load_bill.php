<?php
	if (file_exists('../include/const.inc.php'))
		require_once('../include/const.inc.php');
	else if (file_exists('../../include/const.inc.php'))
		require_once('../../include/const.inc.php');
	
	require_once("session.inc.php");
	require_once("db.inc.php");


	function load_bill($bill_id, $is_draft=false) {

		$arr = array("data"=>array(), "message"=>"nil");


		$sql = "
			SELECT *
			FROM ".Monthalize('bill')."
			WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")
				AND (bill_id = $bill_id)
		";

		$qry = new Query($sql);

		if ($qry->RowCount() > 0) {

			$arr['message'] = 'OK';


			if ($is_draft) {

				$arr['data']['is_draft'] = $qry->FieldByName('is_draft');

			}
			else {

				$_SESSION['current_bill_type'] = $qry->FieldByName('payment_type');
				$arr['data']['bill_type'] = $qry->FieldByName('payment_type');

				$_SESSION['bill_salesperson'] = $qry->FieldByName('salesperson_id');
				$arr['data']['salesperson_id'] = $qry->FieldByName('salesperson_id');

				$_SESSION['bill_table_ref'] = $qry->FieldByName('table_ref');
				$arr['data']['table_ref'] = $qry->FieldByName('table_ref');


				$_SESSION['current_account_number'] = $qry->FieldByName('account_number');
				$arr['data']['account_number'] = $qry->FieldByName('account_number');


				$_SESSION['bill_card_name'] = $qry->FieldByName('card_name');
				$arr['data']['card_name'] = $qry->FieldByName('card_name');

				$_SESSION['bill_card_number'] = $qry->FieldByName('card_number');
				$arr['data']['card_number'] = $qry->FieldByName('card_number');

				$_SESSION['bill_card_date'] = $qry->FieldByName('card_date');
				$arr['data']['card_date'] = $qry->FieldByName('card_date');


				$_SESSION['aurocard_number'] = $qry->FieldByName('aurocard_number');
				$arr['data']['aurocard_number'] = $qry->FieldByName('aurocard_number');

				$_SESSION['aurocard_transaction_id'] = $qry->FieldByName('aurocard_transaction_id');;
				$arr['data']['aurocard_transaction_id'] = $qry->FieldByName('aurocard_transaction_id');


				$qry_items = new Query("
					SELECT 
						bi.quantity, bi.adjusted_quantity, 
						sp.product_id, sp.product_code 
					FROM ".Monthalize('bill_items')." bi
					LEFT JOIN stock_product sp ON (sp.product_id = bi.product_id)
					WHERE bill_id = $bill_id
				");

				for ($i=0;$i<$qry_items->RowCount();$i++) {

					$arr['items'][$i]['id'] = $qry_items->FieldByName('product_id');
					$arr['items'][$i]['code'] = $qry_items->FieldByName('product_code');
					$arr['items'][$i]['qty'] = $qry_items->FieldByName('quantity') + $qry_items->FieldByName('adjusted_quantity');

					$qry_items->Next();
				}

			}

		}

		return $arr;

	}



	function cancel_bill($bill_id) {

		$arr = array("data"=>array(), "message"=>"nil");

		$sql = "
			DELETE FROM ".Monthalize('bill')."
			WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")
				AND (bill_id = $bill_id)
		";

		$qry = new Query($sql);

		$arr["message"] = "Draft bill removed successfully";

		return $arr;
	}





	if ((isset($_POST['action'])) && ($_POST['action'] == 'draft')) {

		if (isset($_POST['bill_id'])) {

			$arr = load_bill($_POST['bill_id']);

			echo json_encode($arr);

		}
	}
	elseif ((isset($_POST['action'])) && ($_POST['action'] == 'is_draft')) {

		if (isset($_POST['bill_id'])) {

			$arr = load_bill($_POST['bill_id'], true);

			echo json_encode($arr);
		}
	}
	elseif ((isset($_POST['action'])) && ($_POST['action'] == 'cancel')) {

		if (isset($_POST['bill_id'])) {

			$arr = cancel_bill($_POST['bill_id']);

			echo json_encode($arr);
		}

	}


?>