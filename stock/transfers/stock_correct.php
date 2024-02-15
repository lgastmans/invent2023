<?
	require_once("../../include/const.inc.php");
	require_once("../../include/db.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../common/product_funcs.inc.php");

	$int_access_level = (getModuleAccessLevel('Stock'));
	if ($_SESSION["int_user_type"]>1) {	
		$int_access_level = ACCESS_ADMIN;
	}

	$int_id = 0;
	if (IsSet($_GET['id']))
		$int_id = $_GET['id'];

	$qry = new Query("
		SELECT product_code
		FROM stock_product
		WHERE (product_id = $int_id)
			AND (deleted = 'N')
	");
	$str_code='';
	if ($qry->RowCount() > 0) {
		$str_code = $qry->FieldByName('product_code');
	}

?>

<script language="javascript">

	var can_save = false;
	var bool_is_decimal = false;
	var current_stock = 0;

	function CloseWindow() {
		if (window.opener) 
		  window.opener.document.location=window.opener.document.location.href;
		window.close();
	}

	function createRequest() {
		try {
			var requester = new XMLHttpRequest();
		}
		catch (error) {
			try {
				requester = new ActiveXObject("Microsoft.XMLHTTP");
			}
			catch (error) {
				return false;
			}
		}
		return requester;
	}

	var requester = createRequest();

	// RETURNS THE DESCRIPTION OF THE CODE ENTERED
	function stateHandler() {
		if (requester.readyState == 4) {
			if (requester.status == 200)  {
				var oTextBoxCode = document.stock_correct.code;
				var oTextBoxDescription = document.getElementById('description');
				var oTextBoxStock = document.getElementById('current_stock');

				str_retval = requester.responseText;

				if (str_retval == '__NOT_FOUND') {
				  can_save = false;
					oTextBoxCode.value = "";
					oTextBoxDescription.innerHTML = '';
				}
				else {
					arr_details = str_retval.split('|');

				if (arr_details[10] == "__NOT_AVAILABLE") {
					can_save = false;
						oTextBoxDescription.innerHTML = '';
						alert('This product cannot be received.\n It has been disabled');
						oTextBoxCode.focus();
				}
				else {
				
					can_save = true;

					oTextBoxDescription.innerHTML = arr_details[0];
					oTextBoxStock.innerHTML = arr_details[11]+" "+arr_details[12];
					current_stock = arr_details[11];

					if (arr_details[13] == 'Y')
						bool_is_decimal = true;
					else
						bool_is_decimal = false;
				}
			}
			}
			else {
				alert("failed to get description... please try again.");
			}
		requester = null;
		requester = createRequest();
		}
	}
	
	function getDescription(strProductCode) {
		requester.onreadystatechange = stateHandler;
		var strPassValue = '';
		if (strProductCode.value == '')
			strPassValue = 'nil'
		else
			strPassValue = strProductCode.value;
		requester.open("GET", "productDetails.php?live=1&product_code="+strPassValue);
		requester.send(null);
	}

	function receiveStock() {
		if (can_save == true) {
			document.stock_correct.Save.onclick = '';
			stock_correct.submit();
		}
  	}
	
	function setCorrected(aField) {
		var oTextBoxCorrected = document.getElementById('corrected_stock');
		
		flt_corrected = ((Number(current_stock) - Number(aField.value)) * -1);
		oTextBoxCorrected.innerHTML = "Corrected by: "+flt_corrected.toFixed(3);
	}
	
	function focusNext(aField, focusElem, evt) {
		evt = (evt) ? evt : event;
		var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);

		var oTextBoxCode = document.stock_correct.code;
		var oTextBoxCorrect = document.stock_correct.correct;
		var oTextBoxNote = document.stock_correct.note;
		var oButtonSave = document.stock_correct.Save;
	
		if (charCode == 113) { // F2 Save
			oButtonSave.click();
                }
		else if (charCode == 13 || charCode == 3) {
			if (focusElem == 'correct') {
				oTextBoxCorrect.select();
			}
			else if (focusElem == 'note') {
			 oTextBoxNote.focus();
			}
			else if (focusElem == 'button_save') {
			 oButtonSave.focus();
			}
		} 
		else if (charCode == 27) {
			oTextBoxCode.select();
			clearValues;
		}	
/*    else if (charCode == 8) {
			if (focusElem == 'correct') {
				oTextBoxCorrect.select();
			}
			else if (focusElem == 'note') {
			 oTextBoxNote.focus();
			}
			else if (focusElem == 'button_save') {
			 oTextBoxMnfrYear.focus();
			}
		}
*/  	
		else if (charCode == 46) {
			if (focusElem == 'correct') {
				if (bool_is_decimal == false)
					return false;
			}
  		}
  	
		return true;
	}  
	
	function clearValues() {
		var oTextBoxCode = document.stock_correct.code;
		var oTextBoxDescription = document.getElementById('description');
		var oTextBoxStock = document.getElementById('current_stock');
		var oTextBoxCorrect = document.stock_correct.correct;
		var oTextBoxNote = document.stock_correct.note;

		oTextBoxCode.value = '';
		oTextBoxDescription.innerHTML = '';
		oTextBoxStock.innerHTML = '';
		oTextBoxCorrect.value = '';
		oTextBoxNote.value = '';
	}
	
	function openSearch() {
		myWin = window.open("../../common/product_search.php?formname=stock_correct&fieldname=code",'searchProduct','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=400,height=600,top=0');
		myWin.focus();
	}
	
</script>

<?	
	$str_message = '';

	if (IsSet($_POST["action"])) {
		
		if ($_POST["action"] == "save") {

			$qry = new Query("
				SELECT *
				FROM stock_product
				WHERE (product_code = '".$_POST["code"]."')
					AND (deleted = 'N')
			");
			if ($qry->RowCount() == 0) {
				$str_message = "Product code not found";
				$can_save = false;
			}
			else {
				$int_product_id = $qry->FieldByName('product_id');
				$ret = stock_correct($int_product_id, floatval($_POST["correct"]), $_POST["note"]);
			}			
		} 
	} 
		
?>

<html>
<head>
	<link rel="stylesheet" type="text/css" href="../../include/styles.css" />
</head>
<body id='body_bgcolor'>
<form name="stock_correct" method="POST" onsubmit="return false">


<table width='100%' height='90%' border='0' >
<tr>
	<td align='center' valign='center'>
	
<?
	boundingBoxStart("400", "../../images/blank.gif");

	if ($str_message != '')  { ?>
		<script language='javascript'>
		alert('<?echo $str_message?>');
		</script>
<?
	}
?>

	<table border="0" cellpadding="2" cellspacing="0">
		<tr>
			<td align="right" class="normaltext"><b>Code</b></td>
			<td><input type="text" name="code" value="<?php echo $str_code;?>" class='input_100' autocomplete="OFF" onblur="javascript:getDescription(this)" onkeypress="focusNext(this, 'correct', event)">&nbsp;<a href="javascript:openSearch()"><img src="../../images/find.png" border="0" title="Search" alt="Search"></a></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td id="description" class="spantext">&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td id="current_stock" class="spantext">&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td id="corrected_stock" class="spantext">&nbsp;</td>
		</tr>
		<tr>
			<td align="right" class="normaltext"><b>Correct Stock</b></td>
			<td><input type="text" name="correct" value="" class='input_100' autocomplete="OFF" onkeyup="setCorrected(this)" onkeypress="return focusNext(this, 'note', event)"></td>
		</tr>
		<tr>
			<td align="right" class="normaltext"><b>Note</b></td>
			<td><input type="text" name="note" value="" class='input_settings' autocomplete="OFF" onkeypress="return focusNext(this, 'button_save', event)"></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="right">
				<input type="hidden" name="action" value="save">
				<? if ($int_access_level > ACCESS_READ) { ?>
				<input type="button" class="mainmenu_button" name="Save" value="Save" onclick="receiveStock()">
				<? } else { ?>
				&nbsp;
				<? } ?>
			</td>
			<td>
				<input type="button" class="mainmenu_button" name="Close" value="Close" onclick="CloseWindow()">
			</td>
		</tr>
		<tr>
		    <td colspan="2">
			    <i><br><font class="normaltext">Correcting stock nullifies all manually adjusted stock</font></i>
		    </td>
		</tr>
	</table>
<?
    boundingBoxEnd("400", "../../images/blank.gif");
?>

</td></tr>
</table>

</form>

<script language="javascript">
  document.stock_correct.code.focus();
</script>

</body>
</html>