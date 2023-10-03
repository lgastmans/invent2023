<?
/**
* 
* @version 	$Id: newmonth.php,v 1.1.1.1 2006/02/14 05:03:57 cvs Exp $
* @copyright 	Cynergy Software 2005
* @author	Luk Gastmans
* 
*/
/*  $str_cur_module='Admin';
  require_once("../include/const.inc.php");

  require_once("../include/session.inc.php");
  require_once("../common/functions.inc.php");
 
  require_once("../include/db.inc.php");
  require_once "../include/grid.inc.php";
*/  
  
  
function dirList ($directory) 
{

    // create an array to hold directory list
    $results = array();
    
   
    // create a handler for the directory
    $handler = opendir($directory);

    // keep going until all files in directory have been read
    while ($file = readdir($handler)) {

        // if $file isn't this directory or its parent, 
        // add it to the results array
        if ($file != '.' && $file != '..')
        {
          if (is_dir($directory.$file)) {
            $results[] = $directory.$file.'/';
            $arr_res = dirList($directory.$file.'/');
            $results = array_merge($results, $arr_res);
          }
        } 
    }

    // tidy up: close the handler
    closedir($handler);

    // done!
    return $results;

}


$arr_folder_list = dirList("../");
$str_res = "";
if ($_REQUEST['action']=='Upload') {
  if (file_exists($_FILES['uploadfile']['tmp_name'])) {
    if (file_exists($_REQUEST['folder'].$_FILES['uploadfile']['name'])) {
      copy($_REQUEST['folder'].$_FILES['uploadfile']['name'],$_REQUEST['folder'].$_FILES['uploadfile']['name'].'.bak');
    }
    unlink($_REQUEST['folder'].$_FILES['uploadfile']['name']);
    move_uploaded_file($_FILES['uploadfile']['tmp_name'],$_REQUEST['folder'].$_FILES['uploadfile']['name']);
    $str_res = 'File successfully uploaded to '.$_REQUEST['folder'].$_FILES['uploadfile']['name'];
  }
}
?>
<html>
<body>
<form method='post' enctype="multipart/form-data">
Update a file:<br>
<font color='red'><? echo $str_res; ?></font><br><br>
Choose Folder: <br>
<select name='folder'>
<option value=''>Select Folder</option>
<? 
foreach ($arr_folder_list as $key=>$value) {
  echo "<option value='$value'>$value</option>\n";
}
?>
</select>
<br>
Select a file:<br>
<input type=file name='uploadfile'>
<br><br>
<input type='submit' name='action' value='Upload'>
</form>
</body></html>