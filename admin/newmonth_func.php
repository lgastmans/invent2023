<?php
require_once("../include/const.inc.php");
require_once("../include/session.inc.php");
require_once("../common/functions.inc.php");
require_once("../include/db.inc.php");
//require_once("MDB2.php");


if (!empty($_GET['openmonth'])) {
	echo open_new_month($_GET['reset_adjusted'], $arr_invent_config);
}
else
	echo "Could not open new month";



function open_new_month($reset_adjusted, $arr_invent_config) {
	$int_month = $_SESSION['int_month_loaded']+1;
	$int_year = $_SESSION['int_year_loaded'];
	if ($int_month==13) {
		$int_year++;
		$int_month=1;
	}
	
	$str_database_name = $arr_invent_config['database']['invent_database'];
	
	/**
	*	If the system date and the current date of the application
	*	are the same then do not allow creation of new month
	*/
	$bool_continue = true;
	if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
		if ((IsSet($_SESSION['invent_is_new_financial_year'])) && ($_SESSION['invent_is_new_financial_year'])) {
			$bool_continue = true;
			$_SESSION['invent_is_new_financial_year'] = false;
		}
		else {
			echo "<font color='red'>Cannot close the current month as the system date and the application date are the same</font>\n";
			return false;
		}
	}
		
	if ($bool_continue) {

		if ($reset_adjusted=='Y')
			$_SESSION['newmonth_reset_adjusted'] = 'Y';
		else
			$_SESSION['newmonth_reset_adjusted'] = 'N';
		
		ob_start();
		
		$qry_trans = new Query("BEGIN");
		
		/**
		*	run the createMonth script of each module
		*/
		for ($i=0;$i<count($_SESSION['arr_modules']);$i++) {

			if (!$_SESSION['arr_modules'][$i]->createMonth($int_month, $int_year)) {

				$qry_trans->Query('ROLLBACK');
				$str = "module ".$_SESSION['arr_modules'][$i]->str_module_name." gave an error";
				
				return $str;
			}
		}
		
		/**
		*	if the current month is APRIL
		*	the previous financial year's data
		*	gets dumped in a backup database
		*/
		
		$int_month = date('n', time());

		if ($int_month == 4) {
		
			// require_once('mysql_backup.php');
			// echo backup_db('N', '', ($int_year-1)."_".$int_year.".sql.gz");

			/*
				create the database
			*/
			$int_year = date('Y', time());
			$str_financial_year = ($int_year-1)."_".$int_year;
			$str_backup_db = $str_database_name."_".$str_financial_year;
			$str_query = "CREATE DATABASE $str_backup_db";
			$qry = new Query($str_query);
			if ($qry->b_error == true) {
				echo "ERROR CREATING BACKUP DATABASE - PLEASE REPORT \n";
			}
			else {
				/*
					retrieve the list of all tables in the current database
				*/
				$qry->Query("SHOW TABLES");
				
				$qry_copy = new Query("SELECT * FROM user LIMIT 1");
				
				/*
					copy all the data over
				*/
				for ($i=0;$i<$qry->RowCount();$i++) {
					/*
						create a copy of the table
						including the indexes
					*/
					$qry_copy->Query("
						CREATE TABLE ".$str_backup_db.".".$qry->row[0]."
						LIKE ".$str_database_name.".".$qry->row[0]
					);
					
					/*
						copy the data over
					*/
					$qry_copy->Query("
						INSERT INTO ".$str_backup_db.".".$qry->row[0]."
							SELECT *
							FROM ".$str_database_name.".".$qry->row[0]
					);
					
					$qry->Next();
				}
				
				/*
					remove non-current financial year tables
					from current database
				*/
				$qry->First();
				for ($i=0;$i<$qry->RowCount();$i++) {
					$arr_table_date = explode("_", $qry->row[0]);
					$int_len = count($arr_table_date);
					if ($int_len > 0) {
						if (is_numeric($arr_table_date[$int_len-1])) {
							/*
								if > 2 it is a year table
							*/
							if (strlen($arr_table_date[$int_len-1]) > 2) {
								if (intval($arr_table_date[$int_len-1]) < $int_year) {
									$qry_copy->Query("DROP TABLE ".$qry->row[0]);
								}
							}
							else {
								if (intval($arr_table_date[$int_len-2]) <> $int_year) {
									$qry_copy->Query("DROP TABLE ".$qry->row[0]);
								}
								else if ((intval($arr_table_date[$int_len-2]) == $int_year) && (intval($arr_table_date[$int_len-1]) < 4)) {
									$qry_copy->Query("DROP TABLE ".$qry->row[0]);
								}
							}
						}
					}
					
					$qry->Next();
				}
				
				/*
					the tables for the current month of APRIL,
					which were created in the createMonth of each module
					need to be removed from the backup database
				*/
				$db_db = $str_backup_db;
				$db_server =  $arr_invent_config['database']['invent_server'];
				$db_login = $arr_invent_config['database']['invent_login'];
				$db_password = $arr_invent_config['database']['invent_password'];
				
				//$dsn_backup = "mysqli://$db_login:$db_password@$db_server/$db_db";
				//$conn_backup =& MDB2::connect($dsn_backup);
				$conn_backup = new mysqli($db_server, $db_login, $db_password, $db_db);

				//if (MDB2::isError($conn_backup)) {
				if ($conn_backup->connect_errno) {
					//echo "Cannot connect: ".$conn_backup->getMessage()."\n";
					echo "Cannot connect: ".$conn_backup->connect_error."\n";
					
					return false;
				}
				
				//$conn_backup->setFetchMode(MDB2_FETCHMODE_ORDERED);

				
				/*
					retrieve the list of all tables in the backup database
				*/
				$qry =& $conn_backup->query("SHOW TABLES");
				
				while ($obj = $qry->fetch_array(MYSQLI_NUM)) {

					$arr_table_date = explode("_", $obj[0]);
					$int_len = count($arr_table_date);

					if ($int_len > 0) {

						if (is_numeric($arr_table_date[$int_len-1])) {

							if ((intval($arr_table_date[$int_len-2]) == $int_year) && (intval($arr_table_date[$int_len-1]) == 4)) {
								$qry_del =& $conn_backup->query("DROP TABLE ".$obj[0]);
							}
							else if (intval($arr_table_date[$int_len-1]) == $int_year) {
								$qry_del =& $conn_backup->query("DROP TABLE ".$obj[0]);
							}
						}
					}
				}
			}
		}
		
		$qry_trans->Query('COMMIT');
		
		echo "Successfully opened new month";
	}
}

?>