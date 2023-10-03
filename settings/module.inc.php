<?
class Module_Settings extends Module {

	function Module_Settings() {
		$this->str_module_name = 'Settings';
		$this->str_module_folder = 'settings/';
		$this->str_active_link = 'settings/';
		$this->int_module_id = 10;

		// call parent constructor which will load permissions
		parent::Module();

	}

	function createMonth($f_month, $f_year) {
		echo "Creating settings module databases...\n";

		$old_month = $f_month-1;
		$old_year = $f_year;
		if ($old_month==0) { 
			$old_year--; 
			$old_month=12; 
		}

		echo "Done.\n";
		ob_flush();
		return true;
	}

	function monthExists($f_month, $f_year) {
		$qry = new Query("select * from stock_storeroom_product_".$f_year."_".$f_month);
		if ($qry->b_error) {
			return false;
		}
		unset($qry);
		return true;
	}

	function yearExists($f_month, $f_year) {
		$old_month = $f_month-1;
		$old_year = $f_year-1; 
		if ($old_month==0) { 
			$old_month=12; 
		}

		if ($f_month < 4)
			$qry = new Query("select * from stock_batch_".$old_year);
		else
			$qry = new Query("select * from stock_batch_".$f_year);

		if ($qry->b_error) {
			return false;
		}

		unset($qry);
		return true;
	}

	function isCurrentData($f_month, $f_year) {
		return false;
		if (($f_month==date('n')) and ($f_year==date('Y')))
			return true;
	}

	function buildMenu($f_int_selected) {
		if (@$_SESSION['int_settings_menu_selected']==1) {
			$this->str_active_link = 'settings/index_templates.php';
		}
		else if (@$_SESSION['int_settings_menu_selected']==2) {
			$this->str_active_link = 'settings/index_global_settings.php';
		}
		else if (@$_SESSION['int_settings_menu_selected']==3) {
			$this->str_active_link = 'settings/index_module_settings.php';
		}
		else if (@$_SESSION['int_settings_menu_selected']==4) {
			$this->str_active_link = 'settings/index_product_types.php';
		}
		else if (@$_SESSION['int_settings_menu_selected']==5) {
			$this->str_active_link = 'settings/salesperson/index.php';
		}
		return $this->str_active_link;
		
		//parent::buildMenu($f_int_selected);

	}

	function buildSubMenu() {
		// add additional stuff if we're selected
		if (@$this->arr_storerooms[$_SESSION['int_current_storeroom']] >= ACCESS_READ) {
			echo "<script language='javascript'>int_num_submenus=4;
			</script>";
			if (@$_SESSION['int_settings_menu_selected']==1) {
				echo "<a id='submenu1' onclick='parent.hilite(1);' href='settings/index_templates.php' class='tabdown' target='content' alt='Templates for printing of bills' title='Templates for printing of bills'>Templates</a>";
			} else {
				echo "<a id='submenu1' onclick='parent.hilite(1);' href='settings/index_templates.php' class='tab' target='content' alt='Templates for printing of bills' title='Templates for printing of bills'>Templates</a>";
			}
			if (@$_SESSION['int_settings_menu_selected']==2) {
				echo "<a id='submenu2' onclick='parent.hilite(2);' href='settings/index_global_settings.php' class='tabdown' target='content' alt='Edit global settings' title='Edit global settings'>Global</a>";
			} else {
				echo "<a id='submenu2' onclick='parent.hilite(2);' href='settings/index_global_settings.php' class='tab' target='content' alt='Edit global settings' title='Edit global settings'>Global</a>";
			}
			if (@$_SESSION['int_settings_menu_selected']==3) {
				echo "<a id='submenu3' onclick='parent.hilite(3);' href='settings/index_module_settings.php' class='tabdown' target='content' alt='Edit module settings' title='Edit module settings'>Module</a>";
			} else {
				echo "<a id='submenu3' onclick='parent.hilite(3);' href='settings/index_module_settings.php' class='tab' target='content' alt='Edit module settings' title='Edit module settings'>Module</a>";
			}
			if (@$_SESSION['int_settings_menu_selected']==4) {
				echo "<a id='submenu4' onclick='parent.hilite(4);' href='settings/index_product_types.php' class='tabdown' target='content' alt='Edit user defined product types' title='Edit user defined product types'>Product Types</a>";
			} else {
				echo "<a id='submenu4' onclick='parent.hilite(4);' href='settings/index_product_types.php' class='tab' target='content' alt='Edit user defined product types' title='Edit user defined product types'>Product Types</a>";
			}
			if (@$_SESSION['int_settings_menu_selected']==5) {
				echo "<a id='submenu5' onclick='parent.hilite(5);' href='settings/salesperson/index.php' class='tabdown' target='content' alt='Edit salespersons' title='Edit salespersons'>Salespersons</a>";
			} else {
				echo "<a id='submenu5' onclick='parent.hilite(5);' href='settings/salesperson/index.php' class='tab' target='content' alt='Edit salespersons' title='Edit salespersons'>Salespersons</a>";
			}
			
			echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		}
	}

} // end class module


if (!empty($bool_register_modules)) {
	$_SESSION['arr_modules'][]=new Module_Settings();
	$_SESSION['int_settings_menu_selected'] = 1;
}

?>