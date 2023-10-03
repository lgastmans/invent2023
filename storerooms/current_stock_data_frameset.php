<?
    $str_variables = "category_type=".$_GET['category_type']."&category_id=".$_GET['category_id']."&order=".$_GET['order']."&global_stock=".$_GET['global_stock']."&show=".$_GET['show'];
?>
<html><head><TITLE>Pour Tous</TITLE></head>
<frameset rows='50,*,50' border=0 scrolling=no>
<frame name='header' src="current_stock_data_header.php" scrolling=no>
<frame name='content' src="current_stock_data.php?<?echo $str_variables;?>" scrolling=auto>
<frame name='footer' src="current_stock_data_footer.php" scrolling=no>
</frameset>
</html>