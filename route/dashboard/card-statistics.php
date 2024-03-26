<?php

// get stats
$stats = $User->get_stats ();

?>

<div class='col-xs-12 col-lg-6'>
<div class='bubble'>
	<div class='bubble-header'><i class='links fa fa-chart-pie'></i> <?php print _("Statistics"); ?></div>
		<div class="bubble-content row" style='padding: 15px 5px 0px 15px;'>
			<?php
			foreach ($stats as $name=>$cnt) {
				print "<div class='col-xs-12 col-lg-6'>";
				print "	<div class='circle circle-card' style='margin-right:6px;'>$cnt</div><div class='circle-text'>"._(ucwords($name))."</div>";
				print "</div>";
			}
			?>
		</div>
	</div>
</div>
</div>