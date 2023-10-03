<?
require_once("../../include/const.inc.php");
//require_once("../../common/product_funcs.inc.php");
require_once("xml_parser_class.php");
require_once("DB.php");
require_once("db_params.php");

import_orders();
import_order_products();

function import_orders() {
$attributes = array("RESELLER_ORDER_ID","CC_ID","ORDER_DATE");
	$identifier = "ORDER";
	$filter = "orders/orders_";
	
	$int_storeroom_id = 1;
	$int_user_id = 1;
	$int_month = date('n', time());
	$int_year = date('Y', time());
	
	global $conn;
	
	// ***
	// get list of 'orders' xml files in folder
	//***
	$arr_files = glob($filter."*.xml");
//print_r($arr_files);
	if (count($arr_files) == 0)
		echo "No orders imported<br>";
	
	for ($i=0;$i<count($arr_files);$i++) {
		$int_updated = 0;
		$int_inserted = 0;
		
		//***
		// parse file
		//***
		$p = &new myParser($attributes, $identifier);
		
		$result = $p->setInputFile($arr_files[$i]);
		$result = $p->parse();
		
		if ($result) {
			$arr_rows = $p->getData();
			
			//***
			// update orders table
			//***
			$qry =& $conn->query("SELECT * FROM orders_".$int_year."_".$int_month." LIMIT 1");
			
			foreach($arr_rows as $value) {
				$str_query = "
					SELECT *
					FROM orders_".$int_year."_".$int_month."
					WHERE reseller_order_id = ".$value['RESELLER_ORDER_ID'];
				$qry =& $conn->query($str_query);
				
				if ($qry->numRows() > 0) {
					$str_query = "
						UPDATE orders_".$int_year."_".$int_month."
						SET CC_id = ".$value['CC_ID'].",
							order_date = '".$value['ORDER_DATE']."'
						WHERE reseller_order_id = ".$value['RESELLER_ORDER_ID'];
					
					echo "Order ".$value['RESELLER_ORDER_ID']." updated! <br>";
					
					$int_updated++;
				}
				else {
					$str_query = "
						INSERT INTO orders_".$int_year."_".$int_month."
						(
							reseller_order_id,
							CC_id,
							order_date,
							order_status,
							storeroom_id,
							user_id
						)
						VALUES (
							".$value['RESELLER_ORDER_ID'].",
							".$value['CC_ID'].",
							'".$value['ORDER_DATE']."',
							".ORDER_STATUS_RECEIVED.",
							".$int_storeroom_id.",
							".$int_user_id."
							
						)
					";
					
					$int_inserted++;
				}
				$qry =& $conn->query($str_query);
				if (DB::isError($conn))
					echo $conn->getMessage()."<br>";
			}
			
			echo "Imported file ".$arr_files[$i]."<br>";
			echo "Updated ".$int_updated."<br>";
			echo "Inserted ".$int_inserted."<br><br>";
			
			$p->free();
			
			//***
			// remove the xml file
			//***
			if (unlink($arr_files[$i]))
				echo "successfully deleted ".$arr_files[$i]."<br>";
			else
				echo $arr_files[$i]." NOT DELETED<br>";
		}
	}
	
	return true;
}

function import_order_products() {
	$attributes = array("RESELLER_ORDER_ID","PRODUCT_ID","QUANTITY");
	$identifier = "ORDER_PRODUCT";
	$filter = "orders/order_products_";
	
	$int_month = date('n', time());
	$int_year = date('Y', time());
	
	global $conn;
	
	// ***
	// get list of 'orders' xml files in folder
	//***
	$arr_files = glob($filter."*.xml");

	if (count($arr_files) == 0)
		echo "No products imported<br>";
	
	for ($i=0;$i<count($arr_files);$i++) {
		$int_updated = 0;
		$int_inserted = 0;
		
		//***
		// parse file
		//***
		$p = &new myParser($attributes, $identifier);
		
		$result = $p->setInputFile($arr_files[$i]);
		$result = $p->parse();
		
		if ($result) {
			$arr_rows = $p->getData();
			
			//***
			// update order_products table
			//***
			$qry =& $conn->query("SELECT * FROM order_items_".$int_year."_".$int_month." LIMIT 1");
			
			foreach($arr_rows as $value) {
				//***
				// get the order_id
				//***
				$str_query = "
					SELECT order_id
					FROM orders_".$int_year."_".$int_month."
					WHERE reseller_order_id = ".$value['RESELLER_ORDER_ID'];
				$qry =& $conn->query($str_query);
				$obj =& $qry->fetchRow();
				$int_order_id = $obj->order_id;
				
				$str_query = "
					SELECT *
					FROM order_items_".$int_year."_".$int_month."
					WHERE order_id = ".$int_order_id."
						AND product_id = ".$value['PRODUCT_ID'];
				$qry =& $conn->query($str_query);
				
				$flt_price = getSellingPrice($value['PRODUCT_ID']);

				if ($qry->numRows() > 0) {
					$str_query = "
						UPDATE order_items_".$int_year."_".$int_month."
						SET quantity_ordered = ".$value['QUANTITY'].",
							price = ".$flt_price."
						WHERE order_id = ".$int_order_id."
							AND product_id = ".$value['PRODUCT_ID'];
					
					echo "Order product ".$value['PRODUCT_ID']." updated! <br>";
					
					$int_updated++;
				}
				else {
					$str_query = "
						INSERT INTO order_items_".$int_year."_".$int_month."
						(
							order_id,
							quantity_ordered,
							price,
							product_id
						)
						VALUES (
							".$int_order_id.",
							".number_format($value['QUANTITY'],0).",
							".$flt_price.",
							".$value['PRODUCT_ID']."
						)
					";
	
					$int_inserted++;
				}
			
				$qry =& $conn->query($str_query);
				if (DB::isError($conn))
					echo $conn->getMessage()."<br>";
			}
			
			echo "Imported file ".$arr_files[$i]."<br>";
			echo "Updated ".$int_updated."<br>";
			echo "Inserted ".$int_inserted."<br><br>";
			
			$p->free();
			
			//***
			// remove the xml file
			//***
			if (unlink($arr_files[$i]))
				echo "successfully deleted ".$arr_files[$i]."<br>";
			else
				echo $arr_files[$i]." NOT DELETED<br>";
		}
	}
	
	return true;
}

function CurMonthalize($str_table) {
	return $str_table."_".date('Y')."_".date('n');
}


function CurYearalize($str_table) {
	if ( (date('n') < 4) && (date('n') > 0)) {
		$int_year = date('Y') -1;
		return $str_table."_".$int_year;
	}
	else
		return $str_table."_".date('Y');
}

function getSellingPrice($int_product_id, $int_batch_id=0) {
	$flt_selling_price = 0;
	$int_storeroom_id = 1;

	global $conn;

	if ($int_batch_id > 0){
		$str_qry="
			SELECT IF(ssp.use_batch_price = 'Y', sb.selling_price, ssp.sale_price) AS selling_price
			FROM ".CurYearalize('stock_batch')." sb, ".CurMonthalize('stock_storeroom_batch')." ssb, ".CurMonthalize('stock_storeroom_product')." ssp
			WHERE (sb.product_id = ".$int_product_id.")
				AND (sb.storeroom_id = $int_storeroom_id)
				AND (ssp.product_id = ".$int_product_id.")
				AND (ssp.storeroom_id = $int_storeroom_id)
				AND (sb.status = ".STATUS_COMPLETED.")
				AND (sb.deleted = 'N')
				AND (ssb.product_id = sb.product_id)
				AND (ssb.batch_id = sb.batch_id)
				AND (ssb.storeroom_id = sb.storeroom_id)
				AND (ssb.is_active = 'Y')
				AND (sb.batch_id = $int_batch_id)
			ORDER BY date_created DESC
		";
		$qry =& $conn->query($str_qry);
	}
	else{
		$str_qry="

			SELECT IF(ssp.use_batch_price = 'Y', sb.selling_price, ssp.sale_price) AS selling_price
			FROM ".CurYearalize('stock_batch')." sb, ".CurMonthalize('stock_storeroom_batch')." ssb, ".CurMonthalize('stock_storeroom_product')." ssp
			WHERE (sb.product_id = ".$int_product_id.")
				AND (sb.storeroom_id = $int_storeroom_id)
				AND (ssp.product_id = ".$int_product_id.")
				AND (ssp.storeroom_id = $int_storeroom_id)
				AND (sb.status = ".STATUS_COMPLETED.")
				AND (sb.deleted = 'N')
				AND (ssb.product_id = sb.product_id)
				AND (ssb.batch_id = sb.batch_id)
				AND (ssb.storeroom_id = sb.storeroom_id)
				AND (ssb.is_active = 'Y')
			ORDER BY date_created DESC
		";
		$qry =& $conn->query($str_qry);
	}
	if ($qry->numRows() > 0 ) {
		$obj =& $qry->fetchRow();
		$flt_selling_price = $obj->selling_price;
//		$flt_selling_price = $qry->FieldByName('selling_price');
	}
	
	return number_format($flt_selling_price,2,'.','');
}

?>