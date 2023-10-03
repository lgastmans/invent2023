<?

class Module_Orders extends Module {

	function Module_Orders() {
		$this->str_module_name = 'Orders';
		$this->str_module_folder = 'orders/';
		$this->str_active_link = 'orders/';
		$this->int_module_id=7;
		parent::Module();
	}

	function createMonth($f_month, $f_year) {
		if ($this->monthExists($f_month,$f_year)) {
			echo "ERROR:  New Month already exists!\n";
			return false;
		}

		echo "Copying Orders module databases...\n";
		
		$old_month = $f_month-1;
		$old_year = $f_year;
		if ($old_month==0) {
			$old_year--;
			$old_month=12;
		}
		
		/*
			if the 'clients' module exists (module_id 9),
			only orders that are pending are carried forward
			else all all orders are carried forward
		*/
		$client_module_exists = false;
		$qry = new Query("
			SELECT *
			FROM module
			WHERE module_id = 9
				AND active='Y'
		");
		if ($qry->RowCount() > 0)
			$client_module_exists = true;

		if ($client_module_exists) {

			$str_create = "
				CREATE TABLE orders_".$f_year."_".$f_month." LIKE orders_".$old_year."_".$old_month;
			$qry->Query($str_create);
			if ($qry->b_error) {
				echo "Unable to create orders...\n $str_create";
				return false;
			}
		
			$str_create = "
				CREATE TABLE order_items_".$f_year."_".$f_month." LIKE order_items_".$old_year."_".$old_month;
			$qry->Query($str_create);
			if ($qry->b_error) {
				echo "Unable to create order_items...\n $str_create";
				return false;
			}
			
			/*
				carry forward orders that are:
					received	ORDER_STATUS_RECEIVED, 6
					pending		ORDER_STATUS_PENDING, 0
			*/
			$str_create = "
			INSERT INTO orders_".$f_year."_".$f_month."
				SELECT *
				FROM orders_".$old_year."_".$old_month."
				WHERE (order_status = ".ORDER_STATUS_RECEIVED.")
					OR (order_status = ".ORDER_STATUS_PENDING.")
			";
			$qry->Query($str_create);
			if ($qry->b_error) {
				echo "Unable to carry forward pending orders (1)...\n $str_create";
				return false;
			}
			
			/*
				carry forward the orders that are:
				pending AND that have corresponding bills
			*/
			$str_create = "
			INSERT INTO orders_".$f_year."_".$f_month."
				SELECT o.*
				FROM orders_".$old_year."_".$old_month." o, bill_".$f_year."_".$f_month." b
				WHERE (o.order_id = b.module_record_id)
					AND (b.module_id = 7)
					AND (
							(b.bill_status = ".BILL_STATUS_UNRESOLVED.")
							OR (b.bill_status = ".BILL_STATUS_PROCESSING.")
							OR (b.bill_status = ".BILL_STATUS_DISPATCHED.")
						)
					AND (b.is_pending = 'Y')
				GROUP BY order_id
			";
			$qry->Query($str_create);
			if ($qry->b_error) {
				echo $qry->err." > Unable to carry forward pending orders (2)...\n $str_create";
				return false;
			}
			
			/*
				carry forward the associated order items
			*/
			$str_create = "
				INSERT INTO order_items_".$f_year."_".$f_month."
					SELECT oi.*
					FROM order_items_".$old_year."_".$old_month." oi, orders_".$f_year."_".$f_month." o
					WHERE (oi.order_id = o.order_id)
			";
			$qry->Query($str_create);
			if ($qry->b_error) {
				echo "Unable to carry forward pending order items...\n $str_create";
				return false;
			}

			/*
				delete the orders in the previous month
			*/
			$str_create = "
				DELETE o.*
				FROM orders_".$old_year."_".$old_month." o, orders_".$f_year."_".$f_month." o_cur
				WHERE (o.order_id = o_cur.order_id)
			";
			$qry->Query($str_create);
			if ($qry->b_error) {
				echo "Unable to remove previous month orders...\n $str_create";
				return false;
			}

			/*
				delete the associated order items in the previous month
			*/
			$str_create = "
				DELETE oi.*
				FROM order_items_".$old_year."_".$old_month." oi, order_items_".$f_year."_".$f_month." oi_cur
				WHERE (oi.order_id = oi_cur.order_id)
			";
			$qry->Query($str_create);
			if ($qry->b_error) {
				echo "Unable to remove previous month order items...\n $str_create";
				return false;
			}
		  
		}
		else {
			$str_create = "
				CREATE TABLE orders_".$f_year."_".$f_month."
				(order_id INT(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY(order_id))
					SELECT *
					FROM orders_".$old_year."_".$old_month;
			$qry->Query($str_create);
			if ($qry->b_error) {
				echo "Unable to create orders...\n $str_create";
				return false;
			}
			$qry->Query("ALTER TABLE orders_".$f_year."_".$f_month." TYPE= INNODB");
  	
			$str_create = "
				CREATE TABLE order_items_".$f_year."_".$f_month."
				(order_item_id INT(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY(order_item_id))
					SELECT *
					FROM order_items_".$old_year."_".$old_month;
			$qry->Query($str_create);
			if ($qry->b_error) {
				echo "Unable to create order_items...\n $str_create";
				return false;
			}
			$qry->Query("ALTER TABLE order_items_".$f_year."_".$f_month." TYPE= INNODB");
		}

		echo "Done.\n";
		ob_flush();
		
		return true;
	}


	function monthExists($f_month, $f_year) {
		$qry = new Query("select * from orders_".$f_year."_".$f_month);
		if ($qry->b_error) {
			return false;
		}
		unset($qry);
		return true;
	}


  function buildMenu($f_int_selected) {
 	if (@$_SESSION['int_orders_menu_selected']==1) {
		$this->str_active_link = 'orders/index.php';
	}
	else if (@$_SESSION['int_orders_menu_selected']==2) {
		$this->str_active_link = 'orders/index_order_bills.php';
	}
	else if (@$_SESSION['int_orders_menu_selected']==3) {
		$this->str_active_link = 'orders/index_order_totals.php';
	}
	else if (@$_SESSION['int_orders_menu_selected']==4) {
		$this->str_active_link = 'orders/index_order_sheet.php';
	}
	else if (@$_SESSION['int_orders_menu_selected']==5) {
		$this->str_active_link = 'orders/index_order_community.php';
	}
	
		return $this->str_active_link;
		
		//parent::buildMenu($f_int_selected);


  }

function buildSubMenu() {
	$client_module_exists = false;
	$qry = new Query("
		SELECT *
		FROM module
		WHERE module_id = 9
	");
	if ($qry->RowCount() > 0)
		$client_module_exists = true;
	// add additional stuff if we're selected
	if ($this->arr_storerooms[$_SESSION['int_current_storeroom']] >= ACCESS_READ) {
		echo "<script language='javascript'>int_num_submenus=5;
		</script>";

		if ($client_module_exists) {
			if (@$_SESSION['int_orders_menu_selected']==1) {
				echo "<a id='submenu1' onclick='parent.hilite(1);' href='orders/index.php' class='tabdown' target='content' alt='List of Pending/Received Orders' title='List of Pending/Received Orders'>Pending Orders</a>";
			} else {
				echo "<a id='submenu1' onclick='parent.hilite(1);' href='orders/index.php' class='tab' target='content' alt='List of Pending/Received Orders' title='List of Pending/Received Orders'>Pending Orders</a>";
			}
		}
		else {
			if (@$_SESSION['int_orders_menu_selected']==1) {
				echo "<a id='submenu1' onclick='parent.hilite(1);' href='orders/index.php' class='tabdown' target='content' alt='Permanent Orders' title='Permanent Orders'>Permanent Orders</a>";
			} else {
				echo "<a id='submenu1' onclick='parent.hilite(1);' href='orders/index.php' class='tab' target='content' alt='Permanent Orders' title='Permanent Orders'>Permanent Orders</a>";
			}
		}
		
		if (@$_SESSION['int_orders_menu_selected']==2) {
			echo "<a id='submenu2' onclick='parent.hilite(2);' href='orders/index_order_bills.php' class='tabdown' target='content' alt='Order Bills List' title='Order Bills List'>Current Orders</a>";
		} else {
			echo "<a id='submenu2' onclick='parent.hilite(2);' href='orders/index_order_bills.php' class='tab' target='content' alt='Order Bills List' title='Order Bills List'>Current Orders</a>";
		}

		if (@$_SESSION['int_orders_menu_selected']==3) {
			echo "<a id='submenu3' onclick='parent.hilite(3);' href='orders/index_order_totals.php' class='tabdown' target='content' alt='Lists the total amount ordered per product' title='Lists the total amount ordered per product'>Order Totals</a>";
		} else {
			echo "<a id='submenu3' onclick='parent.hilite(3);' href='orders/index_order_totals.php' class='tab' target='content' alt='Lists the total amount ordered per product' title='Lists the total amount ordered per product'>Order Totals</a>";
		}

		if (@$_SESSION['int_orders_menu_selected']==4) {
			echo "<a id='submenu4' onclick='parent.hilite(4);' href='orders/index_order_sheet.php' class='tabdown' target='content' alt='Spreadsheet of products ordered per account' title='Spreadsheet of products ordered per account'>Order Sheet</a>";
		} else {
			echo "<a id='submenu4' onclick='parent.hilite(4);' href='orders/index_order_sheet.php' class='tab' target='content' alt='Spreadsheet of products ordered per account' title='Spreadsheet of products ordered per account'>Order Sheet</a>";
		}

		if (@$_SESSION['int_orders_menu_selected']==5) {
			echo "<a id='submenu5' onclick='parent.hilite(5);' href='orders/index_order_community.php' class='tabdown' target='content' alt='Spreadsheet of products ordered per account grouped community-wise' title='Spreadsheet of products ordered per account grouped community-wise'>Community Totals</a>";
		} else {
			echo "<a id='submenu5' onclick='parent.hilite(5);' href='orders/index_order_community.php' class='tab' target='content' alt='Spreadsheet of products ordered per account grouped community-wise' title='Spreadsheet of products ordered per account grouped community-wise'>Community Totals</a>";
		}

		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	}
  }
}


if (!empty($bool_register_modules)) {
	$_SESSION['arr_modules'][] = new Module_Orders();
}


?>