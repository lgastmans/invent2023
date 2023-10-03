<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");

    $str_print_page = '';
    if (IsSet($_GET['print_page']))
        $str_print_page = $_GET['print_page'];
    
    $arr_variables = array();
    if (IsSet($_GET['variables'])) {
        $str_variables = $_GET['variables'];
        $arr_strings = explode('|', $str_variables);
    }
?>

<html>
<head>
    <link rel="stylesheet" type="text/css" href="../include/<?echo $str_css_filename;?>" />
    
    <script language='javascript'>
        
        function print_page() {
            var oRadioAll = document.print_dialog.radio_range[0];
            var oRadioSelect = document.print_dialog.radio_range[1];
            var oTextFrom = document.print_dialog.range_from;
            var oTextTo = document.print_dialog.range_to;
            
            str_variables = '';
            <? foreach ($arr_strings as $value) { ?>
                str_variables = str_variables + '&<? echo $value ?>'
            <? } ?>
            
            if (oRadioAll.checked) {
                str_url = '<?echo $str_print_page?>?' +
                    'print_range=ALL' +
                    str_variables;
            }
            else if (oRadioSelect.checked)
                str_url = '<?echo $str_print_page?>?' +
                    'print_range=RANGE' +
                    '&range_from=' + oTextFrom.value +
                    '&range_to=' + oTextTo.value +
                    str_variables;
            window.open(str_url,'print_dialog','toolbar=no,fullscreen=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=300,height=150,top=0,left=0');
        }
        
    </script>
</head>

<body>
    <form name='print_dialog' method='GET'>
    
    <table border='0' width='100%' cellpadding='0' cellspacing='5'>
        <tr>
            <td class='<?echo $str_class_header?>'><input type='radio' name='radio_range' value='all' checked>print all pages</td>
        </tr>
        <tr>
            <td class='<?echo $str_class_header?>'><input type='radio' name='radio_range' value='range'> from <input type='text' name='range_from' value='1' style='width:50px'> to <input type='text' name='range_to' value='1' style='width:50px'></td>
        </tr>
        <tr>
            <td><input type='button' name='action' value='print' onclick='print_page()'>  <input type='button' name='action' value='close' onclick='window.close()'>
        </tr>
    </table>
    </form>
    
</body>
</html>