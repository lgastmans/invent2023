<?

class Module_Purchase extends Module {

	function Module_Purchase() {
		$this->str_module_name = 'Purchase';
		$this->str_module_folder = 'purchase/';
		$this->str_active_link = 'purchase/';
		$this->int_module_id=3;

		// call parent constructor which will load permissions
		parent::Module();

	}

	function createMonth($f_month, $f_year) {
		echo "Creating purchase module databases...\n";

		$old_month = $f_month-1;
		$old_year = $f_year;
		if ($old_month==0) { 
			$old_year--; 
			$old_month=12; 
		}

		// special processing for new financial year
		if ($f_month==4) {
			echo "Opening new financial year...\n";

			$str_create = "CREATE TABLE purchase_order_".$f_year." 
								(purchase_order_id INT(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY(purchase_order_id))
									SELECT * 
									FROM purchase_order_".($old_year-1)." 
									WHERE (purchase_status = 1)
										OR (purchase_status = 2)";
			$qry = new Query($str_create);
			if ($qry->b_error) {
				echo "Unable to create purchase order...\n $str_create";
				return false;
			}
			else
        echo "Purchase orders created \n";
	$qry->Query("ALTER TABLE purchase_order_".$f_year." TYPE= INNODB");

			$str_create = "CREATE TABLE purchase_items_".$f_year." 
								(purchase_item_id INT(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY(purchase_item_id))
									SELECT * 
									FROM purchase_items_".($old_year-1);
			$qry->Query($str_create);
			if ($qry->b_error) {
				echo "Unable to create purchase order items...\n $str_create";
				return false;
			}
			else
        echo "Purchase order items created \n";
        
      // update the items to include only those that are found in the purchase_order table
      $str_create = "DELETE purchase_items_".$f_year." 
                  FROM purchase_items_".$f_year.", purchase_order_".($f_year-1)."
                  WHERE (purchase_items_".$f_year.".purchase_order_id = purchase_order_".($f_year-1).".purchase_order_id)
                    AND (purchase_order_".($f_year-1).".purchase_status > 2)";
			$qry->Query($str_create);
			if ($qry->b_error) {
				echo "Unable to update purchase order items...\n $str_create";
				return false;
			}
			else
        echo "Purchase order items updated \n";
	$qry->Query("ALTER TABLE purchase_items_".$f_year." TYPE= INNODB");
      
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
		if (@$_SESSION['int_purchase_menu_selected']==1) {
			$this->str_active_link = 'purchase/purchase_list_frameset.php';
		} else if (@$_SESSION['int_purchase_menu_selected']==2) {
			$this->str_active_link = 'purchase/purchase_category_list_frameset.php';
		} else if (@$_SESSION['int_purchase_menu_selected']==3) {
			$this->str_active_link = 'purchase/index.php?cur_selected=3&status=1';
		} else if (@$_SESSION['int_purchase_menu_selected']==4) {
			$this->str_active_link = 'purchase/index.php?cur_selected=4&status=2';
		} else if (@$_SESSION['int_purchase_menu_selected']==5) {
			$this->str_active_link = 'purchase/index.php?cur_selected=5&status=3';
		} else if (@$_SESSION['int_purchase_menu_selected']==6) {
			$this->str_active_link = 'purchase/index.php?cur_selected=6&status=4';
		} else if (@$_SESSION['int_purchase_menu_selected']==7) {
			//$this->str_active_link = 'purchase/index_supplier_statement.php';
			$this->str_active_link = 'purchase/supplier_statement_grid.php';
		} else if (@$_SESSION['int_purchase_menu_selected']==8) {
			$this->str_active_link = 'purchase/index_purchase_sales_statement.php';
		}
		return $this->str_active_link;
		
		//parent::buildMenu($f_int_selected);

	}

	function buildSubMenu() {
		// add additional stuff if we're selected
		if (@$this->arr_storerooms[$_SESSION['int_current_storeroom']] >= ACCESS_READ) {
			echo "<script language='javascript'>int_num_submenus=8;
			</script>";
			if (@$_SESSION['int_purchase_menu_selected']==1) {
				echo "<a id='submenu1' onclick='parent.hilite(1);' href='purchase/purchase_list_frameset.php' class='tabdown' target='content' alt='Show Purchase Supplier List' title='Show Purchase Supplier List'>Supplier List</a>";
			} else {
				echo "<a id='submenu1' onclick='parent.hilite(1);' href='purchase/purchase_list_frameset.php' class='tab' target='content' alt='Show Purchase Supplier List' title='Show Purchase Supplier List'>Supplier List</a>";
			}
			
			if (@$_SESSION['int_purchase_menu_selected']==2) {
				echo "<a id='submenu2' onclick='parent.hilite(2);' href='purchase/purchase_category_list_frameset.php' class='tabdown' target='content' alt='Show Purchase Category List' title='Show Purchase Category List'>Category List</a>";
			} else {
				echo "<a id='submenu2' onclick='parent.hilite(2);' href='purchase/purchase_category_list_frameset.php' class='tab' target='content' alt='Show Purchase Category List' title='Show Purchase Category List'>Category List</a>";
			}
			
			if (@$_SESSION['int_purchase_menu_selected']==3) {
				echo "<a id='submenu3' onclick='parent.hilite(3);' href='purchase/index.php?cur_selected=3&status=1' class='tabdown' target='content' alt='Show Draft Purchase Orders' title='Show Draft Purchase Orders'>Drafts</a>";
			} else {
				echo "<a id='submenu3' onclick='parent.hilite(3);' href='purchase/index.php?cur_selected=3&status=1' class='tab' target='content' alt='Show Draft Purchase Orders' title='Show Draft Purchase Orders'>Drafts</a>";
			}
			
			if (@$_SESSION['int_purchase_menu_selected']==4) {
				echo "<a id='submenu4' onclick='parent.hilite(4);' href='purchase/index.php?cur_selected=4&status=2' class='tabdown' target='content' alt='Show Sent Purchase Orders' title='Show Sent Purchase Orders'>Sent</a>";
			} else {
				echo "<a id='submenu4' onclick='parent.hilite(4);' href='purchase/index.php?cur_selected=4&status=2' class='tab' target='content' aalt='Show Sent Purchase Orders' title='Show Sent Purchase Orders'>Sent</a>";
			}
			
			if (@$_SESSION['int_purchase_menu_selected']==5) {
				echo "<a id='submenu5' onclick='parent.hilite(5);' href='purchase/index.php?cur_selected=5&status=3' class='tabdown' target='content' alt='Show Received Purchase Orders' title='Show Received Purchase Orders'>Received</a>";
			} else {
				echo "<a id='submenu5' onclick='parent.hilite(5);' href='purchase/index.php?cur_selected=5&status=3' class='tab' target='content' alt='Show Received Purchase Orders' title='Show Received Purchase Orders'>Received</a>";
			}
			
			if (@$_SESSION['int_purchase_menu_selected']==6) {
				echo "<a id='submenu6' onclick='parent.hilite(6);' href='purchase/index.php?cur_selected=6&status=4' class='tabdown' target='content' alt='Show Cancelled Purchase Orders' title='Show Cancelled Purchase Orders'>Cancelled</a>";
			} else {
				echo "<a id='submenu6' onclick='parent.hilite(6);' href='purchase/index.php?cur_selected=6&status=4' class='tab' target='content' alt='Show Cancelled Purchase Orders' title='Show Cancelled Purchase Orders'>Cancelled</a>";
			}
			
			if (@$_SESSION['int_purchase_menu_selected']==7) {
				echo "<a id='submenu7' onclick='parent.hilite(7);' href='purchase/supplier_statement_grid.php' class='tabdown' target='content' alt='Supplier statement' title='Supplier statement'>Supplier statement</a>";
			} else {
				echo "<a id='submenu7' onclick='parent.hilite(7);' href='purchase/supplier_statement_grid.php' class='tab' target='content' alt='Supplier statement' title='Supplier statement'>Supplier statement</a>";
			}
			
			if (@$_SESSION['int_purchase_menu_selected']==8) {
				echo "<a id='submenu8' onclick='parent.hilite(8);' href='purchase/index_purchase_sales_statement.php' class='tabdown' target='content' alt='Purchase Sales Register' title='Purchase Sales Register'>Purchase Sales Register</a>";
			} else {
				echo "<a id='submenu8' onclick='parent.hilite(8);' href='purchase/index_purchase_sales_statement.php' class='tab' target='content' alt='Purchase Sales Register' title='Purchase Sales Register'>Purchase Sales Register</a>";
			}
			
			echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

		}
	}

} // end class module


if (!empty($bool_register_modules)) {
	$_SESSION['arr_modules'][]=new Module_Purchase();
}

?>