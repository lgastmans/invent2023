<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");

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
                INSERT INTO templates
                    (is_default,
                        template_type,
                        name,
                        title,
                        header,
                        content,
                        footer)
                    VALUES (
                        '".$str_is_default."',
                        ".$int_type.",
                        '".$str_name."',
                        '".$str_title."',
                        '".$str_header."',
                        '".$str_content."',
                        '".$str_footer."')";
            echo $str_query;
            $qry = new Query($str_query);
            
            $int_id = $qry->getInsertedID();
            
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
    
    <table width='100%' border='0' cellpadding='0' cellspacing='0'>
        <tr>
            <td class='<?echo $str_class_header;?>'><input type='checkbox' name='cb_is_default'>Default</td>
        </tr>
        <tr><td><img src='../images/blank.gif' height='5px'></td></tr>
        
        <tr>
            <td valign='bottom' class='<?echo $str_class_header;?>'>Name</td>
        </tr>
        <tr>
            <td><input type='text' name='name' value='' class='<?echo $str_class_input300?>'><td>
        </tr>
        <tr><td><img src='../images/blank.gif' height='5px'></td></tr>
        
        <tr>
            <td class='<?echo $str_class_header;?>'>Type</td>
        <tr>
        <tr>
            <td>
                <select name='select_type'>
                    <option value='1'>Bill
                    <option value='2'>Order Invoice
                    <option value='3'>Order Proforma Invoice
                </select>
            </td>
        <tr>
        <tr><td><img src='../images/blank.gif' height='5px'></td></tr>
        
        <tr>
            <td class='<?echo $str_class_header;?>'>Title</td>
        <tr>
        <tr>
            <td><textarea rows='6' cols='80' name='text_title'></textarea></td>
        <tr>
        <tr><td><img src='../images/blank.gif' height='5px'></td></tr>
        
        <tr>
            <td class='<?echo $str_class_header;?>'>Header</td>
        <tr>
        <tr>
            <td><textarea rows='6' cols='80' name='text_header'></textarea></td>
        <tr>
        <tr><td><img src='../images/blank.gif' height='5px'></td></tr>
        
        <tr>
            <td class='<?echo $str_class_header;?>'>Content</td>
        <tr>
        <tr>
            <td><textarea rows='6' cols='80' name='text_content'></textarea></td>
        <tr>
        <tr><td><img src='../images/blank.gif' height='5px'></td></tr>
        
        <tr>
            <td class='<?echo $str_class_header;?>'>Footer</td>
        <tr>
        <tr>
            <td><textarea rows='6' cols='80' name='text_footer'></textarea></td>
        <tr>
        <tr><td><img src='../images/blank.gif' height='5px'></td></tr>
        
        <tr>
            <td><input type='submit' name='action' value='Save'>&nbsp;<input type='button' name='action' value='Close' onclick='closeWindow()'></td>
        </tr>
        
    </table>
    
    </form>
    
</body>
</html>