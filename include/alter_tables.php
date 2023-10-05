<html>
<body>
<? 
	require_once("db.inc.php");

	$qry = new Query("SHOW TABLES");
	$qry_alter = new Query("SELECT * FROM account_cc");

	echo $qry->RowCount()."<br>";

	while ($row = mysql_fetch_array($qry->query, MYSQL_NUM)) {
		$qry_alter->Query("ALTER TABLE ".$row[0]." TYPE= INNODB");
		echo "table ".$row[0]." modified<br>";
	}
?>
</body>
</html>