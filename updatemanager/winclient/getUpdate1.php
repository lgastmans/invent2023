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
print "Updated Files: <br>";
$client->GetUpdates();
$clientinc->GetUpdates();
?>
<A href="updatemanager1.php"> Check if application is up to date</A>
<!--<script language="JavaScript">
document.location="updatemanager1.php";
</script>-->
</body>
</html>