<div class='card'>
	<div class='card-header'><?php print $icon_host; ?> <?php print htmlspecialchars($hostname); ?></div>
	<div>
	<table class='table table-borderless table-md table-hover table-td-top table-zones-details'>

		<tr>
			<td class='text-secondary' style='min-width:160px;width:180px;'><?php print _("Hostname"); ?></td>
			<td><b><?php print htmlspecialchars($hostname); ?></b></td>
		</tr>

		<tr>
			<td class='text-secondary'><?php print _("Zone"); ?></td>
			<td><a class='text-info' href='/<?php print $_params['tenant']; ?>/zones/<?php print htmlspecialchars($zone->name); ?>/'><?php print htmlspecialchars($zone->name); ?></a></td>
		</tr>

		<?php if ($user->admin == "1"): ?>
		<tr>
			<td class='text-secondary'><?php print _("Tenant"); ?></td>
			<td><?php print htmlspecialchars($host->tenant_name); ?></td>
		</tr>
		<?php endif; ?>

		<tr>
			<td class='text-secondary'><?php print _("IP address"); ?></td>
			<td><?php print is_null($host->ip) ? "<span class='badge bg-light-lt text-muted'>"._("Unresolved")."</span>" : htmlspecialchars($host->ip); ?></td>
		</tr>

		<tr>
			<td class='text-secondary'><?php print _("Port"); ?></td>
			<td><?php print strlen($host->port) > 0 ? "<span class='badge'>" . htmlspecialchars($host->port) . "</span>" : "/"; ?></td>
		</tr>

		<tr>
			<td class='text-secondary'><?php print _("Port group"); ?></td>
			<td><span class='badge'><?php print htmlspecialchars($port_group_name); ?></span></td>
		</tr>

		<tr>
			<td class='text-secondary'><?php print _("Last checked"); ?></td>
			<td><span class='text-secondary'><?php print $last_check_formatted; ?></span></td>
		</tr>

		<tr>
			<td class='text-secondary'><?php print _("Last changed"); ?></td>
			<td><span class='text-secondary'><?php print $last_change_formatted; ?></span></td>
		</tr>

		<tr>
			<td class='text-secondary'><?php print _("Notifications"); ?></td>
			<td>
				<?php if ($host->mute == "1"): ?>
				<span class='badge bg-red-lt text-red'><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 15h-2a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h2l3.5 -4.5a.8 .8 0 0 1 1.5 .5v14a.8 .8 0 0 1 -1.5 .5l-3.5 -4.5" /><path d="M16 10l4 4m0 -4l-4 4" /></svg> <?php print _("Muted"); ?></span>
				<?php else: ?>
				<span class='badge bg-green-lt text-green'><?php print _("Enabled"); ?></span>
				<?php endif; ?>
			</td>
		</tr>

		<tr>
			<td class='text-secondary'><?php print _("SSL scan"); ?></td>
			<td>
				<?php if ($host->ignore == "1"): ?>
				<span class='badge bg-red-lt text-red'><?php print _("Ignored"); ?></span>
				<?php else: ?>
				<span class='badge bg-green-lt text-green'><?php print _("Active"); ?></span>
				<?php endif; ?>
			</td>
		</tr>

		<?php if (sizeof($recipients) > 0): ?>
		<tr>
			<td class='text-secondary'><?php print _("Recipients"); ?></td>
			<td><?php print implode("<br>", array_map('htmlspecialchars', $recipients)); ?></td>
		</tr>
		<?php endif; ?>

		<tr class='line'>
			<td class='text-secondary'><?php print _("Actions"); ?></td>
			<td>
				<div style='display:flex;flex-direction:column;gap:4px;align-items:flex-start;width:100%' class='actions'>
					<div>
						<a href='/route/modals/zones/host_cert_refresh.php?tenant=<?php print $_params['tenant']; ?>&zone_id=<?php print $zone->id; ?>&host_id=<?php print $host->id; ?>' class='btn btn-sm' data-bs-toggle='modal' data-bs-target='#modal1'><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon text-success"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" /></svg> <?php print _("Refresh certificate"); ?></a>
					</div>
					<div>
						<a href='/route/modals/zones/host-set-recipients.php?tenant=<?php print $_params['tenant']; ?>&zone_id=<?php print $zone->id; ?>&host_id=<?php print $host->id; ?>' class='btn btn-sm' data-bs-toggle='modal' data-bs-target='#modal1'><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon text-info"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" /><path d="M6 21v-2a4 4 0 0 1 4 -4h2.5" /><path d="M17.001 19a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M19.001 15.5v1.5" /><path d="M19.001 21v1.5" /><path d="M22.032 17.25l-1.299 .75" /><path d="M17.27 20l-1.3 .75" /><path d="M15.97 17.25l1.3 .75" /><path d="M20.733 20l1.3 .75" /></svg> <?php print _("Manage recipients"); ?></a>
					</div>
					<div>
						<a href='/route/modals/zones/host_ignore_mute.php?type=mute&tenant=<?php print $_params['tenant']; ?>&zone_id=<?php print $zone->id; ?>&host_id=<?php print $host->id; ?>' class='btn  btn-sm' data-bs-toggle='modal' data-bs-target='#modal1'><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon <?php print $host->mute=="1" ? "text-danger" : "text-secondary"; ?>"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 8a5 5 0 0 1 0 8" /><path d="M17.7 5a9 9 0 0 1 0 14" /><path d="M6 15h-2a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h2l3.5 -4.5a.8 .8 0 0 1 1.5 .5v14a.8 .8 0 0 1 -1.5 .5l-3.5 -4.5" /></svg> <?php print $host->mute=="1" ? _("Unmute notifications") : _("Mute notifications"); ?></a>
					</div>
					<div>
						<a href='/route/modals/zones/host_ignore_mute.php?type=ignore&tenant=<?php print $_params['tenant']; ?>&zone_id=<?php print $zone->id; ?>&host_id=<?php print $host->id; ?>' class='btn btn-sm' data-bs-toggle='modal' data-bs-target='#modal1'><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon <?php print $host->ignore=="1" ? "text-danger" : "text-secondary"; ?>"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 7.2a2.2 2.2 0 0 1 2.2 -2.2h1a2.2 2.2 0 0 0 1.55 -.64l.7 -.7a2.2 2.2 0 0 1 3.12 0l.7 .7c.412 .41 .97 .64 1.55 .64h1a2.2 2.2 0 0 1 2.2 2.2v1c0 .58 .23 1.138 .64 1.55l.7 .7a2.2 2.2 0 0 1 0 3.12l-.7 .7a2.2 2.2 0 0 0 -.64 1.55v1a2.2 2.2 0 0 1 -2.2 2.2h-1a2.2 2.2 0 0 0 -1.55 .64l-.7 .7a2.2 2.2 0 0 1 -3.12 0l-.7 -.7a2.2 2.2 0 0 0 -1.55 -.64h-1a2.2 2.2 0 0 1 -2.2 -2.2v-1a2.2 2.2 0 0 0 -.64 -1.55l-.7 -.7a2.2 2.2 0 0 1 0 -3.12l.7 -.7a2.2 2.2 0 0 0 .64 -1.55v-1c0 -.604 .244 -1.152 .638 -1.55" /><path d="M9 12l2 2l4 -4" /></svg> <?php print $host->ignore=="1" ? _("Enable scanning") : _("Ignore scanning"); ?></a>
					</div>
					<div>
						<a href='/route/modals/zones/delete_hostname.php?tenant=<?php print $_params['tenant']; ?>&zone_id=<?php print $zone->id; ?>&host_id=<?php print $host->id; ?>' class='btn btn-sm' data-bs-toggle='modal' data-bs-target='#modal1'><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon text-danger"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg> <?php print _("Delete host"); ?></a></div>
				</div>
			</td>
		</tr>

	</table>
	</div>
</div>

<style type="text/css">
.actions svg {
	margin-right:20px;
}
</style>
