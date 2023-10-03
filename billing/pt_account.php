<?
	error_reporting(E_ERROR);

	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");

	function getPTAccount($strAccountNumber) {
		// default return string
		// this string gets returned to a javascript function in "billing_enter.php"
		$strRetVal = "__NOT_FOUND";
		
		if ($strAccountNumber != 'nil') {
			
			// check whether the code exists
			$result_search = new Query("
				SELECT ap.account_name, ap.enabled, ap.partner, ap.family_name, c.community_name
				FROM account_pt ap
				LEFT JOIN communities c ON (c.community_id = ap.community_id)
				WHERE (account_number = '".$strAccountNumber."')");
				
			if ($result_search->GetErrorMessage()<>"") die ($result_search->GetErrorMessage());
			
			if ($result_search->RowCount() > 0) {
				if ($result_search->FieldByName('enabled') == 'Y')
					$strRetVal = "OK|".
						$result_search->FieldByName('account_name')."|".
						$result_search->FieldByName('partner')."|".
						$result_search->FieldByName('family_name')."|".
						$result_search->FieldByName('community_name');
				else
					$strRetVal = "__DISABLED|".$result_search->FieldByName('account_name');
			}
		}
		
		return $strRetVal;
	}

	if (!empty($_GET['live'])) {
		if (!empty($_GET['account_number'])) {
			echo getPTAccount($_GET['account_number']);
			die();
		}
		else {
			die("__NOT_FOUND");
		}
	}
?>