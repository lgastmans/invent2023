<?
	require_once('../include/const.inc.php');
	require_once("db_mysqli.php");
	
	if (IsSet($_GET['id']))
		delete_row($_GET['id']);
	
	function delete_row($int_id) {

		global $conn;
		
		$str_delete = "
			SELECT *
			FROM customer
			WHERE id = $int_id";

		$qry_delete = $conn->Query($str_delete);

		if ($qry_delete->num_rows > 0) {

			$obj = $qry_delete->fetch_object();
			
			$str_delete = "
				DELETE FROM customer
				WHERE id = $int_id
				LIMIT 1
			";
			$qry_delete = $conn->query($str_delete);
			
			if (!$qry_delete) {
				$arr_retval['replyCode'] = 400;
				$arr_retval['replyStatus'] = "Error";
				$arr_retval['replyText'] = "Error deleting : ".mysqli_error($conn);
			}
			else {
				$arr_retval['replyCode'] = 201;
				$arr_retval['replyStatus'] = "Ok";
				$arr_retval['replyText'] = "Client deleted successfully";
			}
		}
		else {
			$arr_retval['replyCode'] = 501;
			$arr_retval['replyStatus'] = "Error";
			$arr_retval['replyText'] = "Client not found in database ".$str_delete;
		}
		
		echo (json_encode($arr_retval));
	}
?>