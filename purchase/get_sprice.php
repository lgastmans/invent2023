<?php
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../common/tax.php");

	$code = '';
	if (isset($_POST['code']))
		$code = $_POST['code'];

	$bprice = 0;
	if (isset($_POST['bprice']))
	 	$bprice = $_POST['bprice'];

	$sprice = 0;

	$sql = "
		SELECT * FROM stock_product sp
		LEFT JOIN stock_supplier ss ON (ss.supplier_id = sp.supplier_id)
		WHERE (sp.product_code = '".$code."')
			AND (sp.deleted = 'N')
	";
	
	$qry = new Query($sql);

	$commission = 1;

	if ($qry->RowCount() > 0) {

		$commission = $qry->FieldByName('commission_percent') + $qry->FieldByName('commission_percent_2') + $qry->FieldByName('commission_percent_3');

		$sprice = number_format(round((float)($bprice / ((100 - $commission) / 100)),2),2,'.','');
	}

	$data['sprice'] = $sprice;

	echo json_encode($data);
?>