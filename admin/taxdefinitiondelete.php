<?
	require_once('../include/const.inc.php');
	require_once('db_params.php');
	require_once('JSON.php');
	
	if (IsSet($_GET['id']))
		delete_row($_GET['id']);
  
	function delete_row($int_id) {

		global $conn;
		$json = new Services_JSON();
		
		
		$can_delete = true;


		$qry = $conn->query("
			SELECT * 
			FROM ".Monthalize('stock_tax_definition')."
			WHERE definition_id = $int_id
		");

		if ($qry->num_rows == 0) {

			$arr_retval['replyCode'] = 501;
			$arr_retval['replyStatus'] = "Error";
			$arr_retval['replyText'] = "Not found";

			$can_delete = false;

			die($json->encode($arr_retval));
		}


		/*
			get the corresponding row in table stock_tax_links

			through the 	definition_id
			and retrieve 	tax_id
		*/
		$qry = $conn->query("
			SELECT *
			FROM ".Monthalize('stock_tax_links')." 
			WHERE tax_definition_id = $int_id
		");

		if ($qry->num_rows == 0) {
			$arr_retval['replyCode'] = 501;
			$arr_retval['replyStatus'] = "Error";
			$arr_retval['replyText'] = "Corresponding link not found";

			$can_delete = false;

			die($json->encode($arr_retval));
		}

		$obj = $qry->fetch_object();
		$tax_id = $obj->tax_id;

		
		/*
			check to see if tax is in use
		*/
		$found = false;


		$qry =& $conn->query("
			SELECT * 
			FROM stock_product
			WHERE tax_id = $tax_id
		");
		if ($qry->num_rows > 0) {
			$tax_found = true;
			$used_in = "products";
		}


		$qry =& $conn->query("
			SELECT * 
			FROM ".Yearalize('stock_batch')."
			WHERE tax_id = $tax_id
		");
		if ($qry->num_rows > 0) {
			$tax_found = true;
			$used_in = "batches";
		}


		$qry =& $conn->query("
			SELECT * 
			FROM ".Monthalize('bill_items')."
			WHERE tax_id = $tax_id
		");
		if ($qry->num_rows > 0) {
			$tax_found = true;
			$used_in = "bills";
		}


		if ($tax_found) {

			$arr_retval['replyCode'] = 501;
			$arr_retval['replyStatus'] = "Error";
			$arr_retval['replyText'] = "This tax is in use in ".$used_in;

			die($json->encode($arr_retval));
		}
	


		if ($can_delete) {

			/*
				remove tax from table
				stock_tax_definition
			*/
			$qry =& $conn->query("
				DELETE FROM ".Monthalize('stock_tax_definition')." 
				WHERE definition_id = $int_id
			");
			

			/*
				remove tax from table
				stock_tax
			*/
			$qry =& $conn->query("
				DELETE FROM ".Monthalize('stock_tax')." 
				WHERE tax_id = $tax_id
			");


			/*
				remove tax from table
				stock_tax_links
			*/
			$qry =& $conn->query("
				DELETE FROM ".Monthalize('stock_tax_links')." 
				WHERE tax_id = $tax_id
			");


			$arr_retval['replyCode'] = 200;
			$arr_retval['replyStatus'] = "Ok";
			$arr_retval['replyText'] = "Deleted successfully";

		}
		else {

			$arr_retval['replyCode'] = 501;
			$arr_retval['replyStatus'] = "Error";
			$arr_retval['replyText'] = "Error deleting tax";

		}

		echo ($json->encode($arr_retval));
	} 
?>
