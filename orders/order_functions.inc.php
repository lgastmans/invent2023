<?php

//require_once($str_application_path."include/const.inc.php");
//require_once($str_application_path."include/session.inc.php");
//require_once($str_application_path."include/db.inc.php");

error_reporting(E_ALL);

if (file_exists("get_bill_number.php"))
	require_once("get_bill_number.php");
else if (file_exists("billing/get_bill_number.php"))
	require_once("billing/get_bill_number.php");
else if (file_exists("../billing/get_bill_number.php"))
	require_once("../billing/get_bill_number.php");
else if (file_exists("../../billing/get_bill_number.php"))
	require_once("../../billing/get_bill_number.php");

//====================
// Creates bills based on orders
// that satisfy the given date
//--------------------

function create_order_bills($aDate) {

	$str_retval = 'OK|0';

	$arr_date = getdate($aDate);

	$int_week = getweekofmonth($aDate);

	$int_counter = 0;

	if ($arr_date['wday'] > 0) {
		//====================
		// set the date string
		//--------------------
		$str_given_date = $arr_date['year']."-".sprintf("%02d",$arr_date['mon'])."-".sprintf("%02d",$arr_date['mday']);

		//====================
		// create 'DAILY' order bills
		//--------------------
		$qry_orders = new Query("
			SELECT *
			FROM ".Monthalize('orders')."
			WHERE order_type = ".ORDER_TYPE_DAILY."
				AND storeroom_id = ".$_SESSION['int_current_storeroom']."
				AND order_status <> ".ORDER_STATUS_CANCELLED."
		");

		for ($i=0; $i<$qry_orders->RowCount(); $i++) {

			if (create_an_order_bill($qry_orders->FieldByName('order_id'), $str_given_date))
				$int_counter++;

			$qry_orders->Next();
		}

		//====================
		// create 'weekly' order bills
		//--------------------
		$qry_orders = new Query("
			SELECT *
			FROM ".Monthalize('orders')."
			WHERE order_type = ".ORDER_TYPE_WEEKLY."
				AND day_of_week = ".$arr_date['wday']."
				AND storeroom_id = ".$_SESSION['int_current_storeroom']."
				AND order_status <> ".ORDER_STATUS_CANCELLED."
		");
				
		for ($i=0; $i<$qry_orders->RowCount(); $i++) {
			
			if (create_an_order_bill($qry_orders->FieldByName('order_id'), $str_given_date))
				$int_counter++;
			
			$qry_orders->Next();
		}

		//====================
		// create 'monthly' order bills
		//--------------------
		$qry_orders = new Query("
			SELECT *
			FROM ".Monthalize('orders')."
			WHERE order_type = ".ORDER_TYPE_MONTHLY."
				AND day_of_week = ".$arr_date['wday']."
				AND order_week = ".$int_week."
				AND storeroom_id = ".$_SESSION['int_current_storeroom']."
				AND order_status <> ".ORDER_STATUS_CANCELLED."
		");

		for ($i=0; $i<$qry_orders->RowCount(); $i++) {

			if (create_an_order_bill($qry_orders->FieldByName('order_id'), $str_given_date))
				$int_counter++;

			$qry_orders->Next();
		}

		//====================
		// create 'once' order bills
		//--------------------
		$qry_orders = new Query("
			SELECT *
			FROM ".Monthalize('orders')."
			WHERE order_type = ".ORDER_TYPE_ONCE."
				AND day_of_week = ".$arr_date['wday']."
				AND order_week = ".$int_week."
				AND order_month = ".$arr_date['mon']."
				AND storeroom_id = ".$_SESSION['int_current_storeroom']."
				AND order_status <> ".ORDER_STATUS_CANCELLED."
		");

		for ($i=0; $i<$qry_orders->RowCount(); $i++) {

			if (create_an_order_bill($qry_orders->FieldByName('order_id'), $str_given_date))
				$int_counter++;

			$qry_orders->Next();
		}
		
		$str_retval = "OK|".$int_counter;

	}
	else
		$str_retval = 'FALSE|0';

	return $str_retval;
}

function getweekofmonth($aDate) {
	$arr_date = getdate($aDate);

	$arr_start_date = getdate(strtotime($arr_date['year']."-".$arr_date['mon']."-01"));

	$int_current_day = 1 + (7 - $arr_start_date['wday']);

	$int_current_week = 1;

	while ($arr_date['mday'] > $int_current_day) {
		$int_current_day = $int_current_day + 7;
		$int_current_week = $int_current_week + 1;
	}

	return $int_current_week;
}

//====================
// Creates a bill based on an order id
//--------------------

function create_an_order_bill($anOrderId, $anOrderDate) {

	//====================
	// dummy query
	//--------------------
	$qry_exists = new Query("SELECT * FROM ".Monthalize('orders')." LIMIT 1");
	$qry_account = new Query("SELECT * FROM account_cc LIMIT 1");
	$qry_order_items = new Query("SELECT * FROM ".Monthalize('order_items')." LIMIT 1");
	$qry_stock = new Query("SELECT * FROM ".Monthalize('stock_storeroom_product')." LIMIT 1");

	$qry_order = new Query("
		SElECT *
		FROM ".Monthalize('orders')."
		WHERE order_id = ".$anOrderId."
	");

	$str_query = "
		SELECT *
		FROM ".Monthalize('bill')."
		WHERE module_id = 7
			AND storeroom_id = ".$_SESSION['int_current_storeroom']."
			AND module_record_id = ".$anOrderId."
			AND DATE(date_created) = '".$anOrderDate."'";
	$qry_exists->Query($str_query);
	
	$bool_retval = false;
	
	if ($qry_exists->RowCount() == 0) {
		$bool_retval = true;
		
		//====================
		// create the bill for this order
		// get the next bill number
		// in case the clients module is active
		//--------------------
		if (getModuleByID(9) === null) {
			$int_next_billnumber = get_bill_number($qry_order->FieldByName('payment_type'));
		}
		else {
			$int_next_billnumber = 0;
		}

		//====================
		// get the account details
		//--------------------
		$qry_account->Query("
			SELECT *
			FROM account_cc
			WHERE cc_id = ".$qry_order->FieldByName('CC_id')."
		");
		$str_account_number = '';
		$str_account_name = '';
		if ($qry_account->RowCount() > 0) {
			$str_account_number = $qry_account->FieldByName('account_number');
			$str_account_name = $qry_account->FieldByName('account_name');
		}

		// get the items for this order
		$str_query = "
			SELECT *
			FROM ".Monthalize('order_items')."
			WHERE order_id = ".$anOrderId;
		$qry_order_items->Query($str_query);

		if (getModuleByID(9) === null) {
			if ($qry_order->FieldByName('is_billable') == 'Y') {
				$int_status = BILL_STATUS_UNRESOLVED;
				$str_pending = 'Y';
			}
			else {
				$int_status = BILL_STATUS_RESOLVED;
				$str_pending = 'N';
			}
		}
		else {
			$int_status = BILL_STATUS_PROCESSING;
			$str_pending = 'Y';
		}
		
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
				module_record_id,
				CC_id,
				account_number,
				account_name,
				is_debit_bill,
				discount
			)
			VALUES (
				".$_SESSION['int_current_storeroom'].",
				".$int_next_billnumber.",
				'".$anOrderDate." ".date("H:i:s")."',
				".$qry_order->FieldByName('total_amount').",
				".$qry_order->FieldByName('payment_type').",
				0,
				0,
				".$int_status.",
				'".$str_pending."',
				".$_SESSION['int_user_id'].",
				7,
				".$qry_order->FieldByName('order_id').",
				".$qry_order->FieldByName('CC_id').",
				'".$str_account_number."',
				'".$str_account_name."',
				'".$qry_order->FieldByName('is_debit_invoice')."',
				".$qry_order->FieldByName('discount')."
			)";
		$qry_exists->Query($str_query);

		$temp = $str_query;

		if ($qry_exists->b_error == true) {
			$bool_retval = false;
		}
		$int_bill_id = $qry_exists->getInsertedID();
		
		//==========================================================
		// create the corresponding items
		// the product's relevant batch information
		// is not updated now as the bill is pending - not processed
		// however, the ordered status for the given product
		// should be updated in stock_storeroom_product
		//----------------------------------------------------------
		for ($j=0; $j<$qry_order_items->RowCount(); $j++) {
			
			if ($qry_order->FieldByName('is_debit_invoice') == 'N') {
				$str_query = "
					UPDATE ".Monthalize('stock_storeroom_product')."
					SET stock_reserved = stock_reserved + ".$qry_order_items->FieldByName('quantity_delivered')."
					WHERE storeroom_id = ".$_SESSION['int_current_storeroom']."
						AND product_id = ".$qry_order_items->FieldByName('product_id');
				$qry_stock->Query($str_query);
				if ($qry_stock->b_error == true)
					$bool_retval = false;
			}
			
			$qry_stock->Query("
				SELECT product_description
				FROM stock_product
				WHERE product_id = ".$qry_order_items->FieldByName('product_id')."
			");
			if ($qry_stock->b_error == true)
				$bool_retval = false;
			$str_product_description = '';
			if ($qry_stock->RowCount() > 0)
				$str_product_description = $qry_stock->FieldByName('product_description');
			
			$qry_exists->Query("
				INSERT INTO ".Monthalize('bill_items')."
				(
					quantity,
					quantity_ordered,
					discount,
					price,
					tax_id,
					tax_amount,
					product_id,
					bill_id,
					batch_id,
					adjusted_quantity,
					product_description
				)
				VALUES (
					".$qry_order_items->FieldByName('quantity_ordered').",
					".$qry_order_items->FieldByName('quantity_ordered').",
					0,
					".$qry_order_items->FieldByName('price').",
					0,
					0,
					".$qry_order_items->FieldByName('product_id').",
					".$int_bill_id.",
					0,
					0,
					'".addslashes($str_product_description)."'
				)
			");
			if ($qry_exists->b_error == true)
				$bool_retval = false;
			$qry_order_items->Next();
		}
	}
	if ($bool_retval)
		return "OK";
	else
		return "ERROR".$temp;
}


//==============================================
//  $orderDay is the numeric representation of the day of the week
//      0 (for Sunday) through 6 (for Saturday)

class order_dates {
  
    var $startDate;
    var $orderFrequency;
    var $orderDay;
    var $orderWeek;
    var $orderMonth;
    
    function get_date_list() {
        if ($this->orderFrequency == 'daily') {
            
            $arr_start_date = getdate($this->startDate);
            
            $int_days_in_month = $this->DaysInMonth($arr_start_date[mon],$arr_start_date[year]);
            
            $arr_result = array();
            
            for ($i = $arr_start_date[mday]; $i <= $int_days_in_month; $i++) {
                $str_day_increment = "+".($i-$arr_start_date[mday])." days";
                $cur_date = strtotime($str_day_increment, $arr_start_date[0]);
                $arr_check_date = getdate($cur_date);
                if ($arr_check_date[wday] <> 0) { // sunday not included
                    $arr_result[count($arr_result)] = $cur_date;
                }
            }
        }
        else if ($this->orderFrequency == 'weekly') {
            
            $arr_start_date = getdate($this->startDate);
            
            if ($arr_start_date[wday] > $this->orderDay) {
                $int_difference = (6 - $arr_start_date[wday]) + $this->orderDay + 1;
            }
            else {
                $int_difference = $this->orderDay - $arr_start_date[wday];
            }
            $int_start_day = $arr_start_date[mday] + $int_difference;
            
            $int_days_in_month = $this->DaysInMonth($arr_start_date[mon],$arr_start_date[year]);
            
            $arr_result = array();
            
            for ($i = $int_start_day; $i <= $int_days_in_month; $i=$i+7) {
                $str_day_increment = "+".($i-$int_start_day)." days";
                $input_timestamp = strtotime($arr_start_date[year]."-".$arr_start_date[mon]."-".$int_start_day);
                $cur_date = strtotime($str_day_increment, $input_timestamp);
                $arr_check_date = getdate($cur_date);
                if ($arr_check_date[wday] <> 0) { // sunday not included
                    $arr_result[count($arr_result)] = $cur_date;
                }
            }
        }
        else if ($this->orderFrequency == 'monthly') {

            $arr_start_date = getdate($this->startDate);
            
            $arr_start_date = strtotime($arr_start_date[year]."-".$arr_start_date[mon]."-01");
            $arr_start_date = getdate($arr_start_date);
            
            $int_current_month = $arr_start_date[mon];
            $int_current_year = $arr_start_date[year];
            
            if ($arr_start_date[wday] > $this->orderDay) {
                $int_difference = (6 - $arr_start_date[wday]) + $this->orderDay + 1;
            }
            else {
                $int_difference = $this->orderDay - $arr_start_date[wday];
            }
            $int_start_day = $arr_start_date[mday] + $int_difference;
            
            $input_timestamp = strtotime($arr_start_date[year]."-".$arr_start_date[mon]."-".$int_start_day);
            
            if ($this->orderWeek == 1) {
                if ($input_timestamp < $this->startDate)
                    $arr_result[] = -1;
                else
                    $arr_result[] = $input_timestamp;
            }
            else  {
                $str_week_increment = "+".($this->orderWeek-1)." weeks";
                $input_timestamp = strtotime($str_week_increment, $input_timestamp);
                $arr_date_to_check = getdate($input_timestamp);
                if ($arr_date_to_check[mon] <> $int_current_month)
                    $arr_result[] = -1;
                else {
                    if ($input_timestamp < $this->startDate)
                        $arr_result[] = -1;
                    else
                        $arr_result[] = $input_timestamp;
                }
            }            
        }
        else if ($this->orderFrequency == 'once') {

            $arr_start_date = getdate($this->startDate);
            
            $int_start_day = $arr_start_date[mday];
            $int_start_month = $arr_start_date[mon];
            $int_start_year = $arr_start_date[year];
            
            if ($this->orderMonth >= $arr_start_date[mon]) {
                $arr_start_date = strtotime($arr_start_date[year]."-".$this->orderMonth."-01");
                $arr_start_date = getdate($arr_start_date);
            }
            else {
                $arr_start_date = strtotime(($arr_start_date[year]+1)."-".$this->orderMonth."-01");
                $arr_start_date = getdate($arr_start_date);
            }
            $int_current_month = $arr_start_date[mon];
            $int_current_year = $arr_start_date[year];
            
            if ($arr_start_date[wday] > $this->orderDay) {
                $int_difference = (6 - $arr_start_date[wday]) + $this->orderDay + 1;
            }
            else {
                $int_difference = $this->orderDay - $arr_start_date[wday];
            }
            $int_start_day = $arr_start_date[mday] + $int_difference;
            
            $input_timestamp = strtotime($int_current_year."-".$int_current_month."-".$int_start_day);
            
            if ($this->orderWeek == 1) {
                if ($input_timestamp < $this->startDate)
                    $arr_result[] = -1;
                else
                    $arr_result[] = $input_timestamp;
            }
            else  {
                $str_week_increment = "+".($this->orderWeek-1)." weeks";
                $input_timestamp = strtotime($str_week_increment, $input_timestamp);
                $arr_date_to_check = getdate($input_timestamp);
                if ($arr_date_to_check[mon] <> $this->orderMonth)
                    $arr_result[] = -1;
                else {
                    if ($input_timestamp < $this->startDate)
                        $arr_result[] = -1;
                    else
                        $arr_result[] = $input_timestamp;
                }
            }            
        }
        else
            $arr_result[] = -1;
        
        return $arr_result;
    }
    
    function DaysInMonth($month, $year) {
           if(checkdate($month, 31, $year)) return 31;
           if(checkdate($month, 30, $year)) return 30;
           if(checkdate($month, 29, $year)) return 29;
           if(checkdate($month, 28, $year)) return 28;
           return 0; // error
    }    
}


function create_order_bills_per_order($int_order_id) {

	$str_retval = "OK|Successfull";
	
	$qry_order = new Query("
		SELECT *
		FROM ".Monthalize('orders')."
		WHERE order_id = $int_order_id
	");
	
	// dummy initialization
	$qry_insert = new Query("SELECT * FROM ".Monthalize('order_bills')." LIMIT 1");
	
	if ($qry_order->RowCount() > 0) {
		$obj_order_dates = new order_dates();
		$obj_order_dates->startDate = time();
		if ($qry_order->FieldByName('order_type') == ORDER_TYPE_DAILY)
			$obj_order_dates->orderFrequency = 'daily';
		else if ($qry_order->FieldByName('order_type') == ORDER_TYPE_WEEKLY)
			$obj_order_dates->orderFrequency = 'weekly';
		else if ($qry_order->FieldByName('order_type') == ORDER_TYPE_MONTHLY)
			$obj_order_dates->orderFrequency = 'monthly';
		else if ($qry_order->FieldByName('order_type') == ORDER_TYPE_ONCE)
			$obj_order_dates->orderFrequency = 'once';
		$obj_order_dates->orderDay = $qry_order->FieldByName('day_of_week');
		$obj_order_dates->orderWeek = $qry_order->FieldByName('order_week');
		$obj_order_dates->orderMonth = $qry_order->FieldByName('order_month');
		
		$arr_order_dates = $obj_order_dates->get_date_list();
		
		$qry_order_items = new Query("
			SELECT *
			FROM ".Monthalize('order_items')."
			WHERE order_id = $int_order_id
		");

		for ($i=0;$i<count($arr_order_dates);$i++) {
	
			$qry_insert->Query("
				INSERT INTO ".Monthalize('order_bills')."
				(
					order_id,
					total_amount,
					order_date,
					order_status,
					storeroom_id,
					user_id
				)
				VALUES (
					".$int_order_id.", ".
					$qry_order->FieldByName('total_amount').", '".
					date('Y-m-d', $arr_order_dates[$i])."', ".
					ORDER_STATUS_PENDING.", ".
					$_SESSION['int_current_storeroom'].", ".
					$_SESSION['int_user_id']."
				)
			");
			if ($qry_insert->b_error == true) {
				$str_retval = 'ERROR|Order bills creation error';
				break;
			}

			$int_order_bill_id = $qry_insert->getInsertedID();
			
			if ($qry_order_items->RowCount() > 0) {
				
				for ($j=0;$j<$qry_order_items->RowCount();$j++) {
		
					$str_insert = "
						INSERT INTO ".Monthalize('order_bill_items')."
						(
							order_bill_id,
							quantity,
							is_temporary,
							adjusted,
							product_id
						)
						VALUES (
							".$int_order_bill_id.", ".
							$qry_order_items->FieldByName('quantity').", '".
							$qry_order_items->FieldByName('is_temporary')."', ".
							$qry_order_items->FieldByName('adjusted').", ".
							$qry_order_items->FieldByName('product_id')."
						)" ;
					$qry_insert->Query($str_insert);
					
					if ($qry_insert->b_error == true) {
						$str_retval = "ERROR|Order bill items creation error";
						break;
					}
					
					$qry_order_items->Next();
				}
			}
        	}
    	}
	else
		$str_retval = 'ERROR|Order not found';
 
	return $str_retval;
}

?>