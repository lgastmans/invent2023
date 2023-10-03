<?php
	require_once("../include/const.inc.php");
	require_once("../include/db.inc.php");
	require_once("../include/session.inc.php");
	
	$str_message = '';

	$flt_buying_price = 0;
	$flt_selling_price = 0;
	$str_use_batch_price = 'Y';
	$flt_minimum_quantity = 0;
	$code = '';
	$description = '';
	
	$int_id = 0;

	if (IsSet($_GET["id"])) {

		$int_id = $_GET['id'];
		
		$qry = new Query("
			SELECT ssp.*, sp.product_code, sp.product_description
			FROM ".Monthalize("stock_storeroom_product")." ssp
			INNER JOIN stock_product sp ON (sp.product_id = ssp.product_id)
			WHERE ssp.product_id = $int_id 
				AND ssp.storeroom_id = ".$_SESSION['int_current_storeroom']
		);
		if ($qry->RowCount() == 0) {
			$str_message = "ERROR: Product not found.";
		}
		
		$code = $qry->FieldByName('product_code');
		$description = $qry->FieldByName('product_description');
		$flt_buying_price = $qry->FieldByName('buying_price');
		$flt_selling_price = $qry->FieldByName('sale_price');
		$str_use_batch_price = $qry->FieldByName('use_batch_price');
		$flt_minimum_quantity = $qry->FieldByName('stock_minimum');
	}
	
?>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />

</head>

<body id='body_bgcolor' leftmargin=10 topmargin=10>

	<form id="product_edit" name="product_edit" method="POST">

	<? if ($str_message != '') echo "<font color=\"red\">".$str_message."</font>";?>

		<table width="100%" height="30" border="0" cellpadding="2" cellspacing="0">
			<tr>
				<td class='normaltext' align="right" width="120">Code</td>
				<td><input type="text" id="code" name="code" class='input_200' value="<? echo $code;?>" autocomplete="OFF"></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td class='normaltext' >
					<div id="description"><? echo $description; ?></div>
				</td>
			</tr>

			<?php if ($str_use_batch_price == 'Y') { ?>

				<tr>
					<td class='normaltext' align="right" width="120">Buying Price</td>
					<td><input type="text" id="buying_price" name="buying_price" class='input_200' value="N/A" disabled autocomplete="OFF" ></td>
				</tr>
				<tr>
					<td class='normaltext' align="right" width="120">Selling Price</td>
					<td><input type="text" id="selling_price" name="selling_price" class='input_200' value="N/A" disabled autocomplete="OFF" ></td>
				</tr>

			<?php } else { ?>

				<tr>
					<td class='normaltext' align="right" width="120">Buying Price</td>
					<td><input type="text" id="buying_price" name="buying_price" class='input_200' value="<? echo $flt_buying_price;?>" autocomplete="OFF" ></td>
				</tr>
				<tr>
					<td class='normaltext' align="right" width="120">Selling Price</td>
					<td><input type="text" id="selling_price" name="selling_price" class='input_200' value="<? echo $flt_selling_price;?>" autocomplete="OFF" ></td>
				</tr>

			<?php } ?>


			<tr>
				<td class='normaltext' align="right"></td>
				<td><input type="checkbox" id="use_batch_price" name="use_batch_price" <? if ($str_use_batch_price == 'Y') echo "checked";?> ><font class='normaltext'>Use batch price</font></td>
			</tr>
			<tr>
				<td class='normaltext' align="right">Minimum quantity</td>
				<td><input type="text" id="minimum_quantity" name="minimum_quantity" class='input_200' value="<? echo $flt_minimum_quantity;?>" autocomplete="OFF" ></td>
			</tr>
			<tr>
				<td align="right">
					<button type="button" value="save" name="action" id="btn-save" class="btn btn-default">Save</button>
				</td>
				<td>
				</td>
			</tr>
		</table>
		
		<input type="hidden" id="product_id" name="product_id" value="<?echo $int_id;?>">

	</form>


<script src="../include/js/jquery-3.2.1.min.js"></script>

<script language="javascript">
	document.product_edit.code.select();
	document.product_edit.code.focus();

	var BPrice = <?php echo $qry->FieldByName('buying_price'); ?>;
	var SPrice = <?php echo $qry->FieldByName('sale_price'); ?>;

	$(document).ready(function(){

		$(" #code ").keypress(function( event ) {

			if (( event.keyCode == 13 ) || ( event.keyCode == 9)) {

				event.preventDefault();

				$.ajax({
					method 	: "POST",
					url		: "viewstock_details.php",
					data 	: {product_code:$(this).val()}
				})
				.done( function( msg ) {

					obj = $.parseJSON(msg);

					$(" #product_id ").val(obj.product_id);
					$(" #description ").html(obj.description);
					$(" #minimum_quantity ").val(obj.minimum_quantity);

					BPrice = obj.buying_price;
					SPrice = obj.selling_price;

					if (obj.use_batch_price == 'Y') {

						$(" #use_batch_price ").prop( "checked", true );
						$(" #buying_price ").prop( "disabled", true );
						$(" #selling_price ").prop( "disabled", true );

						$(" #buying_price ").val('N/A');
						$(" #selling_price ").val('N/A');

						$(" #use_batch_price ").focus();

					} else {

						$(" #use_batch_price ").prop( "checked", false);
						$(" #buying_price ").prop( "disabled", false);
						$(" #selling_price ").prop( "disabled", false);

						$(" #buying_price ").val(obj.buying_price);
						$(" #selling_price ").val(obj.selling_price);


						$(" #buying_price ").focus();
					}
				});
			}
		});

		$(" #buying_price ").keypress(function(event) {
			if (( event.keyCode == 13 ) || ( event.keyCode == 9)) {

				event.preventDefault();
				$(" #selling_price ").focus();

			}
		});

		$(" #selling_price ").keypress(function(event) {
			if (( event.keyCode == 13 ) || ( event.keyCode == 9)) {

				event.preventDefault();
				$(" #use_batch_price ").focus();
			}
		});

		$(" #use_batch_price ").click(function() {

			if ($(this).is(':checked')) {
				$(" #buying_price ").prop( "disabled", true );
				$(" #selling_price ").prop( "disabled", true );

				$(" #buying_price ").val('N/A');
				$(" #selling_price ").val('N/A');
			}
			else {
				$(" #buying_price ").prop( "disabled", false);
				$(" #selling_price ").prop( "disabled", false);

				$(" #buying_price ").val(BPrice);
				$(" #selling_price ").val(SPrice);
			}
		});
		$(" #use_batch_price ").keypress(function(event) {
			if (( event.keyCode == 13 ) || ( event.keyCode == 9)) {

				event.preventDefault();
				$(" #minimum_quantity ").focus();
			}
		});

		$(" #minimum_quantity ").keypress(function(event) {
			if (( event.keyCode == 13 ) || ( event.keyCode == 9)) {

				event.preventDefault();
				$(" #btn-save ").focus();
			}
		});

		$(" #btn-save ").click(function(){

			if ($(" #use_batch_price ").is(':checked'))
				var use_batch_price = 'Y';
			else
				var use_batch_price = 'N';

			$.ajax({
				method 	: "POST",
				url		: "viewstock_save.php",
				data 	: { product_id: $(" #product_id ").val(), minimum_quantity: $(" #minimum_quantity ").val(), buying_price: $(" #buying_price ").val(), selling_price: $(" #selling_price ").val(), use_batch_price: use_batch_price}
			})
			.done(function( msg ) {
				obj = JSON.parse(msg);
				if (msg='successfully updated') {

					$(" #code ").val('');
					$(" #product_id ").val(null);
					$(" #description ").html('');					
					$(" #buying_price ").val('');
					$(" #selling_price ").val('');
					$(" #minimum_quantity ").val('1');

					$(" #code ").focus();

				}
			});

		})

	});

</script>

</body>
</html>