<?

class Module_Billing extends Module {

	function Module_Billing() {
		$this->str_module_name = 'Billing';
		$this->str_module_folder = 'billing/';
		$this->str_active_link = 'billing/';
		$this->int_module_id=2;

		// call parent constructor which will load permissions
		parent::Module();

  }

	function createMonth($f_month, $f_year) {
		if ($this->monthExists($f_month,$f_year)) {
  		echo "ERROR:  New Month already exists!\n";
			return false;
		}

		echo "Creating billing module databases...\n";

		$old_month = $f_month-1;
		$old_year = $f_year;
		if ($old_month==0) { 
			$old_year--; 
			$old_month=12; 
		}
		if (!$this->monthExists($old_month,$old_year)) {
			echo "ERROR:  Month does not exist!\n";
			return false;
		}

		/*
			order bills that are
				define('BILL_STATUS_UNRESOLVED', 1);
				define('BILL_STATUS_PROCESSING', 4);
				define('BILL_STATUS_DISPATCHED', 5);
			should be carried forward
		*/
		$str_create = "
			CREATE TABLE bill_".$f_year."_".$f_month."
			(bill_id INT(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY(bill_id))
				SELECT *
				FROM bill_".$old_year."_".$old_month."
				WHERE (module_id = 7)
					AND (
							(bill_status = ".BILL_STATUS_UNRESOLVED.")
							OR (bill_status = ".BILL_STATUS_PROCESSING.")
							OR (bill_status = ".BILL_STATUS_DISPATCHED.")
						)
					AND (is_pending = 'Y')
		";
		$qry = new Query($str_create);
		if ($qry->b_error) {
			echo "Unable to create bill...\n $str_create";
			return false;
		} 
		$qry->Query("ALTER TABLE bill_".$f_year."_".$f_month." TYPE= INNODB");
		
		/*
			carry forward the items of the pending order bills
		*/
		$str_create = "
			CREATE TABLE bill_items_".$f_year."_".$f_month."
			(bill_item_id INT(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY(bill_item_id))
				SELECT bi.*
				FROM bill_items_".$old_year."_".$old_month." bi, bill_".$old_year."_".$old_month." b
				WHERE (bi.bill_id = b.bill_id)
					AND (b.module_id = 7)
					AND (
							(b.bill_status = ".BILL_STATUS_UNRESOLVED.")
							OR (bill_status = ".BILL_STATUS_PROCESSING.")
							OR (bill_status = ".BILL_STATUS_DISPATCHED.")
						)
					AND (b.is_pending = 'Y')";
		$qry->Query($str_create);
		if ($qry->b_error) {
			echo "Unable to create bill items...\n $str_create";
			return false;
		}
		$qry->Query("ALTER TABLE bill_items_".$f_year."_".$f_month." TYPE= INNODB");
		
		/*
			remove the order bills and items that were carried forward to the new month
		*/
		$str_create = "
			DELETE FROM bi, b
			USING bill_items_".$old_year."_".$old_month." AS bi, bill_".$old_year."_".$old_month." AS b
			WHERE (bi.bill_id = b.bill_id)
				AND (b.module_id = 7)
				AND (
						(b.bill_status = ".BILL_STATUS_UNRESOLVED.")
						OR (bill_status = ".BILL_STATUS_PROCESSING.")
						OR (bill_status = ".BILL_STATUS_DISPATCHED.")
					)
				AND (b.is_pending = 'Y')";
		$qry->Query($str_create);
		
		/*
			reset the bill numbers to zero
			if the clients module is included, these should only be reset on the financial year
		*/
		$qry->Query("
			SELECT * 
			FROM module 
			WHERE module_id = 9 
				AND active='Y'
		");
		
		if ($qry->RowCount() == 0) {
			$str_create = "
				UPDATE user_settings 
				SET
					bill_cash_bill_number = 0,
					bill_fs_bill_number = 0,
					bill_pt_bill_number = 0,
					bill_creditcard_bill_number = 0,
					bill_cheque_bill_number = 0,
					bill_transfer_bill_number = 0,
					bill_aurocard_bill_number = 0,
					bill_global_bill_number = 0,
					stock_dc_number = 0";
			$qry->Query($str_create);
			if ($qry->b_error) {
				echo "Unable to reset bill numbers in user_settings...\n $str_create";
				return false;
			}
		}
		else {
			if ($f_month==4) {


				$str_create = "
					CREATE TABLE bill_payments_".$f_year."
					LIKE bill_payments_".($old_year-1);
				$qry->Query($str_create);
				if ($qry->b_error) {
					echo "Unable to create bill_payments ...\n $str_create";
					return false;
				} 

				$str_create = "
					UPDATE user_settings 
					SET
						bill_cash_bill_number = 0,
						bill_fs_bill_number = 0,
						bill_pt_bill_number = 0,
						bill_creditcard_bill_number = 0,
						bill_cheque_bill_number = 0,
						bill_transfer_bill_number = 0,
						bill_aurocard_bill_number = 0,
						bill_global_bill_number = 0,
						stock_dc_number = 0";
				$qry->Query($str_create);
				if ($qry->b_error) {
					echo "Unable to reset bill numbers in user_settings...\n $str_create";
					return false;
				}
			}
		}
		echo "Done.\n";
		ob_flush();
		
		return true;
	}

	function monthExists($f_month, $f_year) {
		$qry = new Query("select * from bill_".$f_year."_".$f_month);
		if ($qry->b_error) {
			return false;
		}
		unset($qry);
		return true;
	}

	function yearExists($f_year) {
		$qry = new Query("select * from purchase_order_".$f_year);
		if ($qry->b_error) {
			return false;
		}
		unset($qry);
		return true;
	}


	function buildMenu($f_int_selected) {

		if (@$_SESSION['int_bills_menu_selected']==1) {
			$this->str_active_link = 'billing/index.php';
		}
		/*
		else if (@$_SESSION['int_bills_menu_selected']==2) {
			$this->str_active_link = 'billing/index_b2b.php';
		}
		else if (@$_SESSION['int_bills_menu_selected']==3) {
			$this->str_active_link = 'billing/index_b2cs.php';
		}
		*/
		else if (@$_SESSION['int_bills_menu_selected']==2) {
			$this->str_active_link = 'billing/index_hsn.php';
		}
		else if (@$_SESSION['int_bills_menu_selected']==3) {
			$this->str_active_link = 'billing/index_bill_statement.php';
		}
		else if (@$_SESSION['int_bills_menu_selected']==4) {
			$this->str_active_link = 'billing/index_daily_sales_register.php';
		}
		else if (@$_SESSION['int_bills_menu_selected']==5) {
			$this->str_active_link = 'billing/index_monthly_sales_register.php';
		}
		else if (@$_SESSION['int_bills_menu_selected']==6) {
			$this->str_active_link = 'billing/index_profit_statement.php';
		}
		else if (@$_SESSION['int_bills_menu_selected']==7) {
			$this->str_active_link = 'billing/index_daily_sales.php';
		}
		else if (@$_SESSION['int_bills_menu_selected']==8) {
			$this->str_active_link = 'billing/index_statements.php';
		}
		else if (@$_SESSION['int_bills_menu_selected']==9) {
			$this->str_active_link = 'billing/index_totals_statement.php';
		}
		else if (@$_SESSION['int_bills_menu_selected']==10) {
			$this->str_active_link = 'billing/statistics/index_statistics.php';
		}
		else if (@$_SESSION['int_bills_menu_selected']==11) {
			$this->str_active_link = 'clients/client_grid.php';
		}

		return $this->str_active_link;
		
		//parent::buildMenu($f_int_selected);
	}

	function buildSubMenu() {
		// add additional stuff if we're selected
		if (@$this->arr_storerooms[$_SESSION['int_current_storeroom']] >= ACCESS_READ) {


			/*
				important to change this total
			*/
			echo "<script language='javascript'>int_num_submenus=11;

			</script>";

			if (@$_SESSION['int_bills_menu_selected']==1) {
				echo "<a id='submenu1' onclick='parent.hilite(1);' href='billing/index.php' class='tabdown' target='content' alt='Bills' title='Bills'>Bills</a>";
			} else {
				echo "<a id='submenu1' onclick='parent.hilite(1);' href='billing/index.php' class='tab' target='content' alt='Bills' title='Bills'>Bills</a>";
			}

			/* b2b */
			/*
			if (@$_SESSION['int_bills_menu_selected']==2) {
				echo "<a id='submenu2' onclick='parent.hilite(2);' href='billing/index_b2b.php' class='tabdown' target='content' alt='b2b Statement' title='b2b Statement'>b2b Statement</a>";
			} else {
				echo "<a id='submenu2' onclick='parent.hilite(2);' href='billing/index_b2b.php' class='tab' target='content' alt='b2b Statement' title='b2b Statement'>b2b Statement</a>";
			}
			*/

			/* b2cs */
			/*
			if (@$_SESSION['int_bills_menu_selected']==3) {
				echo "<a id='submenu3' onclick='parent.hilite(3);' href='billing/index_b2cs.php' class='tabdown' target='content' alt='b2cs Statement' title='b2cs Statement'>b2cs Statement</a>";
			} else {
				echo "<a id='submenu3' onclick='parent.hilite(3);' href='billing/index_b2cs.php' class='tab' target='content' alt='b2cs Statement' title='b2cs Statement'>b2cs Statement</a>";
			}
			*/

			/* hsn */
			if (@$_SESSION['int_bills_menu_selected']==2) {
				echo "<a id='submenu2' onclick='parent.hilite(2);' href='billing/index_hsn.php' class='tabdown' target='content' alt='hsn Statement' title='Bills Statement'>hsn Statement</a>";
			} else {
				echo "<a id='submenu2' onclick='parent.hilite(2);' href='billing/index_hsn.php' class='tab' target='content' alt='hsn Statement' title='Bills Statement'>hsn Statement</a>";
			}

			if (@$_SESSION['int_bills_menu_selected']==3) {
				echo "<a id='submenu3' onclick='parent.hilite(3);' href='billing/index_bill_statement.php' class='tabdown' target='content' alt='Bills Statement' title='Bills Statement'>Bills Statement</a>";
			} else {
				echo "<a id='submenu3' onclick='parent.hilite(3);' href='billing/index_bill_statement.php' class='tab' target='content' alt='Bills Statement' title='Bills Statement'>Bills Statement</a>";
			}

			if (@$_SESSION['int_bills_menu_selected']==4) {
				echo "<a id='submenu4' onclick='parent.hilite(4);' href='billing/index_daily_sales_register.php' class='tabdown' target='content' alt='Daily Sales Register' title='Daily Sales Register'>Daily Sales Register</a>";
			} else {
				echo "<a id='submenu4' onclick='parent.hilite(4);' href='billing/index_daily_sales_register.php' class='tab' target='content' alt='Daily Sales Register' title='Daily Sales Register'>Daily Sales Register</a>";
			}

			if (@$_SESSION['int_bills_menu_selected']==5) {
				echo "<a id='submenu5' onclick='parent.hilite(5);' href='billing/index_monthly_sales_register.php' class='tabdown' target='content' alt='Monthly Sales Register' title='Monthly Sales Register'>Monthly Sales Register</a>";
			} else {
				echo "<a id='submenu5' onclick='parent.hilite(5);' href='billing/index_monthly_sales_register.php' class='tab' target='content' alt='Monthly Sales Register' title='Monthly Sales Register'>Monthly Sales Register</a>";
			}

			if (@$_SESSION['int_bills_menu_selected']==6) {
				echo "<a id='submenu6' onclick='parent.hilite(6);' href='billing/index_profit_statement.php' class='tabdown' target='content' alt='Statement of Profit' title='Statement of Profit'>Statement of Profit</a>";
			} else {
				echo "<a id='submenu6' onclick='parent.hilite(6);' href='billing/index_profit_statement.php' class='tab' target='content' alt='Statement of Profit' title='Statement of Profit'>Statement of Profit</a>";
			}

			if (@$_SESSION['int_bills_menu_selected']==7) {
				echo "<a id='submenu7' onclick='parent.hilite(7);' href='billing/index_daily_sales.php' class='tabdown' target='content' alt='Daily Sales Totals' title='Daily Sales Totals'>Daily Sales Totals</a>";
			} else {
				echo "<a id='submenu7' onclick='parent.hilite(7);' href='billing/index_daily_sales.php' class='tab' target='content' alt='Daily Sales Totals' title='Daily Sales Totals'>Daily Sales Totals</a>";
			}

			if (@$_SESSION['int_bills_menu_selected']==8) {
				echo "<a id='submenu8' onclick='parent.hilite(8);' href='billing/index_statements.php' class='tabdown' target='content' alt='Supplier statement' title='Supplier statement'>Supplier statement</a>";
			} else {
				echo "<a id='submenu8' onclick='parent.hilite(8);' href='billing/index_statements.php' class='tab' target='content' alt='Supplier statement' title='Supplier statement'>Supplier statement</a>";
			}

			if (@$_SESSION['int_bills_menu_selected']==9) {
				echo "<a id='submenu9' onclick='parent.hilite(9);' href='billing/index_totals_statement.php' class='tabdown' target='content' alt='Totals statement' title='Totals statement'>Totals statement</a>";
			} else {
				echo "<a id='submenu9' onclick='parent.hilite(9);' href='billing/index_totals_statement.php' class='tab' target='content' alt='Totals statement' title='Totals statement'>Totals statement</a>";
			}

			if (@$_SESSION['int_bills_menu_selected']==10) {
				echo "<a id='submenu10' onclick='parent.hilite(10);' href='billing/statistics/index_statistics.php' class='tabdown' target='content' alt='Sales Statistics' title='Sales Statistics'>Statistics</a>";
			} else {
				echo "<a id='submenu10' onclick='parent.hilite(10);' href='billing/statistics/index_statistics.php' class='tab' target='content' alt='Sales Statistics' title='Sales Statistics'>Statistics</a>";
			}

			if (@$_SESSION['int_bills_menu_selected']==11) {
				echo "<a id='submenu11' onclick='parent.hilite(11);' href='clients/client_grid.php' class='tabdown' target='content' alt='Clients' title='Clients'>Clients</a>";
			} else {
				echo "<a id='submenu11' onclick='parent.hilite(11);' href='clients/client_grid.php' class='tab' target='content' alt='Clients' title='Clients'>Clients</a>";
			}

			echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

		}
	}
}


if (!empty($bool_register_modules)) {

	$_SESSION['arr_modules'][]=new Module_Billing();
	
}


?>