<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");

    $bool_close = false;
    if (IsSet($_GET['close_window']))
        if ($_GET['close_window'] == 'yes')
            $bool_close = true;

    $bool_open = true;
    if (count($_SESSION['arr_order_edit_bills']) >= 1) {
        $int_id = $_SESSION['arr_order_edit_bills'][0];
        $_SESSION['arr_order_edit_bills'] = array_delete($_SESSION['arr_order_edit_bills'], 0);
    }
    else {
        $bool_open = false;
        $bool_close = true;
    }

?>

<script language='javascript'>
    <? if ($bool_open) { ?>
        myWin = window.open('order_edit_frameset.php?id=<?echo $int_id?>','order_edit_frameset','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=no,resizable=yes,width=800,height=600,top=0,left=0');
        myWin.moveTo((screen.availWidth/2 - 800/2), (screen.availHeight/2 - 600/2));
        myWin.focus();
    <? } ?>
    <? if ($bool_close) { ?>
        window.close();
    <? } ?>
</script>