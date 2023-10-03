<?
class Module_Storerooms extends Module {

	function Module_Storerooms() {
		$this->str_module_name = 'Storerooms';
		$this->str_module_folder = 'storerooms/';
		$this->str_active_link = 'storerooms/';
		$this->int_module_id = 8;

		// call parent constructor which will load permissions
		parent::Module();

	}

	function createMonth($f_month, $f_year) {
		echo "Creating storerooms module databases...\n";

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
			$qry = new Query("select * from purchase_order_".$old_year);
		else
			$qry = new Query("select * from purchase_order_".$f_year);

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
		if (@$_SESSION['int_storerooms_menu_selected']==1) {
			$this->str_active_link = 'storerooms/index.php';
		}
		else if (@$_SESSION['int_storerooms_menu_selected']==2) {
			$this->str_active_link = 'storerooms/index_registry.php';
		}
		return $this->str_active_link;
		
		//parent::buildMenu($f_int_selected);

	}

	function buildSubMenu() {
		// add additional stuff if we're selected
		if ($this->arr_storerooms[$_SESSION['int_current_storeroom']] >= ACCESS_READ) {
			echo "<script language='javascript'>int_num_submenus=2;
			</script>";
			if (@$_SESSION['int_storerooms_menu_selected']==1) {
				echo "<a id='submenu1' onclick='parent.hilite(1);' href='storerooms/index.php' class='tabdown' target='content' alt='Current Stock across storerooms' title='Current Stock across storerooms'>Current Stock</a>";
			} else {
				echo "<a id='submenu1' onclick='parent.hilite(1);' href='storerooms/index.php' class='tab' target='content' alt='Current Stock across storerooms' title='Current Stock across storerooms'>Current Stock</a>";
			}
			
			if (@$_SESSION['int_storerooms_menu_selected']==2) {
				echo "<a id='submenu2' onclick='parent.hilite(2);' href='storerooms/index_registry.php' class='tabdown' target='content' alt='Registry of stock across storerooms' title='Registry of stock across storerooms'>Registry</a>";
			} else {
				echo "<a id='submenu2' onclick='parent.hilite(2);' href='storerooms/index_registry.php' class='tab' target='content' alt='Registry of stock across storerooms' title='Registry of stock across storerooms'>Registry</a>";
			}
			echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		}
	}
} // end class module


if (!empty($bool_register_modules)) {
	$_SESSION['arr_modules'][] = new Module_Storerooms();
}

?>