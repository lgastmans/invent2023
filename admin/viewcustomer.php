<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	
	if ($_SESSION['str_user_font_size'] == 'small') {
		$str_class_header = "headertext_small";
		$str_class_input = "inputbox60_small";
		$str_class_input100 = "inputbox300_small";
		$str_class_select = "select_small";
		$str_class_select100 = "select100_small";
	}
	else if ($_SESSION['str_user_font_size'] == 'standard') {
		$str_class_header = "headertext";
		$str_class_input = "inputbox60";
		$str_class_input100 = "inputbox300";
		$str_class_select = "select";
		$str_class_select100 = "select100";
	}
	else if ($_SESSION['str_user_font_size'] == 'large') {
		$str_class_header = "headertext_large";
		$str_class_input = "inputbox60_large";
		$str_class_input100 = "inputbox300_large";
		$str_class_select = "select_large";
		$str_class_select100 = "select100_large";
	}
	else {
		$str_class_header = "headertext";
		$str_class_input = "inputbox60";
		$str_class_input100 = "inputbox300";
		$str_class_select = "select";
		$str_class_select100 = "select100";
	}
	
	if ($_SESSION['str_user_color_scheme'] == 'standard')
		$str_css_filename = 'bill_styles.css';
	else if ($_SESSION['str_user_color_scheme'] == 'blue')
		$str_css_filename = 'bill_styles_blue.css';
	else if ($_SESSION['str_user_color_scheme'] == 'purple')
		$str_css_filename = 'bill_styles_purple.css';
	else if ($_SESSION['str_user_color_scheme'] == 'green')
		$str_css_filename = 'bill_styles_green.css';
	else
		$str_css_filename = 'bill_styles.css';

	$str_message = '';

	$int_id = 0;
	if (IsSet($_GET['id'])) {
		$int_id = $_GET['id'];
	}

	if (IsSet($_POST['action'])) {
		if ($_POST['action'] == 'save') {
			if (IsSet($_POST['id'])) {
				$int_id = $_POST['id'];
				$str_query = "
					UPDATE customer
					SET	customer_id = '".addslashes($_POST['customer_id'])."',
						company = '".addslashes($_POST['company'])."',
						address = '".addslashes($_POST['address'])."',
						address2 = '".addslashes($_POST['address2'])."',
						city = '".addslashes($_POST['city'])."',
						zip = '".addslashes($_POST['zip'])."',
						phone1 = '".addslashes($_POST['phone1'])."',
						phone2 = '".addslashes($_POST['phone2'])."',
						fax = '".addslashes($_POST['fax'])."',
						email = '".addslashes($_POST['email'])."',
						cell = '".addslashes($_POST['cell'])."',
						contact_person = '".addslashes($_POST['contact_person'])."',
						sales_tax_no = '".addslashes($_POST['sales_tax_no'])."',
						sales_tax_type = '".addslashes($_POST['sales_tax_type'])."',
						tax = ".($_POST['tax']).",
						sur = '".addslashes($_POST['sur'])."',
						surcharge = ".($_POST['surcharge']).",
						delivery_address = '".addslashes($_POST['delivery_address'])."',
						discount = ".($_POST['discount']).",
						payment_terms = '".addslashes($_POST['payment_terms'])."',
						payment_type = '".addslashes($_POST['payment_type'])."'
					WHERE  (Id = $int_id)
				";
				$qry = new Query($str_query);
				if ($qry->b_error == true)
					$str_message = 'Error updating customer information';
			}
			else {
				$str_query = "
					INSERT INTO customer
					(
						customer_id,
						company,
						address,
						address2,
						city,
						zip,
						phone1,
						phone2,
						fax,
						email,
						cell,
						contact_person,
						sales_tax_no,
						sales_tax_type,
						tax,
						sur,
						surcharge,
						delivery_address,
						discount,
						payment_terms,
						payment_type
					)
					VALUES (
						'".addslashes($_POST['customer_id'])."',
						'".addslashes($_POST['company'])."',
						'".addslashes($_POST['address'])."',
						'".addslashes($_POST['address2'])."',
						'".addslashes($_POST['city'])."',
						'".addslashes($_POST['zip'])."',
						'".addslashes($_POST['phone1'])."',
						'".addslashes($_POST['phone2'])."',
						'".addslashes($_POST['fax'])."',
						'".addslashes($_POST['email'])."',
						'".addslashes($_POST['cell'])."',
						'".addslashes($_POST['contact_person'])."',
						'".addslashes($_POST['sales_tax_no'])."',
						'".addslashes($_POST['sales_tax_type'])."',
						".($_POST['tax']).",
						'".addslashes($_POST['sur'])."',
						".($_POST['surcharge']).",
						'".addslashes($_POST['delivery_address'])."',
						".($_POST['discount']).",
						'".addslashes($_POST['payment_terms'])."',
						'".addslashes($_POST['payment_type'])."'
					)
				";
				$qry = new Query($str_query);
				if ($qry->b_error == true) {
					$str_message = 'Error inserting new customer';
				}
			}
		}
	}

	$str_customer_id = '';
	$str_company = '';
	$str_address = '';
	$str_address2 = '';
	$str_city = '';
	$str_zip = '';
	$str_phone1 = '';
	$str_phone2 = '';
	$str_fax = '';
	$str_email = '';
	$str_cell = '';
	$str_contact_person = '';
	$str_sales_tax_no = '';
	$str_sales_tax_type = '';
	$flt_tax = 0;
	$str_sur = '';
	$flt_surcharge = '';
	$str_delivery_address = '';
	$flt_discount = '';
	$str_payment_terms = '';
	$str_payment_type = '';

	if ($int_id > 0) {
		$str_query = "
			SELECT *
			FROM customer
			WHERE id = $int_id";

		$qry = new Query($str_query);

		if ($qry->RowCount() > 0) {
			$str_customer_id = $qry->FieldByName('customer_id');
			$str_company = $qry->FieldByName('company');
			$str_address = $qry->FieldByName('address');
			$str_address2 = $qry->FieldByName('address2');
			$str_city = $qry->FieldByName('city');
			$str_zip = $qry->FieldByName('zip');
			$str_phone1 = $qry->FieldByName('phone1');
			$str_phone2 = $qry->FieldByName('phone2');
			$str_fax = $qry->FieldByName('fax');
			$str_email = $qry->FieldByName('email');
			$str_cell = $qry->FieldByName('cell');
			$str_contact_person = $qry->FieldByName('contact_person');
			$str_sales_tax_no = $qry->FieldByName('sales_tax_no');
			$str_sales_tax_type = $qry->FieldByName('sales_tax_type');
			$flt_tax = $qry->FieldByName('tax');
			$str_sur = $qry->FieldByName('sur');
			$flt_surcharge = $qry->FieldByName('surcharge');
			$str_delivery_address = $qry->FieldByName('delivery_address');
			$flt_discount = $qry->FieldByName('discount');
			$str_payment_terms = $qry->FieldByName('payment_terms');
			$str_payment_type = $qry->FieldByName('payment_type');
		}
	}
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
	<link rel="stylesheet" type="text/css" href="../include/<?echo $str_css_filename;?>" />

</head>

<body leftmargin=7 topmargin=7 marginwidth=15 marginheight=15>
<form name='viewcustomer' method='POST'>

	<input type='hidden' name='id' value='<?echo $int_id;?>'>

<? 
	if ($str_message <> '')
		Echo "<font color='red'>$str_message</font>";
?>

	<table border='0' cellpadding='0' cellspacing='5'>
		<tr>
			<td align='right' class='<?echo $str_class_header;?>'>Customer Id</td>
			<td><input type='text' name='customer_id' value='<?echo $str_customer_id?>' class='<?echo $str_class_input100;?>'></td>
		</tr>
		<tr>
			<td align='right' class='<?echo $str_class_header;?>'>Company</td>
			<td><input type='text' name='company' value='<?echo $str_company?>' class='<?echo $str_class_input100;?>'></td>
		</tr>
		<tr>
			<td align='right' class='<?echo $str_class_header;?>'>Address</td>
			<td><input type='text' name='address' value='<?echo $str_address?>' class='<?echo $str_class_input100;?>'></td>
		</tr>
		<tr>
			<td align='right' class='<?echo $str_class_header;?>'>Address 2</td>
			<td><input type='text' name='address2' value='<?echo $str_address2?>' class='<?echo $str_class_input100;?>'></td>
		</tr>
		<tr>
			<td align='right' class='<?echo $str_class_header;?>'>City</td>
			<td><input type='text' name='city' value='<?echo $str_city?>' class='<?echo $str_class_input100;?>'></td>
		</tr>
		<tr>
			<td align='right' class='<?echo $str_class_header;?>'>Zip</td>
			<td><input type='text' name='zip' value='<?echo $str_zip?>' class='<?echo $str_class_input100;?>'></td>
		</tr>
		<tr>
			<td align='right' class='<?echo $str_class_header;?>'>Phone</td>
			<td><input type='text' name='phone1' value='<?echo $str_phone1?>' class='<?echo $str_class_input100;?>'></td>
		</tr>
		<tr>
			<td align='right' class='<?echo $str_class_header;?>'>Phone 2</td>
			<td><input type='text' name='phone2' value='<?echo $str_phone2?>' class='<?echo $str_class_input100;?>'></td>
		</tr>
		<tr>
			<td align='right' class='<?echo $str_class_header;?>'>Fax</td>
			<td><input type='text' name='fax' value='<?echo $str_fax?>' class='<?echo $str_class_input100;?>'></td>
		</tr>
		<tr>
			<td align='right' class='<?echo $str_class_header;?>'>Email</td>
			<td><input type='text' name='email' value='<?echo $str_email?>' class='<?echo $str_class_input100;?>'></td>
		</tr>
		<tr>
			<td align='right' class='<?echo $str_class_header;?>'>Cell</td>
			<td><input type='text' name='cell' value='<?echo $str_cell?>' class='<?echo $str_class_input100;?>'></td>
		</tr>
		<tr>
			<td align='right' class='<?echo $str_class_header;?>'>Contact Person</td>
			<td><input type='text' name='contact_person' value='<?echo $str_contact_person?>' class='<?echo $str_class_input100;?>'></td>
		</tr>
		<tr>
			<td align='right' class='<?echo $str_class_header;?>'>Sales Tax No</td>
			<td><input type='text' name='sales_tax_no' value='<?echo $str_sales_tax_no?>' class='<?echo $str_class_input100;?>'></td>
		</tr>
		<tr>
			<td align='right' class='<?echo $str_class_header;?>'>Sales Tax Type</td>
			<td><input type='text' name='sales_tax_type' value='<?echo $str_sales_tax_type?>' class='<?echo $str_class_input100;?>'></td>
		</tr>
		<tr>
			<td align='right' class='<?echo $str_class_header;?>'>Tax</td>
			<td><input type='text' name='tax' value='<?echo $flt_tax?>' class='<?echo $str_class_input100;?>'></td>
		</tr>
		<tr>
			<td align='right' class='<?echo $str_class_header;?>'>Sur</td>
			<td><input type='text' name='sur' value='<?echo $str_sur?>' class='<?echo $str_class_input100;?>'></td>
		</tr>
		<tr>
			<td align='right' class='<?echo $str_class_header;?>'>Surcharge</td>
			<td><input type='text' name='surcharge' value='<?echo $flt_surcharge?>' class='<?echo $str_class_input100;?>'></td>
		</tr>
		<tr>
			<td align='right' class='<?echo $str_class_header;?>'>Delivery Address</td>
			<td><input type='text' name='delivery_address' value='<?echo $str_delivery_address?>' class='<?echo $str_class_input100;?>'></td>
		</tr>
		<tr>
			<td align='right' class='<?echo $str_class_header;?>'>Discount</td>
			<td><input type='text' name='discount' value='<?echo $flt_discount?>' class='<?echo $str_class_input100;?>'></td>
		</tr>
		<tr>
			<td align='right' class='<?echo $str_class_header;?>'>Payment Term</td>
			<td><input type='text' name='payment_terms' value='<?echo $str_payment_terms?>' class='<?echo $str_class_input100;?>'></td>
		</tr>
		<tr>
			<td align='right' class='<?echo $str_class_header;?>'>Payment Type</td>
			<td><input type='text' name='payment_type' value='<?echo $str_payment_type?>' class='<?echo $str_class_input100;?>'></td>
		</tr>
		<tr>
			<td align='right'>
				<input type='hidden' name='action' value='save'>
				<input type="button" class="v3button" name="button_save" value="Save" onclick="javascript:saveCustomer()">
			</td>
			<td>
				<input type="button" name="button_close" value="Close" class="v3button" onclick="CloseWindow()">
			</td>
		</tr>
	</table>
</form>
</body>
</html>