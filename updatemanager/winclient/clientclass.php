<?php
require_once('mynusoap.php');
// include the SOAP classes
class updateClient {
  var $ClientName;
  var $serverBaseDir='';
  var $clientBaseDir='/tmp/updateManager';
  var $Excludes=array();
  var $newupdate=false;
  var $badlogin=false;
  var $configdir;
  // define path to server application
  //$serverpath ='http://10.0.2.5/updateManager/upDate_server.php';
  //$serverpath ='http://192.168.3.113/updateManager/upDate_server.php';
  // create client object
  //$client = new soapclient($serverpath);
  function updateClient($configdir) {
    set_time_limit(0);
    $this->configdir=$configdir;
  }
  function getLocFileList_R($aDirectory) {
    if ($handle = opendir($aDirectory)) {
      /* This is the correct way to loop over the directory. */
    // $str_xml="";
      while (false !== ($file = readdir($handle))) { 
        if ( is_file($aDirectory.'/'.$file)) {
          if (!($this->isFileExcluded($file))) {
            $str_xml=$str_xml."<file name='".$file."'>\n<filectime>".filectime($aDirectory.'/'.$file)." </filectime><filesize>".filesize($aDirectory.'/'.$file)." </filesize></file>\n";
          }
        }
        else if (($file!=".")&&($file!="..")&&(!($this->isDirExcluded($file)))) {
          
          $str_xml=$str_xml."<directory name='".$file."'>".$this->getLocFileList_R($aDirectory.'/'.$file)."</directory>";
        }
      }
    // $str_xml=$str_xml."</structure>";
      closedir($handle); 
    }
    
    return $str_xml; 
  }
  
  function getLocFileList($aDirectory) {
    $this->getConfig();
    $str_xml="<?xml version='1.0' encoding='UTF-8'?><DirectoryStructure>".$this->getLocFileList_R ($aDirectory)."</DirectoryStructure>";
    return $str_xml;
  }
  function getConfig() {
    //global $Excludes,$clientBaseDir,$ClientName, $serverpath, $password;
    
    if (file_exists($this->configdir."/updateManager.conf")) {
          $file=file($this->configdir."/updateManager.conf");
          for ($i=0; $i<count($file);$i++) {
                  $line=rtrim($file[$i]);
                  $line=explode('=',$line);
                  if ($line[0][0]!="#") {
                          switch ($line[0]) {
                                  case 'baseDir': 
                                          $this->clientBaseDir=$line[1];
																					$winBaseDir=str_replace('/','\\',$this->clientBaseDir);
																					shell_exec("attrib -r ".$winBaseDir."\*.* /s");
                                          break;
                                  case 'ExcludeDir':
                                          $this->Excludes['dir']=explode(',',$line[1]);
                                          break;	
                                  case 'ExcludeFile':
                                          $this->Excludes['file']=explode(',',$line[1]);
                                          break;
                                  case 'ClientName':
                                          $this->ClientName=$line[1];
                                          break;
                                  case 'password':
                                          $this->password=$line[1];
                                          break;
                                  case 'serverpath':
                                          $this->serverpath=$line[1];
                                          break;
                          }
                  }
          }
    }
  }
  function isFileExcluded($aFile) {
    //global $Excludes;
    for ($i=0;$i<count($this->Excludes['file']);$i++) {
      $rtn=($aFile==$this->Excludes['file'][$i]);
      if ($rtn) $i=count($this->Excludes['file']);
    }
    return $rtn;
  }
  function isDirExcluded($aDir) {
    //global $Excludes;
    for ($i=0;$i<count($this->Excludes['dir']);$i++) {
      $rtn=($aDir==$this->Excludes['dir'][$i]);
      if ($rtn) $i=count($this->Excludes['dir']);
    }
    return $rtn;
  }
  function getFileList($client,$serverBaseDir) {
    $str=$client->call('getFileList',$serverBaseDir);
    return $str;
  }
  function WriteTmpFileList($client,$clientBaseDir,$serverBaseDir) {
    $file=fopen($this->configdir."/tmpFileList.xml","w");
      
  $str=$this->getFileList($client,$serverBaseDir);
    
    fwrite($file,$str);
    fclose($file);
  }
  
  function getFile($client,$clientBaseDir,$aFile) {
    //global $serverBaseDir, $ClientName;
  //print $client->call('getFile',array('aFile'=>$serverBaseDir."/".$aFile,'ClientName'=>$ClientName));
  //print $clientBaseDir."/".$aFile."<br>";
  $xml = new SimpleXMLElement($client->call('getFile',array('aFile'=>$this->serverBaseDir."/".$aFile,'ClientName'=>$this->ClientName)));
    $encodedFile=base64_decode($xml->content);
    if ($encodedFile!='') {
          $DecFile=gzuncompress($encodedFile); }
    else {
          $DecFile="";}
  
  $file=fopen($clientBaseDir."/".$aFile,"w");
    fwrite($file,$DecFile);
    fclose($file);
    chmod($clientBaseDir."/".$aFile,777);
    if (basename($clientBaseDir."/".$aFile)=='autoconfig.php')
                            include ($clientBaseDir."/".$aFile);
  }
  
  function Update($client,$serverBaseDir,$check){
    //global $clientBaseDir, $newupdate;	
    $this->newupdate=false;
    if (file_exists($this->configdir.'/tmpFileList.xml')) {
      $xml_tmp = simplexml_load_file($this->configdir.'/tmpFileList.xml');
    } else {
    exit('Failed to open tmpFileList.xml.');
    }
    $fileList=fopen($this->configdir.'/FileList.xml','w');
    $str=$this->getLocFileList($this->clientBaseDir);
    fwrite($fileList,$str);
    fclose($fileList);
    if (file_exists($this->configdir.'/FileList.xml')) {
      $xml = simplexml_load_file($this->configdir.'/FileList.xml');
      $this->UpdateFile($xml_tmp,$xml,'.',$check);
      
    } else { 
      $this->Update($client,$serverBaseDir,$check);
    }
    $fileList=fopen($this->configdir.'/FileList.xml','w');
    $str=$this->getLocFileList($this->clientBaseDir);
    fwrite($fileList,$str);
    fclose($fileList);	
  }
  function UpdateFile($xml_tmp,$xml,$path,$check) {
    //global $newupdate, $client,$clientBaseDir, $serverBaseDir;
    foreach ($xml_tmp->file as $file_tmp) {
          $updateFile=false;
          $filePresent=false;
          foreach($xml->file as $file) {
                  if (!($this->isFileExcluded($file['name']))) {
                          if (strcmp($file_tmp['name'],$file['name'])==0) {
                                  $filePresent=true;
                                  if ((!($this->isFileExcluded($file['name'])))&&(intval($file_tmp->filesize) <> intval($file->filesize)))
                                          $updateFile=true;
                          }
                          if ($updateFile) break;
                  }
          }
          $updateFile=($updateFile || (! $filePresent)) && (!($this->isFileExcluded($file_tmp['name']))) ;
          if ($updateFile) {
                  if (!($check)) {
                          if ($path==".") {
                                  $this->getFile($this->client,$this->clientBaseDir,$file_tmp['name']);}
                          else {
                          $this->getFile($this->client,$this->clientBaseDir,$path."/".$file_tmp['name']);}
                          $this->newupdate=false;
                  }
                  else {
                          $this->newupdate=true;
                          return "a";
                  }
          }
    }
    foreach ($xml_tmp->directory as $dir_tmp) {
          $dirPresent=false;
          if (!($this->isDirExcluded($dir_tmp['name']))) {
                  foreach($xml->directory as $dir) {
                    if ((strcmp($dir_tmp['name'],$dir['name'])==0)&&(!($this->isDirExcluded($dir['name'])))) {
                          if ($path==".") 
                                  $this->UpdateFile($dir_tmp, $dir,$dir['name'],$check);
                          else 
                                  $this->UpdateFile($dir_tmp, $dir,$path."/".$dir['name'],$check);
                          $dirPresent=true;
                    }
                  }
                  if (! $dirPresent) {
                          if (!($check)) {
                                  if ($path=='.')
                                        $pathDir=$this->clientBaseDir;
                                  else 
                                      $pathDir=$this->clientBaseDir."/".$path;
                                  mkdir($pathDir."/".$dir_tmp['name']);
                                  $this->getAll($path."/".$dir_tmp['name'],$dir_tmp);
                                  $this->newupdate=false;
                                  }
                          else {
                                  $this->newupdate=true;
                                  return "a";
                          }
                  }
          }
    }
  }
  function getAll($path,$xml) {
          //global $client,$clientBaseDir;
      // echo $this->clientBaseDir,$this->serverBaseDir."/".$path."/".$file['name'];
          foreach ($xml->file as $file) {
                  $this->getFile($this->client,$this->clientBaseDir,$path."/".$file['name']);
          }
          foreach ($xml->directory as $dir) {
                  if ($path=='.')
                        $pathDir=$this->clientBaseDir;
                  else 
                        $pathDir=$this->clientBaseDir."/".$path;
                  mkdir($pathDir."/".$dir['name']);
                  
                  $this->getAll($path."/".$dir['name'],$dir);
          }
  }
  function CheckUpdates(){
          //global $serverBaseDir,$clientBaseDir,$client,$newupdate, $serverpath, $ClientName, $password, $badlogin;
          
          $this->getConfig();
          $this->client = new mysoapclient($this->serverpath);
          //print $client->call('getBaseDir','');
          //$client->call('login',array('clientname'=>$ClientName,'password'=>$password));
          if ($this->client->call('login',array('clientname'=>$this->ClientName,'password'=>$this->password) )=='true' ) {
            $this->serverBaseDir=$this->client->call('getBaseDir','');
            $this->WriteTmpFileList($this->client,$this->clientBaseDir,$this->serverBaseDir);
            $this->update($this->client,$this->serverBaseDir,true);
            $this->badlogin=false;
          }
          else 
            $this->badlogin=true;
          return $this->newupdate;
  }
  function GetUpdates() {
          //global $serverBaseDir,$clientBaseDir,$client, $serverpath, $ClientName, $password;
          
          $this->getConfig();
          
          $this->client = new mysoapclient($this->serverpath);
          if ($this->client->call('login',array('clientname'=>$this->ClientName,'password'=>$this->password))=='true' ) {
            $this->serverBaseDir=$this->client->call('getBaseDir','');
            $this->WriteTmpFileList($this->client,$this->clientBaseDir,$this->serverBaseDir);
            $this->update($this->client,$this->serverBaseDir,false);
          }
  }
}
//header('Content-Type: text/xml');
//echo getFileList($client,$serverBaseDir);
//getFile($client,$clientBaseDir,$serverBaseDir."/dateTest.php");
?>