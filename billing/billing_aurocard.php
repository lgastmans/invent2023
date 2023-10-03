<?php
	
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");	
	require_once("../include/db.inc.php");

	if ($_SESSION['str_user_font_size'] == 'small') {
	    $str_class_header = "headertext_small";
	    $str_class_input = "inputbox200_small";
	    $str_class_select = "select100_small";
	    $str_class_span = "spantext_small";
	}
	else if ($_SESSION['str_user_font_size'] == 'standard') {
	    $str_class_header = "headertext";
	    $str_class_input = "inputbox200";
	    $str_class_select = "select100";
	    $str_class_span = "spantext";
	}
	else if ($_SESSION['str_user_font_size'] == 'large') {
	    $str_class_header = "headertext_large";
	    $str_class_input = "inputbox200_large";
	    $str_class_select = "select100_large";
	    $str_class_span = "spantext_large";
	}
	else {
	    $str_class_header = "headertext";
	    $str_class_input = "inputbox200";
	    $str_class_select = "select100";
	    $str_class_span = "spantext";
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
?>

<html>
<head>
	<link rel="stylesheet" type="text/css" href="../yui2.7.0/build/fonts/fonts-min.css" />
	<script type="text/javascript" src="../yui2.7.0/build/yahoo/yahoo-min.js"></script>
	<script type="text/javascript" src="../yui2.7.0/build/event/event-min.js"></script>
	<script type="text/javascript" src="../yui2.7.0/build/connection/connection-min.js"></script>
	
	<link rel="stylesheet" type="text/css" href="../include/<?echo $str_css_filename;?>" />
	
	<script language="javascript">
		
		var handleSuccess = function(o) {
			//alert(o.responseText);
		}
		var handleFailure = function(o){
			alert('An error occurred saving settings');
		}
		var callback = {
			success:handleSuccess,
			failure:handleFailure
		};
		function setSessionVars() {
			var oNumber = document.getElementById('aurocard_number');
			var oId = document.getElementById('aurocard_transaction_id');
			
			YAHOO.util.Connect.asyncRequest('GET', 'billing_sessions.php?live=1&aurocard_number='+oNumber.value+"&aurocard_transaction_id="+oId.value, callback);
		}
		
		function focusNext(aField, focusElem, evt, account_type) {
			evt = (evt) ? evt : event;
			var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
		
			var oNumber = document.billing_aurocard.aurocard_number;
			var oID = document.billing_aurocard.aurocard_transaction_id;
		
			if (charCode == 13 || charCode == 3 || charCode == 9) {
				if (focusElem == 'number')
					oNumber.select();
				else if (focusElem == 'id')
					oID.select();
			} else if (charCode == 27) {
				oNumber.select();
			}
			
			return false;
		}
	</script>
</head>
<body class="yui-skin-sam" leftmargin=0 topmargin=0 marginwidth=7 marginheight=7>

<form name="billing_aurocard" method="GET" onsubmit="return false">

	<table border="0" cellpadding='0' cellspacing='2'>
		<tr>
			<td class="<?echo $str_class_header?>">Aurocard Number</td>
			<td>&nbsp;</td>
			<td width='500px' class="<?echo $str_class_header?>">Transaction ID</td>
		</tr>
		<tr>
			<td>
				<input type="text" class="<?echo $str_class_input?>" name="aurocard_number" id="aurocard_number" <?if ($_SESSION['bill_id'] > -1) echo "readonly";?> value="<?echo $_SESSION['current_aurocard_number']?>" onblur="javascript:setSessionVars();" onkeypress="focusNext(this, 'id', event, <? echo $_SESSION['current_bill_type']?>)" autocomplete="OFF">
			</td>
			<td>
			<td>
				<input type="text" class="<?echo $str_class_input?>" name="aurocard_transaction_id" id="aurocard_transaction_id" <?if ($_SESSION['bill_id'] > -1) echo "readonly";?> value="<?echo $_SESSION['current_aurocard_transaction_id']?>"  onblur="javascript:setSessionVars();" onkeypress="focusNext(this, 'number', event, <? echo $_SESSION['current_bill_type']?>)" autocomplete="OFF">
			</td>
		</tr>
	</table>

</form>

<script language="javascript">
	document.billing_aurocard.aurocard_number.focus();
</script>

</body>
</html>