<?

  session_start();
  
  require_once("../include/db.inc.php");
  require_once("../include/const.inc.php");  
  $filter="";
  
  if ($accountHeadName<>"") {
    $order="Name";
    $filter=" WHERE Name like '".addquotes($accountHeadName)."%' ";    
  } else $order="Name";
  if ($filter<>"") {
  $sql = new QueryYear("
SELECT
  CEs.ID as ID, CEs.Name as Name, ahg.GroupName as GroupName
FROM
  CEs 
INNER JOIN CEsGroups ahg on CEs.GroupID = ahg.ID
  $filter 
  order by $order");
  $numRows=$sql->RowCount();
  } else {
  $sql = new QueryYear("
SELECT
  CEs.ID as ID, CEs.Name as Name, ahg.GroupName as GroupName
FROM
  CEs 
INNER JOIN CEsGroups ahg on CEs.GroupID = ahg.ID
$filter order by $order");
  $numRows=$sql->RowCount();//>100?100:$sql->RowCount());
  
  } 
?>
<html>
<head><TITLE>Select Account Head</TITLE>
<STYLE TYPE="text/css">
.headertext {
font-family:Verdana,sans-serif;
font-size:12px;
color:white;
}
.normaltext {
font-family:Verdana,sans-serif;
font-size:12px;
color:black;
}
</style>
<script language='javascript'>
function ButtonClick() {
  var st = document.forms["selform"].accountList.value;
//  alert(st);  
  var id = st.substring(0,st.indexOf(",")); 
  st = st.substring(st.indexOf(",")+1,st.length);
  window.opener.document.forms["<? echo $frm; ?>"].<? echo $frmField; ?>Name.value = st;
  window.opener.document.forms["<? echo $frm; ?>"].<? echo $frmField; ?>ID.value = id;
  window.close();
}
</script>
</head>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0">
<?
  if ($loggedIn<>1) {
  ?><script language="javascript">
    window.close(); </script><?
  }

  
?>
<table width="100%" bgcolor="#E0E0E0"><TR><TD height=45 class="headerText" bgcolor="#808080"><h1>Select Account Head</h1></TD></TR>
<tr>
<TD>
<br>
<form name='selform' method="POST"><table border=0>
<TR><TD class='normaltext'>Account Head</TD><td><input type='text' size=20 name='accountHeadName' value="<? echo $accountHeadName;?>"> <input type='submit' name='Search' value="Search">
<input type='hidden' name='frm' value="<? echo $frm;?>"><input type='hidden' name='frmField' value="<? echo $frmField;?>">
</td></TR>
<TR><TD class='normaltext'></TD><td><select name="accountList" size=10>
<? echo $sql->RowCount();
for ($i=0;$i<$sql->RowCount();$i++) { ?>
  <option value="<?=$sql->FieldByName("ID").",".stripslashes($sql->FieldByName("Name")); ?>"><?=stripslashes($sql->FieldByName("Name"))." (".stripslashes($sql->FieldByName("GroupName")).")"; ?></option>
  <? $sql->Next(); 
  }
  ?>
</select>
</td></TR>
<TR><TD></TD><td><br><input type='button' name='OK' value="OK" onclick='ButtonClick();'> <input type='button' onclick="window.close();" name='Cancel' value="Cancel"></td></TR>
</table>
</form>
</td></tr>
</table>
</body>
</html>