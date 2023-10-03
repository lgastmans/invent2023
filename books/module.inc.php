<?
// to add a new module
// remember to edit the session.inc.php file
// in the include folder

class Module_Books extends Module {

	function Module_Books() {
		$this->str_module_name = 'Books';
		$this->str_module_folder = 'books/';
		$this->str_active_link = 'books/';
		$this->int_module_id = 11;

		// call parent constructor which will load permissions
		parent::Module();

	}

	function createMonth($f_month, $f_year) {
		echo "Creating books module databases...\n";
		
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
		if (@$_SESSION['int_books_menu_selected']==1) {
			$this->str_active_link = 'books/index_languages.php';
		}
		else if (@$_SESSION['int_books_menu_selected']==2) {
			$this->str_active_link = 'books/index_publishers.php';
		}
		else if (@$_SESSION['int_books_menu_selected']==3) {
			$this->str_active_link = 'books/index_authors.php';
		}
		return $this->str_active_link;
		
		//parent::buildMenu($f_int_selected);

	}

	function buildSubMenu() {
		// add additional stuff if we're selected
		if (@$this->arr_storerooms[$_SESSION['int_current_storeroom']] >= ACCESS_READ) {
			echo "<script language='javascript'>int_num_submenus=3;
			</script>";
			if (@$_SESSION['int_books_menu_selected']==1) {
				echo "<a id='submenu1' onclick='parent.hilite(1);' href='books/index_languages.php' class='tabdown' target='content' alt='Languages' title='Languages'>Languages</a>";
			} else {
				echo "<a id='submenu1' onclick='parent.hilite(1);' href='books/index_languages.php' class='tab' target='content' alt='Languages' title='Languages'>Languages</a>";
			}
			if (@$_SESSION['int_books_menu_selected']==2) {
				echo "<a id='submenu2' onclick='parent.hilite(2);' href='books/index_publishers.php' class='tabdown' target='content' alt='Publishers' title='Publishers'>Publishers</a>";
			} else {
				echo "<a id='submenu2' onclick='parent.hilite(2);' href='books/index_publishers.php' class='tab' target='content' alt='Publishers' title='Publishers'>Publishers</a>";
			}
			if (@$_SESSION['int_books_menu_selected']==3) {
				echo "<a id='submenu3' onclick='parent.hilite(3);' href='books/index_authors.php' class='tabdown' target='content' alt='Authors' title='Authors'>Authors</a>";
			} else {
				echo "<a id='submenu3' onclick='parent.hilite(3);' href='books/index_authors.php' class='tab' target='content' alt='Authors' title='Authors'>Authors</a>";
			}
			
			echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		}
	}

} // end class module


if (!empty($bool_register_modules)) {
	$_SESSION['arr_modules'][]=new Module_Books();
	$_SESSION['int_books_menu_selected'] = 1;
}

?>