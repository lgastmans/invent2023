<? 

/**

* 

* @version 	$Id: title.php,v 1.1.1.1 2006/02/14 05:03:57 cvs Exp $

* @copyright 	Cynergy Software 2005

* @author	Luk Gastmans

* @date		12 Oct 2005

* @module 	Title Bar Navigation

* @name  	title.php

*

* The title bar navigation is used for the entire site.  It lets you jump to

* the various modules that you have access to as well as switch months using

* the Previous/Next buttons.

*/

$bool_root_folder = true;



require_once "../include/session.inc.php";



require_once  "../include/const.inc.php";

require_once  "../include/db.inc.php";

//require_once  "include/module.inc.php";



if (!empty($_GET['int_set_month'])) 

	$int_new_month = $_GET['int_set_month'];

else 

	$int_new_month = $_SESSION["int_month_loaded"];



if (!empty($_GET['int_module_selected'])) {

	$_SESSION['int_module_selected'] = $_GET['int_module_selected'];


}



if (!empty($_GET['int_set_storeroom'])) {

	$_SESSION['int_current_storeroom'] = $_GET['int_set_storeroom'];


}



if (!empty($_GET['int_set_year'])) 

	$int_new_year = $_GET['int_set_year'];

else 

	$int_new_year = $_SESSION["int_year_loaded"];



$bool_new_month_loaded = false;

$bool_month_error = false;



//

// if the month or year changed, reload

//

if (($int_new_year <> $int_year_loaded) || ($int_new_month <> $int_month_loaded)) {

    $_SESSION["int_year_loaded"] = $int_new_year;

    $int_month_loaded = $int_new_month;


} else {

    $int_new_month = $_SESSION["int_month_loaded"];

    $int_new_year =  $_SESSION["int_year_loaded"];

  

}



//

// set a default module if none is selected

//

if (empty($_SESSION['int_module_selected'])) {

	$_SESSION['int_module_selected'] = 1;


}  



?>

<html>

<head>

	<link href="../include/styles.css" rel="stylesheet" type="text/css">

</head>

<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#d0d0d0">

<script language=JavaScript>

<? if ($bool_month_error==false) { ?> 

  parent.frames["content"].document.location = parent.frames["content"].document.location.href;

<? } else { ?>

  alert("The month/year you are trying to load could not be found!");

<? } ?>

var curImage;

var curImageFile;



function selectImage(anObject,anImage) {

  if (curImage) {

  	curImage.src='images/'+curImageFile+".gif";

  }

  curImage=anObject;

  curImageFile=anImage;

  curImage.src='images/'+curImageFile+"down.gif";

}

</script>

	<table height="100%" width="100%" cellspacing=0 border=0 cellpadding=0>

		<TR height="50%">

			<form method='get'>

			<TD class="headerText" bgcolor="#d0d0d0">

				&nbsp;<font class='title'><? echo $str_application_title; ?></font><br>

			</TD>

			<td align=right class='normaltext'>

				<a href='title.php?int_set_year=<? echo $int_new_year; ?>&int_set_month=<? if ($int_new_month>1) { echo ($int_new_month-1); } else echo "12&int_set_year=".($int_new_year-1); ?>'><font color='white'>&lt;&lt; Prev</font></a> <font class='title'> <? echo getMonthName($int_new_month)." ".$int_new_year; ?></font> <a href='title.php?int_set_year=<? echo $int_new_year; ?>&int_set_month=<? if ($int_new_month<12) { echo ($int_new_month+1); } else echo "1&int_set_year=".($int_new_year+1); ?>'><font color='white'> Next &gt;&gt;</font></a><br>

			

				<select name='int_set_storeroom' onchange="this.form.submit();">

<?				

				$arr_storeroom_list = getStoreroomList();		

				foreach ($arr_storeroom_list as $key=>$value) {

					$sel=($key==$_SESSION['int_current_storeroom']?'selected':'');

					echo "<option value='$key' $sel>$value</option>\n";

				}



?>

				</select>

			</td>

			</form>

		</TR>

		<tr height="50%" valign=bottom>

			<TD valign=bottom colspan=2>



<? for ($i=0; $i<count($_SESSION['arr_modules']);$i++) {

/*	if (($_SESSION['int_module_selected']==$_SESSION['arr_modules'][$i]["module_id"]) && ($_SESSION['arr_modules'][$i]["storeroom_id"]==$_SESSION['int_current_storeroom'])) {

		echo "<a href='javascript:openModule(\"".$_SESSION['arr_modules'][$i]["module_id"]."\");' class='v3buttonselected'>".$_SESSION['arr_modules'][$i]["module_name"]."</a>";



		if (file_exists($_SESSION['arr_modules'][$i]["module_folder"]."menu.inc.php")) {

			include($_SESSION['arr_modules'][$i]["module_folder"]."menu.inc.php");

		}

	} else if ($_SESSION['arr_modules'][$i]["storeroom_id"]==$_SESSION['int_current_storeroom']){

	echo "<a href='javascript:openModule(\"".$_SESSION['arr_modules'][$i]["module_id"]."\");' class='v3button'>".$_SESSION['arr_modules'][$i]["module_name"]."</a>";



	}*/

		$_SESSION['arr_modules'][$i]->buildMenu($_SESSION['int_module_selected']);



}

?>

				<img src='images/graydot.gif' width="100%" height='1' border=0>

			</TD>

		</tr>

	</table>

<script language="JavaScript">



</script>  



</body>

</html>