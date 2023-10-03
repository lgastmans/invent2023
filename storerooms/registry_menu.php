<?
    $str_cur_module='Storerooms';
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");

    $_SESSION['int_storerooms_menu_selected']=2;

?>

<script language='javascript'>

    function setText(evt, aField) {
        evt = (evt) ? evt : event;
        var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
        
        if (charCode == 13 || charCode == 3 || charCode == 9) {
            aField.select();
            loadStatement();
        }
    }

    function loadStatement() {
        var oTextCode = document.registry_menu.product_code;
        
        str_url = 'registry_content.php?'+
            'product_code='+oTextCode.value;
        parent.frames['content'].document.location = str_url;
    }

</script>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../include/<?echo $str_css_filename;?>" />
	</head>

<body leftmargin=0 topmargin=0 marginwidth=7 marginheight=7>
<form name="registry_menu" method="GET" onsubmit="return false">

    <table border='0' cellpadding='2' cellspacing='0'>
	<tr>
	    <td align='right' class='<?echo $str_class_header;?>'>
                Code:
	    </td>
            <td>
                <input type='text' name='product_code' value='' onkeypress='setText(event, this)'>
            </td>
	</tr>
    </table>
    
</form>
</body>
</html>
