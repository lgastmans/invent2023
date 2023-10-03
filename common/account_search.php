<html>
<head>
	<link rel="stylesheet" type="text/css" href="../yui2.7.0/build/fonts/fonts-min.css" />
	<script type="text/javascript" src="../yui2.7.0/build/yahoo/yahoo-min.js"></script>
	<script type="text/javascript" src="../yui2.7.0/build/event/event-min.js"></script>
	<script type="text/javascript" src="../yui2.7.0/build/connection/connection-min.js"></script>
	<script type="text/javascript" src="../yui2.7.0/build/json/json-min.js"></script>
	
	<link rel="stylesheet" type="text/css" href="../include/bill_styles.css" />
	<script language="javascript">

		var handleSuccess = function(o) {
			var r = YAHOO.lang.JSON.parse(o.responseText);
			var oSelect = document.getElementById('accountList');
			
			if (r.replyText == 'Ok') {
				oSelect.options.length = 0;
				var intLen = r.replySet.length;
				if (intLen > 0) {
					for (i=0;i<intLen;i++) {
						arrCurrent = r.replySet[i];
						oSelect.options[i] = new Option(arrCurrent[0] + ' ' +arrCurrent[1], arrCurrent[0]);
					}
				}
			}
			else
				alert(r.replyText);
		}
		
		var handleFailure = function(o){
			var r = YAHOO.lang.JSON.parse(o.responseText);
			alert(r.replyText);
		}
		
		var callback = {
			success:handleSuccess,
			failure:handleFailure
		};
		
		function searchFor(strType) {
			var oSearch = document.product_search.strSearch;
			var sUrl = "get_account.php?"+
				"search="+oSearch.value+
				"&type="+strType;
			var request = YAHOO.util.Connect.asyncRequest('GET', sUrl, callback);
			
			return true;
		}

		function getKeyPress(event, strType) {
			if (event.keyCode == '13') {
				searchFor(strType);
			}
			return false;
		}

		function selectItem(aFormname, aFieldname) {
			oSelectItem = document.product_search.productList;
			if (oSelectItem.selectedIndex != -1) {
				if (window.opener && !window.opener.closed) {
					oTextBoxField = eval('window.opener.document.'+aFormname+'.'+aFieldname);
					oTextBoxField.value = oSelectItem.options[oSelectItem.selectedIndex].value;
					oTextBoxField.select();
					oTextBoxField.focus();
				}
				window.close();
			}
			else
				alert('Please select an item');
		}

	</script>
</head>
<body bgcolor="#DADADA">

<?
	$ac_type = 2; // default FS Account (3 = PT Account)
	if (IsSet($_GET['bill_type']))
		$ac_type = $_GET['bill_type'];
		
	if (!IsSet($_GET["fieldname"]))
		$fieldname = 'none';
	else
		$fieldname = $_GET["fieldname"];

	if (!IsSet($_GET["formname"]))
		$formname = 'none';
	else
		$formname = $_GET["formname"];
?>

<form name="product_search" method="POST" action="" onsubmit="return false">

	<font class="headertext">Search for : </font>
	<input type="text" name="strSearch" value="" style="width:215px" onkeypress="getKeyPress(event, '<?echo $ac_type;?>')">
	<br><br>
	<i>Double click an item to select it and exit</i>
	<br>

	<select id="accountList" name="productList" size="27" style="width:95%;heigh:100%;font-family:courier;" ondblclick="javascript:selectItem(<?echo "'".$formname."', '".$fieldname."'";?>)">
	</select>
	<br><br>

	<input type="button" name="action" value="OK" class="v3button" onclick="javascript:selectItem(<?echo "'".$formname."', '".$fieldname."'";?>)">
	<input type="button" name="action" value="Cancel" class="v3button" onclick="window.close()">

<script language="javascript">
  document.product_search.strSearch.focus();
</script>

</body>
</html>