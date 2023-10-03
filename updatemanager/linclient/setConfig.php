<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
<head>

<title>Update Client Settings</title>


</head>
<body>
<div align="center"> Using this web interface to configure will completly rewrite the client configuration file!</div>
<?
  session_start();
 $Excludes;
 $baseDir;
 $ExcludesFile;
 $ExcludesDir;
 $ClientName;
 $serverpath;
 $password;
  
  if ((isset($_POST['baseDir']))or(isset($_POST['file']))or(isset($_POST['dir']) )or (isset($_POST['name']))or (isset($_POST['server']))or (isset($_POST['password']))) {
    if ($_POST['password']=='') {
      getConfig();
    }
    else $password=crypt($_POST['password'],'bb');
    $baseDir=$_POST['baseDir'];
    $ExcludesFile=$_POST['file'];
    $ExcludesDir=$_POST['dir'];
    $ClientName=$_POST['name'];
    $serverpath=$_POST['server'];
    setConfig();
  }
function getConfig() {
  global $Excludes, $ExcludesDir, $ExcludesFile, $baseDir, $ClientName, $serverpath, $password;
  $Excludes=array();
  if (file_exists('updateManager.conf')) {
    $file=file('updateManager.conf');
    for ($i=0; $i<count($file);$i++) {
      $line=rtrim($file[$i]);
      $line=explode('=',$line);
      if ($line[0][0]!="#") {
        switch ($line[0]) {
          case 'baseDir': 
            $baseDir=$line[1];
            break;
          case 'ExcludeDir':
            $ExcludesDir=$line[1];
            $Excludes['dir']=explode(',',$line[1]);
            break;
          case 'ExcludeFile':
            $ExcludesFile=$line[1];
            $Excludes['file']=explode(',',$line[1]);
            break;
          case 'ClientName':
            $ClientName=$line[1];
            break;
          case 'password':
            $password=$line[1];
            break;
          case 'serverpath':
            $serverpath=$line[1];
            break;
        }
      }
    }
  }
}
function setConfig() {
  global $Excludes, $ExcludesDir, $ExcludesFile, $baseDir, $ClientName,$serverpath,$password;
  $file=fopen('updateManager.conf','w');
  if ($ClientName!="")
    fwrite($file,'ClientName='.$ClientName."\n");
  if ($serverpath!="")
  fwrite($file,'serverpath='.$serverpath."\n");
  if ($baseDir!="")
    fwrite($file,'baseDir='.$baseDir."\n");
    //  print $baseDir;
  if ($ExcludesFile!="")
    fwrite($file,'ExcludeFile='.$ExcludesFile."\n");
    // print $ExcludesFile;
  if ($ExcludesDir!="")
    fwrite($file,'ExcludeDir='.$ExcludesDir);
  if ($password!="")
    fwrite($file,'password='.$password );
   // print $ExcludesDir;
  fclose($file);

 
}

  getConfig();
?>
<br>
<FORM action="setConfig.php" method="POST" target="_self" name="setting">
<table align="center" cellspacing="0">
  <tbody>
    <TR>
        <TD>Client Name:</TD>
        <TD><INPUT type="text" name="name"  style="border-bottom-style : solid; border-left-style : solid; border-right-style : solid; border-top-style : solid;" tabindex="0" value="<? echo $ClientName?>" size="40"></TD>
    </TR>
    <TR>
        <TD>Password:</TD>
        <TD><INPUT type="password" name="password"  style="border-bottom-style : solid; border-left-style : solid; border-right-style : solid; border-top-style : solid;" tabindex="0" size="40"></TD>
    </TR>
    <TR>
    <TR>
        <TD>Application base directory:</TD>
        <TD><INPUT type="text" name="baseDir"  style="border-bottom-style : solid; border-left-style : solid; border-right-style : solid; border-top-style : solid;" tabindex="0" value="<? echo $baseDir?>" size="40"></TD>
    </TR>
    <TR>
    <TR>
        <TD>Server URL:</TD>
        <TD><INPUT type="text" name="server"  style="border-bottom-style : solid; border-left-style : solid; border-right-style : solid; border-top-style : solid;" tabindex="0" value="<? echo $serverpath?>" size="40"></TD>
    </TR>
    <TR>
        <TD>Excluded files:</TD>
        <TD><INPUT type="text" name="file"  style="border-bottom-style : solid; border-left-style : solid; border-right-style : solid; border-top-style : solid;" tabindex="0" value="<? echo $ExcludesFile?>" size="40" title="Files to exclude separated by a comma"></TD>
    </TR>
    <TR>
        <TD>Excluded directories:</TD>
        <TD><INPUT type="text" name="dir"  style="border-bottom-style : solid; border-left-style : solid; border-right-style : solid; border-top-style : solid;" tabindex="0" value="<? echo $ExcludesDir?>" size="40" title="Directories to exclude separated by a comma"></TD>
    </TR>
    <TR><TD><input type="submit" value="save"></TD></TR>   
  </tbody>
</table>
</FORM>
</body>
</html>
