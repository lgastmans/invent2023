<?
	error_reporting(E_ALL);
  
		require_once("../../include/const.inc.php");
		require_once("../../include/session.inc.php");
		require_once("../../include/db.inc.php");

  function calculatePrice($f_price, $int_tax_id) {
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
		$f_price_calc = $f_price;
		$f_tax = 0;
		$f_tax += $f_price_calc / (1 + $qry_tax->FieldByName('definition_percent') / 100);
		
		return number_format(round($f_tax,3),3,'.','');
  }

  if (!empty($_GET['live'])) {
	  if (!empty($_GET['price']) && !empty($_GET['tax_id'])) {
		echo calculatePrice(($_GET['price']+0),$_GET['tax_id']); 
		die();
	  } else {
	  	die(0);
	  }
  }

?>