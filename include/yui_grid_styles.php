<?php
header("Content-type: text/css");
require_once("../include/const.inc.php");
require_once("Config.php");

$config = new Config();
$arrConfig =& $config->parseConfig($str_root."include/config.ini", "IniFile");
$templateSection = $arrConfig->getItem("section", 'settings');
if ($templateSection === false) {
	$int_grid_font_size = 12;
	$int_grid_rows = 15;
}
else {
	$font_size_directive =& $templateSection->getItem("directive", "grid_font_size");
	$grid_rows_directive =& $templateSection->getItem("directive", "grid_rows");
	$int_grid_font_size = $font_size_directive->getContent();
	$int_grid_rows = $grid_rows_directive->getContent();
}

?>

.yui-skin-sam .yui-dt table {
	border-collapse:collapse;
	border-spacing:0;
	font-family:verdana,sans-serif;
	font-size:<?php echo $int_grid_font_size;?>px;
}

.yui-skin-sam .yui-dt th, .yui-skin-sam .yui-dt th a {
	color:#000000;
	font-size:1.0em;
	font-weight:bold;
	text-decoration:none;
	vertical-align:bottom;
}

.yui-skin-sam tr.yui-dt-even{
	background-color:#edeae1;
}

.yui-skin-sam tr.yui-dt-odd{
	background-color:#ede4cd;
}

.yui-skin-sam tr.yui-dt-even td.yui-dt-asc,.yui-skin-sam tr.yui-dt-even td.yui-dt-desc{
	background-color:#edeae1;
}

.yui-skin-sam tr.yui-dt-odd td.yui-dt-asc,.yui-skin-sam tr.yui-dt-odd td.yui-dt-desc{
	background-color:#ede4cd;
}

.yui-skin-sam .yui-dt-list tr.yui-dt-even{background-color:#FFF;}
.yui-skin-sam .yui-dt-list tr.yui-dt-odd{background-color:#FFF;}

.yui-skin-sam .yui-dt-selected{
	font-weight:bold;
	border:1px solid #fff;
	background-color:#fff;
}

.yui-skin-sam tr.yui-dt-highlighted,.yui-skin-sam tr.yui-dt-highlighted td.yui-dt-asc,.yui-skin-sam tr.yui-dt-highlighted td.yui-dt-desc,.yui-skin-sam tr.yui-dt-even td.yui-dt-highlighted,.yui-skin-sam tr.yui-dt-odd td.yui-dt-highlighted{
	cursor:pointer;
	background-color: #edd9a6;
}

.yui-skin-sam th.yui-dt-selected,.yui-skin-sam th.yui-dt-selected a{
 	background-color:#edd28e;
}
.yui-skin-sam tr.yui-dt-selected td,.yui-skin-sam tr.yui-dt-selected td.yui-dt-asc,.yui-skin-sam tr.yui-dt-selected td.yui-dt-desc{
 	background-color:#edd28e;
	color:#000;
}