<?
	/*
		get the column headers
	*/
	$str_query = "
		SELECT columnname
		FROM yui_grid
		WHERE gridname = '$grid_name'
			AND user_id = ".$_SESSION['int_user_id']."
			AND (visible='Y')
		ORDER BY position";
	$qry_fields = & $conn->query($str_query);

	if (MDB2::isError($conn)) {
		echo "error".$qry_fields->getDebugInfo();
	}

	$arr_headers = array();
	if ($qry_fields->numRows() > 0) {
		$i=0;
		while ($obj = $qry_fields->fetchRow()) {
			$arr_headers[$i] = $obj->columnname;
			$i++;
		}
	}

	/*
		the data to print
	*/
	$arr_print = $returnValue['records'];
?>
<html>
<head>
	<style>
		.printtext {
			font-family:verdana, sans-serif;
			font-size:10px;
			color:black;
		}
		.printheader {
			font-family:verdana, sans-serif;
			font-size:10px;
			font-weight:bold;
			color:black;
		}
	</style>
</head>
<body>

<table width="100%" class="printtext" cellpadding="1" cellspacing="0" border="1">
<?
	if (count($arr_print) > 0) {
		echo "<tr class='printheader'>";
		foreach ($arr_headers as $key=>$value) {
			echo "<td>".$value."</td>\n";
		}
		echo "</tr>\n";
		
		foreach ($arr_print as $value) {
			echo "<tr class='printtext'>";
			$arr_row = $value;
			foreach ($arr_row as $row) {
				if (empty($row))
					echo "<td>&nbsp;</td>\n";
				else
					echo "<td>".$row."</td>\n";
			}
			echo "</tr>\n";
		}
	}
?>
</table>

<script language="javascript">
	setTimeout("window.print();",1000);
</script>
</body>
</html>