<div class=' col-xs-12 col-lg-6'>
<div class='bubble'>
	<div class='bubble-header'><i class='links fa fa-link'></i> <?php print _("Links"); ?></div>
	<div class='bubble-content'>
		<div style='padding: 15px 5px;'>
		<table class='table table-cert-details table-borderless table-auto table-details table-condensed'>
			<?php if($user->admin=="1") { ?>
			<tr><th><i class='links fa fa-users text-muted'></i> <a href='/admin/tenants/'><?php print _("Tenants"); ?></a></th><td class='text-muted'><?php print _("View and manage system tenants"); ?></td></tr>
			<?php } ?>
			<tr><th><i class='links fa fa-certificate text-muted'></i> <a href='/<?php print $user->href; ?>/certificates/'><?php print _("Certificates"); ?></a></th><td class='text-muted'><?php print _("View and manage found certificates"); ?></td></tr>
			<tr><th><i class='links fa fa-database text-muted'></i> <a href='/<?php print $user->href; ?>/zones/'><?php print _("Zones"); ?></a></th><td class='text-muted'><?php print _("View and manage zones"); ?></td></tr>
			<tr><th><i class='links fa fa-layer-group text-muted'></i> <a href='/<?php print $user->href; ?>/portgroups/'><?php print _("Port groups"); ?></a></th><td class='text-muted'><?php print _("View and manage scanning port groups"); ?></td></tr>
			<tr><th><i class='links fa fa-clock-o text-muted'></i> <a href='/<?php print $user->href; ?>/cron/'><?php print _("Cron jobs"); ?></a></th><td class='text-muted'><?php print _("Scheduled actions"); ?></td></tr>

		</table>
		</div>
	</div>
</div>
</div>