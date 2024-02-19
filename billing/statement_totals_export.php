<?php
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db_mysqli.php");
	require_once("../common/tax.php");
	require_once("../common/product_funcs.inc.php");	
	require_once("statement_totals_func.php");


	$filename = "totals_statement_".date('Y-m-d').".csv";

	header("Content-Type: application/text; name=".$filename);
	header("Content-Transfer-Encoding: binary");
	header("Content-Disposition: attachment; filename=".$filename);
	header("Expires: 0");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");

	$delimiter = "|";
	//$delimiter = "\t";

	$consignment = 'N'; // BN is direct sales , Y is consignment sales
	if (IsSet($_GET["supplier_type"]))
		$consignment = $_GET["supplier_type"];


	$str_include_tax = 'Y';

	$display_gstin = "Y";
	if (isset($_GET['display_gstin']))
		$display_gstin = $_GET['display_gstin'];


	/*
		company details
	*/
	$qry_company = $conn->Query("SELECT * FROM company WHERE 1");	
	$company = $qry_company->fetch_object();


	/*
		check whether to display commissions
	*/
	$display_commissions = false;
	$sql = "
		SELECT SUM(commission_percent + commission_percent_2 + commission_percent_3) AS commissions
		FROM stock_supplier
		WHERE (is_supplier_delivering='Y')
			AND (is_active = 'Y')
	";
	$qry_commission = $conn->Query($sql);
	if ($qry_commission->num_rows>0){
		$commissions = $qry_commission->fetch_object();
		$display_commissions = ($commissions->commissions>0);
	}

	/**
	 * header row 1
	 */
	$str_header =
		"Supplier".$delimiter.$delimiter.
		"Selling Price".$delimiter.$delimiter.$delimiter.$delimiter.$delimiter;

	if ($consignment=='N') {

		$str_header .= "Buying Price".$delimiter.$delimiter.$delimiter;

	} else if (($consignment=='Y') && ($display_commissions)) {
		
		$str_header .= "Commissions".$delimiter.$delimiter.$delimiter;

	}

	if ($consignment=='Y') {
		$str_header .= $delimiter;
	}

	$str_header .= $delimiter."\n";

	/**
	 * header row 2
	 */
	$str_header .= 
		"Name".$delimiter;

	if ($display_gstin == 'Y') { 
		$str_header .= 	"Account No. / GSTIN".$delimiter;
	} else {
		$str_header .= 	"Account No.".$delimiter;
	}

	$str_header .=
		"S. Price Amount".$delimiter.
		"Discount Amount".$delimiter.
		"Taxable Amount".$delimiter.
		"S. Price GST".$delimiter.
		"S. Price Total".$delimiter;

	if ($consignment=='N') {
		$str_header .=
			"B. Price Amount".$delimiter.
			"B. Price GST".$delimiter.
			"B. Price Total ( Given )".$delimiter;
	}
	else if (($consignment=='Y') && ($display_commissions)) {
		$str_header .=
			"Commission 1".$delimiter.
			"Commission 2".$delimiter.
			"Commission 3".$delimiter;
	}

	if ($consignment=='Y')
		$str_header .= "Given".$delimiter;

	$str_header .= "Profit"."\n";


	$data = display_data('Y');

	echo "sep=|"."\n".$str_header.$data;
?>