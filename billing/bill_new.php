<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
?>

<script language="javascript">

	intArrayIndex = 0;
	arrCode = new Array();
	arrQty = new Array();
	arrDiscount = new Array();
	intIndex = -1;
	isValidCode = 0;

	function getCode() {
		var oItemList = document.getElementsByName('itemList');
		var oTextBoxCode = document.getElementsByName('code');
		var intIndex = -1;

		for (i=0; i < arrCode.length; i++) {
			if (arrCode[i] == oTextBoxCode.value) {
				intIndex = i;
				break;
			}
		}

		return intIndex;
	}

	function checkCode() {	
		alert(isValidCode);
	}

	function selectNext(elem, event, intGoto) {
/*		if (event.keyCode == '13') {
			if (intGoto == 1)
				document.bill_new.quantity.focus();
			else
			if (intGoto == 2)
				document.bill_new.discount.focus();
		}
*/
	}
	

	function updateList() {
		var oTextBoxCode = document.bill_new.code;
		var oTextBoxQty = document.bill_new.quantity;
		var oTextBoxDiscount = document.bill_new.discount;
		var oItemList = document.bill_new.itemList;
		var oTextBoxDesc = document.getElementById('description');

		if (intIndex > 1) {
			arrCode[intIndex] = oTextBoxCode.value;
			arrQty[intIndex] = oTextBoxQty.value;
			arrDiscount[intIndex] = oTextBoxDiscount.value;

			oItemList[oItemList.options.length] = new Option(arrCode[arrCodes.length-1] + ' ' + oTextBoxDesc.innerHTML + arrQty[arrQty.length-1], "1");
		}
	}
	

</script>

<html>
<head><TITLE></TITLE>
<link href="../include/styles.css" rel="stylesheet" type="text/css">

</head>

<body>
<form name="bill_new">
		<tr>
			<td><input type="text" name="code" value="" onblur="javascript:getDescription(this);"></td>
			<td><span class="description" id="description" ></span></td>
			<td><input type="text" name="quantity" value="1" onfocus="checkCode();"></td>
			<td><input type="text" name="discount" value="0" onblur="updateList()" onfocus="checkCode()"></td>
		</tr>
		<tr>
			<td colspan="4">items</td>
		</tr>
		<tr>
			<td colspan="4"><select name="itemList" size="15" style="font-style:arial;font-size:12px;width:800px"></select></td>
		</tr>
	</table>
	<input type="button" name="action" value="save">
	<input type="button" name="action" value="cancel">
</form>


</body>
</html>