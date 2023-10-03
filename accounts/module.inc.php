<?

class Module_Accounts extends Module {

  function Module_Accounts() {
	$this->str_module_name = 'FS Accounts';
	$this->str_module_folder = 'accounts/';
	$this->str_active_link = 'accounts/';
	$this->int_module_id=5;
	parent::Module();
  }

  function createMonth($f_month, $f_year) {
	if ($this->monthExists($f_month,$f_year)) {
		echo "ERROR:  New Month already exists!\n";

		return false;
	}
	
//	if ()
	echo "Creating account module databases...\n";
	$old_month = $f_month-1;
	$old_year = $f_year;
	if ($old_month==0) {  
		$old_year--; 
		$old_month=12; 
	}
	$str_create = "
		CREATE TABLE account_record_".$f_year."_".$f_month." 
		(cc_id INT(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY(cc_id))
			SELECT *
			FROM account_record_".$old_year."_".$old_month;
	$qry = new Query($str_create);
	if ($qry->b_error) {
		echo "Unable to create account_record...\n $str_create";
		return false;
	} 

	$str_create = "
		CREATE TABLE account_transfers_".$f_year."_".$f_month." 
		LIKE account_transfers_".$old_year."_".$old_month;
	$qry->Query($str_create);
	if ($qry->b_error) {
		echo "Unable to create account_transfers...\n $str_create";
		return false;
	} 

	echo "Done.\n";
	ob_flush();

	return true;
  }

	function monthExists($f_month, $f_year) {
		$qry = new Query("select * from account_record_".$f_year."_".$f_month);
		if ($qry->b_error) {
			return false;
		}
		unset($qry);
		return true;
	}


  function buildMenu($f_int_selected) {
 	if (@$_SESSION['int_accounts_selected']==1) {
		$this->str_active_link = 'accounts/index.php';
                
	} else if (@$_SESSION['int_accounts_selected']==2) {
		$this->str_active_link = 'accounts/indextransfers.php';

	} else if (@$_SESSION['int_accounts_selected']==3) {
		$this->str_active_link = 'accounts/index_totals.php'; 

	} else if (@$_SESSION['int_accounts_selected']==4) {
		$this->str_active_link = 'accounts/index_statement.php';
	} 
		return $this->str_active_link;
		
		//parent::buildMenu($f_int_selected);


  }

  function buildSubMenu() {
	// add additional stuff if we're selected
	if ($this->arr_storerooms[$_SESSION['int_current_storeroom']] >= ACCESS_READ) {
		echo "<script language='javascript'>int_num_submenus=4;
		</script>";

		if (@$_SESSION['int_accounts_selected']==1) {
			echo "<a id='submenu1' onclick='parent.hilite(1);' href='accounts/index.php' class='tabdown' target='content' alt='Accounts List' title='Accounts List'>Accounts List</a>";
		} else {
			echo "<a id='submenu1' onclick='parent.hilite(1);' href='accounts/index.php' class='tab' target='content' alt='Accounts List' title='Accounts List'>Accounts List</a>";
		}
                
		if (@$_SESSION['int_accounts_selected']==2) {
			echo "<a id='submenu2' onclick='parent.hilite(2);' href='accounts/indextransfers.php' class='tabdown' target='content' alt='Accounts Transfers' title='Accounts Transfers'>Transfers</a>";
		} else {
			echo "<a id='submenu2' onclick='parent.hilite(2);' href='accounts/indextransfers.php' class='tab' target='content' alt='Accounts Transfers' title='Accounts Transfers'>Transfers</a>";
		}

		if (@$_SESSION['int_accounts_selected']==3) {
			echo "<a id='submenu3' onclick='parent.hilite(3);' href='accounts/index_totals.php' class='tabdown' target='content' alt='Transfer Totals' title='Transfer Totals'>Totals</a>";
		} else {
			echo "<a id='submenu3' onclick='parent.hilite(3);' href='accounts/index_totals.php' class='tab' target='content' alt='Transfer Totals' title='Transfer Totals'>Totals</a>";
		}

		if (@$_SESSION['int_accounts_selected']==4) {
			echo "<a id='submenu4' onclick='parent.hilite(4);' href='accounts/index_statement.php' class='tabdown' target='content' alt='Financial Service Statement of accounts' title='Financial Service Statement of accounts'>Statement</a>";
		} else {
			echo "<a id='submenu4' onclick='parent.hilite(4);' href='accounts/index_statement.php' class='tab' target='content' alt='Financial Service Statement of accounts' title='Financial Service Statement of accounts'>Statement</a>";
		}

		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		
	}
  }
}



if (!empty($bool_register_modules)) {
	$_SESSION['arr_modules'][]=new Module_Accounts();
}


?>