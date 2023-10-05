<?
  error_reporting(E_ALL);
  require_once("../include/const.inc.php");

  require_once("../include/session.inc.php");

  require_once("../include/db.inc.php");


  function createTransfer($f_price, $int_tax_id) {
	$qry_tax = new Query("select 
				tl.tax_order,
				td.definition_type,
				td.definition_percent,
				td.definition_type 
			      from ".Monthalize("stock_tax_links")." tl
				inner join ".Monthalize("stock_tax_definition")." td
				ON td.definition_id = tl.tax_definition_id
				where tl.tax_id=".$int_tax_id." order by tl.tax_order"
				);
	if ($qry_tax->GetErrorMessage()<>"") die ($qry_tax->GetErrorMessage());
	$f_price_calc = $f_price;
	$f_tax = 0;
	
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
	return $f_tax;
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