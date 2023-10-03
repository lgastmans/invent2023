<?
	require_once("../../include/const.inc.php");
	require_once("DB.php");
	require_once("db_params.php");
	require_once('ftpclass.php');
	include("../../common/product_funcs.inc.php");
		
	$str_dest_folder = "xml_files/";
	
	function replace_special_chars($str) {
		$str = str_replace('"', '&quot;', $str);
		$str = str_replace('&', '&amp;', $str);
		$str = str_replace("'", '&apos;', $str);
		$str = str_replace('<', '&lt;', $str);
		$str = str_replace('>', '&gt;', $str);
		
		return $str;
	}
	
	function CurMonthalize($str_table) {
		return $str_table."_".date('Y', time())."_".date('n', time());
	}
	
	/*
		CLIENTS
	*
		select modified records
	*/
	$str_query = "
		SELECT *
		FROM customer
		WHERE is_modified = 'Y'
	";
	$qry =& $conn->query($str_query);
	
	//**
	// write data to string
	//**
	if ($qry->numRows() > 0) {
		$bool_success = true;
		$str_contents = '';
		
		$str_contents = "<?xml version='1.0'?>\n";
		$str_contents .= "<clients>\n";
		while ($obj =& $qry->fetchRow()) {
			/*
				get the tax percentage
				eventual update : assuming that there is just
				one entry for the tax definition
			*/
			$qry_tax =& $conn->query("
				SELECT
					td.definition_percent
				FROM ".CurMonthalize("stock_tax_links")." tl
				INNER JOIN ".CurMonthalize("stock_tax_definition")." td
					ON td.definition_id = tl.tax_definition_id
				WHERE tl.tax_id=".$obj->tax_id." order by tl.tax_order"
			);
			$obj_tax = $qry_tax->fetchRow();
			
			$str_contact = $obj->contact_person;
			$str_contact = replace_special_chars($str_contact);
			$str_company = $obj->company;
			$str_company = replace_special_chars($str_company);
			$str_address = $obj->address;
			$str_address = replace_special_chars($str_address);
			
			$str_contents .= "<client>\n";
			$str_contents .= "<id id='".$obj->id."'>".$obj->id."</id>\n";
			$str_contents .= "<username username='".$obj->username."'>".$obj->username."</username>\n";
			$str_contents .= "<password password='".$obj->password."'>".$obj->password."</password>\n";
			$str_contents .= "<active active='".$obj->is_active."'>".$obj->is_active."</active>\n";
			$str_contents .= "<contact contact='".$str_contact."'>".$str_contact."</contact>\n";
			$str_contents .= "<company company='".$str_company."'>".$str_company."</company>\n";
			$str_contents .= "<address address='".$str_address."'>".$str_address."</address>\n";
			$str_contents .= "<city city='".$obj->city."'>".$obj->city."</city>\n";
			$str_contents .= "<zip zip='".$obj->zip."'>".$obj->zip."</zip>\n";
			$str_contents .= "<can_view_price can_view_price='".$obj->can_view_price."'>".$obj->can_view_price."</can_view_price>\n";
			$str_contents .= "<currency_id currency_id='".$obj->currency_id."'>".$obj->currency_id."</currency_id>\n";
			$str_contents .= "<tax tax='".$obj_tax->definition_percent."'>".$obj_tax->definition_percent."</tax>\n";
			$str_contents .= "<email email='".$obj->email."'>".$obj->email."</email>\n";
			$str_contents .= "</client>\n";
		}
		$str_contents .= "</clients>\n";
		
		//**
		//write data to file
		//**
		$filename = $str_dest_folder."clients_".date("YmjHis", time()).".xml";
		
		$handle = fopen($filename, "x");
		if ($handle) {
			fputs($handle, $str_contents);
			fclose($handle);
		}
		else
			$bool_success = false;

		//**
		// reset the is_modified flag
		//**
		if ($bool_success) {
			$str_query = "
				UPDATE customer
				SET is_modified = 'N'
			";
			$qry =& $conn->query($str_query);
		}
	}

	//***
	// PRODUCTS
	//***
	// select modified records
	//***
	$str_query = "
		SELECT *
		FROM stock_product
		WHERE is_modified = 'Y'
			AND deleted = 'N'
	";
	$qry =& $conn->query($str_query);
	
	//**
	// write data to string and send the image
	//**
	if ($qry->numRows() > 0) {
		$bool_success = true;
		$str_contents = '';
		
		$str_contents = "<?xml version='1.0'?>\n";
//		$str_contents .= "<!DOCTYPE names [<!ENTITY ampersand '&#231;'>]>\n";
		$str_contents .= "<products>\n";
		while ($obj =& $qry->fetchRow()) {
			$str_description = $obj->product_description;
			$str_description = replace_special_chars($str_description);
			
			$str_contents .= "<product>\n";
			$str_contents .= "<product_id product_id='".$obj->product_id."'>".$obj->product_id."</product_id>\n";
			$str_contents .= "<product_code product_code='".$obj->product_code."'>".$obj->product_code."</product_code>\n";
			$str_contents .= "<product_description product_description='$str_description'>".$str_description."</product_description>\n";
			$str_contents .= "<tax_id tax_id='".$obj->tax_id."'>".$obj->tax_id."</tax_id>\n";
			$str_contents .= "<measurement_unit_id measurement_unit_id='".$obj->measurement_unit_id."'>".$obj->measurement_unit_id."</measurement_unit_id>\n";
			$str_contents .= "<category_id category_id='".$obj->category_id."'>".$obj->category_id."</category_id>\n";
			$str_contents .= "<mrp mrp='".getSellingPrice($obj->product_id)."'>".$obj->mrp."</mrp>\n";
//			$str_contents .= "<mrp mrp='".$obj->mrp."'>".$obj->mrp."</mrp>\n";
			$str_contents .= "<image_filename image_filename='".$obj->image_filename."'>".$obj->image_filename."</image_filename>\n";
			$str_contents .= "<visible visible='".$obj->is_reseller_visible."'>".$obj->is_reseller_visible."</visible>\n";
			$str_contents .= "<client_id client_id='".$obj->reseller_client_id."'>".$obj->reseller_client_id."</client_id>\n";
			$str_contents .= "</product>\n";
		}
		$str_contents .= "</products>\n";
		
		//**
		//write data to file
		//**
		$filename = $str_dest_folder."products_".date("YmjHis", time()).".xml";
		
		$handle = fopen($filename, "x");
		if ($handle) {
			fputs($handle, $str_contents);
			fclose($handle);
		}
		else
			$bool_success = false;

		//**
		// reset the is_modified flag
		//**
		if ($bool_success) {
			$str_query = "
				UPDATE stock_product
				SET is_modified = 'N'
			";
			$qry =& $conn->query($str_query);
		}
	}

	//***
	// PRODUCTS
	//	taxes
	//***
	// select modified records
	//***
	$str_query = "
		SELECT *
		FROM ".CurMonthalize('stock_tax')."
		WHERE is_modified = 'Y'
	";
	$qry =& $conn->query($str_query);
	
	//**
	// write data to string
	//**
	if ($qry->numRows() > 0) {
		$bool_success = true;
		$str_contents = '';
		
		$str_contents = "<?xml version='1.0'?>\n";
		$str_contents .= "<taxes>\n";
		while ($obj =& $qry->fetchRow()) {
			$str_contents .= "<tax>\n";
			$str_contents .= "<tax_id tax_id='".$obj->tax_id."'>".$obj->tax_id."</tax_id>\n";
			$str_contents .= "<tax_description tax_description='".$obj->tax_description."'>".$obj->tax_description."</tax_description>\n";
			$str_contents .= "</tax>\n";
		}
		$str_contents .= "</taxes>\n";
		
		//**
		//write data to file
		//**
		$filename = $str_dest_folder."taxes_".date("YmjHis", time()).".xml";
		
		$handle = fopen($filename, "x");
		if ($handle) {
			fputs($handle, $str_contents);
			fclose($handle);
		}
		else
			$bool_success = false;

		//**
		// reset the is_modified flag
		//**
		if ($bool_success) {
			$str_query = "
				UPDATE ".CurMonthalize('stock_tax')."
				SET is_modified = 'N'
			";
			$qry =& $conn->query($str_query);
		}
	}

	//***
	// PRODUCTS
	//	measurement unit
	//***
	// select modified records
	//***
	$str_query = "
		SELECT *
		FROM stock_measurement_unit
		WHERE is_modified = 'Y'
	";
	$qry =& $conn->query($str_query);
	
	//**
	// write data to string
	//**
	if ($qry->numRows() > 0) {
		$bool_success = true;
		$str_contents = '';
		
		$str_contents = "<?xml version='1.0'?>\n";
		$str_contents .= "<units>\n";
		while ($obj =& $qry->fetchRow()) {
			$str_contents .= "<unit>\n";
			$str_contents .= "<measurement_unit_id measurement_unit_id='".$obj->measurement_unit_id."'>".$obj->measurement_unit_id."</measurement_unit_id>\n";
			$str_contents .= "<measurement_unit measurement_unit='".$obj->measurement_unit."'>".$obj->measurement_unit."</measurement_unit>\n";
			$str_contents .= "<is_decimal is_decimal='".$obj->is_decimal."'>".$obj->is_decimal."</is_decimal>\n";
			$str_contents .= "</unit>\n";
		}
		$str_contents .= "</units>\n";
		
		//**
		//write data to file
		//**
		$filename = $str_dest_folder."units_".date("YmjHis", time()).".xml";
		
		$handle = fopen($filename, "x");
		if ($handle) {
			fputs($handle, $str_contents);
			fclose($handle);
		}
		else
			$bool_success = false;

		//**
		// reset the is_modified flag
		//**
		if ($bool_success) {
			$str_query = "
				UPDATE stock_measurement_unit
				SET is_modified = 'N'
			";
			$qry =& $conn->query($str_query);
		}
	}

	//***
	// PRODUCTS
	//	categories
	//***
	// select modified records
	//***
	$str_query = "
		SELECT *
		FROM stock_category
		WHERE is_modified = 'Y'
	";
	$qry =& $conn->query($str_query);
	
	//**
	// write data to string
	//**
	if ($qry->numRows() > 0) {
		$bool_success = true;
		$str_contents = '';
		
		$str_contents = "<?xml version='1.0'?>\n";
		$str_contents .= "<categories>\n";
		while ($obj =& $qry->fetchRow()) {
			$str_contents .= "<category>\n";
			$str_contents .= "<category_id category_id='".$obj->category_id."'>".$obj->category_id."</category_id>\n";
			$str_contents .= "<category_code category_code='".$obj->category_code."'>".$obj->category_code."</category_code>\n";
			$str_contents .= "<category_description category_description='".$obj->category_description."'>".$obj->category_description."</category_description>\n";
			$str_contents .= "<parent_category_id parent_category_id='".$obj->parent_category_id."'>".$obj->parent_category_id."</parent_category_id>\n";
			$str_contents .= "</category>\n";
		}
		$str_contents .= "</categories>\n";
		
		//**
		//write data to file
		//**
		$filename = $str_dest_folder."categories_".date("YmjHis", time()).".xml";
		
		$handle = fopen($filename, "x");
		if ($handle) {
			fputs($handle, $str_contents);
			fclose($handle);
		}
		else
			$bool_success = false;

		//**
		// reset the is_modified flag
		//**
		if ($bool_success) {
			$str_query = "
				UPDATE stock_category
				SET is_modified = 'N'
			";
			$qry =& $conn->query($str_query);
		}
	}

	//***
	// PRODUCTS
	//	stock type
	//***
	// select modified records
	//***
	$str_query = "
		SELECT *
		FROM stock_type
		WHERE is_modified = 'Y'
	";
	$qry =& $conn->query($str_query);
	
	//**
	// write data to string
	//**
	if ($qry->numRows() > 0) {
		$bool_success = true;
		$str_contents = '';
		
		$str_contents = "<?xml version='1.0'?>\n";
		$str_contents .= "<stock_types>\n";
		while ($obj =& $qry->fetchRow()) {
			$str_contents .= "<type>\n";
			$str_contents .= "<stock_type_id stock_type_id='".$obj->stock_type_id."'>".$obj->stock_type_id."</stock_type_id>\n";
			$str_contents .= "<product_type product_type='".$obj->product_type."'>".$obj->product_type."</product_type>\n";
			$str_contents .= "</type>\n";
		}
		$str_contents .= "</stock_types>\n";
		
		//**
		//write data to file
		//**
		$filename = $str_dest_folder."stock_type_".date("YmjHis", time()).".xml";
		
		$handle = fopen($filename, "x");
		if ($handle) {
			fputs($handle, $str_contents);
			fclose($handle);
		}
		else
			$bool_success = false;

		//**
		// reset the is_modified flag
		//**
		if ($bool_success) {
			$str_query = "
				UPDATE stock_type
				SET is_modified = 'N'
			";
			$qry =& $conn->query($str_query);
		}
	}

	//***
	// PRODUCTS
	//	stock type description
	//***
	// select modified records
	//***
	$str_query = "
		SELECT *
		FROM stock_type_description
		WHERE is_modified = 'Y'
	";
	$qry =& $conn->query($str_query);
	
	//**
	// write data to string
	//**
	if ($qry->numRows() > 0) {
		$bool_success = true;
		$str_contents = '';
		
		$str_contents = "<?xml version='1.0'?>\n";
		$str_contents .= "<stock_type_descriptions>\n";
		while ($obj =& $qry->fetchRow()) {
			$str_description = $obj->description;
			$str_description = replace_special_chars($str_description);
			
			$str_contents .= "<stock_type_description>\n";
			$str_contents .= "<stock_type_description_id stock_type_description_id='".$obj->stock_type_description_id."'>".$obj->stock_type_description_id."</stock_type_description_id>\n";
			$str_contents .= "<stock_type_id stock_type_id='".$obj->stock_type_id."'>".$obj->stock_type_id."</stock_type_id>\n";
			$str_contents .= "<description description='".$str_description."'>".$str_description."</description>\n";
			$str_contents .= "</stock_type_description>\n";
		}
		$str_contents .= "</stock_type_descriptions>\n";
		
		//**
		//write data to file
		//**
		$filename = $str_dest_folder."stock_type_description_".date("YmjHis", time()).".xml";
		
		$handle = fopen($filename, "x");
		if ($handle) {
			fputs($handle, $str_contents);
			fclose($handle);
		}
		else
			$bool_success = false;

		//**
		// reset the is_modified flag
		//**
		if ($bool_success) {
			$str_query = "
				UPDATE stock_type_description
				SET is_modified = 'N'
			";
			$qry =& $conn->query($str_query);
		}
	}

	//***
	// PRODUCTS
	//	stock product type
	//***
	// select modified records
	//***
	$str_query = "
		SELECT *
		FROM stock_product_type
		WHERE is_modified = 'Y'
	";
	$qry =& $conn->query($str_query);
	
	//**
	// write data to string
	//**
	if ($qry->numRows() > 0) {
		$bool_success = true;
		$str_contents = '';
		
		$str_contents = "<?xml version='1.0'?>\n";
		$str_contents .= "<stock_product_types>\n";
		while ($obj =& $qry->fetchRow()) {
			$str_contents .= "<stock_product_type>\n";
			$str_contents .= "<stock_product_type_id stock_product_type_id='".$obj->stock_product_type_id."'>".$obj->stock_product_type_id."</stock_product_type_id>\n";
			$str_contents .= "<product_id product_id='".$obj->product_id."'>".$obj->product_id."</product_id>\n";
			$str_contents .= "<stock_type_id stock_type_id='".$obj->stock_type_id."'>".$obj->stock_type_id."</stock_type_id>\n";
			$str_contents .= "<stock_type_description_id stock_type_description_id='".$obj->stock_type_description_id."'>".$obj->stock_type_description_id."</stock_type_description_id>\n";
			$str_contents .= "</stock_product_type>\n";
		}
		$str_contents .= "</stock_product_types>\n";
		
		//**
		//write data to file
		//**
		$filename = $str_dest_folder."stock_product_type_".date("YmjHis", time()).".xml";
		
		$handle = fopen($filename, "x");
		if ($handle) {
			fputs($handle, $str_contents);
			fclose($handle);
		}
		else
			$bool_success = false;

		//**
		// reset the is_modified flag
		//**
		if ($bool_success) {
			$str_query = "
				UPDATE stock_product_type
				SET is_modified = 'N'
			";
			$qry =& $conn->query($str_query);
		}
	}

	//***
	// PRODUCTS
	//	stock currency
	//***
	// select modified records
	//***
	$str_query = "
		SELECT *
		FROM stock_currency
		WHERE is_modified = 'Y'
	";
	$qry =& $conn->query($str_query);
	
	//**
	// write data to string
	//**
	if ($qry->numRows() > 0) {
		$bool_success = true;
		$str_contents = '';
		
		$str_contents = "<?xml version='1.0'?>\n";
		$str_contents .= "<stock_currency>\n";
		while ($obj =& $qry->fetchRow()) {
			$str_contents .= "<currency>\n";
			$str_contents .= "<currency_id currency_id='".$obj->currency_id."'>".$obj->currency_id."</currency_id>\n";
			$str_contents .= "<currency_name currency_name='".$obj->currency_name."'>".$obj->currency_name."</currency_name>\n";
			$str_contents .= "<currency_rate currency_rate='".$obj->currency_rate."'>".$obj->currency_rate."</currency_rate>\n";
			$str_contents .= "</currency>\n";
		}
		$str_contents .= "</stock_currency>\n";
		
		//**
		//write data to file
		//**
		$filename = $str_dest_folder."stock_currency_".date("YmjHis", time()).".xml";
		
		$handle = fopen($filename, "x");
		if ($handle) {
			fputs($handle, $str_contents);
			fclose($handle);
		}
		else
			$bool_success = false;

		//**
		// reset the is_modified flag
		//**
		if ($bool_success) {
			$str_query = "
				UPDATE stock_currency
				SET is_modified = 'N'
			";
			$qry =& $conn->query($str_query);
		}
	}
	
	function getBillStatus($int_module_record_id) {
		
		$int_status = 0;
		
		global $conn;
		
		$str_query = "
			SELECT bill_status
			FROM ".CurMonthalize('bill')."
			WHERE module_id = 7
				AND module_record_id = $int_module_record_id
		";
		$qry =& $conn->query($str_query);
		$qry->fetchRow();
		$int_status = $obj->bill_status;
		
		return $int_status;
	}
	
	//***
	// ORDERS
	//***
	// select modified records
	//***
	$str_query = "
		SELECT o.order_id, o.reseller_order_id, o.order_reference,o.handling_charge,o.handling_is_percentage,o.courier_charge,o.courier_is_percentage,o.advance_paid,o.discount,
			b.bill_id,
			IF (o.order_status=4, b.bill_status, o.order_status) AS status
		FROM ".CurMonthalize('orders')." o
		LEFT JOIN ".CurMonthalize('bill')." b ON (b.module_record_id = o.order_id)
		WHERE (o.is_modified = 'Y')
			OR (b.is_modified = 'Y')
	";
	$qry =& $conn->query($str_query);
	
	//**
	// write data to string
	//**
	if ($qry->numRows() > 0) {
		$bool_success = true;
		$str_contents = '';
		
		$str_contents = "<?xml version='1.0'?>\n";
		$str_contents .= "<orders>\n";
		while ($obj =& $qry->fetchRow()) {
			/*
				if the order status is "active"
				then get the status of the corresponding bill
			
			$int_order_status = $obj->order_status;
			if ($int_order_status == 4)
				$int_order_status = getBillStatus($obj->order_id);
			*/
			$str_contents .= "<cur_order>\n";
			$str_contents .= "<order_id order_id='".$obj->order_id."'>".$obj->order_id."</order_id>\n";
			$str_contents .= "<reseller_order_id reseller_order_id='".$obj->reseller_order_id."'>".$obj->reseller_order_id."</reseller_order_id>\n";
			$str_contents .= "<order_status order_status='".$obj->status."'>".$obj->status."</order_status>\n";
			$str_contents .= "<order_reference order_reference='".$obj->order_reference."'>".$obj->order_reference."</order_reference>\n";
			$str_contents .= "<handling_charge handling_charge='".$obj->handling_charge."'>".$obj->handling_charge."</handling_charge>\n";
			$str_contents .= "<handling_is_percentage handling_is_percentage='".$obj->handling_is_percentage."'>".$obj->handling_is_percentage."</handling_is_percentage>\n";
			$str_contents .= "<courier_charge courier_charge='".$obj->courier_charge."'>".$obj->courier_charge."</courier_charge>\n";
			$str_contents .= "<courier_is_percentage courier_is_percentage='".$obj->courier_is_percentage."'>".$obj->courier_is_percentage."</courier_is_percentage>\n";
			$str_contents .= "<advance_paid advance_paid='".$obj->advance_paid."'>".$obj->advance_paid."</advance_paid>\n";
			$str_contents .= "<discount discount='".$obj->discount."'>".$obj->discount."</discount>\n";
			$str_contents .= "</cur_order>\n";
		}
		$str_contents .= "</orders>\n";
		
		//**
		//write data to file
		//**
		$filename = $str_dest_folder."orders_".date("YmjHis", time()).".xml";
		
		$handle = fopen($filename, "x");
		if ($handle) {
			fputs($handle, $str_contents);
			fclose($handle);
		}
		else
			$bool_success = false;

		//**
		// reset the is_modified flag
		//**
		if ($bool_success) {
			$str_query = "
				UPDATE ".CurMonthalize('orders')."
				SET is_modified = 'N'
			";
			$qry =& $conn->query($str_query);
			
			$str_query = "
				UPDATE ".CurMonthalize('bill')."
				SET is_modified = 'N'
			";
			$qry =& $conn->query($str_query);
		}
	}

	
?>