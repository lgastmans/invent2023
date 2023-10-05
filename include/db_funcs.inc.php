<?
	
	function check_current_db($db, $db_server, $db_login, $db_password) {
		/*
			new financial year check:
			the stock_balance period in the "current" database
			will be from the previous fs period
		*/
		/*
		$dsn_check = "mysqli://$db_login:$db_password@$db_server/$db";
		$conn_check =& MDB2::connect($dsn_check);
		$conn_check->setFetchMode(MDB2_FETCHMODE_ORDERED);
		$qry_check =& $conn_check->query("SHOW TABLES");
		*/

		$conn_check = mysqli_connect("$db_server", $db_login, $db_password, $db);
		if (!$conn_check) {
		    echo "Error: Unable to connect to MySQL." . PHP_EOL;
		    echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
		    echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
		    exit;
		}
		$qry_check = $conn_check->query("SHOW TABLES");

		$int_month = date('n');
		$int_year = date('Y')-1;
		
		$bool_found = false;
		if ($int_month >= 4) {
			while ($obj = $qry_check->fetch_row()) {
				if ($obj[0] == "stock_balance_".$int_year) {
					$bool_found = true;
					break;
				}
			}
		}
		
		return $bool_found;
	}
	
	function getFSMonths() {
		global $conn;
		$arr_retval = array();
		
//		$conn->setFetchMode(MDB2_FETCHMODE_ORDERED);
		
		$qry =& $conn->query("SHOW TABLES LIKE 'bill_items_%'");
		
		while ($obj = $qry->fetch_row()) {
			$arr_table = explode('_', $obj[0]);
			if (strlen($arr_table[3]) > 1)
				$arr_temp[] = $obj[0];
			else
				$arr_temp[] = $arr_table[0]."_".$arr_table[1]."_".$arr_table[2]."_0".$arr_table[3];
		}
		sort($arr_temp);
		
		foreach($arr_temp as $key=>$value) {
			$arr_table = explode('_', $value);
			$arr_key = $arr_table[3]."_".$arr_table[2];
			
			if ($arr_table[3][0] == '0')
				$arr_value = getMonthName(substr($arr_table[3],1,1))." ".$arr_table[2];
			else
				$arr_value = getMonthName($arr_table[3])." ".$arr_table[2];
			
			$arr_retval[$arr_key] = $arr_value;
		}
		
//		$conn->setFetchMode(MDB2_FETCHMODE_OBJECT);
		
		return $arr_retval;
	}

?>