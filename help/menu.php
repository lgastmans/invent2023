<?
	require_once('../include/const.inc.php');
	require_once('session.inc.php');
	require_once('db_params.php');
?>

<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	
	<title>Mandala Pottery Reseller</title>
	
	<style type="text/css">
		body {
			margin:0;
			padding:0;
			background-color:transparent;
		}
	</style>
	
	<link rel="stylesheet" type="text/css" href="../yui2.7.0/build/fonts/fonts-min.css" />
	<link rel="stylesheet" type="text/css" href="../yui2.7.0/build/treeview/assets/skins/sam/treeview.css" />
	<link rel="stylesheet" type="text/css" href="../yui2.7.0/examples/treeview/assets/css/menu/tree.css" />
	<link rel="stylesheet" type="text/css" href="../yui2.7.0/build/fonts/fonts-min.css" />
	
	<script type="text/javascript" src="../yui2.7.0/build/yahoo/yahoo-min.js"></script>
	<script type="text/javascript" src="../yui2.7.0/build/event/event-min.js"></script>
	<script type="text/javascript" src="../yui2.7.0/build/connection/connection-min.js"></script>
	<script type="text/javascript" src="../yui2.7.0/build/yahoo-dom-event/yahoo-dom-event.js"></script>
	<script type="text/javascript" src="../yui2.7.0/build/treeview/treeview-min.js"></script>
	
	<style>
		#treeDiv1 {margin-top:1em; padding:1em; min-height:7em;}
		
		/* the style of the text label in ygTextNode */
		.ygtvlabel, .ygtvlabel:link, .ygtvlabel:visited { 
			/*
			margin-left:2px;
			text-decoration: none;
			*/
			color:#5D3804;
			font-size: 12px;
			font-weight:normal;
			background-color:transparent;
		}

		.ygtvlabel:hover { 
			/*
			margin-left:2px;
			text-decoration: none;
			*/
			color:#412703;
			font-size: 12px;
			font-weight:bold;
			background-color:transparent;
		}

	</style>
</head>

<body class="yui-skin-sam">

<div id="help_menu"></div>

<script type="text/javascript">

	YAHOO.example.treeExample = function() {
	
		var tree, currentIconMode;
	
		function changeIconMode() {
			var newVal = parseInt(this.value);
			if (newVal != currentIconMode) {
				currentIconMode = newVal;
			}
			buildTree();
		}
		
		function loadNodeData(node, fnLoadComplete)  {
			
			var nodeLabel = encodeURI(node.label);
			
			var sUrl = "json_submenu.php?category_id="+node.data.category_id+
				"&module_id="+node.data.module_id+
				"&section_id="+node.data.section_id+
				"&level="+node.data.level;
			
			var callback = {
				
				success: function(oResponse) {
					
					var oResults = [];
					oResults = eval("(" + oResponse.responseText + ")");
					if((oResults.Result) && (oResults.Result.length)) {
						if(YAHOO.lang.isArray(oResults.Result)) {
							for (var i=0, j=oResults.Result.length; i<j; i++) {
								var tempNode = new YAHOO.widget.MenuNode(oResults.Result[i], node, false);
								tempArr = oResults.Result[i];
								tempNode.isLeaf = (tempArr['level'] == 0);
							}
						}
					}
					
					oResponse.argument.fnLoadComplete();
				},
				
				failure: function(oResponse) {
					oResponse.argument.fnLoadComplete();
				},
				
				argument: {
					"node": node,
					"fnLoadComplete": fnLoadComplete
				},
				
				timeout: 7000
			};
			YAHOO.util.Connect.asyncRequest('GET', sUrl, callback);
		}
	
		function buildTree() {
			tree = new YAHOO.widget.TreeView("help_menu");
			
			tree.setDynamicLoad(loadNodeData, currentIconMode);
			
			var root = tree.getRoot();
			
			var strCategories = '<? include "json_menu_categories.php";?>';
			var arrCategories = eval("(" + strCategories + ")");
			
			for (var i=0, j=arrCategories.Categories.length; i<j; i++) {
				var tempNode = new YAHOO.widget.MenuNode(arrCategories.Categories[i], root, false);
			}
			
			tree.subscribe("labelClick", function(node) {
				if (node.data.level == 0) {
					var oFrame = top.frames['help_main'].document.getElementById('content');
					oFrame.src = 'content.php?id=' + node.data.id;
				}
			});
			
			tree.draw();
		}
		
		return {
			init: function() {
				YAHOO.util.Event.on(["mode0", "mode1"], "click", changeIconMode);
				var el = document.getElementById("mode1");
				if (el && el.checked) {
					currentIconMode = parseInt(el.value);
				}
				else {
					currentIconMode = 0;
				}
				
				buildTree();
			}
		
		}
	} ();
	
	YAHOO.util.Event.onDOMReady(YAHOO.example.treeExample.init, YAHOO.example.treeExample,true)

</script>

</body>
</html>