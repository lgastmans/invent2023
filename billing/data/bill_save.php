<?
//	require_once("/var/www/html/Gubed/Gubed.php");

	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");
	require_once("../../common/account.php");
	require_once("../../common/product_bill.php");
	require_once("../bill_cancel.php");
	require_once("../get_bill_number.php");
	

	/*
	$print_filename = $arr_invent_config['billing']['print_filename'];
	if (!$print_filename)
		$print_filename='print_bill.php';
	
	$qry_settings = new Query("SELECT bill_transfer_tax FROM user_settings WHERE storeroom_id = ".$_SESSION['int_current_storeroom']);
	$int_transfer_tax = 0;
	if ($qry_settings->RowCount() > 0)
		$int_transfer_tax = $qry_settings->FieldByName('bill_transfer_tax');
	*/



    /*
    	get Company details
    */
    $company = new Query("SELECT * FROM company WHERE 1");
    $gstin = $company->FieldByName('gstin');



	function IsNullOrEmpty($var){
	    return (!isset($var) || trim($var)==='' || intval($var)==0);
	}

	function is_all_nonzero() {
		$str_retval = 'OK';
		for ($i=0;$i<count($_SESSION['arr_total_qty']);$i++) {
			if (($_SESSION['arr_total_qty'][$i][2] == 0) && ($_SESSION['arr_total_qty'][$i][5] == 0)) {
				$str_retval = "A quantity error occurred while billing.\\nPlease cancel product ".$_SESSION['arr_total_qty'][$i][0]." and enter it again.";
			}
			/*
			else if ($_SESSION['arr_total_qty'][$i][6] == 0) {
				$str_retval = "A price error occurred while billing.\\nPlease cancel product ".$_SESSION['arr_total_qty'][$i][0]." and enter it again.";
			}
			*/
		}
		return $str_retval;
	}

	function verify_billed_items($str_path) {
		$str_retval = 'OK|OK';
		$bool_retval = true;
		
		// check for negative entries
		for ($i=0;$i<count($_SESSION['arr_total_qty']);$i++) {
			if (($_SESSION['arr_total_qty'][$i][2] < 0) || ($_SESSION['arr_total_qty'][$i][2] < 0)) {
				$bool_retval = false;
				$str_retval = 'FALSE|There was a negative value entered for product '.$_SESSION['arr_total_qty'][$i][0];
				break;
			}
		}
		
		// cross check quantities with quantities in batches
		// not when editting bill
		if ($_SESSION["bill_id"] == -1) {
			$qry_check = new Query("SELECT * FROM ".Monthalize('stock_storeroom_batch')." LIMIT 1");
			for ($i=0;$i<count($_SESSION['arr_total_qty']);$i++) {
				$_SESSION['arr_total_qty'][$i][21] = "billed: ";
				$qry_check->Query("
					SELECT *
					FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb
					WHERE (sb.batch_code = '".$_SESSION['arr_total_qty'][$i][1]."')
						AND (sb.product_id = ".$_SESSION['arr_total_qty'][$i][13].")
						AND (ssb.batch_id = sb.batch_id)
						AND (sb.storeroom_id = ".$_SESSION['int_current_storeroom'].")
						AND (ssb.storeroom_id = ".$_SESSION['int_current_storeroom'].")
					ORDER BY sb.date_created DESC
					LIMIT 1
				");
				if ($qry_check->RowCount() > 0) {
					if ($_SESSION['arr_total_qty'][$i][2] > $qry_check->FieldByName('stock_available')) {
						$bool_retval = false;
						$str_retval = 'FALSE|Batch quantity and actual quantity entered not matched for product '.$_SESSION['arr_total_qty'][$i][0];
						$_SESSION['arr_total_qty'][$i][21] .= $_SESSION['arr_total_qty'][$i][2]." available : ".$qry_check->FieldByName('stock_available');
					}
				}
			}
		}
		
		// save the array in a file for debugging
		if ($bool_retval == false) {
			$fname = $str_path."bill_error_log_".date('j').".txt";
			$fhandle = fopen($fname,"w");
			for ($i=0;$i<count($_SESSION['arr_total_qty']);$i++) {
				$str_content = "\n".
					"       code :".$_SESSION['arr_total_qty'][$i][0]."\n".
					"      batch :".$_SESSION['arr_total_qty'][$i][1]."\n".
					"        qty :".$_SESSION['arr_total_qty'][$i][2]."\n".
					"   discount :".$_SESSION['arr_total_qty'][$i][3]."\n".
					"    percent :".$_SESSION['arr_total_qty'][$i][4]."\n".
					"   adjusted :".$_SESSION['arr_total_qty'][$i][5]."\n".
					"      price :".$_SESSION['arr_total_qty'][$i][6]."\n".
					"     tax_id :".$_SESSION['arr_total_qty'][$i][7]."\n".
					"  tax descr :".$_SESSION['arr_total_qty'][$i][8]."\n".
					"   is_taxed :".$_SESSION['arr_total_qty'][$i][9]."\n".
					"      total :".$_SESSION['arr_total_qty'][$i][10]."\n".
					"        tax :".$_SESSION['arr_total_qty'][$i][11]."\n".
					"description :".$_SESSION['arr_total_qty'][$i][12]."\n".
					" product_id :".$_SESSION['arr_total_qty'][$i][13]."\n".
					"   supplier :".$_SESSION['arr_total_qty'][$i][14]."\n".
					" is_decimal :".$_SESSION['arr_total_qty'][$i][15]."\n".
					"   location :".$_SESSION['arr_total_qty'][$i][20]."\n".
					"    comment :".$_SESSION['arr_total_qty'][$i][21]."\n";
				
				fwrite($fhandle, $str_content);
			}
			fclose($fhandle);
		}
		
		return $str_retval;
	}


	// clear the session variables related to the billing
	function clear_bill_variables() {
		$_SESSION["bill_id"] = -1;
		$_SESSION['bill_number'] = '';
		unset($_SESSION["arr_total_qty"]);
		unset($_SESSION["arr_item_batches"]);
		/*
		if (IsSet($_POST["bill_type"]))
			$_SESSION['current_bill_type'] = $_POST["bill_type"];
		else
			$_SESSION['current_bill_type'] = 1;
		*/
		$_SESSION['current_bill_day'] = date('j');
		$_SESSION['current_account_number'] = "";
		$_SESSION['current_account_name'] = "";
		$_SESSION['bill_total'] = 0;
		$_SESSION['sales_promotion'] = 0;
		$_SESSION['fs_account_balance'] = 0;
		$_SESSION['bill_table_ref'] = '';

		$_SESSION['save_counter'] = 0;
	}


$err_status = 1;
$can_save = 0;
$sql_string = '';
$fs_account_customer_id = 0;

if (IsSet($_POST['action'])) {

	if ($_POST['action'] == 'save') {

		$_SESSION['save_counter'] = intval($_SESSION['save_counter']) +1;

		if ($_SESSION['save_counter'] > 1)
			die('This bill has already been saved. \nClose this window and open again.');

		if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
			$str_message = 'Current month and year';
			$err_status = 1; 
		}
		else {
			$str_message = 'Cannot bill in previous months. \\n Select the current month/year and continue.';
			$err_status = 0; //false
		}

		$str_account_name = '';
		// IF FS ACCOUNT BILL, CHECK THE ACCOUNT NUMBER
		if (($_SESSION['current_bill_type'] == 2) || ($_SESSION['current_bill_type'] == 6)){

			$str_retval = getAccountName($_SESSION['current_account_number'], ACCOUNT_METHOD);
			$arr_retval = explode("|", $str_retval);

			/*
				If the FS account has an entry in the customer table
				then retrieve the 'id' field
				to save in the bills table.
			*/
			$sql_fs = new Query("
				SELECT id
				FROM customer
				WHERE (fs_account = '".$_SESSION['current_account_number']."')
			");
			if ($sql_fs->RowCount() > 0) {
				$fs_account_customer_id = $sql_fs->FieldByName('id');
			}			
			

			if ($arr_retval[0] != 'OK') {
				$str_message = $arr_retval[0];
				$err_status = 0; //false
			}
			else {
				$str_account_name = $arr_retval[1];
				
				$int_current_CCID = getAccountCCID($_SESSION['current_account_number'], ACCOUNT_METHOD);
				
				if ($int_current_CCID == -1) {
					$str_retval = get_account_status($_SESSION['current_account_number']);
					$arr_retval = explode('|', $str_retval);
					
					if ($arr_retval[0] == 'OK') {
						
						$str_active = 'FALSE';
						if ($arr_retval[1] == 'Y')
							$str_active = 'TRUE';
							
						$str_message = "The active status of this account is ".$str_active.". \\n The balance is ".$arr_retval[2];
					}
					else
						$str_message = $arr_retval[1];
					
					$err_status = 0;
				}
				else {
					/*
						if online, make a test transfer
					*/
					$int_result = 1;
					
					if ($_SESSION['connect_mode'] == CONNECT_ONLINE) {
						//========================================
						// get the account to make the transfer to
						//----------------------------------------
						/*
						$qry = new Query("
							SELECT bill_credit_account
							FROM stock_storeroom
							WHERE (storeroom_id = ".$_SESSION['int_current_storeroom'].")
						");
						$credit_acount = $qry->FieldByName('bill_credit_account');
						
						$int_result = testTransfer(
							$_SESSION['current_account_number'],
							$credit_acount,
							'',
							$_SESSION['bill_total']
						);
						if ($int_result == 0) { // error
							$str_message = "An error occurred completing this transfer.";
							$err_status = 0;
						}
						else if ($int_result == 2) { // no funds
							$str_message = "Insufficient Funds to complete this transfer.";
							$err_status = 0;
						}
						*/

						if ($_SESSION['fs_account_balance'] != -1) {
							if ($_SESSION['fs_account_balance'] < $_SESSION['bill_total']) {
								$str_message = "Insufficient Funds to complete this transfer.";
								$err_status = 0;
							}
						}
					}
					
				}
			}
		}
		
		// IF PT ACCOUNT
		else if ($_SESSION['current_bill_type'] == 3) {
		  // get the account_id of the current account number
			$result_search = new Query("
				SELECT account_id, account_name, enabled
				FROM account_pt
				WHERE (account_number = '".$_SESSION['current_account_number']."')
			");
			if ($result_search->RowCount() > 0) {
				$str_account_name = $result_search->FieldByName('account_name');
				$int_current_CCID = $result_search->FieldByName('account_id');
				if ($result_search->FieldByName('enabled') == 'N') {
					$str_message = "This account has been disabled.";
					$err_status = 0;
				}
			}
			else {
				$str_message = "Invalid PT account";
				$err_status = 0; //false
			}
		}
		else {
			$int_current_CCID = 0;
		}
			
		$int_payment_number = '';

		
		// a check to make sure the quantities stored in the session array
		// are valid
		$str_result = verify_billed_items($str_application_path);
		$arr_retval = explode('|', $str_result);
		if ($arr_retval[0] == 'FALSE') {
			$str_message = $arr_retval[1];
			$err_status = 0;
		}

		// a check to make sure the quantities stored in the session array
		// are non-zero
		$str_result = is_all_nonzero();
		if ($str_result <> 'OK') {
			$str_message = $str_result;
			$err_status = 0;
		}
		
		if ($err_status != 0) {
			//=========================
			// get the last bill number
            //-------------------------
			$int_next_billNumber = get_bill_number($_SESSION['current_bill_type']);
			//====================================================
			// start transaction in case the CREATE TRANSFER fails
            //----------------------------------------------------
			$result_set = new Query("START TRANSACTION");
			
			$bill_saved = 1;
			$bill_items_saved = 1;
			if (!$_SESSION['bill_salesperson'])
				$_SESSION['bill_salesperson']=0;

			$customer_id = 0;
			if (IsNullOrEmpty($_SESSION['client_id'])) 
				$customer_id = $fs_account_customer_id;
			else
				$customer_id = $_SESSION['client_id'];
			
			//=========================
			// insert row in bill table
			//-------------------------

			if ((isset($_POST['is_draft_bill'])) && ($_POST['is_draft_bill']==true)) {

				/*
					update the bill table
				*/
				$str_query = "
					UPDATE ".Monthalize('bill')."
					SET
						storeroom_id = '".$_SESSION['int_current_storeroom']."', 
						bill_number = '".$int_next_billNumber."',
						date_created = '".getBillDate($_SESSION['current_bill_day'])."', 
						total_amount = '".number_format(RoundUp($_SESSION['bill_total']),2,'.','')."',
						payment_type = '".$_SESSION['current_bill_type']."',
						payment_type_number = '".$int_payment_number."',
						bill_promotion = '".$_SESSION["sales_promotion"]."',
						bill_status = '".BILL_STATUS_RESOLVED."',
						is_pending = 'N',
						user_id = '".$_SESSION['int_user_id']."',
						module_id = '2',
						resolved_on = '".getBillDate($_SESSION['current_bill_day'])."',
						CC_id = '".$int_current_CCID."',
						account_number = '".$_SESSION['current_account_number']."',
						account_name = '".addslashes($str_account_name)."',
						card_name = '".addslashes($_SESSION['bill_card_name'])."',
						card_number = '".addslashes($_SESSION['bill_card_number'])."',
						card_date = '".addslashes($_SESSION['bill_card_date'])."',
						aurocard_number = '".intval($_SESSION['aurocard_number'])."',
						aurocard_transaction_id = '".intval($_SESSION['aurocard_transaction_id'])."',
						upi_transaction_id = '".addslashes($_SESSION['upi_transaction_id'])."',
						upi_utr_number = '".addslashes($_SESSION['upi_utr_number'])."',
						salesperson_id = '".$_SESSION['bill_salesperson']."',
						customer_id = '".$customer_id."',
						table_ref = '".$_SESSION['bill_table_ref']."',
						is_draft = '0'
					WHERE bill_id = ".$_POST['draft_bill_id'];

				$result_set->Query($str_query);
				if ($result_set->b_error == true) {
					$bill_saved = 0;
					$str_message = 'An error occurred trying to save the draft. '.$_SESSION['int_user_id'];
					$sql_string = $str_query;
				}
				$int_bill_id = $_POST['draft_bill_id'];


				/*
					clear the previously saved items
				*/
				$sql = "DELETE FROM ".Monthalize('bill_items')." WHERE (bill_id = ".$_POST['draft_bill_id'].")";
				$result_set->Query($sql);


			}
			else {
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
						upi_transaction_id,
						upi_utr_number,
						salesperson_id,
						customer_id,
						table_ref
					)
					VALUES (".
						$_SESSION['int_current_storeroom'].", ".
						$int_next_billNumber.", '".
						getBillDate($_SESSION['current_bill_day'])."', ".
						number_format(RoundUp($_SESSION['bill_total']),2,'.','').", ".
						$_SESSION['current_bill_type'].", '".
						$int_payment_number."', ".
						$_SESSION["sales_promotion"].", ".
						BILL_STATUS_RESOLVED.", ".
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
						intval($_SESSION['aurocard_transaction_id']).", '".
						addslashes($_SESSION['upi_transaction_id'])."', '".
						addslashes($_SESSION['upi_utr_number'])."', ".
						$_SESSION['bill_salesperson'].", ".
						$customer_id.", ".
						"'".$_SESSION['bill_table_ref']."'
					)";
	//				echo $str_query;
				$result_set->Query($str_query);
				if ($result_set->b_error == true) {
					/**
					 * Feb 2024, this version (2023) of the software was installed at PTPS
					 * They started getting duplicate bills, and therefore a Unique index
					 * on the columns `storeroom_id`, `payment_type`, `bill_number` was created
					 * if an error occurs saving the bill, reset/refresh the bill session variables
					 * This refresh is done in billing.php
					 */
					clear_bill_variables();

					$bill_saved = 0;
					$str_message = 'An error occurred trying to save the bill.';
					$sql_string = $result_set->err; //$str_query;
				}
				$int_bill_id = $result_set->getInsertedID();
			}


			//===========================================
			// insert a row for each item that was billed
            //-------------------------------------------
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
				
				//=================================
				// update the 'bill_reserved' field
				//---------------------------------
/*				$result_set->Query("
					UPDATE ".Monthalize('stock_storeroom_batch')."
					SET bill_reserved = ABS(ROUND(bill_reserved - ".$_SESSION['arr_total_qty'][$i][2].", 3))
					WHERE batch_id = ".$current_batch_id."
						AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
				");
*/

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
			
			if (($bill_saved == 1) && ($bill_items_saved == 1)) {
				//=============================
				// set 'ok' to update the stock
				//-----------------------------
				$can_save = 1;
				$result_set->Query("COMMIT");
				
			}
			else {
				recycle_bill_number($int_next_billNumber, $_SESSION['current_bill_type']);
				$result_set->Query("ROLLBACK");
				
				echo json_encode( array("bill_id"=>0, "message"=>$str_message, "sql"=>$sql_string ));
			}

		} // end if ($err_status != 0)

		else {

			echo json_encode( array("bill_id"=>0, "message"=>$str_message, "sql"=>$sql_string ));

		}

		$_SESSION['save_counter'] = 0;

	} // end if (action ='save')


	/*
		draft bill
	*/

	else if ($_POST['action'] == 'draft') {

		
		$can_save = 0;

	
		if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
			$str_message = 'Current month and year';
			$err_status = 1; 
		}
		else {
			$str_message = 'Cannot bill in previous months. \\n Select the current month/year and continue.';
			$err_status = 0; //false
		}

		$str_account_name = '';

		/*
			IF FS ACCOUNT BILL, CHECK THE ACCOUNT NUMBER
		*/
		if (($_SESSION['current_bill_type'] == 2) || ($_SESSION['current_bill_type'] == 6)){

			$str_retval = getAccountName($_SESSION['current_account_number'], ACCOUNT_METHOD);
			$arr_retval = explode("|", $str_retval);

			/*
				If the FS account has an entry in the customer table
				then retrieve the 'id' field
				to save in the bills table.
			*/
			$sql_fs = new Query("
				SELECT id
				FROM customer
				WHERE (fs_account = '".$_SESSION['current_account_number']."')
			");
			if ($sql_fs->RowCount() > 0) {
				$fs_account_customer_id = $sql_fs->FieldByName('id');
			}			
			

			if ($arr_retval[0] != 'OK') {
				$str_message = $arr_retval[0];
				$err_status = 0; //false
			}
			else {
				$str_account_name = $arr_retval[1];
				
				$int_current_CCID = getAccountCCID($_SESSION['current_account_number'], ACCOUNT_METHOD);
				
				if ($int_current_CCID == -1) {

					$str_retval = get_account_status($_SESSION['current_account_number']);
					$arr_retval = explode('|', $str_retval);
					
					if ($arr_retval[0] == 'OK') {
						$str_active = 'FALSE';
						if ($arr_retval[1] == 'Y')
							$str_active = 'TRUE';
							
						$str_message = "The active status of this account is ".$str_active.". \\n The balance is ".$arr_retval[2];
					}
					else
						$str_message = $arr_retval[1];
					
					$err_status = 0;
				}
				else {
					/*
						if online
					*/
					$int_result = 1;
					
					if ($_SESSION['connect_mode'] == CONNECT_ONLINE) {

						if ($_SESSION['fs_account_balance'] != -1) {
							if ($_SESSION['fs_account_balance'] < $_SESSION['bill_total']) {
								$str_message = "Insufficient Funds to complete this transfer.";
								$err_status = 0;
							}
						}
					}
					
				}
			}
		}
		else {

			$int_current_CCID = 0;

		}
			
		$int_payment_number = '';

		
		// a check to make sure the quantities stored in the session array
		// are valid
		$str_result = verify_billed_items($str_application_path);
		$arr_retval = explode('|', $str_result);
		if ($arr_retval[0] == 'FALSE') {
			$str_message = $arr_retval[1];
			$err_status = 0;
		}

		// a check to make sure the quantities stored in the session array
		// are non-zero
		$str_result = is_all_nonzero();
		if ($str_result <> 'OK') {
			$str_message = $str_result;
			$err_status = 0;
		}
		
		if ($err_status != 0) {
			/*
				bill number is zero
            */
			$int_next_billNumber = 0;

			//====================================================
			// start transaction in case the CREATE TRANSFER fails
            //----------------------------------------------------
			$result_set = new Query("START TRANSACTION");
			
			$bill_saved = 1;
			$bill_items_saved = 1;
			if (!$_SESSION['bill_salesperson'])
				$_SESSION['bill_salesperson']=0;

			$customer_id = 0;
			if (IsNullOrEmpty($_SESSION['client_id'])) 
				$customer_id = $fs_account_customer_id;
			else
				$customer_id = $_SESSION['client_id'];
			
			//=========================
			// insert row in bill table
			//-------------------------

			if ((isset($_POST['is_draft_bill'])) && ($_POST['is_draft_bill']==true)) {


				/*
					update the bill table
				*/
				$str_query = "
					UPDATE ".Monthalize('bill')."
					SET
						storeroom_id = '".$_SESSION['int_current_storeroom']."', 
						bill_number = '".$int_next_billNumber."',
						date_created = '".getBillDate($_SESSION['current_bill_day'])."', 
						total_amount = '".number_format(RoundUp($_SESSION['bill_total']),2,'.','')."',
						payment_type = '".$_SESSION['current_bill_type']."',
						payment_type_number = '".$int_payment_number."',
						bill_promotion = '".$_SESSION["sales_promotion"]."',
						bill_status = '".BILL_STATUS_PROCESSING."',
						is_pending = 'N',
						user_id = '".$_SESSION['int_user_id']."',
						module_id = '2',
						resolved_on = '".getBillDate($_SESSION['current_bill_day'])."',
						CC_id = '".$int_current_CCID."',
						account_number = '".$_SESSION['current_account_number']."',
						account_name = '".addslashes($str_account_name)."',
						card_name = '".addslashes($_SESSION['bill_card_name'])."',
						card_number = '".addslashes($_SESSION['bill_card_number'])."',
						card_date = '".addslashes($_SESSION['bill_card_date'])."',
						aurocard_number = '".intval($_SESSION['aurocard_number'])."',
						aurocard_transaction_id = '".intval($_SESSION['aurocard_transaction_id'])."',
						upi_transaction_id = '".addslashes($_SESSION['upi_transaction_id'])."',
						upi_utr_number = '".addslashes($_SESSION['upi_utr_number'])."',
						salesperson_id = '".$_SESSION['bill_salesperson']."',
						customer_id = '".$customer_id."',
						table_ref = '".$_SESSION['bill_table_ref']."',
						is_draft = '1',
						gstin = '".$gstin."'
					WHERE bill_id = ".$_POST['draft_bill_id'];

				$result_set->Query($str_query);
				if ($result_set->b_error == true) {
					$bill_saved = 0;
					$str_message = 'An error occurred trying to save the draft. '.$_SESSION['int_user_id'];
					$sql_string = $str_query;
				}
				$int_bill_id = $_POST['draft_bill_id'];


				/*
					clear the previously saved items
				*/
				$sql = "DELETE FROM ".Monthalize('bill_items')." WHERE (bill_id = ".$_POST['draft_bill_id'].")";
				$result_set->Query($sql);
			}
			else {

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
						upi_transaction_id,
						upi_utr_number,
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
						intval($_SESSION['aurocard_transaction_id']).", '".
						addslashes($_SESSION['upi_transaction_id'])."', '".
						addslashes($_SESSION['upi_utr_number'])."', ".
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


			if (($bill_saved == 1) && ($bill_items_saved == 1)) {

				$result_set->Query("COMMIT");

				echo json_encode( array("bill_id"=>$int_bill_id, "message"=>"draft saved", "sql"=>"") );

				// clear the session variables related to the billing
				$_SESSION["bill_id"] = -1;
				$_SESSION['bill_number'] = '';
				unset($_SESSION["arr_total_qty"]);
				unset($_SESSION["arr_item_batches"]);
				/*
				if (IsSet($_POST["bill_type"]))
					$_SESSION['current_bill_type'] = $_POST["bill_type"];
				else
					$_SESSION['current_bill_type'] = 1;
				*/
				$_SESSION['current_bill_day'] = date('j');
				$_SESSION['current_account_number'] = "";
				$_SESSION['current_account_name'] = "";
				$_SESSION['bill_total'] = 0;
				$_SESSION['sales_promotion'] = 0;
				$_SESSION['fs_account_balance'] = 0;
				$_SESSION['bill_table_ref'] = '';

				$_SESSION['save_counter'] = 0;

			}
			else {

				recycle_bill_number($int_next_billNumber, $_SESSION['current_bill_type']);

				$result_set->Query("ROLLBACK");
				
				echo json_encode( array("bill_id"=>0, "message"=>$str_message, "sql"=>$sql_string ));
			}

		} // end if ($err_status != 0)

		else {

			echo json_encode( array("bill_id"=>0, "message"=>$str_message, "sql"=>$sql_string ));

		}

	} // end if (action ='draft')

} // end if set $_GET['action']


if ($can_save == 1) {
	//===================================
	// if the bill was saved successfully
	// update the stock...
	//-----------------------------------
	// If this bill has been editted
	// then cancel before saving anew
	//-------------------------------
	if ($_SESSION["bill_id"] > -1) {
		$str_message = cancelBill($_SESSION["bill_id"]);
	}
	
	$result_set->Query("START TRANSACTION");
	$bool_success = true;
	
	for ($i=0; $i<count($_SESSION['arr_total_qty']); $i++) {
		
		$str_retval = product_bill(
			$_SESSION['arr_total_qty'][$i]['batch_id'],
			$_SESSION['arr_total_qty'][$i][13],
			$_SESSION['arr_total_qty'][$i][2],
			$_SESSION['arr_total_qty'][$i][5],
			$int_next_billNumber,
			$_SESSION['current_bill_day'],
			2,
			$int_bill_id);
			
		$arr_retval = explode($str_retval, "|");
		if ($arr_retval[0] == 'ERROR') {
			$bool_success = false;
			$str_message = $arr_retval[1];
			break;
		}
	}
	
	if ($bool_success) {
		$result_set->Query("COMMIT");
		
		//===================================
		// create transfer if FS account bill
		//-----------------------------------
        $str_transfer_message = '';
		
		if (($_SESSION['current_bill_type'] == BILL_ACCOUNT) || ($_SESSION['current_bill_type'] == BILL_TRANSFER_GOOD)) {
			//========================================
			// get the account to make the transfer to
			//----------------------------------------
			$result_set->Query("
				SELECT bill_credit_account, bill_description
				FROM stock_storeroom
				WHERE (storeroom_id = ".$_SESSION['int_current_storeroom'].")
			");
			$credit_acount = $result_set->FieldByName('bill_credit_account');
			if ($_SESSION['current_bill_type'] == BILL_ACCOUNT) {
				$bill_description = str_replace("%s", $int_next_billNumber, $result_set->FieldByName('bill_description'));
				$bill_description = str_replace("%d", substr(getBillDate($_SESSION['current_bill_day']),0,10), $bill_description);
			}
			else {
				$bill_description = "TG:".$int_next_billNumber." - ".substr(getBillDate($_SESSION['current_bill_day']),0,10);
			}
			
			//==================
			// make the transfer
			//------------------
			$int_result = createTransfer(
				$_SESSION['current_account_number'],
				$credit_acount,
				$bill_description,
				$_SESSION['bill_total'],
				2,
				$int_bill_id);

			//========================
			// if transfer successfull
			// result +ve => success
			//------------------------
			if (($int_result > 0) || ($int_result == -1)) {
				$str_transfer_message = '';
			}
			//=========================
			// transfer not successfull
			//-------------------------
			else {
				// result -2 => insufficient funds
				if ($int_result == -2) {
					$str_transfer_message = "ERROR (".$int_result."): Insufficient funds to create this transfer.";
				}
				else {
					$str_transfer_message = "ERROR (".$int_result."): This transfer could not be completed.";
				}
			}
		}
		//====================================
		// Create transfer for Pour Tous bills
		//------------------------------------
		else if ($_SESSION['current_bill_type'] == 3) {
			$bill_description = "TRANSFER NUMBER ".$int_next_billNumber;
			
			$int_result = createPTTransfer(
				$_SESSION['current_account_number'],
				0, 
				$bill_description, 
				$_SESSION['bill_total'],
				2,
				$int_bill_id);
				
			if ($int_result > 0) {
				$str_transfer_message = '';
			}
			else {
				// result = -2 => insufficient funds
				if ($int_result == -2) {
					$str_transfer_message = "ERROR (".$int_result."): This transfer could not be completed.";
				}
			}
		}
		
		/*
		if ($str_transfer_message <> '') {
			echo "<script language=\"javascript\">\n";
			echo "alert('".$str_transfer_message."')";
			echo "</script>\n";
		}
		
		echo "<script language=\"javascript\">\n";
		echo "if (confirm('Bill saved successfully. \\n Would you like to print the bill?'))\n";
		echo "	printBill(".$int_bill_id.");\n";
		echo "setTimeout('top.document.location = \"billing_frameset.php?action=clear_bill&bill_type=".$_SESSION['current_bill_type']."\"',500);\n";
		echo "</script>\n";
		*/

		echo json_encode( array("bill_id"=>$int_bill_id, "message"=>$str_transfer_message, "sql"=>$sql_string ));

	}
	else {
		//====================
		// ERROR SAVING STOCK
		//--------------------
		// rollback
		//--------------------
		$result_set->Query("ROLLBACK");
		
		//====================
		// mark bill cancelled
		//--------------------
		$result_set->Query("
			UPDATE ".Monthalize('bill')."
			SET bill_status = ".BILL_STATUS_CANCELLED.",
				cancelled_user_id = ".$_SESSION["int_user_id"].",
				cancelled_reason = 'error saving bill'
			WHERE (bill_id = ".$int_bill_id.")
				AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
		");
		
		//====================
		// inform
		//--------------------
		/*
		echo "<script language=\"javascript\">\n";
		echo "alert('".$str_message." \\nThis bill could not be saved.');\n";
		echo "top.document.location = \"billing_frameset.php?action=clear_bill&bill_type=".$_SESSION['current_bill_type']."\";\n";
		echo "</script>\n";
		*/

		echo json_encode( array("bill_id"=>0, "message"=>$str_message, "sql"=>$sql_string ));
	}

	clear_bill_variables();

} // end of can_save

?>
