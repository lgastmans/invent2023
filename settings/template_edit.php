<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");

    $int_id = 0;
    if (IsSet($_GET['id']))
        $int_id = $_GET['id'];
    
    if (IsSet($_POST['action'])) {
        if ($_POST['action'] == 'Save') {
            if (IsSet($_POST['cb_is_default']))
                $str_is_default = 'Y';
            else
                $str_is_default = 'N';
            
            $str_name = addslashes($_POST['name']);
            $int_type = intval($_POST['select_type']);
            $str_title = addslashes($_POST['text_title']);
            $str_header = addslashes($_POST['text_header']);
            $str_content = addslashes($_POST['text_content']);
            $str_footer = addslashes($_POST['text_footer']);
            
            $str_query = "
                UPDATE templates
                SET is_default = '".$str_is_default."',
                    template_type = ".$int_type.",
                    name = '".$str_name."',
                    title = '".$str_title."',
                    header = '".$str_header."',
                    content = '".$str_content."',
                    footer = '".$str_footer."'
                WHERE id = $int_id";
                
            $qry = new Query($str_query);
            
            if ($str_is_default == 'Y') {
                $qry->Query("UPDATE templates SET is_default='N' WHERE (id <> $int_id) AND (template_type = $int_type)");
            }
            
            echo "<script language='javascript'>";
            echo "if (window.opener)";
            echo "window.opener.document.location=window.opener.document.location.href;";
            echo "window.close()";
            echo "</script>";
        }
    }
    
    $qry = new Query("SELECT * FROM templates WHERE id = $int_id");
    
    $str_is_main = 'N';
    if ($qry->FieldByName('is_main') == 'Y')
        $str_is_main = 'Y';
?>

<html>
<head>
    <link rel="stylesheet" type="text/css" href="../include/<?echo $str_css_filename;?>" />
    
    <script language='javascript'>
        function closeWindow() {
            if (window.opener)
                    window.opener.document.location=window.opener.document.location.href;
            window.close();
        }
    </script>
</head>

<body topmargin='10px' leftmargin='10px'>

    <form name='template_new' method='POST'>
    <?
        if ($str_is_main == 'Y')
            echo "<font color='red'>This template cannot be editted</font>";
    ?>
    
    <table width='100%' border='0' cellpadding='0' cellspacing='0'>
        <tr>
            <td class='<?echo $str_class_header;?>'><input type='checkbox' name='cb_is_default' <?if ($qry->FieldByName('is_default') == 'Y') echo "checked";?>>Default</td>
        </tr>
        <tr><td><img src='../images/blank.gif' height='5px'></td></tr>
        
        <tr>
            <td valign='bottom' class='<?echo $str_class_header;?>'>Name</td>
        </tr>
        <tr>
            <td><input type='text' name='name' value='<?echo stripslashes($qry->FieldByName('name'));?>' class='<?echo $str_class_input400?>'><td>
        </tr>
        <tr><td><img src='../images/blank.gif' height='5px'></td></tr>
        
        <tr>
            <td class='<?echo $str_class_header;?>'>Type</td>
        <tr>
        <tr>
            <td>
                <select name='select_type'>
                    <option value='1' <?if ($qry->FieldByName('template_type') == 1) echo "selected";?>>Bill
                    <option value='2' <?if ($qry->FieldByName('template_type') == 2) echo "selected";?>>Order Invoice
                    <option value='3' <?if ($qry->FieldByName('template_type') == 3) echo "selected";?>>Order Proforma Invoice
                </select>
            </td>
        <tr>
        <tr><td><img src='../images/blank.gif' height='5px'></td></tr>

        <tr>
            <td class='<?echo $str_class_header;?>'>Title</td>
        <tr>
        <tr>
            <td><textarea rows='6' cols='80' name='text_title'><?echo stripslashes($qry->FieldByName('title'));?></textarea></td>
        <tr>
        <tr><td><img src='../images/blank.gif' height='5px'></td></tr>
        
        <tr>
            <td class='<?echo $str_class_header;?>'>Header</td>
        <tr>
        <tr>
            <td><textarea rows='6' cols='80' name='text_header'><?echo stripslashes($qry->FieldByName('header'));?></textarea></td>
        <tr>
        <tr><td><img src='../images/blank.gif' height='5px'></td></tr>
        
        <tr>
            <td class='<?echo $str_class_header;?>'>Content</td>
        <tr>
        <tr>
            <td><textarea rows='6' cols='80' name='text_content'><?echo stripslashes($qry->FieldByName('content'));?></textarea></td>
        <tr>
        <tr><td><img src='../images/blank.gif' height='5px'></td></tr>
        
        <tr>
            <td class='<?echo $str_class_header;?>'>Footer</td>
        <tr>
        <tr>
            <td><textarea rows='6' cols='80' name='text_footer'><?echo stripslashes($qry->FieldByName('footer'));?></textarea></td>
        <tr>
        <tr><td><img src='../images/blank.gif' height='5px'></td></tr>
        
        <? if ($str_is_main <> 'Y') { ?>
        <tr>
            <td><input type='submit' name='action' value='Save'>&nbsp;<input type='button' name='action' value='Close' onclick='closeWindow()'></td>
        </tr>
        <? } ?>
        
    </table>
    
    </form>
    
</body>
</html>