<?
	require_once("../include/db.inc.php");
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	
  $str_message = '';
  
  if (IsSet($_POST["action"])) {
    if ($_POST["action"] == "save") {
	 
     if (!empty($_POST["community_name"])) {
       $cur_community_name = $_POST["community_name"];
       
  	   // verify community name
  	   $qry = new Query("
  	     SELECT *
  	     FROM communities
  	     WHERE (community_name = '".$cur_community_name."')
  	   ");
       if ($qry->RowCount() > 0) {
  	     $str_message = "Community already exists.";
       }
       else {
        $str_query ="
          INSERT INTO communities
          (community_name)
          VALUES ('".$_POST["community_name"]."')";
        $qry->Query($str_query);
        if ($qry->b_error == true) {
          $str_message = "error inserting into communities ".$str_query;
        }
       }
     }
     else
       $str_message = "Community cannot be blank";
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

  function save_data() {
    community_new.submit();
  }
  
	function focusNext(aField, focusElem, evt) {
		evt = (evt) ? evt : event;
		var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);

		var oTextBoxCommunity = document.community_new.community_name;
		var oButtonSave = document.community_new.Save;
		
	
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
		var oTextBoxCommunity = document.community_new.community_name;
    
    oTextBoxCommunity.value = '';
	}
</script>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	</head>
<body id='body_bgcolor' leftmargin=5 topmargin=5 marginwidth=15 marginheight=15>

<form name="community_new" method="POST" onsubmit="return false">

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
  			<input type="text" name="community_name" value="" class='input_100' autocomplete="OFF" onkeypress="focusNext(this, 'button_save', event)" onkeyup="javascript:setUppercase(this)"></td>
		</tr>
		<tr>
		  <td colspan='2'>&nbsp;</td>
		</tr>		<tr>
		  <td align="right">
		    <input type="hidden" name="action" value="save">
	     <input type="button" name="Save" value="Save" class="mainmenu_button" onclick="save_data()">
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
  document.community_new.community_name.focus();
</script>

</body>
</html>