<?

  session_start();
  
  require_once("../include/db.inc.php");
  require_once("../include/const.inc.php");
  
  $filter="";
  
  if ($accountName<>"") {
    $order="Name";
    $filter=" WHERE Name like '".addquotes($accountName)."%' ";    
  } else if ($accountNumber<>"") {
    $accountNumber=strtoupper($accountNumber);
    $order="AccountNumber";
    if (strpos($accountNumber,"C")>0) {
      $accNum=substr($accountNumber,0,strlen($accountNumber)-1);
      $filter=" WHERE AccountNumber = '$accNum' and TypeID=4 ";
    } else if (strpos($accountNumber,"K")>0) {
      $accNum=substr($accountNumber,0,strlen($accountNumber)-1);
      $filter=" WHERE AccountNumber = '$accNum' and TypeID=3 ";
    } else $filter = " WHERE AccountNumber like '$accountNumber%' ";
  } else $order="Name";
  if ($filter<>"") {
  $sql = new Query("
SELECT
  AccountNumber, ID, TypeID,Name, FullName  
FROM
  CCs $filter order by $order");
  $numRows=$sql->RowCount();
  } else {
  $sql = new Query("
SELECT
  AccountNumber, ID, TypeID, Name, FullName  
FROM
  CCs $filter order by $order");
  $numRows=100;
  } 

?>
<html>
<head><TITLE>Select Account</TITLE>
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
  var accNum = st.substring(0,st.indexOf(","));
  st = st.substring(st.indexOf(",")+1,st.length);
  var id = st.substring(0,st.indexOf(",")); 
  st = st.substring(st.indexOf(",")+1,st.length);
  // type is in st
  var stType="";

  switch (st) {
    case "1": stType = "";break;
    case "2": stType = "";break;
    case "3": stType = "K";break;
    case "4": stType = "C";break;
  }
  
  window.opener.document.forms["<? echo $frm; ?>"].<? echo $frmField; ?>Name.value = accNum+stType;
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
<table width="100%" bgcolor="#E0E0E0"><TR><TD height=45 class="headerText" bgcolor="#808080"><h1>Select Account</h1></TD></TR>
<tr>
<TD>
<br>
<form name='selform' method="POST"><table border=0>
<TR><TD class='normaltext'>Account Number</TD><td><input type='text' size=10 name='accountNumber' value="<? echo $accountNumber;?>"><input type='hidden' name='frm' value="<? echo $frm;?>"><input type='hidden' name='frmField' value="<? echo $frmField;?>"> </td></TR>
<TR><TD class='normaltext'>Name</TD><td><input type='text' size=20 name='accountName' value="<? echo htmlentities($accountName);?>"> <input type='submit' name='Search' value="Search"></td></TR>
<TR><TD class='normaltext'></TD><td><select name="accountList" size=10 ondblclick="ButtonClick();">
<? 
for ($i=0;$i<$numRows;$i++) { ?>
  <option value="<?=$sql->FieldByName("AccountNumber").",".$sql->FieldByName("ID").",".$sql->FieldByName("TypeID"); ?>"><?=stripslashes($sql->FieldByName("FullName")); ?></option>
  <? $sql->Next(); 
  }
  $sql->Free();
  ?>
</select>
</td></TR>
<TR><TD></TD><td><br><input type='button' name='OK' value="OK" onclick='ButtonClick();'> <input type='button' onclick="window.close();" name='Cancel' value="Cancel"></td></TR>
</table>
<script language="JavaScript">
document.forms["selform"].accountNumber.focus();
</script>
</form>
</td></tr>
</table>
</body>
</html>