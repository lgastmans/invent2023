<?
	require_once("../../include/db.inc.php");

?>

<script language="javascript">

	function save_data() {
		var oTextName = document.billing_creditcard.card_name;
		var oTextNumber = document.billing_creditcard.card_number;
		var oTextDate = document.billing_creditcard.card_date;

		parent.frames['frame_action'].document.location = "billing_action.php?action=creditcard" +
		"&card_name="+oTextName.value+
		"&card_number="+oTextNumber.value+
		"&card_date="+oTextDate.value;
	}

	function focusNext(aField, focusElem, evt) {
		evt = (evt) ? evt : event;
		var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
		
		var oTextName = document.billing_creditcard.card_name;
		var oTextNumber = document.billing_creditcard.card_number;
		var oTextDate = document.billing_creditcard.card_date;
		
		if (charCode == 13 || charCode == 3 || charCode == 9) {
			if (focusElem == 'card_name') {
				oTextName.focus();
			}
			else if (focusElem == 'card_number') {
				oTextNumber.focus();
			}
			else if (focusElem == 'card_date') {
				oTextDate.focus();
			}
			else if (focusElem == 'code') {
				parent.frames['frame_enter'].document.billing_enter.code.focus();
			}
		} else if (charCode == 27) {
			oTextName.select();
		}
		
		return false;
	}

</script>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../../include/<?echo $str_css_filename;?>" />
	</head>
<body leftmargin=0 topmargin=0 marginwidth=7 marginheight=7>

<form name="billing_creditcard" method="GET" onsubmit="return false">

<table border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td class="<?echo $str_class_header?>">Name</td>
		<td>&nbsp;</td>
		<td class="<?echo $str_class_header?>">Number</td>
		<td>&nbsp;</td>
		<td class="<?echo $str_class_header?>">Valid Till</td>
	</tr>
	<tr>
		<td><input type="text" class="<?echo $str_class_input?>" name="card_name" value="<?echo $_SESSION['bill_card_name']?>" onkeypress="focusNext(this, 'card_number', event)" onblur='javascript:save_data()' autocomplete="OFF"></td>
		<td>&nbsp;</td>
		<td><input type='text' class='<?echo $str_class_input?>' name='card_number' value='<?echo $_SESSION['bill_card_number']?>' onkeypress="focusNext(this, 'card_date', event)" onblur='javascript:save_data()' autocomplete="OFF"></td>
		<td>&nbsp;</td>
		<td><input type='text' class='<?echo $str_class_input_short?>' name='card_date' value='<?echo $_SESSION['bill_card_date']?>' onkeypress="focusNext(this, 'code', event)" onblur='javascript:save_data()' autocomplete="OFF"></td>
	</tr>
</table>

</form>

</body>
</html>