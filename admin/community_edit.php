<?
	require_once("../include/db.inc.php");
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	
  $str_message = '';
  
  if (IsSet($_GET["action"])) {
    if ($_GET["action"] == 'save') {
      $qry = new Query("
        UPDATE communities
        SET community_name = '".$_GET["name"]."'
        WHERE (community_id = ".$_GET["id"].")"
      );
    }
    echo "<script language=\"javascript\">";
    echo "window.opener.document.location=window.opener.document.location.href;";
    echo "window.close();";
    echo "</script>";
  }
  else if (IsSet($_GET["id"])) {
    $qry = new Query("
      SELECT *
      FROM communities
      WHERE (community_id = ".$_GET["id"].")
    ");
    if ($qry->RowCount() == 0) {
      $str_message = "ERROR: Community not found.";
    }
  }

?>

<script language="javascript">

	function setUppercase(aField) {
		aField.value = aField.value.toUpperCase();
	}

	function CloseWindow() {
    window.opener.document.location=window.opener.document.location.href;
    window.close();
	}

  function save_data(id) {
    oTextBoxCommunity = document.community_edit.community_name;
    document.location = "community_edit.php?action=save&id="+id+"&name="+oTextBoxCommunity.value;
  }
  
	function focusNext(aField, focusElem, evt) {
		evt = (evt) ? evt : event;
		var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);

		var oTextBoxCommunity = document.community_edit.community_name;
		var oButtonSave = document.community_edit.Save;
		
	
		if (charCode == 13 || charCode == 3) {
			if (focusElem == 'button_save') {
			 oButtonSave.focus();
			}
		} else if (charCode == 27) {
			oTextBoxCommunity.select();
			clearValues;
		}
		return false;
	}  
	
	function clearValues() {
		var oTextBoxCommunity = document.community_edit.community_name;
    oTextBoxCommunity.value = '';
	}
</script>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	</head>
<body id='body_bgcolor' leftmargin=5 topmargin=5 marginwidth=15 marginheight=15>

<form name="community_edit" method="GET" onsubmit="return false">

<table width='100%' height='90%' border='0' >
<tr>
	<td align='center' valign='center'>
	
<?
	boundingBoxStart("400", "../images/blank.gif");

	if ($str_message != '')  { ?>
		<script language='javascript'>
		alert('<?echo $str_message?>');
		</script>
<?
	}
?>

	<table width="100%" height="30" border="0" cellpadding="2" cellspacing="0">
		<tr>
			<td align="right" width="120" class='normaltext_bold'>Community</td>
			<td>
  			<input type="text" name="community_name" class="input_100" value="<? echo $qry->FieldByName('community_name');?>" autocomplete="OFF" onkeypress="focusNext(this, 'button_save', event)" onkeyup="javascript:setUppercase(this)"></td>
		</tr>
		<tr>
		  <td colspan='2'>&nbsp;</td>
		</tr>
		<tr>
		  <td align="right">
	     <input type="button" name="Save" value="Save" class="mainmenu_button" onclick="save_data(<?echo $_GET["id"]?>)">
	    </td>
	     <td>
      <input type="button" name="Close" value="Close" class="mainmenu_button" onclick="CloseWindow()">
      </td>
    </tr>
	</table>
	
<?
    boundingBoxEnd("400", "../images/blank.gif");
?>

</td></tr>
</table>

</form>

<script language="javascript">
  document.community_edit.community_name.focus();
</script>

</body>
</html>