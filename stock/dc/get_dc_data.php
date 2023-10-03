<?php

	echo $_POST['data'];





				$str_query = "
					INSERT INTO ".Monthalize('bill')."
					(
						storeroom_id,
						bill_number,
						date_created,
						total_amount,
						payment_type,
						payment_type_number,
						bill_promotion,
						bill_status,
						is_pending,
						user_id,
						module_id,
						resolved_on,
						CC_id,
						account_number,
						account_name,
						card_name,
						card_number,
						card_date,
						aurocard_number,
						aurocard_transaction_id,
						salesperson_id,
						customer_id,
						table_ref,
						is_draft,
						gstin
					)
					VALUES (".
						$_SESSION['int_current_storeroom'].", ".
						$int_next_billNumber.", '".
						getBillDate($_SESSION['current_bill_day'])."', ".
						number_format(RoundUp($_SESSION['bill_total']),2,'.','').", ".
						$_SESSION['current_bill_type'].", '".
						$int_payment_number."', ".
						$_SESSION["sales_promotion"].", ".
						BILL_STATUS_PROCESSING.", ".
						"'N', ".
						$_SESSION['int_user_id'].", ".
						"2, '".
						getBillDate($_SESSION['current_bill_day'])."', ".
						$int_current_CCID.", '".
						$_SESSION['current_account_number']."', '".
						addslashes($str_account_name)."', '".
						addslashes($_SESSION['bill_card_name'])."', '".
						addslashes($_SESSION['bill_card_number'])."', '".
						addslashes($_SESSION['bill_card_date'])."', ".
						intval($_SESSION['aurocard_number']).", ".
						intval($_SESSION['aurocard_transaction_id']).", ".
						$_SESSION['bill_salesperson'].", ".
						$customer_id.", ".
						"'".$_SESSION['bill_table_ref']."',
						'1',
						'".$gstin."'
					)";
	//				echo $str_query;
				$result_set->Query($str_query);
				if ($result_set->b_error == true) {
					$bill_saved = 0;
					$str_message = 'An error occurred trying to save the draft. '.$_SESSION['int_user_id'];
					$sql_string = $str_query;
				}
				$int_bill_id = $result_set->getInsertedID();

			}  // if is_draft_bill



			/*
				insert a row for each item that was billed
            */

			for ($i=0; $i<count($_SESSION["arr_total_qty"]); $i++) {
				
				//========================================
				// get the batch id of the current product
                //----------------------------------------
				$result_set->Query("
					SELECT batch_id
					FROM ".Yearalize('stock_batch')."
					WHERE (batch_code = '".$_SESSION['arr_total_qty'][$i][1]."') AND
						(product_id = ".$_SESSION['arr_total_qty'][$i][13].") AND
						(storeroom_id = ".$_SESSION['int_current_storeroom'].")
				");
				if ($result_set->b_error == true) {
					$bill_items_saved = 0;
					$str_message = "An error occurred trying to retrieve the batch (".$_SESSION['arr_total_qty'][$i][1].") of item ".$_SESSION['arr_total_qty'][$i][13];
					break;
				}
				$current_batch_id = $result_set->FieldByName('batch_id');
				

				$bprice = getBuyingPrice($_SESSION['arr_total_qty'][$i][13], $current_batch_id);
				
				if ($_SESSION['current_bill_type'] == 6) // transfer of goods
					$int_tax = $int_transfer_tax;
				else
					$int_tax = $_SESSION['arr_total_qty'][$i][7];

				$sql = "
					INSERT INTO ".Monthalize('bill_items')."
						(quantity,
						discount,
						price,
						bprice,
						tax_id,
						tax_amount,
						product_id,
						bill_id,
						batch_id,
						adjusted_quantity,
						product_description
						)
					VALUES(".
						$_SESSION['arr_total_qty'][$i][2].", ".
						$_SESSION['arr_total_qty'][$i][4].", ".
						number_format($_SESSION['arr_total_qty'][$i][6],3,'.','').", ".
						number_format($bprice,3,'.','').", ".
						$int_tax.", ".
						$_SESSION['arr_total_qty'][$i][11].", ".
						$_SESSION['arr_total_qty'][$i][13].", ".
						$int_bill_id.", ".
						$current_batch_id.", ".
						$_SESSION['arr_total_qty'][$i][5].", '".
						addslashes($_SESSION['arr_total_qty'][$i][12])."')";
				$result_set->Query($sql);
				if ($result_set->b_error == true) {
					$bill_items_saved = 0;
					$str_message = "An error occurred trying to save one of the items (".$_SESSION['arr_total_qty'][$i][13].") of the current bill.\n".$sql;
					break;
				} 
			}
	
?>