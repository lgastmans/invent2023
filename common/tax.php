<?
	error_reporting(E_ALL);
  
	if ($str_application_path=='') {
		require_once($str_application_path."include/const.inc.php");
		require_once($str_application_path."include/session.inc.php");
		require_once($str_application_path."include/db.inc.php");
	}



	function getTaxDetails($f_price, $int_tax_id) {

		$sql = "
			SELECT *
			FROM ".Monthalize("stock_tax_links")." tl
				INNER JOIN ".Monthalize("stock_tax_definition")." td ON (td.definition_id = tl.tax_definition_id)
			WHERE (tl.tax_id=".$int_tax_id.")
			ORDER BY tl.tax_order";
		$qry_tax = new Query($sql);

		if ($qry_tax->GetErrorMessage()<>"") die ($qry_tax->GetErrorMessage());

		$f_price_calc = $f_price;

		$f_tax = 0;

		$f_cur_tax = 0;

		$taxes = array();

		for ($i=0;$i<$qry_tax->RowCount();$i++) {

			switch ($qry_tax->FieldByName('definition_type')) {
				case TAX_EXCISE_TAX:
						$f_tax += $f_price_calc * $qry_tax->FieldByName('definition_percent') / 100;
						$f_cur_tax = $f_price_calc * $qry_tax->FieldByName('definition_percent') / 100;
						$f_price_calc += $f_tax; 
						break;
				case TAX_SALES_TAX:
				case TAX_OTHER_TAX:
				case TAX_SERVICE_TAX:
				case TAX_VAT:
						$f_tax += $f_price_calc * $qry_tax->FieldByName('definition_percent') / 100;
						$f_cur_tax = $f_price_calc * $qry_tax->FieldByName('definition_percent') / 100;
						break;
				case TAX_SALES_TAX_SURCHARGE:
				case TAX_OTHER_TAX_SURCHARGE:
				case TAX_SERVICE_TAX_SURCHARGE:
				case TAX_VAT_SURCHARGE:
				case TAX_EXCISE_TAX_SURCHARGE:
						$f_tax += $f_tax * $qry_tax->FieldByName('definition_percent') / 100;
						$f_cur_tax = $f_tax * $qry_tax->FieldByName('definition_percent') / 100;
						break;
			}

			$taxes[$i]['definition_id'] = $qry_tax->FieldByName('definition_id');
			$taxes[$i]['definition_percent'] = $qry_tax->FieldByName('definition_percent');
			$taxes[$i]['tax'] = round($f_cur_tax,3);
			$taxes[$i]['taxable'] = round($f_cur_tax,3);

			$qry_tax->Next();
		}

		return $taxes;

	}



	function getTaxBreakdown($f_price, $int_tax_id, $client_gstin='') {

		$f_price_calc = $f_price;
		$f_tax = 0;
		$array_taxes = array();
		$f_cur_tax = 0;

//		if ((!empty($_SESSION['company_gstin'])) && (!empty($client_gstin)) && ($_SESSION['company_gstin'] == $client_gstin))
//			return $array_taxes;

		$qry_tax = new Query("
			SELECT *
			FROM ".Monthalize("stock_tax_links")." tl
				INNER JOIN ".Monthalize("stock_tax_definition")." td ON (td.definition_id = tl.tax_definition_id)
			WHERE (tl.tax_id=".$int_tax_id.")
			ORDER BY tl.tax_order
		");

		if ($qry_tax->GetErrorMessage()<>"") 
			die ($qry_tax->GetErrorMessage());

		for ($i=0;$i<$qry_tax->RowCount();$i++) {

			switch ($qry_tax->FieldByName('definition_type')) {
				case TAX_EXCISE_TAX:
						$f_tax += $f_price_calc * $qry_tax->FieldByName('definition_percent') / 100;
						$f_cur_tax = $f_price_calc * $qry_tax->FieldByName('definition_percent') / 100;
						$f_price_calc += $f_tax; 
						break;
				case 	TAX_SALES_TAX:
				case	TAX_OTHER_TAX:
				case	TAX_SERVICE_TAX:
				case	TAX_VAT:
						$f_tax += $f_price_calc * $qry_tax->FieldByName('definition_percent') / 100;
						$f_cur_tax = $f_price_calc * $qry_tax->FieldByName('definition_percent') / 100;
						break;
				case 	TAX_SALES_TAX_SURCHARGE:
				case	TAX_OTHER_TAX_SURCHARGE:
				case	TAX_SERVICE_TAX_SURCHARGE:
				case	TAX_VAT_SURCHARGE:
				case	TAX_EXCISE_TAX_SURCHARGE:
						$f_cur_tax = $f_tax * $qry_tax->FieldByName('definition_percent') / 100;
						$f_tax += $f_tax * $qry_tax->FieldByName('definition_percent') / 100;
						break;
			}

			$array_taxes[] = $qry_tax->FieldByName('definition_id');
			$array_taxes[] = round($f_cur_tax,3);
			//$array_taxes[] = $qry_tax->FieldByName('definition_percent');

			$qry_tax->Next();
		}

		return $array_taxes;
	}


  function calculateTax($f_price, $int_tax_id, $client_gstin='') {

	$f_price_calc = $f_price;
	$f_tax = 0;

	if ((!empty($company_gstin)) && (!empty($client_gstin)) && ($company_gstin == $client_gstin))
		return $f_tax;

	$qry_tax = new Query("
		SELECT
			tl.tax_order,
			td.definition_type,
			td.definition_percent,
			td.definition_type
		FROM ".Monthalize("stock_tax_links")." tl
		INNER JOIN ".Monthalize("stock_tax_definition")." td
			ON td.definition_id = tl.tax_definition_id
		WHERE tl.tax_id=".$int_tax_id." order by tl.tax_order"
	);

	if ($qry_tax->GetErrorMessage()<>"") 
		die ($qry_tax->GetErrorMessage());
	
	for ($i=0;$i<$qry_tax->RowCount();$i++) {
		
		switch ($qry_tax->FieldByName('definition_type')) {
			case TAX_EXCISE_TAX:
					$f_tax += $f_price_calc * $qry_tax->FieldByName('definition_percent') / 100;
					$f_price_calc += $f_tax; 
					break;
			case 	TAX_SALES_TAX:
			case	TAX_OTHER_TAX:
			case	TAX_SERVICE_TAX:
			case	TAX_VAT:

					$f_tax += $f_price_calc * $qry_tax->FieldByName('definition_percent') / 100;
//					echo "$f_tax $f_price_calc";
					break;
			case 	TAX_SALES_TAX_SURCHARGE:
			case	TAX_OTHER_TAX_SURCHARGE:
			case	TAX_SERVICE_TAX_SURCHARGE:
			case	TAX_VAT_SURCHARGE:
			case	TAX_EXCISE_TAX_SURCHARGE :
					$f_tax += $f_tax * $qry_tax->FieldByName('definition_percent') / 100;
					break;
		}
		$qry_tax->Next();
	}

	return number_format(round($f_tax,3),3,'.','');

  }

  if (!empty($_GET['live'])) {

	  if (!empty($_GET['price']) && !empty($_GET['tax_id'])) {

		echo calculateTax(($_GET['price']+0),$_GET['tax_id']); 
		die();

	  } else {

	  	die(0);

	  }
  }

?>