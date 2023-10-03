<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	
/*

ALTER TABLE `customer` ADD `ship_address` VARCHAR(80) NULL AFTER `gstin`, ADD `ship_address2` VARCHAR(60) NULL AFTER `ship_address`, ADD `ship_city` VARCHAR(30) NULL AFTER `ship_address2`, ADD `ship_zip` VARCHAR(10) NULL AFTER `ship_city`;

ALTER TABLE `customer` ADD `ship_company` VARCHAR(60) NULL AFTER `ship_zip`;

ALTER TABLE `customer` ADD `ship_state` VARCHAR(50) NULL AFTER `ship_company`, ADD `ship_state_code` VARCHAR(25) NULL AFTER `ship_state`, ADD `ship_gstin` VARCHAR(50) NULL AFTER `ship_state_code`;

ALTER TABLE `customer` ADD `same_address` CHAR(1) NOT NULL DEFAULT 'Y' AFTER `ship_gstin`;
*/

	$str_message = '';

	$int_id = -1;
	if (IsSet($_GET['id'])) {
		$int_id = $_GET['id'];
	}

	if (IsSet($_POST['action'])) {

		if ($_POST['action'] == 'save') {

			$str_is_active = 'N';
			if (IsSet($_POST['is_active']))
				$str_is_active = 'Y';
				
			$str_can_view_price = 'N';
			if (IsSet($_POST['can_view_price']))
				$str_can_view_price = 'Y';

			$same_address = 'N';
			if (IsSet($_POST['same_address']))
				$same_address = 'Y';

				
			if (IsSet($_POST['id'])) {
				$int_id = $_POST['id'];
				
				$str_query = "
					UPDATE customer
					SET	customer_id = '".($_POST['customer_id'])."',
						fs_account = '".$_POST['fs_account']."',
						company = '".($_POST['company'])."',
						address = '".($_POST['address'])."',
						address2 = '".($_POST['address2'])."',
						city = '".($_POST['city'])."',
						zip = '".($_POST['zip'])."',
						ship_company = '".($_POST['ship_company'])."',
						ship_address = '".($_POST['ship_address'])."',
						ship_address2 = '".($_POST['ship_address2'])."',
						ship_city = '".($_POST['ship_city'])."',
						ship_zip = '".($_POST['ship_zip'])."',
						ship_state = '".$_POST['ship_state']."',
						ship_gstin = '".$_POST['ship_gstin']."',
						phone1 = '".($_POST['phone1'])."',
						phone2 = '".($_POST['phone2'])."',
						fax = '".($_POST['fax'])."',
						email = '".($_POST['email'])."',
						cell = '".($_POST['cell'])."',
						contact_person = '".($_POST['contact_person'])."',
						sales_tax_no = '".($_POST['sales_tax_no'])."',
						sales_tax_type = '".($_POST['sales_tax_type'])."',
						tax_id = ".($_POST['select_tax']).",
						currency_id = ".$_POST['select_currency'].",
						sur = '".($_POST['sur'])."',
						surcharge = ".($_POST['surcharge']).",
						delivery_address = '".($_POST['delivery_address'])."',
						discount = ".($_POST['discount']).",
						payment_terms = '".($_POST['payment_terms'])."',
						payment_type = '".($_POST['payment_type'])."',
						username = '".($_POST['username'])."',
						password = '".($_POST['password'])."',
						is_active = '".$str_is_active."',
						can_view_price = '".$str_can_view_price."',
						is_modified = 'Y',
						price_increase = ".$_POST['price_increase'].",
						is_other_state = '".$_POST['is_other_state']."',
						state = '".$_POST['state']."',
						gstin = '".$_POST['gstin']."',
						same_address = '".$same_address."',
						country_id = '".$_POST['country_id']."',
						ship_country_id = '".$_POST['ship_country_id']."'
					WHERE  (Id = $int_id)
				";
				$qry = new Query($str_query);
				if ($qry->b_error == true)
					$str_message = 'Error updating customer information'.$str_query;
				else {
					echo "<script language='javascript'>\n;";
					echo "if (top.window.opener)\n";
					echo "top.window.opener.document.location=top.window.opener.document.location.href;\n";
					echo "top.window.close();\n";
					echo "</script>";

				}
			}
			else {
				$str_query = "
					INSERT INTO customer
					(
						customer_id,
						fs_account,
						company,
						address,
						address2,
						city,
						zip,
						ship_company,
						ship_address,
						ship_address2,
						ship_city,
						ship_zip,
						ship_state,
						ship_gstin,
						phone1,
						phone2,
						fax,
						email,
						cell,
						contact_person,
						sales_tax_no,
						sales_tax_type,
						tax_id,
						currency_id,
						sur,
						surcharge,
						delivery_address,
						discount,
						payment_terms,
						payment_type,
						username,
						password,
						is_active,
						can_view_price,
						is_modified,
						price_increase,
						is_other_state,
						state,
						gstin,
						same_address,
						country_id,
						ship_country_id
					)
					VALUES (
						'".($_POST['customer_id'])."',
						'".($_POST['fs_account'])."',
						'".($_POST['company'])."',
						'".($_POST['address'])."',
						'".($_POST['address2'])."',
						'".($_POST['city'])."',
						'".($_POST['zip'])."',
						'".($_POST['ship_company'])."',
						'".($_POST['ship_address'])."',
						'".($_POST['ship_address2'])."',
						'".($_POST['ship_city'])."',
						'".($_POST['ship_zip'])."',
						'".($_POST['ship_state'])."',
						'".($_POST['ship_gstin'])."',
						'".($_POST['phone1'])."',
						'".($_POST['phone2'])."',
						'".($_POST['fax'])."',
						'".($_POST['email'])."',
						'".($_POST['cell'])."',
						'".($_POST['contact_person'])."',
						'".($_POST['sales_tax_no'])."',
						'".($_POST['sales_tax_type'])."',
						".($_POST['select_tax']).",
						".$_POST['select_currency'].",
						'".($_POST['sur'])."',
						".($_POST['surcharge']).",
						'".($_POST['delivery_address'])."',
						".($_POST['discount']).",
						'".($_POST['payment_terms'])."',
						'".($_POST['payment_type'])."',
						'".($_POST['username'])."',
						'".($_POST['password'])."',
						'".$str_is_active."',
						'".$str_can_view_price."',
						'Y',
						".$_POST['price_increase'].",
						'".$_POST['is_other_state']."',
						'".$_POST['state']."',
						'".$_POST['gstin']."',
						'".$same_address."',
						'".$_POST['country_id']."',
						'".$_POST['ship_country_id']."'
					)
				";
				$qry = new Query($str_query);
				if ($qry->b_error == true) {
					$str_message = 'Error inserting new customer<br>';
					echo $str_query;
				}
				else {
					echo "<script language='javascript'>\n;";
					echo "if (top.window.opener)\n";
					echo "top.window.opener.document.location=top.window.opener.document.location.href;\n";
					echo "top.window.close();\n";
					echo "</script>";
				}
			}
		}
	}

	$str_customer_id = '';
	$str_fs_account = '';
	$str_company = '';
	$str_address = '';
	$str_address2 = '';
	$str_city = '';
	$str_zip = '';
	$str_ship_company = '';
	$str_ship_address = '';
	$str_ship_address2 = '';
	$str_ship_city = '';
	$str_ship_zip = '';
	$str_ship_state = '';
	$str_ship_state_code = '';
	$str_ship_gstin = '';
	$str_phone1 = '';
	$str_phone2 = '';
	$str_fax = '';
	$str_email = '';
	$str_cell = '';
	$str_contact_person = '';
	$str_sales_tax_no = '';
	$str_sales_tax_type = '';
	$int_tax_id = 0;
	$int_currency_id = 0;
	$str_sur = '';
	$flt_surcharge = 0;
	$str_delivery_address = '';
	$flt_discount = 0;
	$str_payment_terms = '';
	$str_payment_type = '';
	$str_username = '';
	$str_password = '';
	$str_is_active = 'Y';
	$str_can_view_price = 'Y';
	$flt_price_increase = 0;
	$is_other_state = 'N';
	$str_state = '';
	$str_state_code = '';
	$str_gstin = '';
	$same_address = 'Y';

	$int_state_code = 0;
	$int_ship_state_code = 0;

	$country_id = 0;
	$ship_country_id = 0;

	if ($int_id > 0) {

		$str_query = "
			SELECT *, c1.name AS country, c2.name AS ship_country
			FROM customer c
			LEFT JOIN countries c1 ON (c1.id = c.country_id)
			LEFT JOIN countries c2 ON (c2.id = c.ship_country_id)
			WHERE c.id = $int_id";

		$qry = new Query($str_query);

		if ($qry->RowCount() > 0) {
			$str_customer_id = $qry->FieldByName('customer_id');
			$str_fs_account = $qry->FieldByName('fs_account');
			$str_company = ($qry->FieldByName('company'));
			$str_address = ($qry->FieldByName('address'));
			$str_address2 =  ($qry->FieldByName('address2'));
			$str_city = $qry->FieldByName('city');
			$str_zip = $qry->FieldByName('zip');
			$str_ship_company = ($qry->FieldByName('ship_company'));
			$str_ship_address = $qry->FieldByName('ship_address');
			$str_ship_address2 = $qry->FieldByName('ship_address2');
			$str_ship_city = $qry->FieldByName('ship_city');
			$str_ship_zip = $qry->FieldByName('ship_zip');
			$str_ship_gstin = $qry->FieldByName('ship_gstin');
			$str_phone1 = $qry->FieldByName('phone1');
			$str_phone2 = $qry->FieldByName('phone2');
			$str_fax = $qry->FieldByName('fax');
			$str_email = $qry->FieldByName('email');
			$str_cell = $qry->FieldByName('cell');
			$str_contact_person = $qry->FieldByName('contact_person');
			$str_sales_tax_no = $qry->FieldByName('sales_tax_no');
			$str_sales_tax_type = $qry->FieldByName('sales_tax_type');
			$int_tax_id = $qry->FieldByName('tax_id');
			$int_currency_id = $qry->FieldByName('currency_id');
			$str_sur = $qry->FieldByName('sur');
			$flt_surcharge = $qry->FieldByName('surcharge');
			$str_delivery_address = $qry->FieldByName('delivery_address');
			$flt_discount = $qry->FieldByName('discount');
			$str_payment_terms = $qry->FieldByName('payment_terms');
			$str_payment_type = $qry->FieldByName('payment_type');
			$str_username = $qry->FieldByName('username');
			$str_password = $qry->FieldByName('password');
			$str_is_active = $qry->FieldByName('is_active');
			$str_can_view_price = $qry->FieldByName('can_view_price');
			$flt_price_increase = $qry->FieldByName('price_increase');
			$is_other_state = $qry->FieldByName('is_other_state');
			$str_gstin = $qry->FieldByName('gstin');
			$same_address = $qry->FieldByName('same_address');

			$str_state = $qry->FieldByName('state');
			$str_ship_state = $qry->FieldByName('ship_state');

			$country_id = $qry->FieldByName('country_id');
			$ship_country_id = $qry->FieldByName('ship_country_id');

			$str_country = $qry->FieldByName('country');
			$str_ship_country = $qry->FieldByName('ship_country');
		}
	}


	$qry_tax = new Query("
		SELECT tax_id, tax_description
		FROM ".Monthalize('stock_tax')
	);
	
	$qry_currency = new Query("
		SELECT *
		FROM stock_currency
		ORDER BY currency_name
	");

	$qry_state = new Query("
		SELECT *
		FROM state_codes
		ORDER BY state
	")
?>

<script language="javascript">

	function saveCustomer() {
		document.viewcustomer.submit();
	}

	function CloseWindow() {
		if (top.window.opener)
			top.window.opener.document.location=top.window.opener.document.location.href;
		top.window.close();
	}

</script>



<html>
<head><title></title>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
    <link href="../include/js/jquery-typeahead-2.10.1/dist/jquery.typeahead.min.css" rel="stylesheet">	
    <style>
    	.typeahead__list {
    		min-width:300px !important;
    	}

    	#result-container-description {
    		font-style: normal;
    	}
	</style>    
</head>

<body id="body_bgcolor" leftmargin="2" topmargin="2" marginwidth="4" marginheight="4">
<form name='viewcustomer' method='POST'>
<?
	if ($int_id > -1)
		echo "<input type='hidden' name='id' value='".$int_id."'>";
 
	if ($str_message <> '')
		echo "<font color='red'>$str_message</font>";
?>



	<table class="edit" border='0' cellpadding='2' cellspacing='2'>
		<tr>

			<td colspan="2">
				<table>
					<td align='right' class='normaltext' width="200px">Customer Id</td>
					<td ><input type='text' name='customer_id' value='<?echo $str_customer_id?>' class="input_150"></td>

					<td align='right' class='normaltext' width="200px">FS Account</td>
					<td><input type="text" name="fs_account" value="<? echo $str_fs_account;?>" class="input_150"></td>
				</table>
			</td>

		</tr>

		<tr>
			<td colspan="2">

				<table style="border-top: solid 2px grey;">
					<tr>
						<td width="120px"></td>
						<td class='normaltext'><b>Billed To</b></td>
						<td width="120px"></td>
						<td class='normaltext'><b>Shipped To </b><label class="normaltext"><input type="checkbox" name="same_address" <?php if ($same_address=='Y') echo "checked"; ?>>same as "Billed To"</label></td>
					</tr>

					<tr>
						<td align='right' class='normaltext'>Company</td>
						<td ><input type='text' name='company' value="<?echo $str_company?>" class="input_400"></td>
						<td align='right' class='normaltext'>Company</td>
						<td ><input type='text' name='ship_company' value="<?echo $str_ship_company?>" class="input_400"></td>
					</tr>
					<tr>
						<td align='right' class="normaltext">Address</td>
						<td ><input type='text' name='address' value="<?echo $str_address?>" class="input_400"></td>
						<td align='right' class="normaltext">Address</td>
						<td><input type='text' name='ship_address' value="<?echo $str_ship_address?>" class="input_400"></td>
					</tr>
					<tr>
						<td align='right' class="normaltext">Address 2</td>
						<td><input type='text' name='address2' value="<?echo $str_address2?>" class="input_400"></td>
						<td align='right' class="normaltext">Address 2</td>
						<td><input type='text' name='ship_address2' value="<?echo $str_ship_address2?>" class="input_400"></td>
					</tr>
					<tr>
						<td align='right' class='normaltext'>City</td>
						<td><input type='text' name='city' value='<?echo $str_city?>' class="input_400"></td>
						<td align='right' class='normaltext'>City</td>
						<td><input type='text' name='ship_city' value='<?echo $str_ship_city?>' class="input_400"></td>
					</tr>
					<tr>
						<td align='right' class='normaltext'>Zip</td>
						<td><input type='text' name='zip' value='<?echo $str_zip?>' class="input_400"></td>
						<td align='right' class='normaltext'>Zip</td>
						<td><input type='text' name='ship_zip' value='<?echo $str_ship_zip?>' class="input_400"></td>
					</tr>
					<tr>
						<td align='right' class='normaltext'>State</td>
						<td >

							<div class="typeahead__container">
								<div class="typeahead__field">
									<span class="typeahead__query">
										<input class="typeahead-state" value="<?php echo $str_state;?>" id="state_name" autocomplete="off" name="state" type="search" placeholder="State" autocomplete="off">
									</span>
								</div>
							</div>


						</td>
						<td align='right' class='normaltext'>State</td>
						<td >

							<div class="typeahead__container">
								<div class="typeahead__field">
									<span class="typeahead__query">
										<input class="typeahead-ship-state" value="<?php echo $str_ship_state;?>" id="ship_state" autocomplete="off" name="ship_state" type="search" placeholder="State" autocomplete="off">
									</span>
								</div>
							</div>


						</td>
					</tr>
					<tr>
						<td align='right' class='normaltext'>Country</td>
						<td >

							<div class="typeahead__container">
								<div class="typeahead__field">
									<span class="typeahead__query">
										<input class="typeahead-country" value="<?php echo $str_country;?>" id="country" autocomplete="off" name="country" type="search" placeholder="Country" autocomplete="off">
										<input type="hidden" id="country_id" name="country_id" value="<?php echo $country_id;?>">
									</span>
								</div>
							</div>


						</td>
						<td align='right' class='normaltext'>Country</td>
						<td >

							<div class="typeahead__container">
								<div class="typeahead__field">
									<span class="typeahead__query">
										<input class="typeahead-ship-country" value="<?php echo $str_ship_country;?>" id="ship_country" autocomplete="off" name="ship_country" type="search" placeholder="Country" autocomplete="off">
										<input type="hidden" id="ship_country_id" name="ship_country_id" value="<?php echo $ship_country_id;?>">
									</span>
								</div>
							</div>


						</td>
					</tr>


					<tr>
						<td align='right' class='normaltext'>GSTIN</td>
						<td ><input type='text' name='gstin' value='<?echo $str_gstin?>' class="input_400"></td>
						<td align='right' class='normaltext'>GSTIN</td>
						<td ><input type='text' name='ship_gstin' value='<?echo $str_ship_gstin?>' class="input_400"></td>
					</tr>

				</table>

			</td>

		</tr>


		<tr>

			<td colspan="2">

				<table style="border-top: solid 1px grey;">


					<tr>
						<td width="120px" align='right' class='normaltext'>Phone</td>
						<td ><input type='text' name='phone1' value='<?echo $str_phone1?>' class="input_400"></td>
						<td width="120px" align='right' class='normaltext'>Phone 2</td>
						<td ><input type='text' name='phone2' value='<?echo $str_phone2?>' class="input_400"></td>
					</tr>

					<tr>
						<td align='right' class='normaltext'>Fax</td>
						<td ><input type='text' name='fax' value='<?echo $str_fax?>' class="input_400"></td>
						<td align='right' class='normaltext'>Email</td>
						<td ><input type='text' name='email' value='<?echo $str_email?>' class="input_400"></td>
					</tr>

					<tr>
						<td align='right' class='normaltext'>Cell</td>
						<td ><input type='text' name='cell' value='<?echo $str_cell?>' class="input_400"></td>
						<td align='right' class='normaltext'>Contact Person</td>
						<td ><input type='text' name='contact_person' value='<?echo $str_contact_person?>' class="input_400"></td>
					</tr>

					<tr>
						<td align='right' class='normaltext'>Sales Tax No</td>
						<td ><input type='text' name='sales_tax_no' value='<?echo $str_sales_tax_no?>' class="input_400"></td>
						<td align='right' class='normaltext'>Sales Tax Type</td>
						<td ><input type='text' name='sales_tax_type' value='<?echo $str_sales_tax_type?>' class="input_400"></td>
					</tr>

					<tr>
						<td align='right' class='normaltext'>Sales Tax No</td>
						<td ><input type='text' name='sales_tax_no' value='<?echo $str_sales_tax_no?>' class="input_400"></td>
						<td align='right' class='normaltext'>Sales Tax Type</td>
						<td ><input type='text' name='sales_tax_type' value='<?echo $str_sales_tax_type?>' class="input_400"></td>
					</tr>

					<tr>
						<td align='right' class='normaltext'>Tax</td>
						<td>
							<select name="select_tax" class='select_300'>
							<? 
								for ($i=0; $i<$qry_tax->RowCount(); $i++) {
									if ($qry_tax->FieldByName('tax_id') == $int_tax_id)
										echo "<option value=".$qry_tax->FieldByName('tax_id')." selected>".$qry_tax->FieldByName('tax_description');
									else
										echo "<option value=".$qry_tax->FieldByName('tax_id').">".$qry_tax->FieldByName('tax_description');
									$qry_tax->Next();
								}
							?>
							</select>
						</td>

						<td align="right" class="normaltext">Currency</td>
						<td>
							<select name="select_currency" class="select_300">
							<?
								for ($i=0;$i<$qry_currency->RowCount();$i++) {
									if ($qry_currency->FieldByName('currency_id') == $int_currency_id)
										echo "<option value=".$qry_currency->FieldByName('currency_id')." selected>".$qry_currency->FieldByName('currency_name');
									else
										echo "<option value=".$qry_currency->FieldByName('currency_id').">".$qry_currency->FieldByName('currency_name');
									$qry_currency->Next();
								}
							?>
							</select>
						</td>
					</tr>

					<tr>
						<td align='right' class='normaltext'>Sur</td>
						<td>
							<input type='text' name='sur' value='<?echo $str_sur?>' class="input_150">
						</td>
						
						<td align='right' class='normaltext'>Price %</td>
						<td>
							<input type='text' name='price_increase' value='<?echo $flt_price_increase?>' class="input_150">
						</td>
					</tr>

					<tr>
						<td align='right' class='normaltext'>Is Other State</td>
						<td >
							<select name="is_other_state" class="select_150">
								<option value="Y" <?php if ($is_other_state=='Y') echo "selected";?>>Yes</option>
								<option value="N" <?php if ($is_other_state=='N') echo "selected";?>>No</option>
							</select>
						</td>
						<td align='right' class='normaltext'>Surcharge</td>
						<td ><input type='text' name='surcharge' value='<?echo $flt_surcharge?>' class="input_400"></td>
					</tr>

					<tr>
						<td align='right' class='normaltext'>Delivery Address</td>
						<td ><input type='text' name='delivery_address' value='<?echo $str_delivery_address?>' class="input_400"></td>
						<td align='right' class='normaltext'>Discount</td>
						<td ><input type='text' name='discount' value='<?echo $flt_discount?>' class="input_400"></td>
					</tr>

					<tr>
						<td align='right' class='normaltext'>Payment Term</td>
						<td ><input type='text' name='payment_terms' value='<?echo $str_payment_terms?>' class="input_400"></td>
						<td align='right' class='normaltext'>Payment Type</td>
						<td ><input type='text' name='payment_type' value='<?echo $str_payment_type?>' class="input_400"></td>
					</tr>

					<tr>
						<td align='right' class='normaltext'>Username</td>
						<td ><input type='text' name='username' value='<?echo $str_username?>' class="input_400"></td>
						<td align='right' class='normaltext'>Password</td>
						<td ><input type='text' name='password' value='<?echo $str_password?>' class="input_400"></td>
					</tr>


				</table>

			</td>

		</tr>






		<tr>
			<td align='right' class='normaltext'></td>
			<td >
				<label class="normaltext">
				<input type='checkbox' name='is_active' <?if ($str_is_active == 'Y') echo 'checked';?>'>This client is active
				</label>
				&nbsp;
				<label class="normaltext">
				<input type='checkbox' name='can_view_price' <?if ($str_can_view_price == 'Y') echo 'checked';?>'>Can view prices
				</label>
			</td>
		</tr>
		<tr>
			<td align='right'>
				<input type='hidden' name='action' value='save'>
				<input type="button" class="v3button" name="button_save" value="Save" onclick="javascript:saveCustomer()">
			</td>
			<td colspan="3">
				<input type="button" name="button_close" value="Close" class="v3button" onclick="CloseWindow()">
			</td>
		</tr>
	</table>
</form>

<!-- <script src="../include/js/jquery-1.11.1.min.js"></script> -->

<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<script src="../include/js/jquery-3.2.1.min.js"></script>

<!-- Include all compiled plugins (below), or include individual files as needed -->
<script src="../include/bootstrap-3.3.4-dist/js/bootstrap.min.js"></script>

<script type="text/javascript" charset="utf8" src="../include/js/jquery-typeahead-2.10.6/dist/jquery.typeahead.min.js"></script>


<script>
	
	$(document).ready(function(){

			$('.typeahead-state').typeahead({

			    order		: "asc",
			    display 	: ["code", "state"],
			    templateValue: "{{state}}",
			    emptyTemplate: "No results found for {{query}}",
			    autoselect	: true,
			    source		: {
			        products: {
			            ajax: {
			                url: "data/get_states.php"
			            }
			        }
			    },
			    callback: {
			        onClickAfter: function (node, a, item, event) {
			 
			            event.preventDefault();
			 			
						//$("#state_code").html(item.code);

			        },
			        onResult: function(node, query, result, resultCount) {

						//$("#state_code").html('');
/*
						if (query === "") {
							$("#state_code").val('');
							return;
						} 
/*
						var text = "";
						if (result.length > 0 && result.length < resultCount) {
						    text = "Showing <strong>" + result.length + "</strong> of <strong>" + resultCount + '</strong> elements matching "' + query + '"';
						} else if (result.length > 0) {
						    text = 'Showing <strong>' + result.length + '</strong> elements matching "' + query + '"';
						} else {
						    text = 'No results matching "' + query + '"';
						}
						$('#state_code').html(text);			        
*/
					}
				}

			});


			$('.typeahead-ship-state').typeahead({

			    order		: "asc",
			    display 	: ["code", "state"],
			    templateValue: "{{state}}",
			    emptyTemplate: "No results found for {{query}}",
			    autoselect	: true,
			    source		: {
			        products: {
			            ajax: {
			                url: "data/get_states.php"
			            }
			        }
			    },
			    callback: {
			        onClickAfter: function (node, a, item, event) {
			 
			            event.preventDefault();
			 			
//			            console.log(item);

						//$("#ship_state_code").val(item.code);

			        }
				}

			});


			$('.typeahead-country').typeahead({

			    order		: "asc",
			    display 	: ["name"],
			    templateValue: "{{name}}",
			    emptyTemplate: "No results found for {{query}}",
			    autoselect	: true,
			    source		: {
			        products: {
			            ajax: {
			                url: "data/get_countries.php"
			            }
			        }
			    },
			    callback: {
			        onClickAfter: function (node, a, item, event) {
			 
			            event.preventDefault();
			            $("#country_id").val(item.id);
			 			
			        },
			        onResult: function(node, query, result, resultCount) {
			        	$("#country_id").val('');
			        }
				}

			});

			$('.typeahead-ship-country').typeahead({

			    order		: "asc",
			    display 	: ["name"],
			    templateValue: "{{name}}",
			    emptyTemplate: "No results found for {{query}}",
			    autoselect	: true,
			    source		: {
			        products: {
			            ajax: {
			                url: "data/get_countries.php"
			            }
			        }
			    },
			    callback: {
			        onClickAfter: function (node, a, item, event) {
			 
			            event.preventDefault();
			            $("#ship_country_id").val(item.id);
			 			
			        },
			        onResult: function(node, query, result, resultCount) {
			        	$("#ship_country_id").val('');
			        }
				}

			});


	}); // end document ready		

</script>

</body>
</html>
