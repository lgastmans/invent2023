<html>
<head>
	<link href="../include/styles.css" rel="stylesheet" type="text/css">
	<link rel="stylesheet" type="text/css" href="../yui2.7.0/build/fonts/fonts-min.css" />
	<script type="text/javascript" src="../yui2.7.0/build/yahoo/yahoo-min.js"></script>
	<script type="text/javascript" src="../yui2.7.0/build/event/event-min.js"></script>
	<script type="text/javascript" src="../yui2.7.0/build/connection/connection-min.js"></script>
	<script type="text/javascript" src="../yui2.7.0/build/json/json-min.js"></script>
	
	<style>
		.header {
			font-family:helvetica, sans-serif;
			font-size:32px;
			text-align:center;
		}
	</style>
	<script language="javascript">
	
		var handleSuccess = function(o) {
			var r = YAHOO.lang.JSON.parse(o.responseText);
			var oContent = parent.frames['help_main'].document.getElementById('content');
			
			if (r.Result.replyStatus == 'Ok') {
				var oSearch = document.getElementById('search');
				oContent.src = "content.php?search="+oSearch.value+"&search_results="+r.Result.replyText;
			}
			else
				alert('no results found');
		}
		
		var handleFailure = function(o){
			var r = YAHOO.lang.JSON.parse(o.responseText);
			alert(r.replyText);
		}
		
		var callback = {
			success:handleSuccess,
			failure:handleFailure
		};
		
		function Search(event) {
			if (event.keyCode == '13') {
				var oSearch = document.getElementById('search');
				var sUrl = "search_help.php?"+
					"search="+oSearch.value;
				var request = YAHOO.util.Connect.asyncRequest('GET', sUrl, callback);
			}
			return false;
		}	
	</script>
</head>
<body bgcolor='#d0d0d0' leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>
	<table width="100%" height="60" border=0 cellpadding=5 cellspacing=0>
		<tr>
			<td align="center" class="header" colspan="3">Invent Help</td>
		</tr>
		<tr>
			<td style="padding:0 0 10 10px;">
				Search
				<input type="text" id="search" name="search" value="" onkeypress="Search(event)">
			</td>
			<td></td>
			<td></td>
		</tr>
	</table>
</body>
</html>