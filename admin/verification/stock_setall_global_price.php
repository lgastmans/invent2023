<?
	include("../../include/const.inc.php");
	include("../../include/session.inc.php");
	include("../../include/db.inc.php");
	include("../../common/product_funcs.inc.php");
	
	
	//=================================
	// update the sale price to reflect
	// the latest batch price
	// ONLY IF THE use_batch_price is set to 'Y'
	//---------------------------------
	$qry_products = new Query("
		SELECT *
		FROM stock_product
	");

	for ($i=0;$i<=$qry_products->RowCount();$i++) {

		$flt_price = getSellingPrice($qry_products->FieldByName('product_id'));
		$b_price = getBuyingPrice($qry_products->FieldByName('product_id'));

		$qry = new Query("
			UPDATE ".Monthalize('stock_storeroom_product')."
			SET sale_price = $flt_price,
				buying_price = $b_price
			WHERE (product_id = ".$qry_products->FieldByName('product_id').")
				AND (use_batch_price = 'Y')
		");
		$qry_products->Next();
	}
	

	//===============================
	// set the use_batch_price to 'N'
	// for all products
	//-------------------------------
	$str_reset = "
		UPDATE ".Monthalize('stock_storeroom_product')." ssp
		SET use_batch_price = 'N'
		WHERE storeroom_id = ".$_SESSION['int_current_storeroom'];
	$qry = new Query($str_reset);


	$sql = "ALTER TABLE ".Monthalize('stock_storeroom_product')." CHANGE `use_batch_price` `use_batch_price` CHAR(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'N'";
	$qry = new Query($sql);

?>
<html>
<head>
    <link href="../../include/styles.css" rel="stylesheet" type="text/css">
    <script language='javascript'>
        function goBack() {
            document.location = '../index_verification_tools.php?action=prices';
        }
    </script>
</head>

<body leftmargin='20px' rightmargin='20px' topmargin='20px' bottommargin='20px'>

<?
    boundingBoxStart("800", "../../images/blank.gif");
?>
    <br>
    <div class='normaltext'>Successfully updated all products.</div>
    <br>
    <input type='button' name='action' value='Back' class='settings_button' onclick='javascript:goBack()'>
    <br><br>
<?
    boundingBoxEnd("800", "../../images/blank.gif");
?>

</body>
</html>