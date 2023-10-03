<?
  require "../include/const.inc.php";
  require "../include/session.inc.php";
  require "account.php";

  $f_account_from = "102993";
  $f_account_to = "102673";
  $f_description = "test transfer via web";
  $f_amount = 12000.5;
  $f_module_id = 4;
  $f_module_record_id = 1;
  echo createTransfer($f_account_from, $f_account_to, $f_description, $f_amount, $f_module_id,$f_module_record_id,false);
?>