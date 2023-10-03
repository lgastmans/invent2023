<?

class Module_Admin extends Module {

  function Module_Admin() {
	$this->str_module_name = 'Admin';
	$this->str_module_folder = 'admin/';
	$this->str_active_link = 'admin/';
	$this->int_module_id = 4;
	
	// call parent constructor which will load permissions
	parent::Module();

  }

  function createMonth($f_month, $f_year) {
	return true;

  }
  function monthExists($f_month, $f_year) {
	return true;
  }

  function buildMenu($f_int_selected) {
 	if (@$_SESSION['int_admin_selected']==1) {
		$this->str_active_link = 'admin/measurementmaster.php';
	} else if (@$_SESSION['int_admin_selected']==2) {
		$this->str_active_link = 'admin/categorymaster.php';
//	} else if (@$_SESSION['int_admin_selected']==3) {
//		$this->str_active_link = 'admin/taxmaster.php';
	} else if (@$_SESSION['int_admin_selected']==3) {
		$this->str_active_link = 'admin/stockmaster.php';
	} else if (@$_SESSION['int_admin_selected']==4) {
		$this->str_active_link = 'admin/taxdefinitionmaster.php';
	} else if (@$_SESSION['int_admin_selected']==5) {
		$this->str_active_link = 'admin/toolmaster.php';
	} else if (@$_SESSION['int_admin_selected']==6) {
		$this->str_active_link = 'admin/storeroommaster.php';
	} else if (@$_SESSION['int_admin_selected']==7) {
		$this->str_active_link = 'admin/suppliermaster.php';
	} else if (@$_SESSION['int_admin_selected']==8) {
		$this->str_active_link = 'admin/usersmaster.php';
	} else if (@$_SESSION['int_admin_selected']==9) {
		$this->str_active_link = 'admin/index_communities.php';
	} else if (@$_SESSION['int_admin_selected']==10) {
		$this->str_active_link = 'admin/index_verification_tools.php';
	} else if (@$_SESSION['int_admin_selected']==11) {
		$this->str_active_link = 'admin/index_currency.php';
	} 

		return $this->str_active_link;
		
		//parent::buildMenu($f_int_selected);


  }

  function buildSubMenu() {

if ($this->arr_storerooms[$_SESSION['int_current_storeroom']] >= ACCESS_READ) {
		echo "<script language='javascript'>int_num_submenus=11;
		</script>";

		if (@$_SESSION['int_admin_selected']==1) {
			echo "<a id='submenu1' onclick='parent.hilite(1);' class='tabdown' href='admin/measurementmaster.php' class='tabdown' target='content' alt='Set Measurement Units' title='Set Measurement Units'>Measurement Units</a>";

		} else {
			echo "<a id='submenu1' onclick='parent.hilite(1);' class='tab' href='admin/measurementmaster.php' class='tabdown' target='content' alt='Set Measurement Units' title='Set Measurement Units'>Measurement Units</a>";
		}

		if (@$_SESSION['int_admin_selected']==2) {
			echo "<a id='submenu2' onclick='parent.hilite(2);' class='tabdown' href='admin/categorymaster.php' class='tabdown' target='content' alt='Show Category Master' title='Show Category Master'>Categories</a>";

		} else { 
			echo "<a id='submenu2' onclick='parent.hilite(2);' class='tab' href='admin/categorymaster.php' class='tabdown' target='content' alt='Show Category Master' title='Show Category Master'>Categories</a>";
		}

		// if (@$_SESSION['int_admin_selected']==3) {
		// 	echo "<a id='submenu3' onclick='parent.hilite(3);' href='admin/taxmaster.php' class='tabdown' target='content' alt='Tax Master' title='Tax Master'>Taxes</a>";
		// } else {
		// 	echo "<a id='submenu3' onclick='parent.hilite(3);' href='admin/taxmaster.php' class='tab' target='content' alt='Tax Master' title='Tax Master'>Taxes</a>";
		// }

		if (@$_SESSION['int_admin_selected']==3) {
			echo "<a id='submenu3' onclick='parent.hilite(3);' href='admin/stockmaster.php' class='tabdown' target='content' alt='Set Products' title='Set Products'>Products</a>"; 
		} else {
			echo "<a id='submenu3' onclick='parent.hilite(3);' href='admin/stockmaster.php' class='tab' target='content' alt='Set Products' title='Set Products'>Products</a>"; 
		}

		if (@$_SESSION['int_admin_selected']==4) {
			echo "<a id='submenu4' onclick='parent.hilite(4);' href='admin/taxdefinitionmaster.php' class='tabdown' target='content' alt='Tax Definition Master' title='Tax Definition Master'>Tax Definitions</a>"; 
		} else {
			echo "<a id='submenu4' onclick='parent.hilite(4);' href='admin/taxdefinitionmaster.php' class='tab' target='content' alt='Tax Definition Master' title='Tax Definition Master'>Tax Definitions</a>"; 
		}
		if (@$_SESSION['int_admin_selected']==5) {
			echo "<a id='submenu5' onclick='parent.hilite(5);' href='admin/toolmaster.php' class='tabdown' target='content' alt='Database Tools' title='Database Tools'>Database Tools</a>"; 
		} else {
			echo "<a id='submenu5' onclick='parent.hilite(5);' href='admin/toolmaster.php' class='tab' target='content' alt='Database Tools' title='Tax Definition Master'>Database Tools</a>"; 
		}
		if (@$_SESSION['int_admin_selected']==6) {
			echo "<a id='submenu6' onclick='parent.hilite(6);' href='admin/storeroommaster.php' class='tabdown' target='content' alt='Storerooms' title='Storerooms'>Storerooms</a>"; 
		} else {
			echo "<a id='submenu6' onclick='parent.hilite(6);' href='admin/storeroommaster.php' class='tab' target='content' alt='Storerooms' title='Storerooms Master'>Storerooms</a>"; 
		}
		if (@$_SESSION['int_admin_selected']==7) {
			echo "<a id='submenu7' onclick='parent.hilite(7);' href='admin/suppliermaster.php' class='tabdown' target='content' alt='Suppliers' title='Storerooms'>Suppliers</a>"; 
		} else {
			echo "<a id='submenu7' onclick='parent.hilite(7);' href='admin/suppliermaster.php' class='tab' target='content' alt='Suppliers' title='Suppliers'>Suppliers</a>"; 
		}

		if (@$_SESSION['int_admin_selected']==8) {
			echo "<a id='submenu8' onclick='parent.hilite(8);' href='admin/usersmaster.php' class='tabdown' target='content' alt='Users' title='Users'>Users</a>"; 
		} else {
			echo "<a id='submenu8' onclick='parent.hilite(8);' href='admin/usersmaster.php' class='tab' target='content' alt='Users' title='Users'>Users</a>"; 
		}

		if (@$_SESSION['int_admin_selected']==9) {
			echo "<a id='submenu9' onclick='parent.hilite(9);' href='admin/index_communities.php' class='tabdown' target='content' alt='Communities' title='Communities'>Communities</a>"; 
		} else {
			echo "<a id='submenu9' onclick='parent.hilite(9);' href='admin/index_communities.php' class='tab' target='content' alt='Communities' title='Communities'>Communities</a>"; 
		}

		if (@$_SESSION['int_admin_selected']==10) {
			echo "<a id='submenu10' onclick='parent.hilite(10);' href='admin/index_verification_tools.php' class='tabdown' target='content' alt='Database Integrity Verification Tools' title='Verification Tools'>Verification Tools</a>"; 
		} else {
			echo "<a id='submenu10' onclick='parent.hilite(10);' href='admin/index_verification_tools.php' class='tab' target='content' alt='Database Integrity Verification Tools' title='Verification Tools'>Verification Tools</a>"; 
		}

		if (@$_SESSION['int_admin_selected']==11) {
			echo "<a id='submenu11' onclick='parent.hilite(11);' href='admin/index_currency.php' class='tabdown' target='content' alt='Edit currency settings' title='Edit currency settings'>Currencies</a>"; 
		} else {
			echo "<a id='submenu11' onclick='parent.hilite(11);' href='admin/index_currency.php' class='tab' target='content' alt='Edit currency settings' title='Edit currency settings'>Currencies</a>"; 
		}

		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		
	}

  }
}

if (!empty($bool_register_modules)) {
	$_SESSION['arr_modules'][]=new Module_Admin();
}
?>