<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../common/functions.inc.php");
	
	$str_message = '';

	$int_id = -1;
	if (IsSet($_GET['id'])) {
		$int_id = $_GET['id'];
	}
	
	if (IsSet($_POST['action'])) {

		if ($_POST['action'] == 'save') {

			if (IsSet($_POST['id'])) {

				$int_id = $_POST['id'];

				$str_query = "
					UPDATE stock_product
					SET
						language_id = ".$_POST['language'].",
						publisher_id = ".$_POST['publisher'].",
						author_id = ".$_POST['author']."
					WHERE (product_id = $int_id)
				";

				$qry = new Query($str_query);
				
				if ($qry->b_error == true)
					$str_message = 'Error updating supplier information';
			}

			else {
				$str_query = "
					INSERT INTO stock_product
					(
						language_id,
						publisher_id,
						author_id
					)
					VALUES (
						".$_POST['language'].",
						".$_POST['publisher'].",
						".$_POST['author']."
					)
				";
				$qry = new Query($str_query);
				if ($qry->b_error == true) {
					$str_message = 'Error inserting new product';
					echo $str_query;
				}
			}
		}
	}

	$int_language = 0;
	$int_publisher = 0;
	$int_author = 0;

	if ($int_id > 0) {
		$str_query = "
			SELECT *
			FROM stock_product sp
			WHERE sp.product_id = $int_id";
		
		$qry = new Query($str_query);
		
		if ($qry->RowCount() > 0) {
			$int_language = $qry->FieldByName('language_id');
			$int_publisher = $qry->FieldByName('publisher_id');
			$int_author = $qry->FieldByName('author_id');
		}
	}

	$qry_language = new Query("
		SELECT *
		FROM stock_language
		ORDER BY language
	");
	
	$qry_publisher = new Query("
		SELECT *
		FROM stock_publisher
		ORDER BY publisher
	");

	$qry_author = new Query("
		SELECT *
		FROM stock_author
		ORDER BY author
	");
	
?>

<html>
<head><TITLE></TITLE>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	<script language="javascript">
		function isEmpty(str){
			return (str == null) || (str.length == 0);
		}
		
		function isNumeric(str){
			var re = /[\D]/g
			if (re.test(str)) return false;
			return true;
		}
		
		function saveData() {
			var can_save = true;
			
			if (can_save) {
				document.product_book.submit();
			}

			return can_save;
		}
	
		function CloseWindow() {
			if (top.window.opener)
				top.window.opener.document.location=top.window.opener.document.location.href;
			top.window.close();
		}
	</script>
</head>
<body bgcolor="#e9ecf1" marginwidth=5 marginheight=5>
<form name='product_book' method='POST'>
<?
	if ($int_id > -1)
		echo "<input type='hidden' name='id' value='".$int_id."'>";

?>
<input type='hidden' name='action' value='save'>

<table width='100%' cellpadding="3" cellspacing="0" border='0'>
	
	<tr>
		<td class='normaltext_bold'>Language</td>
		<td>
			<select name='language' class='select_200'>
			<?
				$qry_language->First();
				for ($i=0;$i<$qry_language->RowCount();$i++) {
					if ($int_language == $qry_language->FieldByName('language_id'))
						echo "<option value='".$qry_language->FieldByName('language_id')."' selected>".$qry_language->FieldByName('language')."</option>\n";
					else
						echo "<option value='".$qry_language->FieldByName('language_id')."'>".$qry_language->FieldByName('language')."</option>\n";
					$qry_language->Next();
				}
			?>
			</select>
		</td>
	</tr>
	
	<tr>
		<td class='normaltext_bold'>Publisher</td>
		<td>
			<select name='publisher' class='select_200'>
			<?
				$qry_publisher->First();
				for ($i=0;$i<$qry_publisher->RowCount();$i++) {
					if ($int_publisher == $qry_publisher->FieldByName('publisher_id'))
						echo "<option value='".$qry_publisher->FieldByName('publisher_id')."' selected>".$qry_publisher->FieldByName('publisher')."</option>\n";
					else
						echo "<option value='".$qry_publisher->FieldByName('publisher_id')."'>".$qry_publisher->FieldByName('publisher')."</option>\n";
					$qry_publisher->Next();
				}
			?>
			</select>
		</td>
	</tr>
	
	<tr>
		<td class='normaltext_bold'>Author</td>
		<td>
			<select name='author' class='select_200'>
			<?
				$qry_author->First();
				for ($i=0;$i<$qry_author->RowCount();$i++) {
					if ($int_author == $qry_author->FieldByName('author_id'))
						echo "<option value='".$qry_author->FieldByName('author_id')."' selected>".$qry_author->FieldByName('author')."</option>\n";
					else
						echo "<option value='".$qry_author->FieldByName('author_id')."'>".$qry_author->FieldByName('author')."</option>\n";
					$qry_author->Next();
				}
			?>
			</select>
		</td>
	</tr>
	
</table>

</form>

	<script src="../include/js/jquery-3.2.1.min.js"></script>


	<script>

	    $( document ).ready(function() {

	    	window.saveData = function() {

				var can_save = true;
				
				if (can_save) {
					document.product_book.submit();
				}

				return can_save;

	    	}

	    });

	</script>


</body>
</html>