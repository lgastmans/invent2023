<html>
	<body>
<?
	$num1 = 1.33395;
	$num2 = 1.11345;

	$fmt1 = number_format($num1, 3);
	$fmt2 = number_format($num2, 3);

	$total = $fmt1 + $fmt2;

	echo $fmt1."<br>";
	echo $fmt2."<br>";
	echo $total."<br>";
?>

	</body>
</html>