<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
	<title>Invent Help</title>
	<style type="text/css">
		/*margin and padding on body element
		can introduce errors in determining
		element position and are not recommended;
		we turn them off as a foundation for YUI
		CSS treatments. */
		body {
			margin:0;
			padding:0;
		}
		#center1 {
			padding: 0px;
		}
	</style>
	<link rel="stylesheet" type="text/css" href="../yui2.7.0/build/reset-fonts-grids/reset-fonts-grids.css" />
	<link rel="stylesheet" type="text/css" href="../yui2.7.0/build/resize/assets/skins/sam/resize.css" />
	<link rel="stylesheet" type="text/css" href="../yui2.7.0/build/layout/assets/skins/sam/layout.css" />
	<link rel="stylesheet" type="text/css" href="../yui2.7.0/build/button/assets/skins/sam/button.css" />
	<script type="text/javascript" src="../yui2.7.0/build/yahoo/yahoo-min.js"></script>
	<script type="text/javascript" src="../yui2.7.0/build/event/event-min.js"></script>
	<script type="text/javascript" src="../yui2.7.0/build/dom/dom-min.js"></script>

	<script type="text/javascript" src="../yui2.7.0/build/element/element-min.js"></script>
	<script type="text/javascript" src="../yui2.7.0/build/dragdrop/dragdrop-min.js"></script>
	<script type="text/javascript" src="../yui2.7.0/build/resize/resize-min.js"></script>
	<script type="text/javascript" src="../yui2.7.0/build/animation/animation-min.js"></script>
	<script type="text/javascript" src="../yui2.7.0/build/layout/layout-min.js"></script>
</head>

<body class="yui-skin-sam">

<div id="left1" align="center" valign="center">
	<iframe id="menu" src="menu.php" frameborder="0" width="95%" height="95%"></iframe>
</div>

<div id="center1">
	<iframe id="content" src="content.php" frameborder="0" width="100%" height="100%"></iframe>
</div>


<script>

(function() {
	var Dom = YAHOO.util.Dom,
		Event = YAHOO.util.Event;

	Event.onDOMReady(function() {
		var layout = new YAHOO.widget.Layout({
			units: [
				{ position: 'left', header: 'Menu', width: 200, resize: false, body: 'left1', gutter: '3px', collapse: true, close: false, collapseSize: 25, scroll: true, animate: true },
				{ position: 'center', body: 'center1' }
			]
		});
		layout.on('render', function() {
			layout.getUnitByPosition('left').on('close', function() {
				closeLeft();
			});
		});
		layout.render();
		nt.on('tLeft', 'click', function(ev) {
			Event.stopEvent(ev);
			layout.getUnitByPosition('left').toggle();
		});
	});
})();
</script>
</body>
</html>
