<?

$db_loaded=1;



class Query {

  var $link;

  var $query;

  var $row;

  var $curpos;

  var $initDone=0;

  

  function RowCount() {

    if (isset($this->query)) {

      $numrow=@mssql_num_rows($this->query);

	} else $numrow=0; 

    return $numrow;

  }

  

  function Init() {

  

  // CONNECTING

    $db_db = $_SESSION["monthDB"];

    $db_server = "SERVERPT";

    $db_login = "sa";

    $db_password = "fsptavf";

	

  	

	$this->initDone = 0;

    	$res=0;

	if (empty($_SESSION["sessionMonthLink"])) {



  	  $this->link = mssql_pconnect ("$db_server",$db_login,$db_password) or die("unable to connect to db");

	  $_SESSION["sessionMonthLink"] = $this->link;


	  //echo "restarting link.";

	} else $this->link = $_SESSION["sessionMonthLink"];

	

 	mssql_select_db($_SESSION["monthDB"], $this->link) or die("unable to select db $db_db - ".$_SESSION["monthDB"]);  

	$this->initDone = 1;

  }

  function Query($querySt) {

    if ($this->initDone<>1) $this->Init();

	

	$this->row=0;

	

	if ($querySt <> "") {

	  if (isset($this->query))  mssql_free_result($this->query);	

	  $qry = mssql_query($querySt);

 	  $this->query = $qry;

	  //echo ($querySt);

//	  echo("Query: ".$querySt);

      	  $this->curpos = -1;

	  

	  if ($this->RowCount()>0) {

	    $this->Next();

	  }

	  $res = 1;

	} 

	return $res; 

  }

  



  

  function ExecuteQuery($querySt) {

    if ($this->initDone<>1) $this->Init();

//	echo("Executequery: ".$querySt);

    return mssql_query($querySt);

  }

  

  function Next() {

    if ($this->curpos < $this->RowCount()-1) {

      $this->curpos++;

	}

  	mssql_data_seek($this->query, $this->curpos);

    $this->row=mssql_fetch_array($this->query);

  }

  

  function Prev() {

    $this->curpos--;  

	mssql_data_seek($this->query, $this->curpos);

    $this->row=mssql_fetch_array($this->query);

  }

  

  function First() {

    $this->curpos=0;

    mssql_data_seek($this->query, $this->curpos);

    $this->row=mssql_fetch_array($this->query);

  }

  

  function Seek($recordNum) {

    $this->curpos=$recordNum;

    mssql_data_seek($this->query, $this->curpos);

    $this->row=mssql_fetch_array($this->query);

  }

  

  function Last() {

    $this->curpos=RowCount()-1;

    mssql_data_seek($this->query, $this->curpos);

    $this->row=mssql_fetch_array($this->query);

  }

  

  function FieldByName($fieldName) {

    return $this->row[$fieldName];

  }

  

  function GetErrorMessage() {

    return mssql_get_last_message();

  }

  

  function GetLastInsertID() {

  

    $res = $this->Query("SELECT ThisID = @@Identity from CCs"); 

    return $res->FieldByName("ThisID");

  }

  

  function Free() {

  	mssql_free_result($this->query);

//	mssql_close($this->link);



  }

}



class QueryBase {

  var $link;

  var $query;

  var $row;

  var $curpos;

  var $initDone=0;

  

  function RowCount() {

    if (isset($this->query)) {

      $numrow=@mssql_num_rows($this->query);

	} else $numrow=0; 

    return $numrow;

  }

  

  function Init() {

  

  // CONNECTING

  

    $db_db = "FS_Base";

    $db_server = "SERVERPT";

    $db_login = "sa";

    $db_password = "fsptavf";

	

  	

	$this->initDone = 0;

    $res=0;

	

	if (empty($_SESSION["sessionMonthLink"])) {

  	  $this->link = mssql_pconnect ("$db_server",$db_login,$db_password) or die("unable to connect to db".mssql_get_last_message());

	  $_SESSION["sessionMonthLink"] = $this->link;


	} else $this->link = $_SESSION["sessionMonthLink"];

	

 	mssql_select_db($db_db, $this->link) or die("unable to select base db");  

	$this->initDone = 1;

  }

  function QueryBase($querySt) {

    if ($this->initDone<>1) $this->Init();

	

	$this->row=0;

	

	if ($querySt <> "") {

	  $qry = mssql_query($querySt);

 	  $this->query = $qry;

	  //echo ($querySt);

      $this->curpos = -1;

	  

	  if ($this->RowCount()>0) {

	    $this->Next();

	  }

	  $res = 1;

	} 

	return $res; 

  }

  



  

  function ExecuteQuery($querySt) {

    if ($this->initDone<>1) $this->Init();

	//echo($querySt);

    mssql_query($querySt);

  }

  

  function Next() {

    if ($this->curpos < $this->RowCount()-1) {

      $this->curpos++;

	}

  	mssql_data_seek($this->query, $this->curpos);

    $this->row=mssql_fetch_array($this->query);

  }

  

  function Prev() {

    $this->curpos--;  

	mssql_data_seek($this->query, $this->curpos);

    $this->row=mssql_fetch_array($this->query);

  }

  

  function First() {

    $this->curpos=0;

    mssql_data_seek($this->query, $this->curpos);

    $this->row=mssql_fetch_array($this->query);

  }

  

  function Seek($recordNum) {

    $this->curpos=$recordNum;

    mssql_data_seek($this->query, $this->curpos);

    $this->row=mssql_fetch_array($this->query);

  }

  

  function Last() {

    $this->curpos=RowCount()-1;

    mssql_data_seek($this->query, $this->curpos);

    $this->row=mssql_fetch_array($this->query);

  }

  

  function FieldByName($fieldName) {

    return $this->row[$fieldName];

  }

  

  function GetErrorMessage() {

    return mssql_get_last_message($this->query);

  }

  

  function Free() {

  	mssql_free_result($this->query);

//	mssql_close($this->link);



  }

  

}



class QueryYear {

  var $link;

  var $query;

  var $row;

  var $curpos;

  var $initDone=0;

  

  function RowCount() {

    if (isset($this->query)) {

      $numrow=@mssql_num_rows($this->query);

	} else $numrow=0; 

    return $numrow;

  }

  

  function Init() {

  

  // CONNECTING

  

    $db_db = $_SESSION["yearDB"];

    $db_server = "SERVERPT";

    $db_login = "sa";

    $db_password = "fsptavf";

	

  	

	$this->initDone = 0;

    $res=0;

	

	if (empty($_SESSION["sessionMonthLink"])) {

  	  $this->link = mssql_pconnect ("$db_server",$db_login,$db_password) or die("unable to connect to db");

	  $_SESSION["sessionMonthLink"] = $this->link;


	} else $this->link = $_SESSION["sessionMonthLink"];

	

 	mssql_select_db($db_db, $this->link) or die("unable to select db");  

	$this->initDone = 1;

  }

  function QueryYear($querySt) {

    if ($this->initDone<>1) $this->Init();

	

	$this->row=0;

	

	if ($querySt <> "") {

	  $qry = mssql_query($querySt);

 	  $this->query = $qry;

	  //echo ($querySt);

      $this->curpos = -1;

	  

	  if ($this->RowCount()>0) {

	    $this->Next();

	  }

	  $res = 1;

	} 

	return $res; 

  }

  



  

  function ExecuteQuery($querySt) {

    if ($this->initDone<>1) $this->Init();

	//echo($querySt);

    mssql_query($querySt);

  }

  

  function Next() {

    if ($this->curpos < $this->RowCount()-1) {

      $this->curpos++;

	}

  	mssql_data_seek($this->query, $this->curpos);

    $this->row=mssql_fetch_array($this->query);

  }

  

  function Prev() {

    $this->curpos--;  

	mssql_data_seek($this->query, $this->curpos);

    $this->row=mssql_fetch_array($this->query);

  }

  

  function First() {

    $this->curpos=0;

    mssql_data_seek($this->query, $this->curpos);

    $this->row=mssql_fetch_array($this->query);

  }

  

  function Seek($recordNum) {

    $this->curpos=$recordNum;

    mssql_data_seek($this->query, $this->curpos);

    $this->row=mssql_fetch_array($this->query);

  }

  

  function Last() {

    $this->curpos=RowCount()-1;

    mssql_data_seek($this->query, $this->curpos);

    $this->row=mssql_fetch_array($this->query);

  }

  

  function FieldByName($fieldName) {

    return $this->row[$fieldName];

  }

  

  function GetErrorMessage() {

    return mssql_get_last_message($this->query);

  }

  

  function Free() {

  	mssql_free_result($this->query);

	mssql_close($this->link);



  }

  

}



function fnDatabaseExists($dbName) {

//Verifies existence of a mssql database

$bRetVal = FALSE;

    $db_server = "SERVERPT";

    $db_login = "sa";

    $db_password = "fsptavf";



if ($oConn = @mssql_connect($db_server, $db_login, $db_password)) {

if (@mssql_select_db($dbName)) return TRUE; else return FALSE;

//$result = mssql_list_dbs($oConn);

//while ($row=mssql_fetch_array($result, mssql_NUM)) {

//if ($row[0] ==  $dbName)

//$bRetVal = TRUE;

//}



mssql_free_result($result);

//mssql_close($oConn);

}

return ($bRetVal);

}





function fnStatusChange($aChangeType, $aCCID) {

  $qry = new Query("insert into Status (Operation,RecordID,FinalMask) values ($aChangeType, $aCCID,3)");

  $qry->Free();

}



function RecordHistory($aArea,$aSerialNumber,$aAdded,$aModified,$aDeleted) {

	$qry= new QueryBase("insert into RecordHistory (RecordTime,Area,SerialNumber,Added,Modified,Deleted, Username,MonthYear)

		values ('".Date("Y-m-d H:i:s",time())."','$aArea',$aSerialNumber,$aAdded,$aModified,$aDeleted,'".$_SESSION["userName"]."','".$_SESSION["yearLoaded"]."-".$_SESSION["monthLoaded"]."')");

	$qry->Free();

}



?>

