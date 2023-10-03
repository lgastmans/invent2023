<?
	if (IsSet($_GET["display_price"])) {
		if ($_GET["display_price"] == "B")
			$str_price = "sb.buying_price";
		else
			$str_price = "sb.selling_price";
	}
	else
		$str_price = "sb.buying_price";
?>

<html>
    <head>
        <link rel="stylesheet" type="text/css" href="../include/styles.css" />
    </head>
<body id='body_bgcolor' bottommargin='0'>
	
	<table width='100%' cellpadding='0' cellspacing='0'>
	<tr><td align='left'>
	
		<table border=1 cellpadding=7 cellspacing=0>
			<tr class='normaltext_bold' bgcolor='lightgrey'>
				<td width='120px'>Date</td>
				<td width='100px' >Code</td>
				<td width='300px'>Description</td>
				<td width='100px'><? if ($str_price == "sb.buying_price") echo "Buying Price"; else echo "Selling Price";?></td>
				<td width='100px' >Quantity</td>
				<td width='100px' >Amount</td>
				<td width='100px' >User</td>
			</tr>
                </table>
                
        </td></tr>
        </table>
        
</body>
</html>