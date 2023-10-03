<?

class Module_PT_Accounts extends Module {

  function Module_PT_Accounts() {
	$this->str_module_name = 'PT Accounts';
	$this->str_module_folder = 'pt_accounts/';
	$this->str_active_link = 'pt_accounts/';
	$this->int_module_id=6;
	parent::Module();
  }

  function createMonth($f_month, $f_year) {
	 if ($this->monthExists($f_month,$f_year)) {
		echo "ERROR:  New Month already exists!\n";
  	return false;
	 }
	
	echo "Copying PT account module databases...\n";
	$old_month = $f_month-1;
	$old_year = $f_year;
	if ($old_month==0) {  
		$old_year--; 
		$old_month=12; 
	}
	
	$str_create = "
		CREATE TABLE account_pt_transfers_".$f_year."_".$f_month." 
			LIKE account_pt_transfers_".$old_year."_".$old_month;
	$qry = new Query($str_create);
	if ($qry->b_error) {
		echo "Unable to create account_pt_transfers...\n $str_create";
		return false;
	} 

	$str_create = "
		CREATE TABLE account_pt_balances_".$f_year."_".$f_month."
		(account_pt_balance_id INT(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY(account_pt_balance_id))
			SELECT *
			FROM account_pt_balances_".$old_year."_".$old_month;
	$qry->Query($str_create);
	if ($qry->b_error) {
		echo "Unable to create account_pt_balances...\n $str_create";
		return false;
	}
	$qry->Query("ALTER TABLE account_pt_balances_".$f_year."_".$f_month." TYPE= INNODB");

      //update the balances
      $str_create = "
	UPDATE account_pt_balances_".$f_year."_".$f_month."
	SET closing_balance = opening_balance
      ";
      $qry->Query($str_create);
      if ($qry->b_error) {
	      echo "ERROR: Could not update balances...\n $str_create";
	      return false;
      } 
  
	echo "Done.\n";
	ob_flush();

	return true;
  }


	function monthExists($f_month, $f_year) {
		$qry = new Query("
			SELECT * 
			FROM account_pt_balances_".$f_year."_".$f_month);
		if ($qry->b_error) {
			return false;
		}
		unset($qry);
		return true;
	}


  function buildMenu($f_int_selected) {
 	if (@$_SESSION['int_pt_accounts_selected']==1) {
		$this->str_active_link = 'pt_accounts/index.php';
	}
 	else if (@$_SESSION['int_pt_accounts_selected']==2) {
		$this->str_active_link = 'pt_accounts/index_transfers.php';
	}
 	else if (@$_SESSION['int_pt_accounts_selected']==3) {
		$this->str_active_link = 'pt_accounts/index_accounts_statement.php';
	}
 	else if (@$_SESSION['int_pt_accounts_selected']==4) {
		$this->str_active_link = 'pt_accounts/index_totals_statement.php';
	}
 	else if (@$_SESSION['int_pt_accounts_selected']==5) {
		$this->str_active_link = 'pt_accounts/index_category.php';
	}
 	else if (@$_SESSION['int_pt_accounts_selected']==6) {
		$this->str_active_link = 'pt_accounts/index_nationality.php';
	}
 	else if (@$_SESSION['int_pt_accounts_selected']==7) {
		$this->str_active_link = 'pt_accounts/index_status.php';
	}

		return $this->str_active_link;
		
		//parent::buildMenu($f_int_selected);


  }

  function buildSubMenu() {
	// add additional stuff if we're selected
	if ($this->arr_storerooms[$_SESSION['int_current_storeroom']] >= ACCESS_READ) {
		echo "<script language='javascript'>int_num_submenus=7;
		</script>";

		if (@$_SESSION['int_pt_accounts_selected']==1) {
			echo "<a id='submenu1' onclick='parent.hilite(1);' href='pt_accounts/index.php' class='tabdown' target='content' alt='Accounts List' title='Accounts List'>Accounts List</a>";
		} else {
			echo "<a id='submenu1' onclick='parent.hilite(1);' href='pt_accounts/index.php' class='tab' target='content' alt='Accounts List' title='Accounts List'>Accounts List</a>";
		}

		if (@$_SESSION['int_pt_accounts_selected']==2) {
			echo "<a id='submenu2' onclick='parent.hilite(2);' href='pt_accounts/index_transfers.php' class='tabdown' target='content' alt='Transfers' title='Transfers'>Transfers</a>";
		} else {
			echo "<a id='submenu2' onclick='parent.hilite(2);' href='pt_accounts/index_transfers.php' class='tab' target='content' alt='Transfers' title='Transfers'>Transfers</a>";
		}
		
		if (@$_SESSION['int_pt_accounts_selected']==3) {
			echo "<a id='submenu3' onclick='parent.hilite(3);' href='pt_accounts/index_accounts_statement.php' class='tabdown' target='content' alt='Monthly accounts statement' title='Monthly accounts statement'>Accounts Statement</a>";
		} else {
			echo "<a id='submenu3' onclick='parent.hilite(3);' href='pt_accounts/index_accounts_statement.php' class='tab' target='content' alt='Monthly accounts statement' title='Monthly accounts statement'>Accounts Statement</a>";
		}

		if (@$_SESSION['int_pt_accounts_selected']==4) {
			echo "<a id='submenu4' onclick='parent.hilite(4);' href='pt_accounts/index_totals_statement.php' class='tabdown' target='content' alt='Monthly totals statement' title='Monthly totals statement'>Monthly Statement</a>";
		} else {
			echo "<a id='submenu4' onclick='parent.hilite(4);' href='pt_accounts/index_totals_statement.php' class='tab' target='content' alt='Monthly totals statement' title='Monthly totals statement'>Monthly Statement</a>";
		}

		if (@$_SESSION['int_pt_accounts_selected']==5) {
			echo "<a id='submenu5' onclick='parent.hilite(5);' href='pt_accounts/index_category.php' class='tabdown' target='content' alt='' title=''>Category</a>";
		} else {
			echo "<a id='submenu5' onclick='parent.hilite(5);' href='pt_accounts/index_category.php' class='tab' target='content' alt='' title=''>Category</a>";
		}

		if (@$_SESSION['int_pt_accounts_selected']==6) {
			echo "<a id='submenu6' onclick='parent.hilite(6);' href='pt_accounts/index_nationality.php' class='tabdown' target='content' alt='' title=''>Nationality</a>";
		} else {
			echo "<a id='submenu6' onclick='parent.hilite(6);' href='pt_accounts/index_nationality.php' class='tab' target='content' alt='' title=''>Nationality</a>";
		}

		if (@$_SESSION['int_pt_accounts_selected']==7) {
			echo "<a id='submenu7' onclick='parent.hilite(7);' href='pt_accounts/index_status.php' class='tabdown' target='content' alt='' title=''>Status</a>";
		} else {
			echo "<a id='submenu7' onclick='parent.hilite(7);' href='pt_accounts/index_status.php' class='tab' target='content' alt='' title=''>Status</a>";
		}

		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	}
  }
}



if (!empty($bool_register_modules)) {
	$_SESSION['arr_modules'][]=new Module_PT_Accounts();
}


?>