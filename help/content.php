<?
	require_once('../include/const.inc.php');
	require_once('db_params.php');
	require_once('JSON.php');
	
	$html_text = '';
	
	
	function str_stop($string, $start, $max_length) {
		if (strlen($string) > $max_length) {
			$string = substr($string, $start, $max_length);
			$pos = strrpos($string, " ");
			if($pos === false) {
					return substr($string, 0, $max_length)." [...]";
				}
			return substr($string, 0, $pos)." [...]";
		}
		else {
			return $string;
		}
	}
	
	$int_id = 0;
	if (IsSet($_GET['id'])) {
		$int_id = $_GET['id'];
		
		$str_query = "
			SELECT *
			FROM help
			WHERE id = $int_id
		";
		$qry =& $conn_help->query($str_query);

		$html_text = '';
		if ($qry->numRows() > 0) {
			$obj = $qry->fetchRow();
			$html_text = ($obj->html_text);
		}
	}
	else if (IsSet($_GET['search_results'])) {
		$arr_ids = explode(',', $_GET['search_results']);
		$str_search = $_GET['search'];
		
		$int_rows = count($arr_ids);
		for ($i=0;$i<$int_rows;$i++) {
			$qry =& $conn_help->query("SELECT * FROM help WHERE text LIKE '%$str_search%' AND id = ".$arr_ids[$i]);
			if ($qry->numRows() > 0) {
				$obj = $qry->fetchRow();
				$int_pos = strpos($obj->text, $str_search);
				$str_result = $obj->section_name." | ".str_stop($obj->text, $int_pos, 50)."<br><br>";
			
				$html_text .= $str_result;
			}
			$i++;
		}
	}
?>
<html>
<head>
	<style>
		body {
			margin: 20 20 20 20px;
		}
		
		a {
			font-family:Verdana,sans-serif;
			font-weight:bold;
			font-size:14px;
			color:#E7AA41;
			text-align:center;
		}

		a:link {
			text-decoration: none;
		}

		a:visited {
			text-decoration: none;
		}

		a:hover {
			color:#D29B3B;
			text-decoration: none;
		}
	</style>
</head>
<body>
<?	echo $html_text; ?>
</body>
</html>