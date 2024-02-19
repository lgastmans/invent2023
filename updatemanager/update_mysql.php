<?php

	if ((isset($_GET['install'])) && ($_GET['install']=='Y')) {

		session_start();

		$_SESSION['bool_logged_in'] = true;

		$_SESSION["int_month_loaded"] = Date("n",time());
		$_SESSION["int_year_loaded"] = Date("Y",time());

	}



	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");



// https://stackoverflow.com/questions/6432178/how-can-i-check-if-a-mysql-table-exists-with-php
// The cleanest way to achieve this in PHP is to simply use DESCRIBE statement.
// if(mysql_query("DESCRIBE `table`")) {
//     // Exists
// }



	function insert_table($table_name, $sql) {

		$res = new Query("DESCRIBE `".$table_name."`");

		if ($res->b_error) {

			foreach($sql as $row) {

				$res->Query($row);

				if ($res->b_error) {
					return $res->err;
				}
			}
		    return "TABLE $table_name CREATED<br>";

		}
		else {

			//return "TABLE $table_name EXISTS ALREADY<br>";
			return "CHECKED $table_name <br>";

		}

	}



	function insert_column($table_name, $sql) {

		$column_name = strtok($sql, "\`");

		$res = new Query("SELECT ".$column_name." FROM ".$table_name);

		if ($res->b_error) {

		    $res->Query("ALTER TABLE ".$table_name." ADD ".$sql);

			if ($res->b_error) {
				return $res->err;
			}

		    return $column_name.' HAS BEEN ADDED TO '.$table_name."<br>";

		} else {

		    //return $table_name."->".$column_name.' exists already<br>';
		    return $res->err;

		}

	}


	
	function execute_update($stamp, $sql) {


		$res = new Query("SELECT * FROM update_log WHERE type_id = ".$stamp);

		if ($res->b_error) {

			return $res->err."<br>";

		}
		else {

			if ($res->RowCount() > 0) {

				return "$stamp already updated<br>";

			}
			else {

				$res->Query($sql);

				if ($res->b_error) {

					return $res->err."<br>";

				}
				else {

					$res->Query("
						INSERT INTO update_log 
							(type_id, updated_on, user_id) 
							VALUES('".$stamp."', '".date('Y-m-d H:i:s')."', '".$_SESSION['int_user_id']."') 
					");

					return $stamp." UPDATED<br>";

				}
			}

		}
	}



	$sql=array();


	/*

			ACCOUNTS

	*/


	$sql[0] = "
		CREATE TABLE IF NOT EXISTS `account_cc` (
		  `cc_id` int(11) NOT NULL DEFAULT '0',
		  `account_number` varchar(6) NOT NULL DEFAULT '',
		  `account_name` varchar(50) NOT NULL DEFAULT '',
		  `account_enabled` char(1) NOT NULL DEFAULT 'Y',
		  `community` varchar(50) NOT NULL DEFAULT '',
		  `account_balance` float NOT NULL DEFAULT '0',
		  `account_active` char(1) NOT NULL DEFAULT 'Y',
		  `account_credit_line` float NOT NULL DEFAULT '0',
		  `account_may_go_below` char(1) NOT NULL DEFAULT 'N',
		  `account_type` tinyint(4) NOT NULL DEFAULT '0',
		  `linked_cc_id` int(11) NOT NULL DEFAULT '0'
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;	
	";

	$sql[1]= "ALTER TABLE `account_cc`
		  ADD PRIMARY KEY (`cc_id`),
		  ADD KEY `number` (`account_number`);		
	";

	echo insert_table('account_cc', $sql);

	echo insert_column('account_cc', "`cc_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('account_cc', "`account_number` varchar(6) NOT NULL DEFAULT ''");
	echo insert_column('account_cc', "`account_name` varchar(50) NOT NULL DEFAULT ''");
	echo insert_column('account_cc', "`account_enabled` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('account_cc', "`community` varchar(50) NOT NULL DEFAULT ''");
	echo insert_column('account_cc', "`account_balance` float NOT NULL DEFAULT '0'");
	echo insert_column('account_cc', "`account_active` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('account_cc', "`account_credit_line` float NOT NULL DEFAULT '0'");
	echo insert_column('account_cc', "`account_may_go_below` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('account_cc', "`account_type` tinyint(4) NOT NULL DEFAULT '0'");
	echo insert_column('account_cc', "`linked_cc_id` int(11) NOT NULL DEFAULT '0'");




	$sql[0] = "
		CREATE TABLE IF NOT EXISTS `account_profile` (
		  `profile_id` int(11) NOT NULL,
		  `profile_name` varchar(40) NOT NULL DEFAULT '',
		  `profile_description` tinytext NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;
	";

	$sql[1] = "ALTER TABLE `account_profile` ADD PRIMARY KEY (`profile_id`);";

	$sql[2] = "ALTER TABLE `account_profile` MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT;";

	echo insert_table('account_profile', $sql);

	echo insert_column('account_profile', "`profile_id` int(11) NOT NULL");
	echo insert_column('account_profile', "`profile_name` varchar(40) NOT NULL DEFAULT ''");
	echo insert_column('account_profile', "`profile_description` tinytext NOT NULL");




	$sql[0] = "
		CREATE TABLE `account_pt` (
		  `account_id` int(11) NOT NULL,
		  `account_name` varchar(50) COLLATE latin1_general_ci NOT NULL DEFAULT '',
		  `account_number` varchar(10) COLLATE latin1_general_ci NOT NULL DEFAULT '',
		  `account_status` varchar(50) COLLATE latin1_general_ci NOT NULL DEFAULT '',
		  `enabled` char(1) COLLATE latin1_general_ci NOT NULL DEFAULT 'Y',
		  `community_id` int(11) NOT NULL DEFAULT '0'
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
	";

	$sql[1] = "ALTER TABLE `account_pt` ADD PRIMARY KEY (`account_id`);";

	$sql[2] = "ALTER TABLE `account_pt` MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT;";

	echo insert_table('account_pt', $sql);

	echo insert_column('account_pt', "`account_id` int(11) NOT NULL");
	echo insert_column('account_pt', "`account_name` varchar(50) COLLATE latin1_general_ci NOT NULL DEFAULT ''");
	echo insert_column('account_pt', "`account_number` varchar(10) COLLATE latin1_general_ci NOT NULL DEFAULT ''");
	echo insert_column('account_pt', "`account_status` varchar(50) COLLATE latin1_general_ci NOT NULL DEFAULT ''");
	echo insert_column('account_pt', "`enabled` char(1) COLLATE latin1_general_ci NOT NULL DEFAULT 'Y'");
	echo insert_column('account_pt', "`community_id` int(11) NOT NULL DEFAULT '0'");




	$sql[0] = "
		CREATE TABLE `".Monthalize('account_record')."` (
		  `cc_id` int(11) NOT NULL,
		  `profile_id` int(11) NOT NULL DEFAULT '0',
		  `debit_balance` float NOT NULL DEFAULT '0',
		  `credit_balance` float NOT NULL DEFAULT '0',
		  `account_comments` varchar(255) NOT NULL DEFAULT ''
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";


	$sql[1] = "ALTER TABLE `".Monthalize('account_record')."` ADD PRIMARY KEY (`cc_id`);";

	$sql[2] = "ALTER TABLE `".Monthalize('account_record')."` MODIFY `cc_id` int(11) NOT NULL AUTO_INCREMENT;";

	echo insert_table(Monthalize('account_record'), $sql);

	echo insert_column(Monthalize('account_record'), "`cc_id` int(11) NOT NULL");
	echo insert_column(Monthalize('account_record'), "`profile_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('account_record'), "`debit_balance` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('account_record'), "`credit_balance` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('account_record'), "`account_comments` varchar(255) NOT NULL DEFAULT ''");




	$sql[0] = "
		CREATE TABLE `".Monthalize('account_transfers')."` (
		  `transfer_id` int(11) NOT NULL,
		  `cc_id_from` int(11) NOT NULL DEFAULT '0',
		  `cc_id_to` int(11) NOT NULL DEFAULT '0',
		  `account_from` varchar(6) NOT NULL DEFAULT '',
		  `account_to` varchar(6) NOT NULL DEFAULT '',
		  `amount` float NOT NULL DEFAULT '0',
		  `description` varchar(50) NOT NULL DEFAULT '',
		  `module_id` tinyint(4) NOT NULL DEFAULT '0',
		  `module_record_id` int(11) NOT NULL DEFAULT '0',
		  `user_id` int(11) NOT NULL DEFAULT '0',
		  `date_created` datetime NOT NULL,
		  `date_completed` datetime NOT NULL,
		  `transfer_status` tinyint(4) NOT NULL DEFAULT '0'
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";


	$sql[1] = "ALTER TABLE `".Monthalize('account_transfers')."` ADD PRIMARY KEY (`transfer_id`);";

	$sql[2] = "ALTER TABLE `".Monthalize('account_transfers')."` MODIFY `transfer_id` int(11) NOT NULL AUTO_INCREMENT;";

	echo insert_table(Monthalize('account_transfers'), $sql);

	echo insert_column(Monthalize('account_transfers'), "`transfer_id` int(11) NOT NULL");
	echo insert_column(Monthalize('account_transfers'), "`cc_id_from` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('account_transfers'), "`cc_id_to` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('account_transfers'), "`account_from` varchar(6) NOT NULL DEFAULT ''");
	echo insert_column(Monthalize('account_transfers'), "`account_to` varchar(6) NOT NULL DEFAULT ''");
	echo insert_column(Monthalize('account_transfers'), "`amount` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('account_transfers'), "`description` varchar(50) NOT NULL DEFAULT ''");
	echo insert_column(Monthalize('account_transfers'), "`module_id` tinyint(4) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('account_transfers'), "`module_record_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('account_transfers'), "`user_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('account_transfers'), "`date_created` datetime NOT NULL'");
	echo insert_column(Monthalize('account_transfers'), "`date_completed` datetime NOT NULL");
	echo insert_column(Monthalize('account_transfers'), "`transfer_status` tinyint(4) NOT NULL DEFAULT '0'");




	/*

			BILLS

	*/

	$sql[0] = "
		CREATE TABLE `".Monthalize('bill')."` (
		  `bill_id` int(11) NOT NULL,
		  `storeroom_id` int(11) NOT NULL DEFAULT '0',
		  `bill_number` int(11) NOT NULL DEFAULT '0',
		  `date_created` datetime DEFAULT NULL,
		  `total_amount` float NOT NULL DEFAULT '0',
		  `payment_type` tinyint(4) NOT NULL DEFAULT '0',
		  `payment_type_number` varchar(50) NOT NULL DEFAULT '',
		  `bill_promotion` float NOT NULL DEFAULT '0',
		  `bill_status` tinyint(4) NOT NULL DEFAULT '0',
		  `is_pending` char(1) NOT NULL DEFAULT '',
		  `user_id` int(11) NOT NULL DEFAULT '0',
		  `module_id` int(11) NOT NULL DEFAULT '0',
		  `module_record_id` int(11) NOT NULL DEFAULT '0',
		  `resolved_on` datetime DEFAULT NULL,
		  `CC_id` int(11) NOT NULL DEFAULT '0',
		  `account_number` varchar(6) NOT NULL DEFAULT '',
		  `account_name` varchar(50) NOT NULL DEFAULT '',
		  `cancelled_user_id` int(11) NOT NULL DEFAULT '0',
		  `cancelled_reason` varchar(64) NOT NULL DEFAULT '',
		  `card_name` varchar(32) NOT NULL DEFAULT '',
		  `card_number` varchar(19) NOT NULL DEFAULT '',
		  `card_date` varchar(10) NOT NULL DEFAULT '',
		  `is_debit_bill` char(1) NOT NULL DEFAULT 'N',
		  `discount` smallint(6) NOT NULL DEFAULT '0',
		  `is_modified` char(1) NOT NULL DEFAULT 'N',
		  `aurocard_number` int(11) NOT NULL DEFAULT '0',
		  `aurocard_transaction_id` int(11) NOT NULL DEFAULT '0',
		  `salesperson_id` int(11) DEFAULT NULL,
		  `supply_date_time` datetime DEFAULT NULL,
		  `supply_place` varchar(75) DEFAULT NULL,
		  `customer_id` int(11) DEFAULT NULL,
		  `table_ref` varchar(16) DEFAULT NULL,
		  `is_draft` tinyint(4) NOT NULL DEFAULT '0',
		  `gstin` varchar(16) NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";


	$sql[1] = "ALTER TABLE `".Monthalize('bill')."` ADD PRIMARY KEY (`bill_id`);";

	$sql[2] = "ALTER TABLE `".Monthalize('bill')."` MODIFY `bill_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;";

	echo insert_table(Monthalize('bill'), $sql);

	echo insert_column(Monthalize('bill'), "`bill_id` int(11) NOT NULL");
	echo insert_column(Monthalize('bill'), "`storeroom_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('bill'), "`bill_number` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('bill'), "`date_created` datetime DEFAULT NULL");
	echo insert_column(Monthalize('bill'), "`total_amount` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('bill'), "`payment_type` tinyint(4) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('bill'), "`payment_type_number` varchar(50) NOT NULL DEFAULT ''");
	echo insert_column(Monthalize('bill'), "`bill_promotion` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('bill'), "`bill_status` tinyint(4) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('bill'), "`is_pending` char(1) NOT NULL DEFAULT ''");
	echo insert_column(Monthalize('bill'), "`user_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('bill'), "`module_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('bill'), "`module_record_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('bill'), "`resolved_on` datetime DEFAULT NULL");
	echo insert_column(Monthalize('bill'), "`CC_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('bill'), "`account_number` varchar(6) NOT NULL DEFAULT ''");
	echo insert_column(Monthalize('bill'), "`account_name` varchar(50) NOT NULL DEFAULT ''");
	echo insert_column(Monthalize('bill'), "`cancelled_user_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('bill'), "`cancelled_reason` varchar(64) NOT NULL DEFAULT ''");
	echo insert_column(Monthalize('bill'), "`card_name` varchar(32) NOT NULL DEFAULT ''");
	echo insert_column(Monthalize('bill'), "`card_number` varchar(19) NOT NULL DEFAULT ''");
	echo insert_column(Monthalize('bill'), "`card_date` varchar(10) NOT NULL DEFAULT ''");
	echo insert_column(Monthalize('bill'), "`is_debit_bill` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column(Monthalize('bill'), "`discount` smallint(6) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('bill'), "`is_modified` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column(Monthalize('bill'), "`aurocard_number` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('bill'), "`aurocard_transaction_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('bill'), "`salesperson_id` int(11) DEFAULT NULL");
	echo insert_column(Monthalize('bill'), "`supply_date_time` datetime DEFAULT NULL");
	echo insert_column(Monthalize('bill'), "`supply_place` varchar(75) DEFAULT NULL");
	echo insert_column(Monthalize('bill'), "`customer_id` int(11) DEFAULT NULL");
	echo insert_column(Monthalize('bill'), "`table_ref` varchar(16) DEFAULT NULL");
	echo insert_column(Monthalize('bill'), "`is_draft` tinyint(4) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('bill'), "`gstin` varchar(16) NOT NULL");




	$sql[0] = "
		CREATE TABLE `".Monthalize('bill_items')."` (
		  `bill_item_id` int(11) NOT NULL,
		  `quantity` float NOT NULL DEFAULT '0',
		  `quantity_ordered` float NOT NULL DEFAULT '0',
		  `discount` smallint(6) NOT NULL DEFAULT '0',
		  `price` float NOT NULL DEFAULT '0',
		  `bprice` float NOT NULL DEFAULT '0',
		  `tax_id` smallint(6) NOT NULL DEFAULT '0',
		  `tax_amount` float NOT NULL DEFAULT '0',
		  `product_id` int(11) NOT NULL DEFAULT '0',
		  `bill_id` int(11) NOT NULL DEFAULT '0',
		  `batch_id` int(11) NOT NULL DEFAULT '0',
		  `adjusted_quantity` float NOT NULL DEFAULT '0',
		  `product_description` varchar(50) NOT NULL DEFAULT ''
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `".Monthalize('bill_items')."` ADD PRIMARY KEY (`bill_item_id`);";

	$sql[2] = "ALTER TABLE `".Monthalize('bill_items')."` MODIFY `bill_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;";

	echo insert_table(Monthalize('bill_items'), $sql);

	echo insert_column(Monthalize('bill_items'), "`bill_item_id` int(11) NOT NULL");
	echo insert_column(Monthalize('bill_items'), "`quantity` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('bill_items'), "`quantity_ordered` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('bill_items'), "`discount` smallint(6) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('bill_items'), "`price` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('bill_items'), "`bprice` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('bill_items'), "`tax_id` smallint(6) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('bill_items'), "`tax_amount` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('bill_items'), "`product_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('bill_items'), "`bill_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('bill_items'), "`batch_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('bill_items'), "`adjusted_quantity` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('bill_items'), "`product_description` varchar(50) NOT NULL DEFAULT ''");




	$sql[0] = "
		CREATE TABLE `".Yearalize('bill_payments')."` (
		  `id` int(11) NOT NULL,
		  `bill_id` int(11) NOT NULL,
		  `amount` decimal(10,2) NOT NULL,
		  `payment_reference` varchar(128) NOT NULL,
		  `payment_type` set('Cash','Bank Transfer','Cheque','Financial Service','Wire Transfer','Other') NOT NULL,
		  `payment_date` date NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `".Yearalize('bill_payments')."`  ADD PRIMARY KEY (`id`),  ADD KEY `bill_id` (`bill_id`) USING BTREE;";

	$sql[2] = "ALTER TABLE `".Yearalize('bill_payments')."`  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;";

	echo insert_table(Yearalize('bill_payments'), $sql);

	echo insert_column(Yearalize('bill_payments'), "`id` int(11) NOT NULL");
	echo insert_column(Yearalize('bill_payments'), "`bill_id` int(11) NOT NULL");
	echo insert_column(Yearalize('bill_payments'), "`amount` decimal(10,2) NOT NULL");
	echo insert_column(Yearalize('bill_payments'), "`payment_reference` varchar(128) NOT NULL");
	echo insert_column(Yearalize('bill_payments'), "`payment_type` set('Cash','Bank Transfer','Cheque','Financial Service','Wire Transfer','Other') NOT NULL");
	echo insert_column(Yearalize('bill_payments'), "`payment_date` date NOT NULL");




	$sql[0] = "
		CREATE TABLE `communities` (
		  `community_id` int(11) NOT NULL,
		  `community_name` varchar(50) COLLATE latin1_general_ci NOT NULL DEFAULT '',
		  `is_individual` char(1) COLLATE latin1_general_ci NOT NULL DEFAULT 'N'
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;";

	$sql[1] = "ALTER TABLE `communities`  ADD PRIMARY KEY (`community_id`);";

	$sql[2] = "ALTER TABLE `communities`  MODIFY `community_id` int(11) NOT NULL AUTO_INCREMENT;";

	echo insert_table('communities', $sql);

	echo insert_column('communities', "`community_id` int(11) NOT NULL");
	echo insert_column('communities', "`community_name` varchar(50) COLLATE latin1_general_ci NOT NULL DEFAULT ''");
	echo insert_column('communities', "`is_individual` char(1) COLLATE latin1_general_ci NOT NULL DEFAULT 'N'");




	$sql[0] = "
		CREATE TABLE `company` (
		  `id` int(11) NOT NULL,
		  `title` varchar(128) DEFAULT NULL,
		  `legal_name` varchar(128) DEFAULT NULL,
		  `trade_name` varchar(128) NOT NULL,
		  `trust` varchar(128) NOT NULL,
		  `gstin` varchar(32) NOT NULL,
		  `address` varchar(128) NOT NULL,
		  `phone` varchar(32) NOT NULL,
		  `email` varchar(64) NOT NULL,
		  `email_password` varchar(256) DEFAULT NULL,
		  `footer` varchar(512) NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "INSERT INTO `company` (`id`, `title`, `legal_name`, `trade_name`, `trust`, `gstin`, `address`, `phone`, `email`, `email_password`, `footer`) VALUES (1, 'Company', '', '', 'Trust', '', '', '', '', '', '');";

	$sql[2] = "ALTER TABLE `company`  ADD PRIMARY KEY (`id`);";

	$sql[3] = "ALTER TABLE `company`  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;";


	echo insert_table('company', $sql);

	echo insert_column('company', "`id` int(11) NOT NULL");
	echo insert_column('company', "`title` varchar(128) DEFAULT NULL");
	echo insert_column('company', "`legal_name` varchar(128) DEFAULT NULL");
	echo insert_column('company', "`trade_name` varchar(128) NOT NULL");
	echo insert_column('company', "`trust` varchar(128) NOT NULL");
	echo insert_column('company', "`gstin` varchar(32) NOT NULL");
	echo insert_column('company', "`address` varchar(128) NOT NULL");
	echo insert_column('company', "`phone` varchar(32) NOT NULL");
	echo insert_column('company', "`email` varchar(64) NOT NULL");
	echo insert_column('company', "`email_password` varchar(256) DEFAULT NULL");
	echo insert_column('company', "`footer` varchar(512) NOT NULL");




	$sql[0] = "
		CREATE TABLE `customer` (
		  `id` int(11) NOT NULL,
		  `customer_id` varchar(15) DEFAULT ' ',
		  `company` varchar(60) DEFAULT ' ',
		  `address` varchar(80) DEFAULT ' ',
		  `address2` varchar(60) DEFAULT ' ',
		  `city` varchar(30) DEFAULT ' ',
		  `zip` varchar(10) DEFAULT ' ',
		  `phone1` varchar(30) DEFAULT ' ',
		  `phone2` varchar(30) DEFAULT ' ',
		  `fax` varchar(30) DEFAULT '',
		  `email` varchar(30) DEFAULT ' ',
		  `cell` varchar(30) DEFAULT ' ',
		  `contact_person` varchar(40) DEFAULT '',
		  `sales_tax_no` varchar(50) DEFAULT ' ',
		  `sales_tax_type` varchar(50) DEFAULT ' ',
		  `tax` float NOT NULL DEFAULT '0',
		  `tax_id` int(11) NOT NULL DEFAULT '0',
		  `sur` varchar(50) DEFAULT ' ',
		  `surcharge` float DEFAULT '0',
		  `delivery_address` varchar(150) DEFAULT ' ',
		  `discount` float DEFAULT '0',
		  `payment_terms` varchar(90) DEFAULT ' ',
		  `payment_type` varchar(255) NOT NULL DEFAULT 'rupees',
		  `username` varchar(64) NOT NULL,
		  `password` varchar(128) NOT NULL,
		  `is_active` char(1) NOT NULL DEFAULT 'Y',
		  `is_modified` char(1) NOT NULL DEFAULT 'N',
		  `can_view_price` char(1) NOT NULL DEFAULT 'Y',
		  `currency_id` int(11) NOT NULL,
		  `price_increase` decimal(2,2) NOT NULL,
		  `is_other_state` char(1) NOT NULL DEFAULT 'N',
		  `state` varchar(50) NOT NULL,
		  `state_code` varchar(25) NOT NULL,
		  `gstin` varchar(50) NOT NULL,
		  `ship_address` varchar(80) DEFAULT NULL,
		  `ship_address2` varchar(60) DEFAULT NULL,
		  `ship_city` varchar(30) DEFAULT NULL,
		  `ship_zip` varchar(10) DEFAULT NULL,
		  `ship_company` varchar(60) DEFAULT NULL,
		  `ship_state` varchar(50) DEFAULT NULL,
		  `ship_state_code` varchar(25) DEFAULT NULL,
		  `ship_gstin` varchar(50) DEFAULT NULL,
		  `same_address` char(1) NOT NULL DEFAULT 'Y',
		  `fs_account` varchar(16) DEFAULT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `customer`  ADD PRIMARY KEY (`id`);";

	$sql[2] = "ALTER TABLE `customer`  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;";


	echo insert_table('customer', $sql);

	echo insert_column('customer', "`id` int(11) NOT NULL");
	echo insert_column('customer', "`customer_id` varchar(15) DEFAULT ' '");
	echo insert_column('customer', "`company` varchar(60) DEFAULT ' '");
	echo insert_column('customer', "`address` varchar(80) DEFAULT ' '");
	echo insert_column('customer', "`address2` varchar(60) DEFAULT ' '");
	echo insert_column('customer', "`city` varchar(30) DEFAULT ' '");
	echo insert_column('customer', "`zip` varchar(10) DEFAULT ' '");
	echo insert_column('customer', "`phone1` varchar(30) DEFAULT ' '");
	echo insert_column('customer', "`phone2` varchar(30) DEFAULT ' '");
	echo insert_column('customer', "`fax` varchar(30) DEFAULT ''");
	echo insert_column('customer', "`email` varchar(30) DEFAULT ' '");
	echo insert_column('customer', "`cell` varchar(30) DEFAULT ' '");
	echo insert_column('customer', "`contact_person` varchar(40) DEFAULT ''");
	echo insert_column('customer', "`sales_tax_no` varchar(50) DEFAULT ' '");
	echo insert_column('customer', "`sales_tax_type` varchar(50) DEFAULT ' '");
	echo insert_column('customer', "`tax` float NOT NULL DEFAULT '0'");
	echo insert_column('customer', "`tax_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('customer', "`sur` varchar(50) DEFAULT ' '");
	echo insert_column('customer', "`surcharge` float DEFAULT '0'");
	echo insert_column('customer', "`delivery_address` varchar(150) DEFAULT ' '");
	echo insert_column('customer', "`discount` float DEFAULT '0'");
	echo insert_column('customer', "`payment_terms` varchar(90) DEFAULT ' '");
	echo insert_column('customer', "`payment_type` varchar(255) NOT NULL DEFAULT 'rupees'");
	echo insert_column('customer', "`username` varchar(64) NOT NULL");
	echo insert_column('customer', "`password` varchar(128) NOT NULL");
	echo insert_column('customer', "`is_active` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('customer', "`is_modified` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('customer', "`can_view_price` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('customer', "`currency_id` int(11) NOT NULL");
	echo insert_column('customer', "`price_increase` decimal(2,2) NOT NULL");
	echo insert_column('customer', "`is_other_state` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('customer', "`state` varchar(50) NOT NULL");
	echo insert_column('customer', "`state_code` varchar(25) NOT NULL");
	echo insert_column('customer', "`gstin` varchar(50) NOT NULL");
	echo insert_column('customer', "`ship_address` varchar(80) DEFAULT NULL");
	echo insert_column('customer', "`ship_address2` varchar(60) DEFAULT NULL");
	echo insert_column('customer', "`ship_city` varchar(30) DEFAULT NULL");
	echo insert_column('customer', "`ship_zip` varchar(10) DEFAULT NULL");
	echo insert_column('customer', "`ship_company` varchar(60) DEFAULT NULL");
	echo insert_column('customer', "`ship_state` varchar(50) DEFAULT NULL");
	echo insert_column('customer', "`ship_state_code` varchar(25) DEFAULT NULL");
	echo insert_column('customer', "`ship_gstin` varchar(50) DEFAULT NULL");
	echo insert_column('customer', "`same_address` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('customer', "`fs_account` varchar(16) DEFAULT NULL");
	echo insert_column('customer', "`country_id` INT NULL AFTER `state_code`");
	echo insert_column('customer', "`ship_country_id` INT NULL AFTER `country_id`");





	$sql[0] = "
		CREATE TABLE `".Monthalize('dc')."` (
		  `dc_id` int(11) NOT NULL,
		  `storeroom_id` int(11) NOT NULL DEFAULT '0',
		  `client_id` int(11) NOT NULL DEFAULT '0',
		  `dc_number` int(11) NOT NULL DEFAULT '0',
		  `date_created` datetime DEFAULT NULL,
		  `total_amount` float NOT NULL DEFAULT '0',
		  `user_id` int(11) NOT NULL DEFAULT '0',
		  `is_modified` char(1) NOT NULL DEFAULT 'N'
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `".Monthalize('dc')."`  ADD PRIMARY KEY (`dc_id`);";

	$sql[2] = "ALTER TABLE `".Monthalize('dc')."`  MODIFY `dc_id` int(11) NOT NULL AUTO_INCREMENT;";

	echo insert_table(Monthalize('dc'), $sql);

	echo insert_column(Monthalize('dc'), "`dc_id` int(11) NOT NULL");
	echo insert_column(Monthalize('dc'), "`storeroom_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('dc'), "`client_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('dc'), "`dc_number` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('dc'), "`date_created` datetime DEFAULT NULL");
	echo insert_column(Monthalize('dc'), "`total_amount` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('dc'), "`user_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('dc'), "`is_modified` char(1) NOT NULL DEFAULT 'N'");




	$sql[0] = "
		CREATE TABLE `".Monthalize('dc_items')."` (
		  `dc_item_id` int(11) NOT NULL,
		  `quantity` float NOT NULL DEFAULT '0',
		  `discount` smallint(6) NOT NULL DEFAULT '0',
		  `price` float NOT NULL DEFAULT '0',
		  `product_id` int(11) NOT NULL DEFAULT '0',
		  `dc_id` int(11) NOT NULL DEFAULT '0',
		  `batch_id` int(11) NOT NULL DEFAULT '0',
		  `product_description` varchar(256) NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `".Monthalize('dc_items')."`  ADD PRIMARY KEY (`dc_item_id`);";

	$sql[2] = "ALTER TABLE `".Monthalize('dc_items')."`  MODIFY `dc_item_id` int(11) NOT NULL AUTO_INCREMENT;";

	echo insert_table(Monthalize('dc_items'), $sql);

	echo insert_column(Monthalize('dc_items'), "`dc_item_id` int(11) NOT NULL");
	echo insert_column(Monthalize('dc_items'), "`quantity` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('dc_items'), "`discount` smallint(6) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('dc_items'), "`price` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('dc_items'), "`product_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('dc_items'), "`dc_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('dc_items'), "`batch_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('dc_items'), "`product_description` varchar(256) NOT NULL");





	$sql[0] = "
		CREATE TABLE `grid` (
		  `grid_id` int(11) NOT NULL,
		  `user_id` int(11) NOT NULL DEFAULT '0',
		  `grid_name` varchar(20) NOT NULL DEFAULT '',
		  `column_name` varchar(30) NOT NULL DEFAULT '',
		  `field_name` varchar(30) NOT NULL DEFAULT '',
		  `field_type` varchar(20) NOT NULL DEFAULT '',
		  `width` int(11) NOT NULL DEFAULT '0',
		  `can_filter` char(1) NOT NULL DEFAULT 'Y',
		  `callback` varchar(40) NOT NULL DEFAULT '',
		  `visible` char(1) NOT NULL DEFAULT 'Y',
		  `view_name` varchar(20) NOT NULL DEFAULT 'default',
		  `column_order` int(11) NOT NULL DEFAULT '0'
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `grid`  ADD PRIMARY KEY (`grid_id`);";

	$sql[2] = "ALTER TABLE `grid`  MODIFY `grid_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;";

	echo insert_table('grid', $sql);

	echo insert_column('grid', "`grid_id` int(11) NOT NULL");
	echo insert_column('grid', "`user_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('grid', "`grid_name` varchar(20) NOT NULL DEFAULT ''");
	echo insert_column('grid', "`column_name` varchar(30) NOT NULL DEFAULT ''");
	echo insert_column('grid', "`field_name` varchar(30) NOT NULL DEFAULT ''");
	echo insert_column('grid', "`field_type` varchar(20) NOT NULL DEFAULT ''");
	echo insert_column('grid', "`width` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('grid', "`can_filter` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('grid', "`callback` varchar(40) NOT NULL DEFAULT ''");
	echo insert_column('grid', "`visible` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('grid', "`view_name` varchar(20) NOT NULL DEFAULT 'default'");
	echo insert_column('grid', "`column_order` int(11) NOT NULL DEFAULT '0'");




	$sql[0] = "
		CREATE TABLE `module` (
		  `module_id` int(11) NOT NULL,
		  `module_name` varchar(128) NOT NULL DEFAULT '',
		  `description` varchar(255) NOT NULL DEFAULT '',
		  `module_folder` varchar(128) NOT NULL DEFAULT '',
		  `active` char(1) NOT NULL DEFAULT 'Y'
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";


	$sql[1] = "
		INSERT INTO `module` (`module_id`, `module_name`, `description`, `module_folder`, `active`) VALUES
		(1, 'stock', 'stock', 'stock/', 'Y'),
		(2, 'billing', 'billing', 'billing/', 'Y'),
		(3, 'purchase', 'purchase', 'purchase/', 'Y'),
		(4, 'admin', 'admin', 'admin/', 'Y'),
		(5, 'accounts', 'accounts', 'accounts/', 'Y'),
		(6, 'pt_accounts', 'PT Accounts', 'pt_accounts/', 'N'),
		(7, 'orders', 'orders', 'orders/', 'Y'),
		(8, 'storerooms', 'storerooms', 'storerooms/', 'Y'),
		(9, 'clients', 'clients', 'clients/', 'Y'),
		(10, 'settings', 'settings', 'settings/', 'Y'),
		(11, 'books', 'books', 'books/', 'Y');";


	$sql[2] = "ALTER TABLE `module`  ADD PRIMARY KEY (`module_id`);";

	$sql[3] = "ALTER TABLE `module`  MODIFY `module_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;";

	echo insert_table('module', $sql);

	echo insert_column('module', "`module_id` int(11) NOT NULL");
	echo insert_column('module', "`module_name` varchar(128) NOT NULL DEFAULT ''");
	echo insert_column('module', "`description` varchar(255) NOT NULL DEFAULT ''");
	echo insert_column('module', "`module_folder` varchar(128) NOT NULL DEFAULT ''");
	echo insert_column('module', "`active` char(1) NOT NULL DEFAULT 'Y'");





	$sql[0] = "
		CREATE TABLE `online_settings` (
		  `supplier_id` int(11) NOT NULL,
		  `sales_id` int(11) NOT NULL,
		  `stock_id` int(11) NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `online_settings`  ADD UNIQUE KEY `supplier_id` (`supplier_id`);";

	echo insert_table('online_settings', $sql);

	echo insert_column('online_settings', "`supplier_id` int(11) NOT NULL");
	echo insert_column('online_settings', "`sales_id` int(11) NOT NULL");
	echo insert_column('online_settings', "`stock_id` int(11) NOT NULL");





	$sql[0] = "
		CREATE TABLE `".Monthalize('orders')."` (
		  `order_id` int(11) NOT NULL,
		  `CC_id` int(11) NOT NULL DEFAULT '0',
		  `order_type` int(11) NOT NULL DEFAULT '0',
		  `day_of_week` int(11) NOT NULL DEFAULT '0',
		  `order_month` int(11) NOT NULL DEFAULT '0',
		  `order_week` int(11) NOT NULL DEFAULT '0',
		  `total_amount` float NOT NULL DEFAULT '0',
		  `payment_type` int(11) NOT NULL DEFAULT '0',
		  `is_billable` char(1) NOT NULL DEFAULT 'Y',
		  `order_status` int(11) NOT NULL DEFAULT '0',
		  `date_cancel_from` date DEFAULT NULL,
		  `date_cancel_till` date DEFAULT NULL,
		  `storeroom_id` int(11) NOT NULL DEFAULT '0',
		  `user_id` int(11) NOT NULL DEFAULT '0',
		  `community` varchar(50) NOT NULL DEFAULT '',
		  `community_id` int(11) NOT NULL DEFAULT '0',
		  `note` varchar(64) NOT NULL DEFAULT '',
		  `handling_charge` float NOT NULL DEFAULT '0',
		  `order_reference` varchar(30) NOT NULL DEFAULT '',
		  `order_date` date DEFAULT NULL,
		  `advance_paid` float NOT NULL DEFAULT '0',
		  `is_debit_invoice` char(1) NOT NULL DEFAULT 'N',
		  `discount` smallint(6) NOT NULL DEFAULT '0',
		  `handling_is_percentage` char(1) NOT NULL DEFAULT 'Y',
		  `courier_charge` float NOT NULL DEFAULT '0',
		  `courier_is_percentage` char(1) NOT NULL DEFAULT 'Y',
		  `reseller_order_id` int(11) NOT NULL DEFAULT '0',
		  `is_modified` char(1) NOT NULL DEFAULT 'N',
		  `has_c_form` char(1) NOT NULL DEFAULT 'N',
		  `has_h_form` char(1) NOT NULL DEFAULT 'N',
		  `account_number` varchar(50) DEFAULT NULL,
		  `account_name` varchar(25) DEFAULT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `".Monthalize('orders')."`  ADD PRIMARY KEY (`order_id`);";

	$sql[2] = "ALTER TABLE `".Monthalize('orders')."`  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;";

	echo insert_table(Monthalize('orders'), $sql);

	echo insert_column(Monthalize('orders'), "`order_id` int(11) NOT NULL");
	echo insert_column(Monthalize('orders'), "`CC_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('orders'), "`order_type` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('orders'), "`day_of_week` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('orders'), "`order_month` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('orders'), "`order_week` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('orders'), "`total_amount` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('orders'), "`payment_type` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('orders'), "`is_billable` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column(Monthalize('orders'), "`order_status` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('orders'), "`date_cancel_from` date DEFAULT NULL");
	echo insert_column(Monthalize('orders'), "`date_cancel_till` date DEFAULT NULL");
	echo insert_column(Monthalize('orders'), "`storeroom_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('orders'), "`user_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('orders'), "`community` varchar(50) NOT NULL DEFAULT ''");
	echo insert_column(Monthalize('orders'), "`community_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('orders'), "`note` varchar(64) NOT NULL DEFAULT ''");
	echo insert_column(Monthalize('orders'), "`handling_charge` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('orders'), "`order_reference` varchar(30) NOT NULL DEFAULT ''");
	echo insert_column(Monthalize('orders'), "`order_date` date DEFAULT NULL");
	echo insert_column(Monthalize('orders'), "`advance_paid` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('orders'), "`is_debit_invoice` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column(Monthalize('orders'), "`discount` smallint(6) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('orders'), "`handling_is_percentage` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column(Monthalize('orders'), "`courier_charge` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('orders'), "`courier_is_percentage` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column(Monthalize('orders'), "`reseller_order_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('orders'), "`is_modified` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column(Monthalize('orders'), "`has_c_form` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column(Monthalize('orders'), "`has_h_form` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column(Monthalize('orders'), "`account_number` varchar(50) DEFAULT NULL");
	echo insert_column(Monthalize('orders'), "`account_name` varchar(25) DEFAULT NULL");




	$sql[0] = "
		CREATE TABLE `".Monthalize('order_items')."` (
		  `order_item_id` int(11) NOT NULL,
		  `order_id` int(11) NOT NULL DEFAULT '0',
		  `quantity_ordered` float NOT NULL DEFAULT '0',
		  `quantity_delivered` float NOT NULL DEFAULT '0',
		  `price` float NOT NULL DEFAULT '0',
		  `bprice` float NOT NULL DEFAULT '0',
		  `is_temporary` char(1) NOT NULL DEFAULT '',
		  `adjusted` float NOT NULL DEFAULT '0',
		  `product_id` int(11) NOT NULL DEFAULT '0'
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `".Monthalize('order_items')."`  ADD PRIMARY KEY (`order_item_id`);";

	$sql[2] = "ALTER TABLE `".Monthalize('order_items')."`  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;";

	echo insert_table(Monthalize('order_items'), $sql);

	echo insert_column(Monthalize('order_items'), "`order_item_id` int(11) NOT NULL");
	echo insert_column(Monthalize('order_items'), "`order_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('order_items'), "`quantity_ordered` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('order_items'), "`quantity_delivered` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('order_items'), "`price` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('order_items'), "`bprice` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('order_items'), "`is_temporary` char(1) NOT NULL DEFAULT ''");
	echo insert_column(Monthalize('order_items'), "`adjusted` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('order_items'), "`product_id` int(11) NOT NULL DEFAULT '0'");




	$sql[0] = "
		CREATE TABLE `".Yearalize('purchase_items')."` (
		  `purchase_item_id` int(11) NOT NULL,
		  `purchase_order_id` int(11) NOT NULL DEFAULT '0',
		  `product_id` int(11) NOT NULL DEFAULT '0',
		  `is_received` char(1) NOT NULL DEFAULT 'N',
		  `buying_price` float NOT NULL DEFAULT '0',
		  `selling_price` float NOT NULL DEFAULT '0',
		  `quantity_ordered` float NOT NULL DEFAULT '0',
		  `quantity_received` float NOT NULL DEFAULT '0',
		  `quantity_bonus` float NOT NULL DEFAULT '0',
		  `batch_id` int(11) NOT NULL DEFAULT '0',
		  `supplier_id` int(11) NOT NULL DEFAULT '0',
		  `tax_id` int(11) NOT NULL DEFAULT '0'
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `".Yearalize('purchase_items')."` ADD PRIMARY KEY (`purchase_item_id`);";

	$sql[2] = "ALTER TABLE `".Yearalize('purchase_items')."` MODIFY `purchase_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;";

	echo insert_table(Yearalize('purchase_items'), $sql);

	echo insert_column(Yearalize('purchase_items'), "`purchase_item_id` int(11) NOT NULL");
	echo insert_column(Yearalize('purchase_items'), "`purchase_order_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('purchase_items'), "`product_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('purchase_items'), "`is_received` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column(Yearalize('purchase_items'), "`buying_price` float NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('purchase_items'), "`selling_price` float NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('purchase_items'), "`quantity_ordered` float NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('purchase_items'), "`quantity_received` float NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('purchase_items'), "`quantity_bonus` float NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('purchase_items'), "`batch_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('purchase_items'), "`supplier_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('purchase_items'), "`tax_id` int(11) NOT NULL DEFAULT '0'");




	$sql[0] = "
		CREATE TABLE `".Yearalize('purchase_order')."` (
		  `purchase_order_id` int(11) NOT NULL,
		  `comment` mediumtext NOT NULL,
		  `purchase_status` smallint(6) NOT NULL DEFAULT '0',
		  `date_created` datetime NOT NULL,
		  `date_received` datetime NOT NULL,
		  `purchase_order_ref` varchar(20) NOT NULL DEFAULT '0',
		  `user_id` smallint(6) NOT NULL DEFAULT '0',
		  `date_expected_delivery` datetime NOT NULL,
		  `supplier_id` int(11) NOT NULL DEFAULT '0',
		  `assigned_to_user_id` int(11) NOT NULL DEFAULT '0',
		  `storeroom_id` int(11) NOT NULL DEFAULT '0',
		  `single_supplier` char(1) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL DEFAULT 'Y',
		  `discount` float NOT NULL DEFAULT '0',
		  `invoice_number` varchar(64) DEFAULT NULL,
		  `invoice_date` date DEFAULT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `".Yearalize('purchase_order')."` ADD PRIMARY KEY (`purchase_order_id`);";

	$sql[2] = "ALTER TABLE `".Yearalize('purchase_order')."` MODIFY `purchase_order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;";

	echo insert_table(Yearalize('purchase_order'), $sql);

	echo insert_column(Yearalize('purchase_order'), "`purchase_order_id` int(11) NOT NULL");
	echo insert_column(Yearalize('purchase_order'), "`comment` mediumtext NOT NULL");
	echo insert_column(Yearalize('purchase_order'), "`purchase_status` smallint(6) NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('purchase_order'), "`date_created` datetime NOT NULL");
	echo insert_column(Yearalize('purchase_order'), "`date_received` datetime NOT NULL");
	echo insert_column(Yearalize('purchase_order'), "`purchase_order_ref` varchar(20) NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('purchase_order'), "`user_id` smallint(6) NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('purchase_order'), "`date_expected_delivery` datetime NOT NULL");
	echo insert_column(Yearalize('purchase_order'), "`supplier_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('purchase_order'), "`assigned_to_user_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('purchase_order'), "`storeroom_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('purchase_order'), "`single_supplier` char(1) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL DEFAULT 'Y'");
	echo insert_column(Yearalize('purchase_order'), "`discount` float NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('purchase_order'), "`invoice_number` varchar(64) DEFAULT NULL");
	echo insert_column(Yearalize('purchase_order'), "`invoice_date` date DEFAULT NULL");




	$sql[0] = "
		CREATE TABLE `recycled_bill_numbers` (
		  `bill_type` int(11) NOT NULL,
		  `bill_number` int(11) NOT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;";

	echo insert_table('recycled_bill_numbers', $sql);

	echo insert_column('recycled_bill_numbers', "`bill_type` int(11) NOT NULL");
	echo insert_column('recycled_bill_numbers', "`bill_number` int(11) NOT NULL");




	$sql[0] = "
		CREATE TABLE `salespersons` (
		  `id` int(11) NOT NULL,
		  `address` varchar(128) NOT NULL,
		  `first` varchar(32) NOT NULL,
		  `last` varchar(32) NOT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

	$sql[1] = "INSERT INTO `salespersons` (`id`, `address`, `first`, `last`) VALUES
		(1, 'Address', 'Name', 'Family');";

	$sql[2] = "ALTER TABLE `salespersons` ADD PRIMARY KEY (`id`);";

	$sql[3] = "ALTER TABLE `salespersons` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;";

	echo insert_table('salespersons', $sql);

	echo insert_column('salespersons', "`id` int(11) NOT NULL");
	echo insert_column('salespersons', "`address` varchar(128) NOT NULL");
	echo insert_column('salespersons', "`first` varchar(32) NOT NULL");
	echo insert_column('salespersons', "`last` varchar(32) NOT NULL");




	$sql[0] = "
		CREATE TABLE `state_codes` (
		  `id` int(11) NOT NULL,
		  `state` varchar(32) NOT NULL,
		  `code` int(11) NOT NULL,
		  `abbr` varchar(2) NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "
		INSERT INTO `state_codes` (`id`, `state`, `code`, `abbr`) VALUES
		(1, 'Andaman and Nicobar Islands', 35, 'AN'),
		(2, 'Andhra Pradesh', 28, 'AP'),
		(3, 'Andhra Pradesh (New)', 37, 'AD'),
		(4, 'Arunachal Pradesh', 12, 'AR'),
		(5, 'Assam', 18, 'AS'),
		(6, 'Bihar', 10, 'BH'),
		(7, 'Chandigarh', 4, 'CH'),
		(8, 'Chattisgarh', 22, 'CT'),
		(9, 'Dadra and Nagar Haveli', 26, 'DN'),
		(10, 'Daman and Diu', 25, 'DD'),
		(11, 'Delhi', 7, 'DL'),
		(12, 'Goa', 30, 'GA'),
		(13, 'Gujarat', 24, 'GJ'),
		(14, 'Haryana', 6, 'HR'),
		(15, 'Himachal Pradesh', 2, 'HP'),
		(16, 'Jammu and Kashmir', 1, 'JK'),
		(17, 'Jharkhand', 20, 'JH'),
		(18, 'Karnataka', 29, 'KA'),
		(19, 'Kerala', 32, 'KL'),
		(20, 'Lakshadweep Islands', 31, 'LD'),
		(21, 'Madhya Pradesh', 23, 'MP'),
		(22, 'Maharashtra', 27, 'MH'),
		(23, 'Manipur', 14, 'MN'),
		(24, 'Meghalaya', 17, 'ME'),
		(25, 'Mizoram', 15, 'MI'),
		(26, 'Nagaland', 13, 'NL'),
		(27, 'Odisha', 21, 'OR'),
		(28, 'Pondicherry', 34, 'PY'),
		(29, 'Punjab', 3, 'PB'),
		(30, 'Rajasthan', 8, 'RJ'),
		(31, 'Sikkim', 11, 'SK'),
		(32, 'Tamil Nadu', 33, 'TN'),
		(33, 'Telangana', 36, 'TS'),
		(34, 'Tripura', 16, 'TR'),
		(35, 'Uttar Pradesh', 9, 'UP'),
		(36, 'Uttarakhand', 5, 'UT'),
		(37, 'West Bengal', 19, 'WB');";

	$sql[2] = "ALTER TABLE `state_codes` ADD PRIMARY KEY (`id`);";
	$sql[3] = "ALTER TABLE `state_codes` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;";

	echo insert_table('state_codes', $sql);

	echo insert_column('state_codes', "`id` int(11) NOT NULL");
	echo insert_column('state_codes', "`state` varchar(32) NOT NULL");
	echo insert_column('state_codes', "`code` int(11) NOT NULL");
	echo insert_column('state_codes', "`abbr` varchar(2) NOT NULL");




	$sql[0] = "
		CREATE TABLE `".Yearalize('stock_balance')."` (
		  `balance_id` bigint(20) NOT NULL,
		  `stock_opening_balance` float NOT NULL DEFAULT '0',
		  `stock_closing_balance` float NOT NULL DEFAULT '0',
		  `balance_month` int(11) NOT NULL DEFAULT '0',
		  `balance_year` int(11) NOT NULL DEFAULT '0',
		  `stock_in` float NOT NULL DEFAULT '0',
		  `stock_out` float NOT NULL DEFAULT '0',
		  `stock_sold` float NOT NULL DEFAULT '0',
		  `stock_damaged` float NOT NULL DEFAULT '0',
		  `stock_wasted` float NOT NULL DEFAULT '0',
		  `stock_returned` float NOT NULL DEFAULT '0',
		  `stock_received` float NOT NULL DEFAULT '0',
		  `stock_mismatch_addition` float NOT NULL DEFAULT '0',
		  `stock_mismatch_deduction` float NOT NULL DEFAULT '0',
		  `stock_cancelled` float NOT NULL DEFAULT '0',
		  `product_id` int(11) NOT NULL DEFAULT '0',
		  `storeroom_id` int(11) NOT NULL DEFAULT '0'
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `".Yearalize('stock_balance')."` ADD PRIMARY KEY (`balance_id`),  ADD UNIQUE KEY `productid_storeroomid_monthyear` (`product_id`,`storeroom_id`,`balance_month`,`balance_year`) USING BTREE, ADD KEY `productid_storeroomid_month` (`product_id`,`storeroom_id`,`balance_month`);";

	$sql[2] = "ALTER TABLE `".Yearalize('stock_balance')."` MODIFY `balance_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;";

	echo insert_table(Yearalize('stock_balance'), $sql);

	echo insert_column(Yearalize('stock_balance'), "`balance_id` bigint(20) NOT NULL");
	echo insert_column(Yearalize('stock_balance'), "`stock_opening_balance` float NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('stock_balance'), "`stock_closing_balance` float NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('stock_balance'), "`balance_month` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('stock_balance'), "`balance_year` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('stock_balance'), "`stock_in` float NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('stock_balance'), "`stock_out` float NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('stock_balance'), "`stock_sold` float NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('stock_balance'), "`stock_damaged` float NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('stock_balance'), "`stock_wasted` float NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('stock_balance'), "`stock_returned` float NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('stock_balance'), "`stock_received` float NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('stock_balance'), "`stock_mismatch_addition` float NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('stock_balance'), "`stock_mismatch_deduction` float NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('stock_balance'), "`stock_cancelled` float NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('stock_balance'), "`product_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('stock_balance'), "`storeroom_id` int(11) NOT NULL DEFAULT '0'");




	$sql[0] = "
		CREATE TABLE `".Yearalize('stock_batch')."` (
		  `batch_id` int(11) NOT NULL,
		  `batch_code` varchar(16) NOT NULL DEFAULT '',
		  `buying_price` float NOT NULL DEFAULT '0',
		  `selling_price` float NOT NULL DEFAULT '0',
		  `date_created` datetime NOT NULL,
		  `opening_balance` float NOT NULL DEFAULT '0',
		  `date_manufacture` datetime NOT NULL,
		  `date_expiry` datetime NOT NULL,
		  `is_active` char(1) NOT NULL DEFAULT '',
		  `status` char(1) NOT NULL DEFAULT 'P',
		  `user_id` int(11) NOT NULL DEFAULT '0',
		  `buyer_id` int(11) NOT NULL DEFAULT '0',
		  `supplier_id` int(11) NOT NULL DEFAULT '0',
		  `product_id` int(11) NOT NULL DEFAULT '0',
		  `deleted` char(1) NOT NULL DEFAULT 'N',
		  `storeroom_id` int(11) NOT NULL DEFAULT '0',
		  `tax_id` int(11) NOT NULL DEFAULT '0'
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `".Yearalize('stock_batch')."` ADD PRIMARY KEY (`batch_id`), ADD KEY `batch_supplier_product` (`batch_id`,`supplier_id`,`product_id`), ADD KEY `product_id` (`product_id`,`storeroom_id`,`status`,`deleted`), ADD KEY `product_id_2` (`product_id`);";

	$sql[2] = "ALTER TABLE `".Yearalize('stock_batch')."` MODIFY `batch_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;";

	echo insert_table(Yearalize('stock_batch'), $sql);

	echo insert_column(Yearalize('stock_batch'), "`batch_id` int(11) NOT NULL");
	echo insert_column(Yearalize('stock_batch'), "`batch_code` varchar(16) NOT NULL DEFAULT ''");
	echo insert_column(Yearalize('stock_batch'), "`buying_price` float NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('stock_batch'), "`selling_price` float NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('stock_batch'), "`date_created` datetime NOT NULL");
	echo insert_column(Yearalize('stock_batch'), "`opening_balance` float NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('stock_batch'), "`date_manufacture` datetime NOT NULL");
	echo insert_column(Yearalize('stock_batch'), "`date_expiry` datetime NOT NULL");
	echo insert_column(Yearalize('stock_batch'), "`is_active` char(1) NOT NULL DEFAULT ''");
	echo insert_column(Yearalize('stock_batch'), "`status` char(1) NOT NULL DEFAULT 'P'");
	echo insert_column(Yearalize('stock_batch'), "`user_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('stock_batch'), "`buyer_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('stock_batch'), "`supplier_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('stock_batch'), "`product_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('stock_batch'), "`deleted` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column(Yearalize('stock_batch'), "`storeroom_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Yearalize('stock_batch'), "`tax_id` int(11) NOT NULL DEFAULT '0'");



	$sql[0] = "
		CREATE TABLE `stock_category` (
		  `category_id` int(11) NOT NULL,
		  `category_code` varchar(16) NOT NULL DEFAULT '',
		  `category_description` varchar(255) NOT NULL DEFAULT '',
		  `parent_category_id` int(11) NOT NULL DEFAULT '0',
		  `is_perishable` char(1) NOT NULL DEFAULT 'N',
		  `is_modified` char(1) NOT NULL DEFAULT 'N',
		  `hsn` varchar(50) NOT NULL,
		  `apply_tax_rule` BOOLEAN NOT NULL DEFAULT FALSE
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "INSERT INTO `stock_category` (`category_id`, `category_code`, `category_description`, `parent_category_id`, `is_perishable`, `is_modified`, `hsn`) VALUES
		(1, '100', 'Category1', 0, 'N', 'Y', 'HSN');";

	$sql[2] = "ALTER TABLE `stock_category` ADD PRIMARY KEY (`category_id`), ADD KEY `is_perishable` (`is_perishable`), ADD KEY `category_description` (`category_description`);";

	$sql[3] = "ALTER TABLE `stock_category` MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;";

	echo insert_table('stock_category', $sql);

	echo insert_column('stock_category', "`category_id` int(11) NOT NULL");
	echo insert_column('stock_category', "`category_code` varchar(16) NOT NULL DEFAULT ''");
	echo insert_column('stock_category', "`category_description` varchar(255) NOT NULL DEFAULT ''");
	echo insert_column('stock_category', "`parent_category_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('stock_category', "`is_perishable` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('stock_category', "`is_modified` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('stock_category', "`hsn` varchar(50) NOT NULL");
	echo insert_column('stock_category', "`apply_tax_rule` BOOLEAN NOT NULL DEFAULT FALSE");




	$sql[0] = "
		CREATE TABLE `stock_currency` (
		  `currency_id` int(11) NOT NULL,
		  `currency_name` varchar(64) NOT NULL DEFAULT ' ',
		  `currency_rate` float NOT NULL DEFAULT '1',
		  `is_modified` char(1) NOT NULL DEFAULT 'N'
		) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

	$sql[1] = "INSERT INTO `stock_currency` (`currency_id`, `currency_name`, `currency_rate`, `is_modified`) VALUES (1, 'INR', 0, 'N');";

	$sql[2] = "ALTER TABLE `stock_currency` ADD PRIMARY KEY (`currency_id`);";

	$sql[3] = "ALTER TABLE `stock_currency` MODIFY `currency_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;";

	echo insert_table('stock_currency', $sql);

	echo insert_column('stock_currency', "`currency_id` int(11) NOT NULL");
	echo insert_column('stock_currency', "`currency_name` varchar(64) NOT NULL DEFAULT ' '");
	echo insert_column('stock_currency', "`currency_rate` float NOT NULL DEFAULT '1'");
	echo insert_column('stock_currency', "`is_modified` char(1) NOT NULL DEFAULT 'N'");




	$sql[0] = "
		CREATE TABLE `stock_measurement_unit` (
		  `measurement_unit_id` int(11) NOT NULL,
		  `measurement_unit` varchar(16) NOT NULL DEFAULT '',
		  `is_decimal` char(1) NOT NULL DEFAULT '',
		  `is_modified` char(1) NOT NULL DEFAULT 'N'
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "INSERT INTO `stock_measurement_unit` (`measurement_unit_id`, `measurement_unit`, `is_decimal`, `is_modified`) VALUES (1, 'No', 'N', 'Y');";

	$sql[2] = "ALTER TABLE `stock_measurement_unit` ADD PRIMARY KEY (`measurement_unit_id`);";

	$sql[3] = "ALTER TABLE `stock_measurement_unit` MODIFY `measurement_unit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;";

	echo insert_table('stock_measurement_unit', $sql);

	echo insert_column('stock_measurement_unit', "`measurement_unit_id` int(11) NOT NULL");
	echo insert_column('stock_measurement_unit', "`measurement_unit` varchar(16) NOT NULL DEFAULT ''");
	echo insert_column('stock_measurement_unit', "`is_decimal` char(1) NOT NULL DEFAULT ''");
	echo insert_column('stock_measurement_unit', "`is_modified` char(1) NOT NULL DEFAULT 'N'");




	$sql[0] = "
		CREATE TABLE `stock_product` (
			`product_id` int(11) NOT NULL,
			`product_code` varchar(16) NOT NULL DEFAULT '',
			`product_bar_code` varchar(13) NOT NULL DEFAULT ' ',
			`product_description` varchar(255) NOT NULL DEFAULT '',
			`product_abbreviation` varchar(5) NOT NULL DEFAULT '',
			`is_available` char(1) NOT NULL DEFAULT 'Y',
			`minimum_qty` float NOT NULL DEFAULT '0',
			`is_minimum_consolidated` char(1) NOT NULL DEFAULT 'N',
			`is_av_product` char(1) NOT NULL DEFAULT 'N',
			`tax_id` int(11) NOT NULL DEFAULT '0',
			`is_perishable` char(1) NOT NULL DEFAULT 'N',
			`shelf_life` smallint(6) NOT NULL DEFAULT '0',
			`measurement_unit_id` int(11) NOT NULL DEFAULT '0',
			`category_id` int(11) NOT NULL DEFAULT '0',
			`deleted` char(1) NOT NULL DEFAULT 'N',
			`supplier_id` int(11) NOT NULL DEFAULT '0',
			`supplier2_id` int(11) NOT NULL DEFAULT '0',
			`supplier3_id` int(11) NOT NULL DEFAULT '0',
			`quantity_per_box` int(11) NOT NULL DEFAULT '1',
			`margin_percent` float NOT NULL DEFAULT '0',
			`adjusted_stock` float NOT NULL DEFAULT '0',
			`list_in_purchase` char(1) NOT NULL DEFAULT 'Y',
			`purchase_round` int(11) NOT NULL DEFAULT '0',
			`list_in_order_sheet` char(1) NOT NULL DEFAULT 'N',
			`product_weight` float NOT NULL DEFAULT '0',
			`mrp` float NOT NULL DEFAULT '0',
			`list_in_price_list` char(1) NOT NULL DEFAULT 'Y',
			`bulk_unit_id` int(11) NOT NULL DEFAULT '0',
			`currency_id` int(11) NOT NULL DEFAULT '0',
			`language_id` int(11) NOT NULL DEFAULT '0',
			`publisher_id` int(11) NOT NULL DEFAULT '0',
			`author_id` int(11) NOT NULL DEFAULT '0',
			`image_filename` varchar(128) NOT NULL DEFAULT '',
			`img2` varchar(128) NOT NULL DEFAULT '',
			`img3` varchar(128) NOT NULL DEFAULT '',
			`img4` varchar(128) NOT NULL DEFAULT '',
			`img5` varchar(128) NOT NULL DEFAULT '',
			`img6` varchar(128) NOT NULL DEFAULT '',
			`is_reseller_visible` char(1) NOT NULL DEFAULT 'Y',
			`is_modified` char(1) NOT NULL DEFAULT 'N',
			`reseller_client_id` int(11) NOT NULL DEFAULT '0',
			`is_image_modified` char(1) NOT NULL DEFAULT 'N',
			`is_active` char(1) NOT NULL DEFAULT 'Y',
			`is_reseller_public` char(1) NOT NULL DEFAULT 'Y',
			`web_description` text,
			`web_dimensions` text,
			`web_weight` varchar(32) DEFAULT NULL,
			`web_title` varchar(256) DEFAULT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;";


	$sql[1] = "ALTER TABLE `stock_product` ADD PRIMARY KEY (`product_id`), ADD KEY `product_code` (`product_code`), ADD KEY `list_in_purchase` (`list_in_purchase`), ADD KEY `product_description` (`product_description`);";

	$sql[2] = "ALTER TABLE `stock_product` MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;";

	echo insert_table('stock_product', $sql);

	echo insert_column('stock_product', "`product_id` int(11) NOT NULL");
	echo insert_column('stock_product', "`product_code` varchar(16) NOT NULL DEFAULT ''");
	echo insert_column('stock_product', "`product_bar_code` varchar(13) NOT NULL DEFAULT ' '");
	echo insert_column('stock_product', "`product_description` varchar(255) NOT NULL DEFAULT ''");
	echo insert_column('stock_product', "`product_abbreviation` varchar(5) NOT NULL DEFAULT ''");
	echo insert_column('stock_product', "`is_available` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('stock_product', "`minimum_qty` float NOT NULL DEFAULT '0'");
	echo insert_column('stock_product', "`is_minimum_consolidated` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('stock_product', "`is_av_product` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('stock_product', "`tax_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('stock_product', "`is_perishable` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('stock_product', "`shelf_life` smallint(6) NOT NULL DEFAULT '0'");
	echo insert_column('stock_product', "`measurement_unit_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('stock_product', "`category_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('stock_product', "`deleted` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('stock_product', "`supplier_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('stock_product', "`supplier2_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('stock_product', "`supplier3_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('stock_product', "`quantity_per_box` int(11) NOT NULL DEFAULT '1'");
	echo insert_column('stock_product', "`margin_percent` float NOT NULL DEFAULT '0'");
	echo insert_column('stock_product', "`adjusted_stock` float NOT NULL DEFAULT '0'");
	echo insert_column('stock_product', "`list_in_purchase` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('stock_product', "`purchase_round` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('stock_product', "`list_in_order_sheet` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('stock_product', "`product_weight` float NOT NULL DEFAULT '0'");
	echo insert_column('stock_product', "`mrp` float NOT NULL DEFAULT '0'");
	echo insert_column('stock_product', "`list_in_price_list` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('stock_product', "`bulk_unit_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('stock_product', "`currency_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('stock_product', "`language_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('stock_product', "`publisher_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('stock_product', "`author_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('stock_product', "`image_filename` varchar(128) NOT NULL DEFAULT ' '");
	echo insert_column('stock_product', "`is_reseller_visible` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('stock_product', "`is_modified` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('stock_product', "`reseller_client_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('stock_product', "`is_image_modified` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('stock_product', "`is_active` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('stock_product', "`is_reseller_public` char(1) NOT NULL DEFAULT 'Y'");




	$sql[0] = "
		CREATE TABLE `stock_product_supplier` (
		  `product_supplier_id` bigint(20) NOT NULL,
		  `supplier_id` int(11) NOT NULL DEFAULT '0',
		  `product_id` int(11) NOT NULL DEFAULT '0'
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `stock_product_supplier` ADD PRIMARY KEY (`product_supplier_id`);";

	$sql[2] = "ALTER TABLE `stock_product_supplier` MODIFY `product_supplier_id` bigint(20) NOT NULL AUTO_INCREMENT;";

	echo insert_table('stock_product_supplier', $sql);

	echo insert_column('stock_product_supplier', "`product_supplier_id` bigint(20) NOT NULL");
	echo insert_column('stock_product_supplier', "`supplier_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('stock_product_supplier', "`product_id` int(11) NOT NULL DEFAULT '0'");





	$sql[0] = "
		CREATE TABLE `stock_product_type` (
		  `stock_product_type_id` int(11) NOT NULL,
		  `product_id` int(11) NOT NULL DEFAULT '0',
		  `stock_type_id` int(11) NOT NULL DEFAULT '0',
		  `stock_type_description_id` int(11) NOT NULL DEFAULT '0',
		  `is_modified` char(1) NOT NULL DEFAULT 'N'
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `stock_product_type` ADD PRIMARY KEY (`stock_product_type_id`), ADD KEY `product_id` (`product_id`), ADD KEY `stock_type_id` (`stock_type_id`), ADD KEY `stock_type_description_id` (`stock_type_description_id`);";

	$sql[2] = "ALTER TABLE `stock_product_type` MODIFY `stock_product_type_id` int(11) NOT NULL AUTO_INCREMENT;";

	echo insert_table('stock_product_type', $sql);

	echo insert_column('stock_product_type', "`stock_product_type_id` int(11) NOT NULL");
	echo insert_column('stock_product_type', "`product_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('stock_product_type', "`stock_type_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('stock_product_type', "`stock_type_description_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('stock_product_type', "`is_modified` char(1) NOT NULL DEFAULT 'N'");




	$sql[0] = "
		CREATE TABLE `".Monthalize('stock_rts')."` (
		  `stock_rts_id` int(11) NOT NULL,
		  `storeroom_id` int(11) NOT NULL DEFAULT '0',
		  `bill_number` int(11) NOT NULL DEFAULT '0',
		  `date_created` datetime DEFAULT NULL,
		  `total_amount` float NOT NULL DEFAULT '0',
		  `discount` smallint(6) NOT NULL DEFAULT '0',
		  `bill_status` tinyint(4) NOT NULL DEFAULT '0',
		  `description` varchar(150) NOT NULL DEFAULT '',
		  `user_id` int(11) NOT NULL DEFAULT '0',
		  `supplier_id` int(11) NOT NULL DEFAULT '0',
		  `module_id` int(11) NOT NULL DEFAULT '0',
		  `invoice_number` varchar(64) DEFAULT NULL,
		  `invoice_date` varchar(12) DEFAULT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `".Monthalize('stock_rts')."` ADD PRIMARY KEY (`stock_rts_id`), ADD KEY `storeroom_billnumber_idx` (`storeroom_id`,`bill_number`);";

	$sql[2] = "ALTER TABLE `".Monthalize('stock_rts')."` MODIFY `stock_rts_id` int(11) NOT NULL AUTO_INCREMENT;";

	echo insert_table(Monthalize('stock_rts'), $sql);

	echo insert_column(Monthalize('stock_rts'), "`stock_rts_id` int(11) NOT NULL");
	echo insert_column(Monthalize('stock_rts'), "`storeroom_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_rts'), "`bill_number` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_rts'), "`date_created` datetime DEFAULT NULL");
	echo insert_column(Monthalize('stock_rts'), "`total_amount` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_rts'), "`discount` smallint(6) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_rts'), "`bill_status` tinyint(4) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_rts'), "`description` varchar(150) NOT NULL DEFAULT ''");
	echo insert_column(Monthalize('stock_rts'), "`user_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_rts'), "`supplier_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_rts'), "`module_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_rts'), "`invoice_number` varchar(64) DEFAULT NULL");
	echo insert_column(Monthalize('stock_rts'), "`invoice_date` varchar(12) DEFAULT NULL");




	$sql[0] = "
		CREATE TABLE `".Monthalize('stock_rts_items')."` (
		  `rts_item_id` int(11) NOT NULL,
		  `quantity` float NOT NULL DEFAULT '0',
		  `price` float NOT NULL DEFAULT '0',
		  `bprice` float NOT NULL DEFAULT '0',
		  `product_id` int(11) NOT NULL DEFAULT '0',
		  `rts_id` int(11) NOT NULL DEFAULT '0',
		  `batch_id` int(11) NOT NULL DEFAULT '0',
		  `tax_id` int(11) DEFAULT '0'
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `".Monthalize('stock_rts_items')."` ADD PRIMARY KEY (`rts_item_id`);";

	$sql[2] = "ALTER TABLE `".Monthalize('stock_rts_items')."` MODIFY `rts_item_id` int(11) NOT NULL AUTO_INCREMENT;";

	echo insert_table(Monthalize('stock_rts_items'), $sql);

	echo insert_column(Monthalize('stock_rts_items'), "`rts_item_id` int(11) NOT NULL");
	echo insert_column(Monthalize('stock_rts_items'), "`quantity` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_rts_items'), "`price` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_rts_items'), "`bprice` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_rts_items'), "`product_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_rts_items'), "`rts_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_rts_items'), "`batch_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_rts_items'), "`tax_id` int(11) DEFAULT '0'");





	$sql[0] = "
		CREATE TABLE `stock_shelf` (
		  `shelf_id` int(11) NOT NULL,
		  `storeroom_id` int(11) NOT NULL DEFAULT '0',
		  `description` varchar(255) NOT NULL DEFAULT ''
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `stock_shelf` ADD PRIMARY KEY (`shelf_id`);";

	$sql[2] = "ALTER TABLE `stock_shelf` MODIFY `shelf_id` int(11) NOT NULL AUTO_INCREMENT;";

	echo insert_table('stock_shelf', $sql);

	echo insert_column('stock_shelf', "`shelf_id` int(11) NOT NULL");
	echo insert_column('stock_shelf', "`storeroom_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('stock_shelf', "`description` varchar(255) NOT NULL DEFAULT ''");




	$sql[0] = "
		CREATE TABLE `stock_storeroom` (
		  `storeroom_id` int(11) NOT NULL,
		  `storeroom_code` varchar(16) NOT NULL DEFAULT '',
		  `description` varchar(255) NOT NULL DEFAULT '',
		  `location` varchar(255) NOT NULL DEFAULT '',
		  `is_taxed` char(1) NOT NULL DEFAULT 'Y',
		  `bill_description` varchar(40) NOT NULL DEFAULT 'BN:%s- %d',
		  `bill_order_description` varchar(20) NOT NULL DEFAULT 'OB:%s- %d',
		  `bill_credit_account` varchar(6) NOT NULL DEFAULT '',
		  `is_cash_taxed` char(1) NOT NULL DEFAULT 'Y',
		  `is_account_taxed` char(1) NOT NULL DEFAULT 'Y',
		  `can_bill_cash` char(1) NOT NULL DEFAULT 'Y',
		  `can_bill_fs_account` char(1) NOT NULL DEFAULT 'Y',
		  `can_bill_pt_account` char(1) NOT NULL DEFAULT 'Y',
		  `can_bill_creditcard` char(1) NOT NULL DEFAULT 'N',
		  `can_bill_aurocard` char(1) NOT NULL DEFAULT 'N',
		  `default_tax_id` int(11) NOT NULL DEFAULT '0',
		  `default_supplier_id` int(11) NOT NULL DEFAULT '0',
		  `default_category_id` int(11) NOT NULL DEFAULT '0',
		  `default_currency_id` int(11) NOT NULL DEFAULT '1',
		  `default_unit_id` int(11) NOT NULL DEFAULT '1',
		  `enabled_table_billing` char(1) NOT NULL DEFAULT 'N'
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "INSERT INTO `stock_storeroom` (`storeroom_id`, `storeroom_code`, `description`, `location`, `is_taxed`, `bill_description`, `bill_order_description`, `bill_credit_account`, `is_cash_taxed`, `is_account_taxed`, `can_bill_cash`, `can_bill_fs_account`, `can_bill_pt_account`, `can_bill_creditcard`, `can_bill_aurocard`, `default_tax_id`, `default_supplier_id`, `default_category_id`, `default_currency_id`, `default_unit_id`, `enabled_table_billing`) VALUES (1, 'S', 'Storeroom', '', 'Y', 'BN:%s- %d', 'OB:%s- %d', '', 'Y', 'N', 'Y', 'Y', 'N', 'Y', 'Y', 1, 1, 1, 1, 1, 'Y');";

	$sql[2] = "ALTER TABLE `stock_storeroom` ADD PRIMARY KEY (`storeroom_id`);";

	$sql[3] = "ALTER TABLE `stock_storeroom` MODIFY `storeroom_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;";

	echo insert_table('stock_storeroom', $sql);

	echo insert_column('stock_storeroom', "`storeroom_id` int(11) NOT NULL");
	echo insert_column('stock_storeroom', "`storeroom_code` varchar(16) NOT NULL DEFAULT ''");
	echo insert_column('stock_storeroom', "`description` varchar(255) NOT NULL DEFAULT ''");
	echo insert_column('stock_storeroom', "`location` varchar(255) NOT NULL DEFAULT ''");
	echo insert_column('stock_storeroom', "`is_taxed` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('stock_storeroom', "`bill_description` varchar(40) NOT NULL DEFAULT 'BN:%s- %d'");
	echo insert_column('stock_storeroom', "`bill_order_description` varchar(20) NOT NULL DEFAULT 'OB:%s- %d'");
	echo insert_column('stock_storeroom', "`bill_credit_account` varchar(6) NOT NULL DEFAULT ''");
	echo insert_column('stock_storeroom', "`is_cash_taxed` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('stock_storeroom', "`is_account_taxed` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('stock_storeroom', "`can_bill_cash` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('stock_storeroom', "`can_bill_fs_account` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('stock_storeroom', "`can_bill_pt_account` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('stock_storeroom', "`can_bill_creditcard` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('stock_storeroom', "`can_bill_aurocard` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('stock_storeroom', "`default_tax_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('stock_storeroom', "`default_supplier_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('stock_storeroom', "`default_category_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('stock_storeroom', "`default_currency_id` int(11) NOT NULL DEFAULT '1'");
	echo insert_column('stock_storeroom', "`default_unit_id` int(11) NOT NULL DEFAULT '1'");
	echo insert_column('stock_storeroom', "`enabled_table_billing` char(1) NOT NULL DEFAULT 'N'");



	$sql[0] = "
		CREATE TABLE `".Monthalize('stock_storeroom_batch')."` (
		  `stock_storeroom_batch_id` int(11) NOT NULL,
		  `stock_available` float NOT NULL DEFAULT '0',
		  `stock_reserved` float NOT NULL DEFAULT '0',
		  `bill_reserved` float NOT NULL DEFAULT '0',
		  `stock_ordered` float NOT NULL DEFAULT '0',
		  `shelf_id` int(11) NOT NULL DEFAULT '0',
		  `batch_id` int(11) NOT NULL DEFAULT '0',
		  `storeroom_id` int(11) NOT NULL DEFAULT '0',
		  `product_id` int(11) NOT NULL DEFAULT '0',
		  `is_active` char(1) NOT NULL DEFAULT 'Y',
		  `debug` varchar(10) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL DEFAULT ''
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `".Monthalize('stock_storeroom_batch')."` ADD PRIMARY KEY (`stock_storeroom_batch_id`), ADD KEY `batch_supplier_product` (`batch_id`,`product_id`), ADD KEY `product_id` (`product_id`,`batch_id`,`storeroom_id`,`is_active`), ADD KEY `is_active` (`is_active`);";

	$sql[2] = "ALTER TABLE `".Monthalize('stock_storeroom_batch')."` MODIFY `stock_storeroom_batch_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;";

	echo insert_table(Monthalize('stock_storeroom_batch'), $sql);

	echo insert_column(Monthalize('stock_storeroom_batch'), "`stock_storeroom_batch_id` int(11) NOT NULL");
	echo insert_column(Monthalize('stock_storeroom_batch'), "`stock_available` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_storeroom_batch'), "`stock_reserved` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_storeroom_batch'), "`bill_reserved` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_storeroom_batch'), "`stock_ordered` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_storeroom_batch'), "`shelf_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_storeroom_batch'), "`batch_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_storeroom_batch'), "`storeroom_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_storeroom_batch'), "`product_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_storeroom_batch'), "`is_active` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column(Monthalize('stock_storeroom_batch'), "`debug` varchar(10) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL DEFAULT ''");




	$sql[0] = "
		CREATE TABLE `".Monthalize('stock_storeroom_product')."` (
		  `storeroom_product_id` int(11) NOT NULL,
		  `product_id` int(11) NOT NULL DEFAULT '0',
		  `storeroom_id` int(11) NOT NULL DEFAULT '0',
		  `stock_current` float NOT NULL DEFAULT '0',
		  `stock_reserved` float NOT NULL DEFAULT '0',
		  `stock_ordered` float NOT NULL DEFAULT '0',
		  `stock_adjusted` float NOT NULL DEFAULT '0',
		  `stock_minimum` float NOT NULL DEFAULT '0',
		  `buying_price` float NOT NULL DEFAULT '0',
		  `sale_price` float NOT NULL DEFAULT '0',
		  `point_price` float NOT NULL DEFAULT '0',
		  `use_batch_price` char(1) NOT NULL DEFAULT 'N',
		  `discount_qty` float NOT NULL DEFAULT '0',
		  `discount_percent` float NOT NULL DEFAULT '0'
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `".Monthalize('stock_storeroom_product')."` ADD PRIMARY KEY (`storeroom_product_id`), ADD KEY `productid_storeroomid` (`product_id`,`storeroom_id`), ADD KEY `product_id` (`product_id`);";

	$sql[2] = "ALTER TABLE `".Monthalize('stock_storeroom_product')."` MODIFY `storeroom_product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;";

	echo insert_table(Monthalize('stock_storeroom_product'), $sql);

	echo insert_column(Monthalize('stock_storeroom_product'), "`storeroom_product_id` int(11) NOT NULL");
	echo insert_column(Monthalize('stock_storeroom_product'), "`product_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_storeroom_product'), "`storeroom_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_storeroom_product'), "`stock_current` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_storeroom_product'), "`stock_reserved` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_storeroom_product'), "`stock_ordered` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_storeroom_product'), "`stock_adjusted` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_storeroom_product'), "`stock_minimum` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_storeroom_product'), "`buying_price` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_storeroom_product'), "`sale_price` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_storeroom_product'), "`point_price` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_storeroom_product'), "`use_batch_price` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column(Monthalize('stock_storeroom_product'), "`discount_qty` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_storeroom_product'), "`discount_percent` float NOT NULL DEFAULT '0'");




	$sql[0] = "
		CREATE TABLE `stock_supplier` (
		  `supplier_id` int(11) NOT NULL,
		  `supplier_code` varchar(16) NOT NULL DEFAULT '',
		  `supplier_name` varchar(128) NOT NULL DEFAULT '',
		  `contact_person` varchar(128) NOT NULL DEFAULT '',
		  `supplier_address` varchar(128) NOT NULL DEFAULT '',
		  `supplier_city` varchar(64) NOT NULL DEFAULT '',
		  `supplier_state` varchar(20) NOT NULL DEFAULT '',
		  `supplier_phone` varchar(12) NOT NULL DEFAULT '',
		  `supplier_cell` varchar(12) NOT NULL DEFAULT '',
		  `is_supplier_delivering` char(1) NOT NULL DEFAULT 'Y',
		  `commission_percent` float NOT NULL DEFAULT '0',
		  `commission_percent_2` float NOT NULL DEFAULT '0',
		  `commission_percent_3` float NOT NULL DEFAULT '0',
		  `supplier_type` char(1) NOT NULL DEFAULT '',
		  `supplier_abbreviation` varchar(8) NOT NULL DEFAULT '',
		  `supplier_zip` varchar(6) NOT NULL DEFAULT '',
		  `supplier_email` varchar(128) NOT NULL DEFAULT '',
		  `supplier_discount` float NOT NULL DEFAULT '0',
		  `trust` varchar(50) NOT NULL DEFAULT ' ',
		  `supplier_TIN` varchar(30) NOT NULL DEFAULT ' ',
		  `supplier_CST` varchar(30) NOT NULL DEFAULT ' ',
		  `is_active` char(1) NOT NULL DEFAULT 'Y',
		  `is_other_state` char(1) NOT NULL DEFAULT 'N',
		  `account_number` varchar(64) DEFAULT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "
		INSERT INTO `stock_supplier` (`supplier_id`, `supplier_code`, `supplier_name`, `contact_person`, `supplier_address`, `supplier_city`, `supplier_state`, `supplier_phone`, `supplier_cell`, `is_supplier_delivering`, `commission_percent`, `commission_percent_2`, `commission_percent_3`, `supplier_type`, `supplier_abbreviation`, `supplier_zip`, `supplier_email`, `supplier_discount`, `trust`, `supplier_TIN`, `supplier_CST`, `is_active`, `is_other_state`, `account_number`) VALUES
		(1, 'S', 'Supplier1', '', '', 'Auroville', 'TN', '', '', 'N', 10, 0, 0, '', 'S', '', '', 0, '', '', '', 'Y', 'N', '');";

	$sql[2] = "ALTER TABLE `stock_supplier` ADD PRIMARY KEY (`supplier_id`), ADD KEY `supplier_code` (`supplier_code`);";

	$sql[3] = "ALTER TABLE `stock_supplier` MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;";

	echo insert_table('stock_supplier', $sql);

	echo insert_column('stock_supplier', "`supplier_id` int(11) NOT NULL");
	echo insert_column('stock_supplier', "`supplier_code` varchar(16) NOT NULL DEFAULT ''");
	echo insert_column('stock_supplier', "`supplier_name` varchar(128) NOT NULL DEFAULT ''");
	echo insert_column('stock_supplier', "`contact_person` varchar(128) NOT NULL DEFAULT ''");
	echo insert_column('stock_supplier', "`supplier_address` varchar(128) NOT NULL DEFAULT ''");
	echo insert_column('stock_supplier', "`supplier_city` varchar(64) NOT NULL DEFAULT ''");
	echo insert_column('stock_supplier', "`supplier_state` varchar(20) NOT NULL DEFAULT ''");
	echo insert_column('stock_supplier', "`supplier_phone` varchar(12) NOT NULL DEFAULT ''");
	echo insert_column('stock_supplier', "`supplier_cell` varchar(12) NOT NULL DEFAULT ''");
	echo insert_column('stock_supplier', "`is_supplier_delivering` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('stock_supplier', "`commission_percent` float NOT NULL DEFAULT '0'");
	echo insert_column('stock_supplier', "`commission_percent_2` float NOT NULL DEFAULT '0'");
	echo insert_column('stock_supplier', "`commission_percent_3` float NOT NULL DEFAULT '0'");
	echo insert_column('stock_supplier', "`supplier_type` char(1) NOT NULL DEFAULT ''");
	echo insert_column('stock_supplier', "`supplier_abbreviation` varchar(8) NOT NULL DEFAULT ''");
	echo insert_column('stock_supplier', "`supplier_zip` varchar(6) NOT NULL DEFAULT ''");
	echo insert_column('stock_supplier', "`supplier_email` varchar(128) NOT NULL DEFAULT ''");
	echo insert_column('stock_supplier', "`supplier_discount` float NOT NULL DEFAULT '0'");
	echo insert_column('stock_supplier', "`trust` varchar(50) NOT NULL DEFAULT ' '");
	echo insert_column('stock_supplier', "`supplier_TIN` varchar(30) NOT NULL DEFAULT ' '");
	echo insert_column('stock_supplier', "`supplier_CST` varchar(30) NOT NULL DEFAULT ' '");
	echo insert_column('stock_supplier', "`is_active` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('stock_supplier', "`is_other_state` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('stock_supplier', "`account_number` varchar(64) DEFAULT NULL");
	echo insert_column('stock_supplier', "`gstin` VARCHAR(32) NULL AFTER `account_number`");
	echo insert_column('stock_supplier', "`supplier_discounted` BOOLEAN NOT NULL DEFAULT FALSE AFTER `gstin`");



	$sql[0] = "
		CREATE TABLE `stock_supplier_commissions` (
		  `id` int(11) NOT NULL,
		  `supplier_id` int(11) NOT NULL,
		  `month` int(11) NOT NULL,
		  `year` int(11) NOT NULL,
		  `commission_percent` float NOT NULL,
		  `commission_percent_2` float NOT NULL,
		  `commission_percent_3` float NOT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `stock_supplier_commissions` ADD PRIMARY KEY (`id`);";

	$sql[2] = "ALTER TABLE `stock_supplier_commissions` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;";

	echo insert_table('stock_supplier_commissions', $sql);

	echo insert_column('stock_supplier_commissions', "`id` int(11) NOT NULL");
	echo insert_column('stock_supplier_commissions', "`supplier_id` int(11) NOT NULL");
	echo insert_column('stock_supplier_commissions', "`month` int(11) NOT NULL");
	echo insert_column('stock_supplier_commissions', "`year` int(11) NOT NULL");
	echo insert_column('stock_supplier_commissions', "`commission_percent` float NOT NULL");
	echo insert_column('stock_supplier_commissions', "`commission_percent_2` float NOT NULL");
	echo insert_column('stock_supplier_commissions', "`commission_percent_3` float NOT NULL");




	$sql[0] = "
		CREATE TABLE `stock_supplier_receipt` (
		  `supplier_receipt_id` bigint(20) NOT NULL,
		  `receipt_number` int(11) NOT NULL DEFAULT '0',
		  `date_created` datetime NOT NULL,
		  `supplier_id` int(11) NOT NULL DEFAULT '0'
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `stock_supplier_receipt` ADD PRIMARY KEY (`supplier_receipt_id`);";

	$sql[2] = "ALTER TABLE `stock_supplier_receipt` MODIFY `supplier_receipt_id` bigint(20) NOT NULL AUTO_INCREMENT;";

	echo insert_table('stock_supplier_receipt', $sql);

	echo insert_column('stock_supplier_receipt', "`supplier_receipt_id` bigint(20) NOT NULL");
	echo insert_column('stock_supplier_receipt', "`receipt_number` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('stock_supplier_receipt', "`date_created` datetime NOT NULL");
	echo insert_column('stock_supplier_receipt', "`supplier_id` int(11) NOT NULL DEFAULT '0'");




	$sql[0] = "
		CREATE TABLE `".Monthalize('stock_tax')."` (
		  `tax_id` int(11) NOT NULL,
		  `tax_description` varchar(255) NOT NULL DEFAULT '',
		  `tax_type` int(11) NOT NULL DEFAULT '0',
		  `is_modified` char(1) NOT NULL DEFAULT 'N',
		  `is_active` char(1) NOT NULL DEFAULT 'Y'
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "INSERT INTO `".Monthalize('stock_tax')."` (`tax_id`, `tax_description`, `tax_type`, `is_modified`, `is_active`) VALUES (1, 'GST 18%', 0, 'Y', 'Y'), (2, 'GST 12%', 0, 'Y', 'N'), (3, 'GST 5%', 0, 'Y', 'Y'), (8, 'GST 1%', 0, 'Y', 'N');";

	$sql[2] = "ALTER TABLE `".Monthalize('stock_tax')."` ADD PRIMARY KEY (`tax_id`);";

	$sql[3] = "ALTER TABLE `".Monthalize('stock_tax')."` MODIFY `tax_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;";

	echo insert_table(Monthalize('stock_tax'), $sql);

	echo insert_column(Monthalize('stock_tax'), "`tax_id` int(11) NOT NULL");
	echo insert_column(Monthalize('stock_tax'), "`tax_description` varchar(255) NOT NULL DEFAULT ''");
	echo insert_column(Monthalize('stock_tax'), "`tax_type` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_tax'), "`is_modified` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column(Monthalize('stock_tax'), "`is_active` char(1) NOT NULL DEFAULT 'Y'");




	$sql[0] = "
		CREATE TABLE `".Monthalize('stock_tax_definition')."` (
		  `definition_id` int(11) NOT NULL,
		  `definition_description` varchar(20) NOT NULL DEFAULT '',
		  `definition_percent` float NOT NULL DEFAULT '0',
		  `definition_type` tinyint(4) NOT NULL DEFAULT '1',
		  `definition_explanation` varchar(250) NOT NULL DEFAULT '',
		  `rule_lower_limit` BOOLEAN NOT NULL DEFAULT FALSE
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "INSERT INTO `".Monthalize('stock_tax_definition')."` (`definition_id`, `definition_description`, `definition_percent`, `definition_type`, `definition_explanation`) VALUES (1, 'GST 18%', 18, 1, 'GST 18%'), (2, 'GST 12%', 12, 1, 'GST 12%'), (3, 'GST 5%', 5, 1, 'GST 5%'), (4, 'GST 1%', 1, 1, 'GST 1%');";

	$sql[2] = "ALTER TABLE `".Monthalize('stock_tax_definition')."` ADD PRIMARY KEY (`definition_id`);";

	$sql[3] = "ALTER TABLE `".Monthalize('stock_tax_definition')."` MODIFY `definition_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;";

	echo insert_table(Monthalize('stock_tax_definition'), $sql);

	echo insert_column(Monthalize('stock_tax_definition'), "`definition_id` int(11) NOT NULL");
	echo insert_column(Monthalize('stock_tax_definition'), "`definition_description` varchar(20) NOT NULL DEFAULT ''");
	echo insert_column(Monthalize('stock_tax_definition'), "`definition_percent` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_tax_definition'), "`definition_type` tinyint(4) NOT NULL DEFAULT '1'");
	echo insert_column(Monthalize('stock_tax_definition'), "`definition_explanation` varchar(250) NOT NULL DEFAULT ''");
	echo insert_column(Monthalize('stock_tax_definition'), "`rule_lower_limit` BOOLEAN NOT NULL DEFAULT FALSE");




	$sql[0] = "
		CREATE TABLE `".Monthalize('stock_tax_links')."` (
		  `tax_id` int(11) NOT NULL DEFAULT '0',
		  `tax_definition_id` int(11) NOT NULL DEFAULT '0',
		  `tax_order` tinyint(4) NOT NULL DEFAULT '0'
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "INSERT INTO `".Monthalize('stock_tax_links')."` (`tax_id`, `tax_definition_id`, `tax_order`) VALUES (1, 1, 1), (3, 3, 1), (2, 2, 1), (8, 4, 1);";

	echo insert_table(Monthalize('stock_tax_links'), $sql);

	echo insert_column(Monthalize('stock_tax_links'), "`tax_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_tax_links'), "`tax_definition_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_tax_links'), "`tax_order` tinyint(4) NOT NULL DEFAULT '0'");




	$sql[0] = "CREATE TABLE `stock_author` (
		  `author_id` int(11) NOT NULL,
		  `author` varchar(64) NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `stock_author`
	  ADD PRIMARY KEY (`author_id`),
	  ADD KEY `author` (`author`);";

	$sql[2] = "ALTER TABLE `stock_author`
	  MODIFY `author_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;";

	echo insert_table('stock_author', $sql);

	echo insert_column('stock_author', "`author_id` int(11) NOT NULL");
	echo insert_column('stock_author', "`author` varchar(64) NOT NULL");



	$sql[0] = "CREATE TABLE `stock_language` (
	  `language_id` int(11) NOT NULL,
	  `language` varchar(64) NOT NULL,
	  `language_group` varchar(64) NOT NULL
	) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "INSERT INTO `stock_language` (`language_id`, `language`, `language_group`) VALUES
		(1, 'English', ''),
		(9, 'Bengali', ''),
		(10, 'Malayalam', ''),
		(11, 'German', ''),
		(12, 'French', ''),
		(13, 'Spanish', ''),
		(14, 'Tamil', ''),
		(15, 'French & Tamil', ''),
		(16, 'French & English', ''),
		(17, 'Russian', ''),
		(18, 'Italy', ''),
		(20, 'Hebrew', ''),
		(21, 'Rumanian', ''),
		(22, 'Catalon', ''),
		(23, 'Hindi', ''),
		(24, 'Oriya', ''),
		(25, 'Gujarati', ''),
		(26, 'Marati', ''),
		(27, 'Telugu', ''),
		(28, 'Punjabi', ''),
		(29, 'Kannadam', ''),
		(30, 'Nepali', ''),
		(31, 'Sanskrit', ''),
		(32, 'Instrumental Music', ''),
		(33, 'English & Hindi', ''),
		(34, 'English & Tamil', ''),
		(35, 'English & Gujarati', ''),
		(36, 'English & Korean', ''),
		(37, 'Chinese', ''),
		(38, 'English & Bengali', '');";

	$sql[2] = "ALTER TABLE `stock_language`
  		ADD PRIMARY KEY (`language_id`),
  		ADD KEY `language` (`language`);";

	$sql[3] = "ALTER TABLE `stock_language`
	  MODIFY `language_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39; ";

	echo insert_table('stock_language', $sql);

	echo insert_column('stock_language', "`language_id` int(11) NOT NULL");
	echo insert_column('stock_language', "`language` varchar(64) NOT NULL");
	echo insert_column('stock_language', "`language_group` varchar(64) NOT NULL");



	$sql[0] = "CREATE TABLE `stock_publisher` (
	  	`publisher_id` int(11) NOT NULL,
	  	`publisher` varchar(64) NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `stock_publisher`
	  ADD PRIMARY KEY (`publisher_id`),
	  ADD KEY `publisher` (`publisher`);";

	$sql[2] = "ALTER TABLE `stock_publisher`
  		MODIFY `publisher_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;";

	echo insert_table('stock_publisher', $sql);

	echo insert_column('stock_publisher', "`publisher_id` int(11) NOT NULL");
	echo insert_column('stock_publisher', "`publisher` varchar(64) NOT NULL");





	$sql[0] = "
		CREATE TABLE `".Monthalize('stock_transfer')."` (
		  `transfer_id` int(11) NOT NULL,
		  `transfer_quantity` float NOT NULL DEFAULT '0',
		  `transfer_description` varchar(255) NOT NULL DEFAULT '',
		  `transfer_reference` varchar(40) NOT NULL DEFAULT ' ',
		  `date_created` datetime DEFAULT NULL,
		  `module_id` int(11) NOT NULL DEFAULT '0',
		  `user_id` int(11) NOT NULL DEFAULT '0',
		  `storeroom_id_from` int(11) NOT NULL DEFAULT '0',
		  `storeroom_id_to` int(11) NOT NULL DEFAULT '0',
		  `product_id` int(11) NOT NULL DEFAULT '0',
		  `batch_id` int(11) NOT NULL DEFAULT '0',
		  `module_record_id` int(11) NOT NULL DEFAULT '0',
		  `transfer_type` tinyint(1) NOT NULL DEFAULT '0',
		  `transfer_status` int(11) NOT NULL DEFAULT '1',
		  `user_id_dispatched` int(11) NOT NULL DEFAULT '0',
		  `user_id_received` int(11) NOT NULL DEFAULT '0',
		  `is_deleted` char(1) NOT NULL DEFAULT 'N',
		  `is_adjusted` char(1) NOT NULL DEFAULT 'N'
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `".Monthalize('stock_transfer')."` ADD PRIMARY KEY (`transfer_id`);";

	$sql[2] = "ALTER TABLE `".Monthalize('stock_transfer')."` MODIFY `transfer_id` int(11) NOT NULL AUTO_INCREMENT;";

	echo insert_table(Monthalize('stock_transfer'), $sql);

	echo insert_column(Monthalize('stock_transfer'), "`transfer_id` int(11) NOT NULL");
	echo insert_column(Monthalize('stock_transfer'), "`transfer_quantity` float NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_transfer'), "`transfer_description` varchar(255) NOT NULL DEFAULT ''");
	echo insert_column(Monthalize('stock_transfer'), "`transfer_reference` varchar(40) NOT NULL DEFAULT ' '");
	echo insert_column(Monthalize('stock_transfer'), "`date_created` datetime DEFAULT NULL");
	echo insert_column(Monthalize('stock_transfer'), "`module_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_transfer'), "`user_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_transfer'), "`storeroom_id_from` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_transfer'), "`storeroom_id_to` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_transfer'), "`product_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_transfer'), "`batch_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_transfer'), "`module_record_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_transfer'), "`transfer_type` tinyint(1) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_transfer'), "`transfer_status` int(11) NOT NULL DEFAULT '1'");
	echo insert_column(Monthalize('stock_transfer'), "`user_id_dispatched` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_transfer'), "`user_id_received` int(11) NOT NULL DEFAULT '0'");
	echo insert_column(Monthalize('stock_transfer'), "`is_deleted` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column(Monthalize('stock_transfer'), "`is_adjusted` char(1) NOT NULL DEFAULT 'N'");



	$sql[0] = "
		CREATE TABLE `stock_transfer_type` (
		  `transfer_type` int(11) NOT NULL DEFAULT '0',
		  `transfer_type_description` varchar(40) NOT NULL DEFAULT ''
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";


	$sql[1] = "INSERT INTO `stock_transfer_type` (`transfer_type`, `transfer_type_description`) VALUES (1, 'Internal Transfer'), (2, 'Returned Goods'), (3, 'Bill'), (4, 'Adjustment'), (5, 'Received Goods'), (6, 'Correction'), (7, 'Cancelled'), (8, 'Debit Bill'), (9, 'Delivery Chalan');";

	$sql[2] = "ALTER TABLE `stock_transfer_type` ADD PRIMARY KEY (`transfer_type`);";

	echo insert_table('stock_transfer_type', $sql);

	echo insert_column('stock_transfer_type', "`transfer_type` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('stock_transfer_type', "`transfer_type_description` varchar(40) NOT NULL DEFAULT ''");



	$sql[0] = "
		CREATE TABLE `stock_type` (
		  `stock_type_id` int(11) NOT NULL,
		  `product_type` varchar(64) NOT NULL DEFAULT ' ',
		  `is_modified` char(1) NOT NULL DEFAULT 'N'
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `stock_type` ADD PRIMARY KEY (`stock_type_id`), ADD UNIQUE KEY `product_type` (`product_type`);";

	$sql[2] = "ALTER TABLE `stock_type` MODIFY `stock_type_id` int(11) NOT NULL AUTO_INCREMENT;";

	echo insert_table('stock_type', $sql);

	echo insert_column('stock_type', "`stock_type_id` int(11) NOT NULL");
	echo insert_column('stock_type', "`product_type` varchar(64) NOT NULL DEFAULT ' '");
	echo insert_column('stock_type', "`is_modified` char(1) NOT NULL DEFAULT 'N'");



	$sql[0] = "
		CREATE TABLE `stock_type_description` (
		  `stock_type_description_id` int(11) NOT NULL,
		  `stock_type_id` int(11) NOT NULL,
		  `description` varchar(64) NOT NULL,
		  `is_modified` char(1) NOT NULL DEFAULT 'N'
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `stock_type_description` ADD PRIMARY KEY (`stock_type_description_id`), ADD UNIQUE KEY `stock_type_id` (`stock_type_id`,`description`);";

	$sql[2] = "ALTER TABLE `stock_type_description` MODIFY `stock_type_description_id` int(11) NOT NULL AUTO_INCREMENT;";

	echo insert_table('stock_type_description', $sql);

	echo insert_column('stock_type_description', "`stock_type_description_id` int(11) NOT NULL");
	echo insert_column('stock_type_description', "`stock_type_id` int(11) NOT NULL");
	echo insert_column('stock_type_description', "`description` varchar(64) NOT NULL");
	echo insert_column('stock_type_description', "`is_modified` char(1) NOT NULL DEFAULT 'N'");



	$sql[0] = "
		CREATE TABLE `templates` (
		  `id` int(11) NOT NULL,
		  `template_type` smallint(6) NOT NULL DEFAULT '1',
		  `print_to_filename` varchar(50) NOT NULL,
		  `name` varchar(100) NOT NULL,
		  `is_default` char(1) NOT NULL DEFAULT 'N',
		  `title` text NOT NULL,
		  `header` text NOT NULL,
		  `content` text NOT NULL,
		  `footer` text NOT NULL,
		  `is_main` char(1) NOT NULL DEFAULT 'N'
		) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `templates` ADD PRIMARY KEY (`id`);";

	$sql[2] = "ALTER TABLE `templates` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;";

	echo insert_table('templates', $sql);

	echo insert_column('templates', "`id` int(11) NOT NULL");
	echo insert_column('templates', "`template_type` smallint(6) NOT NULL DEFAULT '1'");
	echo insert_column('templates', "`print_to_filename` varchar(50) NOT NULL");
	echo insert_column('templates', "`name` varchar(100) NOT NULL");
	echo insert_column('templates', "`is_default` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('templates', "`title` text NOT NULL");
	echo insert_column('templates', "`header` text NOT NULL");
	echo insert_column('templates', "`content` text NOT NULL");
	echo insert_column('templates', "`footer` text NOT NULL");
	echo insert_column('templates', "`is_main` char(1) NOT NULL DEFAULT 'N'");




	$sql[0] = "
		CREATE TABLE `update_log` (
		  `id` int(11) NOT NULL,
		  `type_id` int(11) NOT NULL,
		  `updated_on` datetime NOT NULL,
		  `filename` varchar(128) NOT NULL,
		  `user_id` int(11) NOT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `update_log` ADD PRIMARY KEY (`id`);";

	$sql[2] = "ALTER TABLE `update_log` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;";

	echo insert_table('update_log', $sql);

	echo insert_column('update_log', "`id` int(11) NOT NULL");
	echo insert_column('update_log', "`type_id` int(11) NOT NULL");
	echo insert_column('update_log', "`updated_on` datetime NOT NULL");
	echo insert_column('update_log', "`filename` varchar(128) NOT NULL");
	echo insert_column('update_log', "`user_id` int(11) NOT NULL");




	$sql[0] = "
		CREATE TABLE `user` (
		  `user_id` int(11) NOT NULL,
		  `username` varchar(10) NOT NULL DEFAULT '',
		  `password` varchar(128) NOT NULL DEFAULT '',
		  `last_login` datetime NOT NULL,
		  `user_type` int(11) NOT NULL DEFAULT '0',
		  `default_storeroom_id` int(11) NOT NULL DEFAULT '0',
		  `po_prediction_method` smallint(6) NOT NULL DEFAULT '1',
		  `deleted` char(1) NOT NULL DEFAULT 'N',
		  `color_scheme` varchar(20) NOT NULL DEFAULT '',
		  `font_size` varchar(20) NOT NULL DEFAULT '',
		  `printing_type` tinyint(4) NOT NULL DEFAULT '1',
		  `can_change_price` char(1) NOT NULL DEFAULT 'Y',
		  `can_change_bill_date` char(1) NOT NULL DEFAULT 'Y',
		  `can_edit_batch` char(1) NOT NULL DEFAULT 'N',
		  `supplier_access` text NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	$sql[1] = "INSERT INTO `user` (`user_id`, `username`, `password`, `last_login`, `user_type`, `default_storeroom_id`, `po_prediction_method`, `deleted`, `color_scheme`, `font_size`, `printing_type`, `can_change_price`, `can_change_bill_date`, `can_edit_batch`, `supplier_access`) VALUES (1, 'admin', 'YWRtaW4=', '2019-01-31 16:53:42', 2, 1, 3, 'N', 'standard', 'small', 1, 'Y', 'Y', 'Y', '');";

	$sql[2] = "ALTER TABLE `user` ADD PRIMARY KEY (`user_id`);";

	$sql[3] = "ALTER TABLE `user` MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;";

	echo insert_table('user', $sql);

	echo insert_column('user', "`user_id` int(11) NOT NULL");
	echo insert_column('user', "`username` varchar(10) NOT NULL DEFAULT ''");
	echo insert_column('user', "`password` varchar(128) NOT NULL DEFAULT ''");
	echo insert_column('user', "`last_login` datetime NOT NULL");
	echo insert_column('user', "`user_type` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('user', "`default_storeroom_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('user', "`po_prediction_method` smallint(6) NOT NULL DEFAULT '1'");
	echo insert_column('user', "`deleted` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('user', "`color_scheme` varchar(20) NOT NULL DEFAULT ''");
	echo insert_column('user', "`font_size` varchar(20) NOT NULL DEFAULT ''");
	echo insert_column('user', "`printing_type` tinyint(4) NOT NULL DEFAULT '1'");
	echo insert_column('user', "`can_change_price` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('user', "`can_change_bill_date` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('user', "`can_edit_batch` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('user', "`supplier_access` text NOT NULL");




	$sql[0] = "
		CREATE TABLE `user_permissions` (
		  `permission_id` int(11) NOT NULL,
		  `user_id` int(11) NOT NULL DEFAULT '0',
		  `module_id` int(11) NOT NULL DEFAULT '0',
		  `access_level` int(11) NOT NULL DEFAULT '0',
		  `storeroom_id` int(11) NOT NULL DEFAULT '0'
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";


	$sql[1] = "INSERT INTO `user_permissions` (`permission_id`, `user_id`, `module_id`, `access_level`, `storeroom_id`) VALUES (22, 1, 4, 3, 1), (23, 1, 1, 3, 1), (24, 1, 2, 3, 1), (25, 1, 3, 3, 1), (140, 1, 8, 3, 1), (165, 1, 10, 3, 1), (166, 1, 7, 3, 1), (167, 1, 5, 3, 1), (168, 1, 9, 3, 1);";

	$sql[2] = "ALTER TABLE `user_permissions` ADD PRIMARY KEY (`permission_id`);";

	$sql[3] = "ALTER TABLE `user_permissions` MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=169;";

	echo insert_table('user_permissions', $sql);

	echo insert_column('user_permissions', "`permission_id` int(11) NOT NULL");
	echo insert_column('user_permissions', "`user_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('user_permissions', "`module_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('user_permissions', "`access_level` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('user_permissions', "`storeroom_id` int(11) NOT NULL DEFAULT '0'");




	$sql[0] = "
		CREATE TABLE `user_settings` (
		  `storeroom_id` int(11) NOT NULL DEFAULT '0',
		  `bill_print_note` varchar(100) NOT NULL DEFAULT '',
		  `bill_print_note_2` varchar(64) NOT NULL DEFAULT '',
		  `bill_print_note_3` varchar(64) NOT NULL DEFAULT '',
		  `bill_print_lines_to_eject` smallint(6) NOT NULL DEFAULT '0',
		  `bill_cash_bill_number` int(11) NOT NULL DEFAULT '0',
		  `bill_fs_bill_number` int(11) NOT NULL DEFAULT '0',
		  `bill_pt_bill_number` int(11) NOT NULL DEFAULT '0',
		  `bill_creditcard_bill_number` int(11) NOT NULL DEFAULT '0',
		  `bill_cheque_bill_number` int(11) NOT NULL,
		  `bill_transfer_bill_number` int(11) NOT NULL,
		  `bill_aurocard_bill_number` int(11) NOT NULL DEFAULT '0',
		  `bill_global_bill_number` int(11) NOT NULL,
		  `bill_transfer_tax` int(11) NOT NULL,
		  `bill_print_address` varchar(50) NOT NULL DEFAULT '',
		  `bill_print_phone` varchar(50) NOT NULL DEFAULT '',
		  `bill_print_batch` char(1) NOT NULL DEFAULT 'N',
		  `bill_closing_time` time NOT NULL,
		  `bill_font_size` varchar(15) NOT NULL DEFAULT '',
		  `bill_default_discount` smallint(6) NOT NULL DEFAULT '10',
		  `bill_display_messages` char(1) NOT NULL DEFAULT 'N',
		  `stock_is_equal_prices` char(1) NOT NULL DEFAULT 'N',
		  `stock_bulk_unit` int(11) NOT NULL,
		  `stock_packaged_unit` int(11) NOT NULL,
		  `stock_show_returned` char(1) NOT NULL DEFAULT 'Y',
		  `stock_show_available` char(1) NOT NULL DEFAULT 'Y',
		  `application_pid` varchar(10) NOT NULL DEFAULT '0',
		  `application_pin` varchar(10) NOT NULL DEFAULT '0',
		  `bill_decimal_places` tinyint(4) NOT NULL DEFAULT '2',
		  `bill_print_supplier_abbreviation` char(1) NOT NULL DEFAULT 'N',
		  `bill_enable_batches` char(1) NOT NULL DEFAULT 'N',
		  `calculate_tax_before_discount` char(1) NOT NULL DEFAULT 'N',
		  `order_global_message` varchar(255) NOT NULL,
		  `order_print_bill` char(1) NOT NULL DEFAULT 'Y',
		  `order_show_bills` char(1) NOT NULL DEFAULT 'Y',
		  `admin_last_loadall` datetime NOT NULL,
		  `bill_header` varchar(80) NOT NULL,
		  `bill_print_header` char(1) NOT NULL DEFAULT 'Y',
		  `admin_product_type` smallint(6) NOT NULL DEFAULT '2',
		  `bill_adjusted_enabled` char(1) NOT NULL DEFAULT 'Y',
		  `bill_edit_price` char(1) NOT NULL DEFAULT 'N',
		  `bill_print_tax_totals` char(1) NOT NULL DEFAULT 'N',
		  `bill_fs_discount` smallint(6) NOT NULL DEFAULT '0',
		  `bill_fs_low_balance` smallint(6) NOT NULL DEFAULT '0',
		  `pt_account_has_prefix` char(1) NOT NULL DEFAULT 'N',
		  `pt_account_prefix` varchar(5) NOT NULL,
		  `pt_account_has_suffix` char(1) NOT NULL DEFAULT 'N',
		  `pt_account_suffix` varchar(5) NOT NULL,
		  `stock_dc_number` int(11) NOT NULL DEFAULT '0',
		  `gstin` varchar(25) DEFAULT NULL,
		  `place_of_supply` varchar(50) NOT NULL,
		  `fs_user` VARCHAR(128) NULL,
		  `fs_password` VARCHAR(128) NULL
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";


	$sql[1] = "INSERT INTO `user_settings` (`storeroom_id`, `bill_print_note`, `bill_print_note_2`, `bill_print_note_3`, `bill_print_lines_to_eject`, `bill_cash_bill_number`, `bill_fs_bill_number`, `bill_pt_bill_number`, `bill_creditcard_bill_number`, `bill_cheque_bill_number`, `bill_transfer_bill_number`, `bill_aurocard_bill_number`, `bill_global_bill_number`, `bill_transfer_tax`, `bill_print_address`, `bill_print_phone`, `bill_print_batch`, `bill_closing_time`, `bill_font_size`, `bill_default_discount`, `bill_display_messages`, `stock_is_equal_prices`, `stock_bulk_unit`, `stock_packaged_unit`, `stock_show_returned`, `stock_show_available`, `application_pid`, `application_pin`, `bill_decimal_places`, `bill_print_supplier_abbreviation`, `bill_enable_batches`, `calculate_tax_before_discount`, `order_global_message`, `order_print_bill`, `order_show_bills`, `admin_last_loadall`, `bill_header`, `bill_print_header`, `admin_product_type`, `bill_adjusted_enabled`, `bill_edit_price`, `bill_print_tax_totals`, `bill_fs_discount`, `bill_fs_low_balance`, `pt_account_has_prefix`, `pt_account_prefix`, `pt_account_has_suffix`, `pt_account_suffix`, `stock_dc_number`, `gstin`, `place_of_supply`) VALUES (1, '', '', '', 11, 0, 0, 0, 0, 0, 0, 0, 13, 1, '', '', 'N', '13:00:00', '', 0, 'N', 'N', 1, 1, 'N', 'Y', '', '', 3, 'N', 'N', 'N', '', 'Y', 'Y', '2009-07-20 16:20:17', '', 'N', 2, 'Y', 'Y', 'Y', 25, 500, 'N', '', 'N', '', 0, '', 'Tamil Nadu');";

	$sql[2] = "ALTER TABLE `user_settings` ADD UNIQUE KEY `storeroom_id` (`storeroom_id`);";

	echo insert_table('user_settings', $sql);

	echo insert_column('user_settings', "`storeroom_id` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('user_settings', "`bill_print_note` varchar(100) NOT NULL DEFAULT ''");
	echo insert_column('user_settings', "`bill_print_note_2` varchar(64) NOT NULL DEFAULT ''");
	echo insert_column('user_settings', "`bill_print_note_3` varchar(64) NOT NULL DEFAULT ''");
	echo insert_column('user_settings', "`bill_print_lines_to_eject` smallint(6) NOT NULL DEFAULT '0'");
	echo insert_column('user_settings', "`bill_cash_bill_number` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('user_settings', "`bill_fs_bill_number` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('user_settings', "`bill_pt_bill_number` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('user_settings', "`bill_creditcard_bill_number` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('user_settings', "`bill_cheque_bill_number` int(11) NOT NULL");
	echo insert_column('user_settings', "`bill_transfer_bill_number` int(11) NOT NULL");
	echo insert_column('user_settings', "`bill_aurocard_bill_number` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('user_settings', "`bill_global_bill_number` int(11) NOT NULL");
	echo insert_column('user_settings', "`bill_transfer_tax` int(11) NOT NULL");
	echo insert_column('user_settings', "`bill_print_address` varchar(50) NOT NULL DEFAULT ''");
	echo insert_column('user_settings', "`bill_print_phone` varchar(50) NOT NULL DEFAULT ''");
	echo insert_column('user_settings', "`bill_print_batch` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('user_settings', "`bill_closing_time` time NOT NULL");
	echo insert_column('user_settings', "`bill_font_size` varchar(15) NOT NULL DEFAULT ''");
	echo insert_column('user_settings', "`bill_default_discount` smallint(6) NOT NULL DEFAULT '10'");
	echo insert_column('user_settings', "`bill_display_messages` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('user_settings', "`stock_is_equal_prices` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('user_settings', "`stock_bulk_unit` int(11) NOT NULL");
	echo insert_column('user_settings', "`stock_packaged_unit` int(11) NOT NULL");
	echo insert_column('user_settings', "`stock_show_returned` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('user_settings', "`stock_show_available` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('user_settings', "`application_pid` varchar(10) NOT NULL DEFAULT '0'");
	echo insert_column('user_settings', "`application_pin` varchar(10) NOT NULL DEFAULT '0'");
	echo insert_column('user_settings', "`bill_decimal_places` tinyint(4) NOT NULL DEFAULT '2'");
	echo insert_column('user_settings', "`bill_print_supplier_abbreviation` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('user_settings', "`bill_enable_batches` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('user_settings', "`calculate_tax_before_discount` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('user_settings', "`order_global_message` varchar(255) NOT NULL");
	echo insert_column('user_settings', "`order_print_bill` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('user_settings', "`order_show_bills` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('user_settings', "`admin_last_loadall` datetime NOT NULL");
	echo insert_column('user_settings', "`bill_header` varchar(80) NOT NULL");
	echo insert_column('user_settings', "`bill_print_header` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('user_settings', "`admin_product_type` smallint(6) NOT NULL DEFAULT '2'");
	echo insert_column('user_settings', "`bill_adjusted_enabled` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('user_settings', "`bill_edit_price` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('user_settings', "`bill_print_tax_totals` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('user_settings', "`bill_fs_discount` smallint(6) NOT NULL DEFAULT '0'");
	echo insert_column('user_settings', "`bill_fs_low_balance` smallint(6) NOT NULL DEFAULT '0'");
	echo insert_column('user_settings', "`pt_account_has_prefix` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('user_settings', "`pt_account_prefix` varchar(5) NOT NULL");
	echo insert_column('user_settings', "`pt_account_has_suffix` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('user_settings', "`pt_account_suffix` varchar(5) NOT NULL");
	echo insert_column('user_settings', "`stock_dc_number` int(11) NOT NULL DEFAULT '0'");
	echo insert_column('user_settings', "`gstin` varchar(25) DEFAULT NULL");
	echo insert_column('user_settings', "`place_of_supply` varchar(50) NOT NULL");
	echo insert_column('user_settings', "`fs_user` VARCHAR(128) NULL AFTER `place_of_supply`");
	echo insert_column('user_settings', "`fs_password` VARCHAR(128) NULL AFTER `fs_user`");



	$sql[0] = "
		CREATE TABLE `yui_grid` (
		  `id` int(11) NOT NULL,
		  `fieldname` varchar(64) NOT NULL,
		  `yui_fieldname` varchar(64) NOT NULL,
		  `columnname` varchar(64) NOT NULL,
		  `formatter` varchar(32) NOT NULL,
		  `is_custom_formatter` char(1) NOT NULL DEFAULT 'N',
		  `parser` varchar(16) NOT NULL,
		  `columnwidth` int(11) NOT NULL DEFAULT '100',
		  `visible` char(1) NOT NULL DEFAULT 'Y',
		  `sortable` char(1) NOT NULL DEFAULT 'Y',
		  `filter` char(1) NOT NULL DEFAULT 'N',
		  `gridname` varchar(64) NOT NULL,
		  `view_name` varchar(32) NOT NULL DEFAULT 'default',
		  `position` smallint(6) NOT NULL DEFAULT '1',
		  `is_primary_key` char(1) NOT NULL DEFAULT 'N',
		  `user_id` int(11) NOT NULL DEFAULT '1',
		  `alias` varchar(16) NOT NULL,
		  `defaultsort` char(1) NOT NULL DEFAULT 'N',
		  `defaultdir` char(1) NOT NULL DEFAULT 'A'
		) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

	$sql[1] = "ALTER TABLE `yui_grid` ADD PRIMARY KEY (`id`);";

	$sql[2] = "ALTER TABLE `yui_grid` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;";

	echo insert_table('yui_grid', $sql);

	echo insert_column('yui_grid', "`id` int(11) NOT NULL");
	echo insert_column('yui_grid', "`fieldname` varchar(64) NOT NULL");
	echo insert_column('yui_grid', "`yui_fieldname` varchar(64) NOT NULL");
	echo insert_column('yui_grid', "`columnname` varchar(64) NOT NULL");
	echo insert_column('yui_grid', "`formatter` varchar(32) NOT NULL");
	echo insert_column('yui_grid', "`is_custom_formatter` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('yui_grid', "`parser` varchar(16) NOT NULL");
	echo insert_column('yui_grid', "`columnwidth` int(11) NOT NULL DEFAULT '100'");
	echo insert_column('yui_grid', "`visible` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('yui_grid', "`sortable` char(1) NOT NULL DEFAULT 'Y'");
	echo insert_column('yui_grid', "`filter` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('yui_grid', "`gridname` varchar(64) NOT NULL");
	echo insert_column('yui_grid', "`view_name` varchar(32) NOT NULL DEFAULT 'default'");
	echo insert_column('yui_grid', "`position` smallint(6) NOT NULL DEFAULT '1'");
	echo insert_column('yui_grid', "`is_primary_key` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('yui_grid', "`user_id` int(11) NOT NULL DEFAULT '1'");
	echo insert_column('yui_grid', "`alias` varchar(16) NOT NULL");
	echo insert_column('yui_grid', "`defaultsort` char(1) NOT NULL DEFAULT 'N'");
	echo insert_column('yui_grid', "`defaultdir` char(1) NOT NULL DEFAULT 'A'");

	$sql[0] = "
		CREATE TABLE IF NOT EXISTS `countries` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `sortname` varchar(3) NOT NULL,
		  `name` varchar(150) NOT NULL,
		  `phonecode` int(11) NOT NULL,
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=249 ;";


	$sql[1] = "
		INSERT INTO `countries` (`id`, `sortname`, `name`, `phonecode`) VALUES
		(1, 'AF', 'Afghanistan', 93),
		(2, 'AL', 'Albania', 355),
		(3, 'DZ', 'Algeria', 213),
		(4, 'AS', 'American Samoa', 1684),
		(5, 'AD', 'Andorra', 376),
		(6, 'AO', 'Angola', 244),
		(7, 'AI', 'Anguilla', 1264),
		(8, 'AQ', 'Antarctica', 0),
		(9, 'AG', 'Antigua And Barbuda', 1268),
		(10, 'AR', 'Argentina', 54),
		(11, 'AM', 'Armenia', 374),
		(12, 'AW', 'Aruba', 297),
		(13, 'AU', 'Australia', 61),
		(14, 'AT', 'Austria', 43),
		(15, 'AZ', 'Azerbaijan', 994),
		(16, 'BS', 'Bahamas The', 1242),
		(17, 'BH', 'Bahrain', 973),
		(18, 'BD', 'Bangladesh', 880),
		(19, 'BB', 'Barbados', 1246),
		(20, 'BY', 'Belarus', 375),
		(21, 'BE', 'Belgium', 32),
		(22, 'BZ', 'Belize', 501),
		(23, 'BJ', 'Benin', 229),
		(24, 'BM', 'Bermuda', 1441),
		(25, 'BT', 'Bhutan', 975),
		(26, 'BO', 'Bolivia', 591),
		(27, 'BA', 'Bosnia and Herzegovina', 387),
		(28, 'BW', 'Botswana', 267),
		(29, 'BV', 'Bouvet Island', 0),
		(30, 'BR', 'Brazil', 55),
		(31, 'IO', 'British Indian Ocean Territory', 246),
		(32, 'BN', 'Brunei', 673),
		(33, 'BG', 'Bulgaria', 359),
		(34, 'BF', 'Burkina Faso', 226),
		(35, 'BI', 'Burundi', 257),
		(36, 'KH', 'Cambodia', 855),
		(37, 'CM', 'Cameroon', 237),
		(38, 'CA', 'Canada', 1),
		(39, 'CV', 'Cape Verde', 238),
		(40, 'KY', 'Cayman Islands', 1345),
		(41, 'CF', 'Central African Republic', 236),
		(42, 'TD', 'Chad', 235),
		(43, 'CL', 'Chile', 56),
		(44, 'CN', 'China', 86),
		(45, 'CX', 'Christmas Island', 61),
		(46, 'CC', 'Cocos (Keeling) Islands', 672),
		(47, 'CO', 'Colombia', 57),
		(48, 'KM', 'Comoros', 269),
		(49, 'CG', 'Republic Of The Congo', 242),
		(50, 'CD', 'Democratic Republic Of The Congo', 242),
		(51, 'CK', 'Cook Islands', 682),
		(52, 'CR', 'Costa Rica', 506),
		(53, 'CI', 'Cote D''Ivoire (Ivory Coast)', 225),
		(54, 'HR', 'Croatia (Hrvatska)', 385),
		(55, 'CU', 'Cuba', 53),
		(56, 'CY', 'Cyprus', 357),
		(57, 'CZ', 'Czech Republic', 420),
		(58, 'DK', 'Denmark', 45),
		(59, 'DJ', 'Djibouti', 253),
		(60, 'DM', 'Dominica', 1767),
		(61, 'DO', 'Dominican Republic', 1809),
		(62, 'TP', 'East Timor', 670),
		(63, 'EC', 'Ecuador', 593),
		(64, 'EG', 'Egypt', 20),
		(65, 'SV', 'El Salvador', 503),
		(66, 'GQ', 'Equatorial Guinea', 240),
		(67, 'ER', 'Eritrea', 291),
		(68, 'EE', 'Estonia', 372),
		(69, 'ET', 'Ethiopia', 251),
		(70, 'XA', 'External Territories of Australia', 61),
		(71, 'FK', 'Falkland Islands', 500),
		(72, 'FO', 'Faroe Islands', 298),
		(73, 'FJ', 'Fiji Islands', 679),
		(74, 'FI', 'Finland', 358),
		(75, 'FR', 'France', 33),
		(76, 'GF', 'French Guiana', 594),
		(77, 'PF', 'French Polynesia', 689),
		(78, 'TF', 'French Southern Territories', 0),
		(79, 'GA', 'Gabon', 241),
		(80, 'GM', 'Gambia The', 220),
		(81, 'GE', 'Georgia', 995),
		(82, 'DE', 'Germany', 49),
		(83, 'GH', 'Ghana', 233),
		(84, 'GI', 'Gibraltar', 350),
		(85, 'GR', 'Greece', 30),
		(86, 'GL', 'Greenland', 299),
		(87, 'GD', 'Grenada', 1473),
		(88, 'GP', 'Guadeloupe', 590),
		(89, 'GU', 'Guam', 1671),
		(90, 'GT', 'Guatemala', 502),
		(91, 'XU', 'Guernsey and Alderney', 44),
		(92, 'GN', 'Guinea', 224),
		(93, 'GW', 'Guinea-Bissau', 245),
		(94, 'GY', 'Guyana', 592),
		(95, 'HT', 'Haiti', 509),
		(96, 'HM', 'Heard and McDonald Islands', 0),
		(97, 'HN', 'Honduras', 504),
		(98, 'HK', 'Hong Kong S.A.R.', 852),
		(99, 'HU', 'Hungary', 36),
		(100, 'IS', 'Iceland', 354),
		(101, 'IN', 'India', 91),
		(102, 'ID', 'Indonesia', 62),
		(103, 'IR', 'Iran', 98),
		(104, 'IQ', 'Iraq', 964),
		(105, 'IE', 'Ireland', 353),
		(106, 'IL', 'Israel', 972),
		(107, 'IT', 'Italy', 39),
		(108, 'JM', 'Jamaica', 1876),
		(109, 'JP', 'Japan', 81),
		(110, 'XJ', 'Jersey', 44),
		(111, 'JO', 'Jordan', 962),
		(112, 'KZ', 'Kazakhstan', 7),
		(113, 'KE', 'Kenya', 254),
		(114, 'KI', 'Kiribati', 686),
		(115, 'KP', 'Korea North', 850),
		(116, 'KR', 'Korea South', 82),
		(117, 'KW', 'Kuwait', 965),
		(118, 'KG', 'Kyrgyzstan', 996),
		(119, 'LA', 'Laos', 856),
		(120, 'LV', 'Latvia', 371),
		(121, 'LB', 'Lebanon', 961),
		(122, 'LS', 'Lesotho', 266),
		(123, 'LR', 'Liberia', 231),
		(124, 'LY', 'Libya', 218),
		(125, 'LI', 'Liechtenstein', 423),
		(126, 'LT', 'Lithuania', 370),
		(127, 'LU', 'Luxembourg', 352),
		(128, 'MO', 'Macau S.A.R.', 853),
		(129, 'MK', 'Macedonia', 389),
		(130, 'MG', 'Madagascar', 261),
		(131, 'MW', 'Malawi', 265),
		(132, 'MY', 'Malaysia', 60),
		(133, 'MV', 'Maldives', 960),
		(134, 'ML', 'Mali', 223),
		(135, 'MT', 'Malta', 356),
		(136, 'XM', 'Man (Isle of)', 44),
		(137, 'MH', 'Marshall Islands', 692),
		(138, 'MQ', 'Martinique', 596),
		(139, 'MR', 'Mauritania', 222),
		(140, 'MU', 'Mauritius', 230),
		(141, 'YT', 'Mayotte', 269),
		(142, 'MX', 'Mexico', 52),
		(143, 'FM', 'Micronesia', 691),
		(144, 'MD', 'Moldova', 373),
		(145, 'MC', 'Monaco', 377),
		(146, 'MN', 'Mongolia', 976),
		(147, 'MS', 'Montserrat', 1664),
		(148, 'MA', 'Morocco', 212),
		(149, 'MZ', 'Mozambique', 258),
		(150, 'MM', 'Myanmar', 95),
		(151, 'NA', 'Namibia', 264),
		(152, 'NR', 'Nauru', 674),
		(153, 'NP', 'Nepal', 977),
		(154, 'AN', 'Netherlands Antilles', 599),
		(155, 'NL', 'Netherlands The', 31),
		(156, 'NC', 'New Caledonia', 687),
		(157, 'NZ', 'New Zealand', 64),
		(158, 'NI', 'Nicaragua', 505),
		(159, 'NE', 'Niger', 227),
		(160, 'NG', 'Nigeria', 234),
		(161, 'NU', 'Niue', 683),
		(162, 'NF', 'Norfolk Island', 672),
		(163, 'MP', 'Northern Mariana Islands', 1670),
		(164, 'NO', 'Norway', 47),
		(165, 'OM', 'Oman', 968),
		(166, 'PK', 'Pakistan', 92),
		(167, 'PW', 'Palau', 680),
		(168, 'PS', 'Palestinian Territory Occupied', 970),
		(169, 'PA', 'Panama', 507),
		(170, 'PG', 'Papua new Guinea', 675),
		(171, 'PY', 'Paraguay', 595),
		(172, 'PE', 'Peru', 51),
		(173, 'PH', 'Philippines', 63),
		(174, 'PN', 'Pitcairn Island', 0),
		(175, 'PL', 'Poland', 48),
		(176, 'PT', 'Portugal', 351),
		(177, 'PR', 'Puerto Rico', 1787),
		(178, 'QA', 'Qatar', 974),
		(179, 'RE', 'Reunion', 262),
		(180, 'RO', 'Romania', 40),
		(181, 'RU', 'Russia', 70),
		(182, 'RW', 'Rwanda', 250),
		(183, 'SH', 'Saint Helena', 290),
		(184, 'KN', 'Saint Kitts And Nevis', 1869),
		(185, 'LC', 'Saint Lucia', 1758),
		(186, 'PM', 'Saint Pierre and Miquelon', 508),
		(187, 'VC', 'Saint Vincent And The Grenadines', 1784),
		(188, 'WS', 'Samoa', 684),
		(189, 'SM', 'San Marino', 378),
		(190, 'ST', 'Sao Tome and Principe', 239),
		(191, 'SA', 'Saudi Arabia', 966),
		(192, 'SN', 'Senegal', 221),
		(193, 'RS', 'Serbia', 381),
		(194, 'SC', 'Seychelles', 248),
		(195, 'SL', 'Sierra Leone', 232),
		(196, 'SG', 'Singapore', 65),
		(197, 'SK', 'Slovakia', 421),
		(198, 'SI', 'Slovenia', 386),
		(199, 'XG', 'Smaller Territories of the UK', 44),
		(200, 'SB', 'Solomon Islands', 677),
		(201, 'SO', 'Somalia', 252),
		(202, 'ZA', 'South Africa', 27),
		(203, 'GS', 'South Georgia', 0),
		(204, 'SS', 'South Sudan', 211),
		(205, 'ES', 'Spain', 34),
		(206, 'LK', 'Sri Lanka', 94),
		(207, 'SD', 'Sudan', 249),
		(208, 'SR', 'Suriname', 597),
		(209, 'SJ', 'Svalbard And Jan Mayen Islands', 47),
		(210, 'SZ', 'Swaziland', 268),
		(211, 'SE', 'Sweden', 46),
		(212, 'CH', 'Switzerland', 41),
		(213, 'SY', 'Syria', 963),
		(214, 'TW', 'Taiwan', 886),
		(215, 'TJ', 'Tajikistan', 992),
		(216, 'TZ', 'Tanzania', 255),
		(217, 'TH', 'Thailand', 66),
		(218, 'TG', 'Togo', 228),
		(219, 'TK', 'Tokelau', 690),
		(220, 'TO', 'Tonga', 676),
		(221, 'TT', 'Trinidad And Tobago', 1868),
		(222, 'TN', 'Tunisia', 216),
		(223, 'TR', 'Turkey', 90),
		(224, 'TM', 'Turkmenistan', 7370),
		(225, 'TC', 'Turks And Caicos Islands', 1649),
		(226, 'TV', 'Tuvalu', 688),
		(227, 'UG', 'Uganda', 256),
		(228, 'UA', 'Ukraine', 380),
		(229, 'AE', 'United Arab Emirates', 971),
		(230, 'GB', 'United Kingdom', 44),
		(231, 'US', 'United States', 1),
		(232, 'UM', 'United States Minor Outlying Islands', 1),
		(233, 'UY', 'Uruguay', 598),
		(234, 'UZ', 'Uzbekistan', 998),
		(235, 'VU', 'Vanuatu', 678),
		(236, 'VA', 'Vatican City State (Holy See)', 39),
		(237, 'VE', 'Venezuela', 58),
		(238, 'VN', 'Vietnam', 84),
		(239, 'VG', 'Virgin Islands (British)', 1284),
		(240, 'VI', 'Virgin Islands (US)', 1340),
		(241, 'WF', 'Wallis And Futuna Islands', 681),
		(242, 'EH', 'Western Sahara', 212),
		(243, 'YE', 'Yemen', 967),
		(244, 'YU', 'Yugoslavia', 38),
		(245, 'ZM', 'Zambia', 260),
		(246, 'ZW', 'Zimbabwe', 263);";

	echo insert_table('countries', $sql);

	echo insert_column('countries', "`id` int(11) NOT NULL AUTO_INCREMENT");
	echo insert_column('countries', "`sortname` varchar(3) NOT NULL");
	echo insert_column('countries', "`name` varchar(150) NOT NULL");
	echo insert_column('countries', "`phonecode` int(11) NOT NULL");
	  
		  


	echo execute_update(20190912, "ALTER TABLE `".Monthalize('stock_rts')."` CHANGE `bill_number` `bill_number` VARCHAR(16) NULL DEFAULT '0';");

	echo execute_update(20191022, "ALTER TABLE `user_settings` ADD `bill_invoice_suffix` VARCHAR(8) NOT NULL AFTER `fs_password`;");

	echo execute_update(20191023, "ALTER TABLE `stock_product` CHANGE `mrp` `mrp` DECIMAL(10,3) NOT NULL DEFAULT '0';");

	echo execute_update(20200829, "ALTER TABLE `".Monthalize('dc')."` ADD `dc_status` TINYINT(4) NOT NULL AFTER `is_modified`;");
	 
	echo execute_update(20220107, "ALTER TABLE `account_cc` CHANGE `account_number` `account_number` VARCHAR(12) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '';");

	echo execute_update(20230706, "ALTER TABLE `user_settings` ADD `bill_upi_bill_number` INT NULL DEFAULT '0' AFTER `bill_aurocard_bill_number`; ");

	echo execute_update(20230707, "ALTER TABLE `".Monthalize('bill')."` ADD `upi_transaction_id` VARCHAR(64) NOT NULL DEFAULT '' AFTER `aurocard_transaction_id`; ");
	echo execute_update(20230708, "ALTER TABLE `".Monthalize('bill')."` ADD `upi_utr_number` VARCHAR(64) NOT NULL DEFAULT '' AFTER `aurocard_transaction_id`;");

	echo execute_update(20231010, "ALTER TABLE `company` ADD `address2` VARCHAR(128) NOT NULL AFTER `address`; ");

	echo execute_update(20231012, "ALTER TABLE `user_settings` ADD `bill_bank_transfer_bill_number` INT NOT NULL DEFAULT '0' AFTER `bill_upi_bill_number`;");

	echo execute_update(20240209, "ALTER TABLE `user_settings` ADD `bill_scroll_billed_items` CHAR(1) NOT NULL DEFAULT 'Y' AFTER `bill_invoice_suffix`; ");

	echo execute_update(20240218, "ALTER TABLE `".Monthalize('bill')."` ADD UNIQUE  (`storeroom_id`, `payment_type`, `bill_number`);");

	// echo execute_update();


	echo "done.";

?>