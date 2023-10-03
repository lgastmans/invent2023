<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");

    $str_id_list = '';
    if (IsSet($_GET['id_list']))
            $str_id_list = $_GET['id_list'];

    $arr_id_list = explode('|', $str_id_list);
    
    unset($_SESSION['arr_order_edit_bills']);
    
    for ($i=0; $i<count($arr_id_list)-1; $i++) {
        $_SESSION['arr_order_edit_bills'][] = $arr_id_list[$i];
    }
    
?>

<script language='javascript'>
    document.location = 'edit_order_bill_frameset.php?close_window=yes';
</script>