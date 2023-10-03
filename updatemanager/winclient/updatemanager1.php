
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
<head>

<title>Update Manager </title>
</head>
<body>
<?
include "clientclass.php";
$client= new updateClient('.');
$clientinc= new updateClient('include');

if ($client->CheckUpdates() or $clientinc->CheckUpdates()) {
	echo "new updates available!";
	?>
<FORM action="getUpdate1.php" method="POST" target="_self" >
<INPUT type="submit" value="Update"></td>
</FORM>
<?}
else {
  if ($client->badlogin) {
    Echo "invalid username and/or password";
  }
  else Echo "Your application is upto date";
}

?>
</body>
</html>