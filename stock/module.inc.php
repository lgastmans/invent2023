<?php

class Module_Stock extends Module {

function Module_Stock() {
	$this->str_module_name = 'Stock';
	$this->str_module_folder = 'stock/';
	$this->str_active_link = 'stock/';
	$this->int_module_id=1;
	parent::Module();
}

function createMonth($f_month, $f_year) {
	if ($this->monthExists($f_month,$f_year)) {
		echo "ERROR:  New Month already exists!\n";
		return false;
	}
	
	echo "Creating stock module databases...\n";
	
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
		stock storeroom batch
	*/
	$str_check = "
		SELECT COUNT(*)
		FROM information_schema.tables 
		WHERE table_schema = '".$arr_invent_config['database']['invent_database']."'
		AND table_name = 'stock_storeroom_batch_".$old_year."_".$old_month."'
	";
	$qry = new Query($str_check);
	
	if ($qry->RowCount() > 0) {
		$str_create = "
			CREATE TABLE stock_storeroom_batch_".$f_year."_".$f_month." 
			(stock_storeroom_batch_id INT(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY(stock_storeroom_batch_id))
				SELECT *
				FROM stock_storeroom_batch_".$old_year."_".$old_month." 
				WHERE (
				(stock_available > 0) OR
				(stock_ordered > 0) OR
				(stock_reserved > 0) OR
				(is_active = 'Y')
				)";
		$qry->Query($str_create);
		if ($qry->b_error) {
			echo "Unable to create storeroom_batch...\n $str_create";
			//return false;
		}
		$qry->Query("ALTER TABLE stock_storeroom_batch_".$f_year."_".$f_month." TYPE= INNODB");
		$qry->Query("ALTER TABLE `stock_storeroom_batch_".$f_year."_".$f_month."` ADD INDEX `batch_supplier_product` ( `batch_id` , `product_id` )");
		$qry->Query("ALTER TABLE `stock_storeroom_batch_".$f_year."_".$f_month."` ADD INDEX ( `product_id` , `batch_id` , `storeroom_id` , `is_active` )");
		$qry->Query("ALTER TABLE `stock_storeroom_batch_".$f_year."_".$f_month."` ADD INDEX ( `is_active` )");
	}
	else {
		$handle = fopen($arr_invent_config['application']['path']."newmonth.txt", "a");
		if ($handle) {
			fwrite($handle, date("Y-m-d").":: TABLE stock_storeroom_batch_".$old_year."_".$old_month." NOT FOUND \n");
			fclose($handle);
		}
		echo "TABLE stock_storeroom_batch_".$old_year."_".$old_month." NOT FOUND \n";
	}
	
	
	/*
		stock storeroom product
	*/
	$str_check = "
		SELECT COUNT(*)
		FROM information_schema.tables
		WHERE table_schema = '".$arr_invent_config['database']['invent_database']."'
		AND table_name = 'stock_storeroom_product_".$old_year."_".$old_month."'
	";
	$qry->Query($str_check);
	
	if ($qry->RowCount() > 0) {
		$str_create = "
			CREATE TABLE stock_storeroom_product_".$f_year."_".$f_month." 
			(storeroom_product_id INT(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY(storeroom_product_id))
				SELECT * FROM stock_storeroom_product_".$old_year."_".$old_month;
		$qry->Query($str_create);
		if ($qry->b_error) {
			echo "Unable to create storeroom_product...\n $str_create";
			return false;
		}
		
		if (IsSet($_SESSION['newmonth_reset_adjusted'])) {
			if ($_SESSION['newmonth_reset_adjusted'] == 'Y') {
				$str_create = "
					UPDATE stock_storeroom_product_".$f_year."_".$f_month."
					SET stock_adjusted = 0
				";
				$qry->Query($str_create);
				if ($qry->b_error) {
					echo "Unable to reset the adjusted stock to zero...\n $str_create";
					return false;
				}
				else
					echo "All adjusted stock reset to zero...\n";
			}
		}
		
		$qry->Query("ALTER TABLE stock_storeroom_product_".$f_year."_".$f_month." TYPE= INNODB");
		$qry->Query("ALTER TABLE `stock_storeroom_product_".$f_year."_".$f_month."` ADD INDEX `productid_storeroomid` ( `product_id` , `storeroom_id` ) ");
		$qry->Query("ALTER TABLE `stock_storeroom_product_".$f_year."_".$f_month."` ADD INDEX ( `product_id` )");
	}
	else {
		$handle = fopen($arr_invent_config['application']['path']."newmonth.txt", "a");
		if ($handle) {
			fwrite($handle, date("Y-m-d").":: TABLE stock_storeroom_product_".$old_year."_".$old_month." NOT FOUND \n");
			fclose($handle);
		}
		echo "TABLE stock_storeroom_batch".$old_year."_".$old_month." NOT FOUND \n";
	}
	
	$str_create = "
		CREATE TABLE stock_transfer_".$f_year."_".$f_month." 
			LIKE stock_transfer_".$old_year."_".$old_month;
	$qry->Query($str_create);
	if ($qry->b_error) {
		echo "Unable to create stock_transfer...<br> $str_create <br> ".$qry->err;
		return false;
	} 


	$str_create = "
		CREATE TABLE stock_tax_".$f_year."_".$f_month." 
		(tax_id INT(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY(tax_id))
			SELECT * FROM stock_tax_".$old_year."_".$old_month;
	$qry->Query($str_create);
	if ($qry->b_error) {
		echo "Unable to create storeroom_stock_tax...\n $str_create";
		return false;
	} 
	$qry->Query("ALTER TABLE stock_tax_".$f_year."_".$f_month." TYPE= INNODB");
	

	$str_create = "
		CREATE TABLE stock_tax_links_".$f_year."_".$f_month." 
			SELECT * FROM stock_tax_links_".$old_year."_".$old_month;
	$qry->Query($str_create);
	if ($qry->b_error) {
		echo "Unable to create stock_tax_links...\n $str_create";
		return false;
	} 
	$qry->Query("ALTER TABLE stock_tax_links_".$f_year."_".$f_month." TYPE= INNODB");
	

	$str_create = "
		CREATE TABLE stock_tax_definition_".$f_year."_".$f_month." 
		(definition_id INT(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY(definition_id))
			SELECT * FROM stock_tax_definition_".$old_year."_".$old_month;
	$qry->Query($str_create);
	if ($qry->b_error) {
		echo "Unable to create stock_tax_definition...\n $str_create";
		return false;
	} 
	$qry->Query("ALTER TABLE stock_tax_definition_".$f_year."_".$f_month." TYPE= INNODB");
	
	/*
		return to supplier tables
	*/
	$str_create = "
		CREATE TABLE stock_rts_".$f_year."_".$f_month."
		LIKE stock_rts_".$old_year."_".$old_month;
	$qry->Query($str_create);
	if ($qry->b_error) {
		echo "Unable to create stock_rts...\n $str_create";
		return false;
	} 

	$str_create = "
		CREATE TABLE stock_rts_items_".$f_year."_".$f_month."
		LIKE stock_rts_items_".$old_year."_".$old_month;
	$qry->Query($str_create);
	if ($qry->b_error) {
		echo "Unable to create stock_rts_items...\n $str_create";
		return false;
	} 

	/*
		add the monthly row for the supplier commissions
	*/
	$str_create = "
		INSERT INTO stock_supplier_commissions
			(
				supplier_id,
				commission_percent,
				commission_percent_2,
				commission_percent_3,
				month,
				year)
  			SELECT
  				stock_supplier.supplier_id,
  				commission_percent,
  				commission_percent_2,
  				commission_percent_3,
  				'".$old_month."' AS month,
  				'".$old_year."' AS year
  			FROM stock_supplier
	";
	$qry->Query($str_create);
	if ($qry->b_error) {
		echo "Unable to add row to table stock_supplier_commissions...\n $str_create";
		return false;
	} 
	
	
	/*
		delivery chalan tables
	*/
	$str_create = "
		CREATE TABLE dc_".$f_year."_".$f_month."
		LIKE dc_".$old_year."_".$old_month;
	$qry->Query($str_create);
	if ($qry->b_error) {
		echo "Unable to create dc...\n $str_create";
		return false;
	} 

	$str_create = "
		CREATE TABLE dc_items_".$f_year."_".$f_month."
		LIKE dc_items_".$old_year."_".$old_month;
	$qry->Query($str_create);
	if ($qry->b_error) {
		echo "Unable to create stock_rts_items...\n $str_create";
		return false;
	} 

	// special processing for new financial year
	if ($f_month==4) {
		echo "Opening new financial year...\n";

		$str_create = "
			CREATE TABLE stock_balance_".$f_year." 
				LIKE stock_balance_".($old_year-1);
		$qry->Query($str_create);
		
		if ($qry->b_error) {
			echo "Unable to create storeroom_balance table...\n $str_create";
			return false;
		}
		$qry->Query("ALTER TABLE `stock_balance_".$f_year."` TYPE= INNODB");
		$qry->Query("ALTER TABLE `stock_balance_".$f_year."` ADD INDEX `productid_storeroomid_month` ( `product_id` , `storeroom_id` , `balance_month` )");
		$qry->Query("ALTER TABLE `stock_balance_".$f_year."` ADD INDEX `productid_storeroomid_monthyear` ( `product_id` , `storeroom_id` , `balance_month` , `balance_year` )");

		  $str_create = "
			CREATE TEMPORARY TABLE tmp_stock_balance 
				SELECT * FROM stock_balance_".($old_year-1)." 
				WHERE balance_year=$old_year and balance_month=$old_month";

		$qry->Query($str_create);
		if ($qry->b_error) {
			echo "Unable to create temporary table required to process balances...\n $str_create";
			return false;
		}

		$str_create = "
			UPDATE tmp_stock_balance 
				SET	balance_id=0,
					  stock_opening_balance=stock_closing_balance, 
					  stock_in=0,
					  stock_out=0,
					  stock_sold=0,
					  stock_damaged=0,
					  stock_wasted=0,
					  stock_returned=0,
					  stock_received=0,
					  stock_mismatch_addition=0,
					  stock_mismatch_deduction=0,
					  stock_cancelled=0,
					  balance_month=$f_month, 
					  balance_year=$f_year 
			WHERE balance_year=$old_year and balance_month=$old_month";
		$qry->Query($str_create);
		if ($qry->b_error) {
			echo "Unable to process balances in temp table...\n $str_create";
			return false;
		}
		
		$str_create = "
			INSERT INTO stock_balance_".$f_year." 
				SELECT * FROM tmp_stock_balance";
		$qry->Query($str_create);
		if ($qry->b_error) {
			echo "Unable to import temporary table data into new year table...\n $str_create";
			return false;
		}

		$str_create = "DROP TABLE tmp_stock_balance";
		$qry->Query($str_create);
		if ($qry->b_error) {
			echo "Unable to delete temporary table...\n $str_create";
			return false;
		}
		
		// carry over the stock_batch table
		// is_active='N' is not removed from the table as inactive batches could be reactivated.
		$str_create = "
			CREATE TABLE stock_batch_".$f_year." 
			(batch_id INT(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY(batch_id))
				SELECT *
				FROM stock_batch_".($old_year-1)." 
				WHERE (deleted = 'N')";
		$qry->Query($str_create);
		if ($qry->b_error) {
			echo "Unable to carry over stock batch table...\n $str_create";
			return false;
		}
		$qry->Query("ALTER TABLE stock_batch_".$f_year." TYPE= INNODB");
		$qry->Query("ALTER TABLE `stock_batch_".$f_year."` ADD INDEX `batch_supplier_product` ( `batch_id` , `supplier_id` , `product_id` )");
		$qry->Query("ALTER TABLE `stock_batch_".$f_year."` ADD INDEX ( `product_id` , `storeroom_id` , `status` , `deleted` ) ");
		$qry->Query("ALTER TABLE `stock_batch_".$f_year."` ADD INDEX ( `product_id` )");
		$qry->Query("ALTER TABLE `stock_batch_2007` ADD INDEX ( `supplier_id` )");
		$qry->Query("ALTER TABLE `stock_batch_2007` ADD INDEX ( `storeroom_id` )");
		$qry->Query("ALTER TABLE `stock_batch_2007` ADD INDEX ( `status` )");
		$qry->Query("ALTER TABLE `stock_batch_2007` ADD INDEX ( `deleted` )");

	} else {
		$bal_year = $old_year;
		if ($old_month<4)  
			$bal_year = $bal_year - 1;

		$str_create = "
			CREATE TEMPORARY TABLE tmp_stock_balance 
				SELECT * 
				FROM stock_balance_".($bal_year)." 
				WHERE balance_year=$old_year and balance_month=$old_month";

		$qry->Query($str_create);
		if ($qry->b_error) {
			echo "Unable to create temporary table required to process balances...";
			return false;
		}

		$str_create = "
			UPDATE tmp_stock_balance 
			SET balance_id=0,
			      stock_opening_balance=stock_closing_balance, 
			      stock_in=0,
			      stock_out=0,
			      stock_sold=0,
			      stock_damaged=0,
			      stock_wasted=0,
			      stock_returned=0,
			      stock_received=0,
			      stock_mismatch_addition=0,
			      stock_mismatch_deduction=0,
			      stock_cancelled=0,
			      balance_month=$f_month, 
			      balance_year=$f_year 
			WHERE balance_year=$old_year and balance_month=$old_month";
		$qry->Query($str_create);
		if ($qry->b_error) {
			echo "Unable to process balances in temp table...";
			return false;
		}
		
		$str_create = "
			INSERT INTO stock_balance_".$bal_year." 
				SELECT *
				FROM tmp_stock_balance";
		$qry->Query($str_create);
		if ($qry->b_error) {
			echo "Unable to import temporary table data into new year table...";
			return false;
		}

		$str_create = "DROP TABLE tmp_stock_balance";
		$qry->Query($str_create);
		if ($qry->b_error) {
			echo "Unable to delete temporary table...";
			return false;
		}
	}

//==================
// Check batches with zero quantity and flagged active
//------------------

	$str_batches = "
		SELECT stock_storeroom_batch_id, batch_id, @cur_id := product_id AS product_id,
			(SELECT 
				COUNT(batch_id) 
				FROM stock_storeroom_batch_".$f_year."_".$f_month."
				WHERE stock_available  > 0 
					AND is_active = 'Y' 
					AND product_id = @cur_id
			) AS counter
		FROM stock_storeroom_batch_".$f_year."_".$f_month."
		WHERE is_active = 'Y' AND stock_available = 0";
	$qry_batches = new Query($str_batches);
	
	$arr_batches = array();
	
	for ($i=0;$i<$qry_batches->RowCount();$i++) {
		if ($qry_batches->FieldByName('counter') > 0)
			$arr_batches[] = $qry_batches->FieldByName('stock_storeroom_batch_id');
		
		$qry_batches->Next();
	}

	if (count($arr_batches) > 0) {
		for ($i=0; $i<count($arr_batches); $i++) {
			$str_update = "
				UPDATE stock_storeroom_batch_".$f_year."_".$f_month."
				SET is_active = 'N'
				WHERE stock_storeroom_batch_id = ".$arr_batches[$i]."
				LIMIT 1
			";
			$qry_batches->Query($str_update);
		}
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


function buildMenu($f_int_selected) {
	if (@$_SESSION['int_stock_selected']==1) {
		$this->str_active_link = 'stock/index.php';
	} else if (@$_SESSION['int_stock_selected']==2) {
		$this->str_active_link = 'stock/batchgrid/index.php';

	} else if (@$_SESSION['int_stock_selected']==3) {
		$this->str_active_link = 'stock/transfers/index_received.php';

	} else if (@$_SESSION['int_stock_selected']==4) {
		$this->str_active_link = 'stock/transfers/index.php';
	/*
	} else if (@$_SESSION['int_stock_selected']==5) {
		$this->str_active_link = 'stock/transfers/index_requested.php';

	} else if (@$_SESSION['int_stock_selected']==6) {
		$this->str_active_link = 'stock/transfers/index_dispatched.php';
	*/
	} else if (@$_SESSION['int_stock_selected']==5) {
		$this->str_active_link = 'stock/rts/index_rts.php';

	} else if (@$_SESSION['int_stock_selected']==6) {
		$this->str_active_link = 'stock/index_supplier_statement.php';

	} else if (@$_SESSION['int_stock_selected']==7) {
		$this->str_active_link = 'stock/index_supplier_currentstock.php';
	
	} else if (@$_SESSION['int_stock_selected']==8) {
		$this->str_active_link = 'stock/index_in_list.php';
		
	} else if (@$_SESSION['int_stock_selected']==9) {
		$this->str_active_link = 'stock/index_stock_register.php';
	} else if (@$_SESSION['int_stock_selected']==10) {
		$this->str_active_link = 'stock/dc/index_dc.php';
	}
	
	return $this->str_active_link;
		
		//parent::buildMenu($f_int_selected);
}

function buildSubMenu() {
	// add additional stuff if we're selected
	if ($this->arr_storerooms[$_SESSION['int_current_storeroom']] >= ACCESS_READ) {
		echo "<script language='javascript'>int_num_submenus=10;
		</script>";

		if (@$_SESSION['int_stock_selected']==1) {
			echo "<a id='submenu1' onclick='parent.hilite(1);' href='stock/index.php' class='tabdown' target='content' alt='Product Grid' title='Show Product Grid'>Products</a>";
		} else {
			echo "<a id='submenu1' onclick='parent.hilite(1);' href='stock/index.php' class='tab' target='content' alt='Product Grid' title='Show Product Grid'>Products</a>";
		}

		if (@$_SESSION['int_stock_selected']==2) {
			echo "<a id='submenu2' onclick='parent.hilite(2);' href='stock/batchgrid/index.php' class='tabdown' target='content' alt='Batch Grid' title='Show Batch Grid'>Batches</a>";
		} else {
			echo "<a id='submenu2' onclick='parent.hilite(2);' href='stock/batchgrid/index.php' class='tab' target='content' alt='Batch Grid' title='Show Batch Grid'>Batches</a>";
		}

		if (@$_SESSION['int_stock_selected']==3) {
			echo "<a id='submenu3' onclick='parent.hilite(3);' href='stock/transfers/index_received.php' class='tabdown' target='content' alt='Show Received Stock' title='Show Received Stock'>Stock In</a>";
		} else {
			echo "<a id='submenu3' onclick='parent.hilite(3);' href='stock/transfers/index_received.php' class='tab' target='content' alt='Show Received Stock' title='Show Received Stock'>Stock In</a>";
		}

		if (@$_SESSION['int_stock_selected']==4) {
			echo "<a id='submenu4' onclick='parent.hilite(4);' href='stock/transfers/index.php' class='tabdown' target='content' alt='Show Transfers Made' title='Show Transfers Made'>Stock Out</a>";
		} else {
			echo "<a id='submenu4' onclick='parent.hilite(4);' href='stock/transfers/index.php' class='tab' target='content' alt='Transfers Grid' title='Show Transfers Grid'>Stock Out</a>";
		}
		/*
		if (@$_SESSION['int_stock_selected']==5) {
			echo "<a id='submenu5' onclick='parent.hilite(5);' href='stock/transfers/index_requested.php' class='tabdown' target='content' alt='Show Transfers Requested' title='Show Transfers Requested'>Stock Requested</a>";
		} else {
			echo "<a id='submenu5' onclick='parent.hilite(5);' href='stock/transfers/index_requested.php' class='tab' target='content' alt='Show Transfers Requested' title='Show Transfers Requested'>Stock Requested</a>";
		}

		if (@$_SESSION['int_stock_selected']==6) {
			echo "<a id='submenu6' onclick='parent.hilite(6);' href='stock/transfers/index_dispatched.php' class='tabdown' target='content' alt='Show Transfers Dispatched' title='Show Transfers Dispatched'>Stock Dispatched</a>";
		} else {
			echo "<a id='submenu6' onclick='parent.hilite(6);' href='stock/transfers/index_dispatched.php' class='tab' target='content' alt='Show Transfers Dispatched' title='Show Transfers Dispatched'>Stock Dispatched</a>";
		}
		*/
		if (@$_SESSION['int_stock_selected']==5) {
			echo "<a id='submenu5' onclick='parent.hilite(5);' href='stock/rts/index_rts.php' class='tabdown' target='content' alt='Return to section' title='Return to section'>Return to supplier</a>";
		} else {
			echo "<a id='submenu5' onclick='parent.hilite(5);' href='stock/rts/index_rts.php' class='tab' target='content' alt='Return to section' title='Return to section'>Return to supplier</a>";
		}

		if (@$_SESSION['int_stock_selected']==6) {
			echo "<a id='submenu6' onclick='parent.hilite(6);' href='stock/index_supplier_statement.php' class='tabdown' target='content' alt='Statement of total supplied per supplier' title='Statement of total supplied per supplier'>Supplier received</a>";
		} else {
			echo "<a id='submenu6' onclick='parent.hilite(6);' href='stock/index_supplier_statement.php' class='tab' target='content' alt='Statement of total supplied per supplier' title='Statement of total supplied per supplier'>Supplier received</a>";
		}

		if (@$_SESSION['int_stock_selected']==7) {
			echo "<a id='submenu7' onclick='parent.hilite(7);' href='stock/index_supplier_currentstock.php' class='tabdown' target='content' alt='Statement of current stock per supplier' title='Statement of current stock per supplier'>Supplier stock</a>";
		} else {
			echo "<a id='submenu7' onclick='parent.hilite(7);' href='stock/index_supplier_currentstock.php' class='tab' target='content' alt='Statement of current stock per supplier' title='Statement of current stock per supplier'>Supplier stock</a>";
		}

		if (@$_SESSION['int_stock_selected']==8) {
			echo "<a id='submenu8' onclick='parent.hilite(8);' href='stock/index_in_list.php' class='tabdown' target='content' alt='List of stock received' title='List of stock received'>In&nbsp;List</a>";
		} else {
			echo "<a id='submenu8' onclick='parent.hilite(8);' href='stock/index_in_list.php' class='tab' target='content' alt='List of stock received' title='List of stock received'>In&nbsp;List</a>";
		}

		if (@$_SESSION['int_stock_selected']==9) {
			echo "<a id='submenu9' onclick='parent.hilite(9);' href='stock/index_stock_register.php' class='tabdown' target='content' alt='Registry of stock movement' title='Registry of stock movement'>Registry</a>";
		} else {
			echo "<a id='submenu9' onclick='parent.hilite(9);' href='stock/index_stock_register.php' class='tab' target='content' alt='Registry of stock movement' title='Registry of stock movement'>Registry</a>";
		}

		if (@$_SESSION['int_stock_selected']==10) {
			echo "<a id='submenu10' onclick='parent.hilite(10);' href='stock/dc/index_dc.php' class='tabdown' target='content' alt='Delivery Challans' title='Delivery Challans'>DC</a>";
		} else {
			echo "<a id='submenu10' onclick='parent.hilite(10);' href='stock/dc/index_dc.php' class='tab' target='content' alt='Delivery Challans' title='Delivery Challans'>DC</a>";
		}
		
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		
	}
}
}


if (!empty($bool_register_modules)) {
	$_SESSION['arr_modules'][]=new Module_Stock();
}


?>