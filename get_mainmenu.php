<?php

	$btns['products'] = '
	<div class="btn-group">
		<button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
			Products <span class="caret"></span>
		</button>
		<ul class="dropdown-menu">
			<li><a href="#">Products</a></li>
			<li><a href="#">Batches</a></li>
			<li><a href="#">Stock In</a></li>
		</ul>
	</div>	

	';

	$ret = array('btns'=>$btns);

	echo json_encode($ret);
?>