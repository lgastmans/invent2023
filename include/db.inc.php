<?
if (!class_exists('Query')) {

class Query {
	
	var $link;
	var $query;
	var $row;
	var $curpos;
	var $initDone=0;
	var $b_error=false;
	var $err='';

	// function __construct() {
 //    	// copy your old constructor function code here
 //   	}

   	function RowCount() {
		if (isset($this->query))
			$numrow=@mysqli_num_rows($this->query);

		else
			$numrow=0;
		return $numrow;
	}

	function Init() {
		/*
			the following array gets initialized in the const.inc.php file
			which is assumed to be included
		*/
		global $arr_invent_config;
		if (!IsSet($_SESSION['invent_database_loaded'])) {
			$_SESSION['invent_database_loaded'] = $arr_invent_config['database']['invent_database'];
		}
//		echo $_SESSION['invent_database_loaded']."::".$arr_invent_config['database']['invent_database'];
//		die();
		
		$db_db = $_SESSION['invent_database_loaded']; //$arr_invent_config['database']['invent_database'];
		$db_server =  $arr_invent_config['database']['invent_server'];
		$db_login = $arr_invent_config['database']['invent_login'];
		$db_password = $arr_invent_config['database']['invent_password'];
		
		$this->initDone = 0;
		$res=0;
		
		$this->link = mysqli_connect("$db_server", $db_login, $db_password, $db_db);
		//$this->link = mysql_connect ("$db_server:3306",$db_login,$db_password) or die("unable to connect to db");

		if (!$this->link) {
		    echo "Error: Unable to connect to MySQL." . PHP_EOL;
		    echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
		    echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
		    exit;
		}

		//mysql_select_db($db_db, $this->link) or die("unable to select db");
		$this->initDone = 1;
	}



	function Query($querySt) {
		if ($this->initDone<>1) $this->Init();

		$this->row=0;
		$this->b_error=false;

		$res = 0;

		if ($querySt <> "") {

			$qry = mysqli_query ( $this->link , $querySt );

			if ($qry!==false) {
				$this->query = $qry;
				
				$this->curpos = -1;

				if (mysqli_num_rows($this->query) > 0) {
					$this->Next();
				}
				$res = 1;
			}
			else {
				$this->b_error = true;
				$this->err = mysqli_error($this->link);
			}


		}
		
		return $res;
	}



  function ExecuteQuery($querySt) {
    if ($this->initDone<>1)
    	$this->Init();
	//echo($querySt);
    return mysqli_query($this->link, $querySt);

  }

  function Next() {
    if ($this->curpos < $this->RowCount()-1) {
      $this->curpos++;
	}
  	mysqli_data_seek($this->query, $this->curpos);
    $this->row=mysqli_fetch_array($this->query);
  }

  function Prev() {
    $this->curpos--;
		mysqli_data_seek($this->query, $this->curpos);
    $this->row=mysql_fetch_array($this->query);
  }

  function First() {
    $this->curpos=0;
    mysqli_data_seek($this->query, $this->curpos);
    $this->row=mysqli_fetch_array($this->query);
  }

  function Seek($recordNum) {
    if (mysqli_num_rows($this->query)>0) {
    $this->curpos=$recordNum;
    mysqli_data_seek($this->query, $this->curpos);
    $this->row=mysqli_fetch_array($this->query);
    }
  }

  function Last() {
    $this->curpos=RowCount()-1;
    mysqli_data_seek($this->query, $this->curpos);
    $this->row=mysqli_fetch_array($this->query);
  }

  function Free() {
//    unset();
  }

  function FieldByName($fieldName) {
    return $this->row[$fieldName];
  }

  function GetErrorMessage() {
  	return mysqli_error($this->link);
  }

	function getInsertedID() {
		$used_id = 0;
		if (mysqli_insert_id($this->link) != 0) {
			$used_id = mysqli_insert_id($this->link);
		}
		return $used_id;
	}

	function getFieldInfo($int_pos) {
		$arr_info = Array();
		
		$arr_info['fieldname'] = mysql_field_name($this->query, $int_pos);
		$arr_info['fieldtype'] = mysql_field_type($this->query, $int_pos);
		$arr_info['fieldlen'] = mysql_field_len($this->query, $int_pos);
		
		return $arr_info;
	}
	
	function ColCount() {
		return mysql_num_fields($this->query);
	}
	
	function getFieldNames() {
		$arr_columns = Array();
		$columns = mysql_num_fields($this->query);
		for($i=0; $i<$columns; $i++) {
			$arr_columns[$i] = $this->getFieldInfo($i);
		}
		return $arr_columns;
	}
	
}
}
?>
