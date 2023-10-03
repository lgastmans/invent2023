<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");


    $int_type = 1;
    if (IsSet($_GET['registry_type']))
        $int_type = $_GET['registry_type'];

?>

<html>
<head>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
        
        <script language='javascript'>
            function setRegistryType() {
                var oSelectType = document.getElementById('select_type');
                
                parent.document.location = 'index_stock_register.php?registry_type='+oSelectType.value;
            }
        </script>
</head>
<body id='body_bgcolor'>

<font class='normaltext'>Type:</font>
<select name='select_type' id='select_type' onchange='javascript:setRegistryType()' class='select_200'>
    <option value=1 <? if ($int_type == 1) echo 'selected';?> >Detailed
    <option value=2 <? if ($int_type == 2) echo 'selected';?> >Month-wise
</select>

</body>
</html>