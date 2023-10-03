<?
	require_once("../../include/const.inc.php");
	require_once("../../common/functions.inc.php");
	require_once("db_mysqli.php");

	$str_type = "SALESPERSON";
	if (IsSet($_GET['stat_type']))
		$str_type = $_GET['stat_type'];
	
	if ($str_type == 'CATEGORY') {
		$arr = buildCategoryList();
	}
	else if ($str_type == 'SUPPLIER') {
		$str_query = "
			SELECT *
			FROM stock_supplier
			ORDER BY supplier_name
		";
		$qry =& $conn->query($str_query);
	}
	else if ($str_type == 'SALESPERSON') {
		$str_query = "
			SELECT *
			FROM salespersons
			ORDER BY first
		";
		$qry =& $conn->query($str_query);
	}

?>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="../../include/styles.css" />
	<script language="javascript">
		var handleSuccess = function(o){
			if(o.responseText !== undefined){
				div.innerHTML += o.responseText;
			}
		}

		var handleFailure = function(o){
			if(o.responseText !== undefined){
				div.innerHTML = "<ul><li>Transaction id: " + o.tId + "</li>";
				div.innerHTML += "<li>HTTP status: " + o.status + "</li>";
				div.innerHTML += "<li>Status code message: " + o.statusText + "</li></ul>";
			}
		}

		var callback = {
			success:handleSuccess,
			failure:handleFailure
		};

		//var sUrl = "admin/import_xml_files.php?import="+arrImport[i];
		//var request = YAHOO.util.Connect.asyncRequest('GET', sUrl, callback);
		
		function setText(evt, aField) {
			evt = (evt) ? evt : event;
			var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
		
			if (charCode == 13 || charCode == 3 || charCode == 9) {
				aField.select();
				apply_settings('screen');
			}
			return true;
		}
	</script>
</head>
<body id='body_bgcolor'>
	<? if ($str_type == 'CODE') { ?>
		<input type="text" id="product_code" name="product_code" value="" onkeypress="return setText(event, this)"  class='input_100'>
	<? } else { ?>
		<select name="select_value" id="select_value">
		<?
			if ($str_type == 'CATEGORY') {
				foreach ($arr as $value) {
					echo "<option value=".$value['category_id'].">".$value['category_description']."</option>\n";
				}
			}
			else if ($str_type == 'SUPPLIER') {
				while ($obj = $qry->fetch_object()) {
					echo "<option value=".$obj->supplier_id.">".$obj->supplier_name."</option>\n";
				}
			}
			else if ($str_type == 'SALESPERSON') {
				echo "<option value='ALL'>ALL</option>\n";
				while ($obj = $qry->fetch_object()) {
					echo "<option value=".$obj->id.">".$obj->first." ".$obj->last."</option>\n";
				}
			}
		?>
		</select>
	<? } ?>
</body>
</html>