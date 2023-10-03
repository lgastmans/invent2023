<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	
	$str_message = '';

	$int_id = -1;
	if (IsSet($_GET['id'])) {
		$int_id = $_GET['id'];
	}
	
	if (IsSet($_POST['action'])) {
		if ($_POST['action'] == 'save') {
			if (IsSet($_POST['id'])) {
				$int_id = $_POST['id'];
				
				/*
					check for duplicate supplier code
				*/
				/*
				$qry_check = new Query("
					SELECT *
					FROM stock_supplier
					WHERE (supplier_code = '".addslashes($_POST['code'])."')
						AND (supplier_id <> $int_id)
				");
				if ($qry_check->RowCount() > 0) {
					$str_message = 'Duplicate supplier code found';
				}
				else {
				*/

					$str_query = "
						UPDATE stock_supplier
						SET
							supplier_code = '".addslashes($_POST['code'])."',
							supplier_abbreviation = '".addslashes($_POST['abbreviation'])."',
							supplier_name = '".addslashes($_POST['name'])."',
							contact_person = '".addslashes($_POST['contact_person'])."',
							supplier_address = '".addslashes($_POST['address'])."',
							supplier_city = '".addslashes($_POST['city'])."',
							supplier_state = '".addslashes($_POST['state'])."',
							supplier_zip = '".addslashes($_POST['zip'])."',
							supplier_phone = '".addslashes($_POST['phone'])."',
							supplier_cell = '".addslashes($_POST['cell'])."',
							supplier_email = '".addslashes($_POST['email'])."',
							is_supplier_delivering = '".addslashes($_POST['delivers'])."',
							is_active = '".addslashes($_POST['is_active'])."',
							commission_percent = ".$_POST['commission_1'].",
							commission_percent_2 = ".$_POST['commission_2'].",
							commission_percent_3 = ".$_POST['commission_3'].",
							supplier_discount = ".$_POST['discount'].",
							trust = '".addslashes($_POST['trust'])."',
							supplier_TIN = '".addslashes($_POST['TIN'])."',
							supplier_CST = '".addslashes($_POST['CST'])."',
							account_number = '".addslashes($_POST['account_number'])."',
							is_other_state = '".addslashes($_POST['is_other_state'])."',
							gstin = '".$_POST['gstin']."',
							supplier_discounted = '".$_POST['supplier_discounted']."'
						WHERE (supplier_id = $int_id)
					";
					$qry = new Query($str_query);
					
					if ($qry->b_error == true) {
						$str_message = "Error updating supplier information: ".$qry->err;
					}
					else {
						echo "<script language='javascript'>\n;";
						echo "if (top.window.opener)\n";
						echo "top.window.opener.document.location=top.window.opener.document.location.href;\n";
						echo "top.window.close();\n";
						echo "</script>";
					}
				//}
			}
			else {
				/*
					check for duplicate supplier code
				*/
				/*
				$qry_check = new Query("
					SELECT *
					FROM stock_supplier
					WHERE (supplier_code = '".addslashes($_POST['code'])."')
				");
				if ($qry_check->RowCount() > 0) {
					$str_message = 'Duplicate supplier code found';
				}
				else {
				*/
					$str_query = "
						INSERT INTO stock_supplier
						(
							supplier_code,
							supplier_abbreviation,
							supplier_name,
							contact_person,
							supplier_address,
							supplier_city,
							supplier_state,
							supplier_zip,
							supplier_phone,
							supplier_cell, 
							supplier_email,
							is_supplier_delivering,
							is_active,
							commission_percent,
							commission_percent_2,
							commission_percent_3,
							supplier_discount,
							trust,
							supplier_TIN,
							supplier_CST,
							is_other_state,
							account_number,
							gstin,
							supplier_discounted
						)
						VALUES (
							'".addslashes($_POST['code'])."',
							'".addslashes($_POST['abbreviation'])."',
							'".addslashes($_POST['name'])."',
							'".addslashes($_POST['contact_person'])."',
							'".addslashes($_POST['address'])."',
							'".addslashes($_POST['city'])."',
							'".addslashes($_POST['state'])."',
							'".addslashes($_POST['zip'])."',
							'".addslashes($_POST['phone'])."',
							'".addslashes($_POST['cell'])."',
							'".addslashes($_POST['email'])."',
							'".addslashes($_POST['delivers'])."',
							'".addslashes($_POST['is_active'])."',
							".$_POST['commission_1'].",
							".$_POST['commission_2'].",
							".$_POST['commission_3'].",
							".$_POST['discount'].",
							'".addslashes($_POST['trust'])."',
							'".addslashes($_POST['TIN'])."',
							'".addslashes($_POST['CST'])."',
							'".addslashes($_POST['is_other_state'])."',
							'".addslashes($_POST['account_number'])."',
							'".$_POST['gstin']."',
							'".$_POST['supplier_discounted']."'
						)
					";
					$qry = new Query($str_query);
					if ($qry->b_error == true) {
						$str_message = 'Error inserting new customer';
						echo $str_query;
					}
					else {
						echo "<script language='javascript'>\n;";
						echo "if (top.window.opener)\n";
						echo "top.window.opener.document.location=top.window.opener.document.location.href;\n";
						echo "top.window.close();\n";
						echo "</script>";
					}
				//}
			}
		}
	}
	
	$str_code = '';
	$str_abbreviation = '';
	$str_name = '';
	$str_contact = '';
	$str_address = '';
	$str_city = '';
	$str_state = '';
	$str_zip = '';
	$str_phone = '';
	$str_cell = '';
	$str_email = '';
	$str_delivers = 'Y';
	$str_is_active = '';
	$int_commission_1 = '0.0';
	$int_commission_2 = '0.0';
	$int_commission_3 = '0.0';
	$int_discount = 0;
//	$int_type = '';
	$str_trust = '';
	$str_TIN = '';
	$str_CST = '';
	$str_is_other_state = 'N';
	$str_account_number = '';
	$gstin = '';
	$supplier_discounted = false;
	
	if ($int_id > 0) {
		$str_query = "
			SELECT *
			FROM stock_supplier
			WHERE supplier_id = $int_id";
		
		$qry = new Query($str_query);
		
		if ($qry->RowCount() > 0) {
			$str_code = $qry->FieldByName('supplier_code');
			$str_abbreviation = $qry->FieldByName('supplier_abbreviation');
			$str_name = $qry->FieldByName('supplier_name');
			$str_contact = $qry->FieldByName('contact_person');
			$str_address = $qry->FieldByName('supplier_address');
			$str_city = $qry->FieldByName('supplier_city');
			$str_state = $qry->FieldByName('supplier_state');
			$str_zip = $qry->FieldByName('supplier_zip');
			$str_phone = $qry->FieldByName('supplier_phone');
			$str_cell = $qry->FieldByName('supplier_cell');
			$str_email = $qry->FieldByName('supplier_email');
			$str_delivers = $qry->FieldByName('is_supplier_delivering');
			$str_is_active = $qry->FieldByName('is_active');
			$int_commission_1 = number_format($qry->FieldByName('commission_percent'),2,'.','');
			$int_commission_2 = number_format($qry->FieldByName('commission_percent_2'),2,'.','');
			$int_commission_3 = number_format($qry->FieldByName('commission_percent_3'),2,'.','');
			$int_discount = $qry->FieldByName('supplier_discount');
//			$int_type = $qry->FieldByName('supplier_type');
			$str_trust = $qry->FieldByName('trust');
			$str_TIN = $qry->FieldByName('supplier_TIN');
			$str_CST = $qry->FieldByName('supplier_CST');
			$str_is_other_state = $qry->FieldByName('is_other_state');
			$str_account_number = $qry->FieldByName('account_number');
			$gstin = $qry->FieldByName('gstin');
			$supplier_discounted = $qry->FieldByName('supplier_discounted');
		}
	}


?>

<html>
<head><TITLE></TITLE>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	
	<script language="javascript">
		function trim(s) {
			return s.replace(/^\s+|\s+$/g, "");
		}
		
		function isEmpty(str){
			return (str == null) || (str.length == 0);
		}
		
		function isEmail(str){
			if(isEmpty(str)) return false;
			var re = /^[^\s()<>@,;:\/]+@\w[\w\.-]+\.[a-z]{2,}$/i
			return re.test(str);
		}
		
		function isNumeric(str){
			var re = /[\D]/g
			if (re.test(str)) return false;
			return true;
		}
		
		function isFloat(s) {
			var n = trim(s);
			return n.length>0 && !(/[^0-9.]/).test(n) && (/\.\d/).test(n);
		}
		
		function saveData() {
//			var oTextCode = document.supplier_edit.code;
			var oTextEmail = document.supplier_edit.email;
			var oTextCommission1 = document.supplier_edit.commission_1;
			var oTextCommission2 = document.supplier_edit.commission_2;
			var oTextCommission3 = document.supplier_edit.commission_3;
			var oTextDiscount = document.supplier_edit.discount;
			var can_save = true;
			
/*			if (isEmpty(oTextCode.value)) {
				can_save = false;
				alert('Code cannot be blank');
				oTextCode.focus();
				return false;
			}*/
			if (!isEmpty(oTextEmail.value)) {
				if (!isEmail(oTextEmail.value)) {
					can_save = false;
					alert('Invalid email address');
					oTextEmail.focus();
					return false;
				}
			}
			if ((!isFloat(oTextCommission1.value)) || (isEmpty(oTextCommission1.value))) {
				can_save = false;
				alert('Invalid commission value');
				oTextCommission1.focus();
				return false;
			}
			if ((!isFloat(oTextCommission2.value)) || (isEmpty(oTextCommission2.value))) {
				can_save = false;
				alert('Invalid commission value');
				oTextCommission2.focus();
				return false;
			}
			if ((!isFloat(oTextCommission3.value)) || (isEmpty(oTextCommission3.value))) {
				can_save = false;
				alert('Invalid commission value');
				oTextCommission3.focus();
				return false;
			}
			if ((!isNumeric(oTextDiscount.value)) || (isEmpty(oTextDiscount.value))) {
				can_save = false;
				alert('Invalid discount value');
				oTextDiscount.focus();
				return false;
			}
			
			if (can_save)
				document.supplier_edit.submit();
		}
	
		function CloseWindow() {
			if (top.window.opener)
				top.window.opener.document.location=top.window.opener.document.location.href;
			top.window.close();
		}
	</script>
</head>
<body id='body_bgcolor' leftmargin=7 topmargin=7 marginwidth=15 marginheight=15>

<form name='supplier_edit' method='POST'>
<?
	if ($int_id > -1)
		echo "<input type='hidden' name='id' value='".$int_id."'>";

//===================
// bounding box start
//-------------------
?>
<table width='100%' height='90%' border='0' >
<tr>
	<td align='center' valign='center'>
	
<?
	boundingBoxStart("550", "../images/blank.gif");

	if ($str_message != '')  { ?>
		<script language='javascript'>
		alert('<?echo $str_message?>');
		</script>
<?
	}
//===================
?>


<table width='700px' cellpadding="5" cellspacing="0" border="0">
	<tr>
		<td colspan="2">

			<table>

				<tr>
					<td width='120px' align="right" class='normaltext_bold'>Code:</td>
					<td>
						<input type='text' name='code' value='<?echo $str_code?>' class='input_100'>
					</td>
					<td align="right" class='normaltext_bold'>Abbreviation:</td>
					<td>
						<input type='text' name='abbreviation' value='<?echo $str_abbreviation?>' class='input_100'>
					</td>
				</tr>

			</table>

		</td>
	</tr>


	<tr>
		<td align="right" class='normaltext_bold'>Name:</td>
		<td>
			<input type='text' name='name' value='<?echo $str_name?>' class='input_400'>
		</td>
	</tr>
	<tr>
		<td align="right" class='normaltext_bold'>Contact Person:</td>
		<td>
			<input type='text' name='contact_person' value='<?echo $str_contact?>' class='input_300'>
		</td>
	</tr>
	<tr>
		<td align="right" class='normaltext_bold'>Address:</td>
		<td>
			<input type='text' name='address' value='<?echo $str_address?>' class='input_400'>
		</td>
	</tr>
	<tr>
		<td align="right" class='normaltext_bold'>City:</td>
		<td>
			<input type='text' name='city' value='<?echo $str_city?>' class='input_300'>
		</td>
	</tr>
	<tr>
		<td align="right" class='normaltext_bold'>State:</td>
		<td>
			<input type='text' name='state' value='<?echo $str_state?>' class='input_300'>
		</td>
	</tr>
	<tr>
		<td align="right" class='normaltext_bold'>Zip:</td>
		<td>
			<input type='text' name='zip' value='<?echo $str_zip?>' class='input_100'>
		</td>
	</tr>
	<tr>
		<td align="right" class='normaltext_bold'>Phone:</td>
		<td>
			<input type='text' name='phone' value='<?echo $str_phone?>' class='input_200'>
		</td>
	</tr>
	<tr>
		<td align="right" class='normaltext_bold'>Cell:</td>
		<td>
			<input type='text' name='cell' value='<?echo $str_cell?>' class='input_200'>
		</td>
	</tr>
	<tr>
		<td align="right" class='normaltext_bold'>Email:</td>
		<td>
			<input type='text' name='email' value='<?echo $str_email?>' class='input_300'>
		</td>
	</tr>


	<tr>
		<td colspan="2">

			<table style="border-top: solid 2px grey;padding-top: 5px;" border="0">
				<tr>
					<td width="150px" align="right" class='normaltext_bold'>Delivers:</td>
					<td>
						<select name='delivers' class='select_150'>
							<option value='Y' <? if ($str_delivers=='Y') echo "selected"?>>Consignment</option>
							<option value='N' <? if ($str_delivers=='N') echo "selected"?>>Direct</option>
						</select>
					</td>
					<td width="150px" align="right" class='normaltext_bold'>Active:</td>
					<td>
						<select name='is_active' class='select_150'>
							<option value='Y' <? if ($str_is_active=='Y') echo "selected"?>>Yes</option>
							<option value='N' <? if ($str_is_active=='N') echo "selected"?>>No</option>
						</select>
					</td>
				</tr>

				<tr>
					<td align="right" class='normaltext_bold'>Commission:</td>
					<td>
						<input type='text' name='commission_1' value='<?echo $int_commission_1?>' class='input_50'>
					</td>
					<td align="right" class='normaltext_bold'>Commission 2:</td>
					<td>
						<input type='text' name='commission_2' value='<?echo $int_commission_2?>' class='input_50'>
					</td>
				</tr>

				<tr>
					<td align="right" class='normaltext_bold'>Commission 3:</td>
					<td>
						<input type='text' name='commission_3' value='<?echo $int_commission_3?>' class='input_50'>
					</td>
					<td align="right" class='normaltext_bold'>Discount:</td>
					<td>
						<input type='text' name='discount' value='<?echo $int_discount?>' class='input_50'>
					</td>
				</tr>

				<tr>
					<td align="right" class='normaltext_bold'>Trust:</td>
					<td>
						<input type='text' name='trust' value='<?echo $str_trust?>' class='input_200'>
					</td>
					<td align="right" class='normaltext_bold'>TIN:</td>
					<td>
						<input type='text' name='TIN' value='<?echo $str_TIN?>' class='input_200'>
					</td>
				</tr>

				<tr>
					<td align="right" class='normaltext_bold'>CST:</td>
					<td>
						<input type='text' name='CST' value='<?echo $str_CST?>' class='input_200'>
					</td>
					<td align="right" class='normaltext_bold'>Account Number:</td>
					<td>
						<input type='text' name='account_number' value='<?echo $str_account_number?>' class='input_200'>
					</td>
				</tr>

				<tr>
					<td align="right" class='normaltext_bold'>Is Other State:</td>
					<td>
						<select name='is_other_state' class='select_200'>
							<option value='Y' <? if ($str_is_other_state=='Y') echo "selected"?>>Yes</option>
							<option value='N' <? if ($str_is_other_state=='N') echo "selected"?>>No</option>
						</select>
					</td>
					<td></td>
					<td></td>
				</tr>

				<tr>
					<td align="right" class='normaltext_bold'>GSTIN:</td>
					<td>
						<input type='text' name='gstin' value='<?echo $gstin?>' class='input_200'>
					</td>
					<td align="right" class='normaltext_bold'>Supplier Discounted:</td>
					<td>
						<select name='supplier_discounted' class='select_200'>
							<option value='1' <? if ($supplier_discounted==1) echo "selected"?>>Yes</option>
							<option value='0' <? if ($supplier_discounted==0) echo "selected"?>>No</option>
						</select>
					</td>
				</tr>

			</table>

		</td>
	</tr>


	<tr>
		<td align='right'>
			<input type='hidden' name='action' value='save'>
			<input type="button" class="settings_button" name="button_save" value="Save" onclick="javascript:saveData()">
		</td>
		<td>
			<input type="button" name="button_close" value="Close" class="settings_button" onclick="CloseWindow()">
		</td>
	</tr>
</table>


<?
//=================
// bounding box end
//-----------------
    boundingBoxEnd("550", "../images/blank.gif");
?>
</td></tr>
</table>
<?
//===================
?>
</form>
</body>
</html>