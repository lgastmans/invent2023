<?php

require_once("../include/const.inc.php");
require_once("../include/session.inc.php");
require_once("../include/db.inc.php");

				$db_db = "wtinvent_2018_2019";
				$db_server =  $arr_invent_config['database']['invent_server'];
				$db_login = $arr_invent_config['database']['invent_login'];
				$db_password = $arr_invent_config['database']['invent_password'];
				
				$conn_backup = new mysqli($db_server, $db_login, $db_password, $db_db);

				echo $db_db;
				echo $db_server;
				echo $db_login;
				echo $db_password;

				if ($conn_backup->connect_errno) {
				    printf("Connect failed: %s\n", $conn_backup->connect_error);
				    exit();
				}


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
?>