<?
	require_once("../include/const.inc.php");
	require_once("db.inc.php");
	
	function getAccountName($f_account_number) {
		$str_res = 'ERROR|Not Found';
		
		$qry_account = new Query("select * from account_cc where account_number='".$f_account_number."' and account_active='Y'");
		
		if ($qry_account->RowCount()>0) {
			$str_res = "OK|".$qry_account->FieldByName('account_name');
		}
		
		return $str_res;
	}
	
	
	if (!empty($_GET['live'])) {
		if (!empty($_GET['account_number'])) {
			echo getAccountName( ($_GET['account_number']), 0 ); 
			die();
		} else {
			die(0);
		}
	}
?>