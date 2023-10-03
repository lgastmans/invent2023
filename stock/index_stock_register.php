<html><head><TITLE><? require_once('../include/const.inc.php'); echo $str_application_title; ?> </TITLE></head>

<?
    $int_type = 1;
    if (IsSet($_GET['registry_type']))
        $int_type = $_GET['registry_type'];

    if ($int_type == 1) { ?>
        <frameset id='stock_registry' rows='40,40,*' border=1 scrolling=no>
                <frame name='stock_registry_type' src="stock_registry_type.php?registry_type=<?echo $int_type?>" scrolling=no noresize>
                <frame name='stock_registry_summary' src="stock_registry_menu.php" scrolling=no noresize>
                <frame name='stock_registry_frameset' src="stock_registry_frameset.php" scrolling=no noresize>
        </frameset>
    <? } else { ?>
        <frameset id='stock_registry' rows='40,40,*' border=2 scrolling=no>
                <frame name='stock_registry_type' src="stock_registry_type.php?registry_type=<?echo $int_type?>" scrolling=no noresize>
                <frame name='stock_registry_month_menu' src="stock_registry_month_menu.php" scrolling=no noresize>
                <frame name='stock_registry_month' src="stock_registry_month_frameset.php" scrolling=yes noresize>
        </frameset>
    <? } ?>
</html>