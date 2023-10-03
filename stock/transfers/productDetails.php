<?
	error_reporting(E_ERROR);

	require_once("../../include/db.inc.php");
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	

	function productDetails($strProductCode) {
	
		// default return string
		$strDescription = "__NOT_FOUND";

		if ($strProductCode != 'nil') {

			// check whether the code exists
			$result_search = new Query("
				SELECT sp.product_id, sp.product_code, sp.product_description, sp.tax_id, sp.is_available, 
					sp.margin_percent, smu.measurement_unit, smu.is_decimal
				FROM stock_product sp
					INNER JOIN stock_measurement_unit smu ON (sp.measurement_unit_id = smu.measurement_unit_id)
				WHERE ((product_code = '".$strProductCode."') OR (product_bar_code = '".$strProductCode."'))
					AND (deleted = 'N')");

			if ($result_search->GetErrorMessage()<>"") die ($result_search->GetErrorMessage());

			// get the list of suppliers for the given code
			$result_supplier = new Query("
				SELECT sp.supplier2_id, sp.supplier3_id, 
					ss.supplier_id AS id_1, ss.supplier_name AS supplier_name1,
					ss2.supplier_id AS id_2, ss2.supplier_name AS supplier_name2,
					ss3.supplier_id AS id_3, ss3.supplier_name AS supplier_name3
				FROM
					stock_product sp
				LEFT JOIN stock_supplier ss ON (sp.supplier_id = ss.supplier_id)
				LEFT JOIN stock_supplier ss2 ON (sp.supplier2_id = ss2.supplier_id) AND
					(sp.product_id=".$result_search->FieldByName('product_id').")
				LEFT JOIN stock_supplier ss3 ON (sp.supplier3_id = ss3.supplier_id) AND
					(sp.product_id=".$result_search->FieldByName('product_id').")
				WHERE (sp.product_id=".$result_search->FieldByName('product_id').")
			");
			$supplier1 = "";
			$supplier2 = "";
			$supplier3 = "";
			$supplier1_id = 0;
			$supplier2_id = 0;
			$supplier3_id = 0;
			if ($result_supplier->b_error == false) {
				$supplier1 = $result_supplier->FieldByName('supplier_name1');
				$supplier2 = $result_supplier->FieldByName('supplier_name2');
				$supplier3 = $result_supplier->FieldByName('supplier_name3');
				$supplier1_id = $result_supplier->FieldByName('id_1');
				$supplier2_id = $result_supplier->FieldByName('id_2');
				$supplier3_id = $result_supplier->FieldByName('id_3');
			}

			$description = '__NOT_FOUND';
			$buying_price = 0;
			$selling_price = 0;
			$tax_id = 0;
			$current_stock = 0;
			$adjusted_stock = 0;
			
			if ($result_search->RowCount() > 0) {
				// check whether there are transfers of type TYPE_ADJUSTMENT
				$qry_stock = new Query("
					SELECT stock_adjusted
					FROM ".Monthalize('stock_storeroom_product')."
					WHERE (product_id = ".$result_search->FieldByName('product_id').")
						AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
				");
				if ($qry_stock->RowCount() > 0) {
					$adjusted_stock = $qry_stock->FieldByName('stock_adjusted');
				}

				$qry = new Query("
					SELECT * 
					FROM ".Monthalize('stock_storeroom_product')."
					WHERE (product_id = ".$result_search->FieldByName('product_id').") 
					AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
				");

				if ($qry->FieldByName('use_batch_price') == 'Y') {
					$qry_prices = new Query("
						SELECT * 
						FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb
						WHERE (sb.product_id = ".$result_search->FieldByName('product_id').") 
							AND (sb.storeroom_id = ".$_SESSION["int_current_storeroom"].") 
							AND (sb.is_active = 'Y') 
							AND (sb.status = ".STATUS_COMPLETED.") 
							AND (sb.deleted = 'N') 
							AND (ssb.product_id = sb.product_id) 
							AND (ssb.batch_id = sb.batch_id) 
							AND (ssb.storeroom_id = sb.storeroom_id) 
							AND (ssb.is_active = 'Y')
						ORDER BY date_created DESC 
						LIMIT 1
					");
					if ($qry_prices->b_error == false) {
						$buying_price = $qry_prices->FieldByName('buying_price');
						$selling_price = $qry_prices->FieldByName('selling_price');
					}
				}
				else {
					$buying_price = $qry->FieldByName('buying_price');
					$selling_price = $qry->FieldByName('sale_price');
				}
				
				$current_stock = $qry->FieldByName('stock_current');
				$description = $result_search->FieldByName('product_description');
			}

			$tax_id = $result_search->FieldByName('tax_id');
			if ($result_search->FieldByName('is_available') == 'N')
				$is_available = "__NOT_AVAILABLE";
			else
				$is_available = "__AVAILABLE";
			
			$_SESSION["drcve_margin_percent"] = $result_search->FieldByName('margin_percent');
	
			$strDescription = $description."|".$buying_price."|".$selling_price."|".$tax_id."|".$supplier1_id."|".$supplier1."|".$supplier2_id."|".$supplier2."|".$supplier3_id."|".$supplier3."|".$is_available."|".$current_stock."|".$result_search->FieldByName('measurement_unit')."|".$result_search->FieldByName('is_decimal')."|".$adjusted_stock."|".$result_search->FieldByName('product_id');
		}

		// return the description of the item
		return $strDescription;
	}
	
	if (!empty($_GET['live'])) {
		if (!empty($_GET['product_code'])) {
			echo productDetails($_GET['product_code']);
			die();
		}
		else {
			die("__NOT_FOUND");
		}
	}

?>