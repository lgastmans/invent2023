<?
	require_once("../include/const.inc.php");
	require_once("Config.php");
	
	$config = new Config();
	$arrConfig = & $config->parseConfig($str_root."include/config.ini", "IniFile");
	
	$templateSection = $arrConfig->getItem("section", 'billing');
	$account_discount_directive =& $templateSection->getItem("directive", "fs_account_discount");
	$account_discount_directive->setContent(intval($_GET['discount']));
	$config->writeConfig($str_root."include/config.ini", "IniFile");
	
	echo "ok";
?>