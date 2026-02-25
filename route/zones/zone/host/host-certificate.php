<?php

$icon_cert = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-certificate"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 15m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M13 17.5v4.5l2 -1.5l2 1.5v-4.5" /><path d="M10 19h-5a2 2 0 0 1 -2 -2v-10c0 -1.1 .9 -2 2 -2h14a2 2 0 0 1 2 2v3.5" /></svg>';

?>

<div class='card'>
	<div class='card-header'><?php print $icon_cert; ?> <?php print _("Current certificate"); ?></div>
	<div>
	<?php if ($cert_parsed === null || empty($cert_parsed)): ?>
	<table class='table table-borderless table-md table-hover table-td-top table-zones-details'>
		<tr>
			<td colspan='2'><span class='text-secondary'><?php print _("No certificate data available."); ?></span></td>
		</tr>
	</table>
	<?php else: ?>
	<table class='table table-borderless table-md table-hover table-td-top table-zones-details table-condensed'>

		<tr>
			<td class="text-secondary" style='min-width:160px;width:180px;'><?php print _("Status"); ?></td>
			<td>
				<?php print ($status['text']); ?>
			</td>
		</tr>

		<tr>
			<td class="text-secondary"><?php print _("Serial"); ?></td>
			<td>
				<a class='btn btn-sm' target="_blank" href='/<?php print $_params['tenant']; ?>/certificates/<?php print $zone->name; ?>/<?php print $cert_parsed['serialNumber']; ?>/'>
					<?php print htmlspecialchars(chunk_split($cert_parsed['serialNumber'])); ?>
				</a>
			</td>
		</tr>

		<tr>
			<td class="text-secondary"><?php print _("Common name"); ?></td>
			<td><?php print htmlspecialchars($cert_parsed['subject']['CN'] ?? '/'); ?></td>
		</tr>

		<?php if (!empty($san_list)): ?>
		<tr>
			<td class="text-secondary"><?php print _("SANs"); ?></td>
			<td><?php print implode("<br>", array_map('htmlspecialchars', $san_list)); ?></td>
		</tr>
		<?php endif; ?>

		<tr>
			<td class="text-secondary"><?php print _("Issuer"); ?></td>
			<td><?php print htmlspecialchars($cert_parsed['issuer']['CN'] ?? ($cert_parsed['issuer']['O'] ?? '/')); ?></td>
		</tr>

		<tr>
			<td class="text-secondary"><?php print _("Valid from"); ?></td>
			<td><span class='text-secondary'><?php print isset($cert_parsed['custom_validFrom']) ? htmlspecialchars($cert_parsed['custom_validFrom']) : date("Y-m-d H:i:s", $cert_parsed['validFrom_time_t']); ?></span></td>
		</tr>

		<tr>
			<td class="text-secondary"><?php print _("Valid to"); ?></td>
			<td class='text-<?php print $days_class; ?>'><?php print isset($cert_parsed['custom_validTo']) ? htmlspecialchars($cert_parsed['custom_validTo']) : date("Y-m-d H:i:s", $cert_parsed['validTo_time_t']); ?></td>
		</tr>

		<tr>
			<td class="text-secondary"><?php print _("Days remaining"); ?></td>
			<td><span class='text-<?php print $days_class; ?>'><?php print htmlspecialchars($days_valid); ?></span></td>
		</tr>

	</table>
	<?php endif; ?>
	</div>
</div>

<?php if (!empty($host->c_id_old) && $cert_old_parsed !== null): ?>
<div class='card' style='margin-top:10px;'>
	<div class='card-header text-secondary'><?php print $icon_cert; ?> <?php print _("Previous certificate"); ?></div>
	<div>
	<table class='table table-borderless table-md table-hover table-zones-details'>

		<tr>
			<td class="text-secondary" style='min-width:160px;width:180px;'><?php print _("Status"); ?></td>
			<td>
				<?php print ($cert_old_status['text']); ?>
			</td>
		</tr>

		<tr>
			<td class="text-secondary"><?php print _("Serial"); ?></td>
			<td>
				<a class='btn btn-sm' target="_blank"  href='/<?php print $_params['tenant']; ?>/certificates/<?php print $zone->name; ?>/<?php print $cert_old_parsed['serialNumber']; ?>/'>
					<?php print htmlspecialchars(chunk_split($cert_old_parsed['serialNumber'])); ?>
				</a>
			</td>
		</tr>

		<tr>
			<td class="text-secondary"><?php print _("Common name"); ?></td>
			<td><?php print htmlspecialchars($cert_old_parsed['subject']['CN'] ?? '/'); ?></td>
		</tr>

		<?php if (!empty($cert_old_san_list)): ?>
		<tr>
			<td class="text-secondary"><?php print _("SANs"); ?></td>
			<td><?php print implode("<br>", array_map('htmlspecialchars', $cert_old_san_list)); ?></td>
		</tr>
		<?php endif; ?>

		<tr>
			<td class="text-secondary"><?php print _("Issuer"); ?></td>
			<td><?php print htmlspecialchars($cert_old_parsed['issuer']['CN'] ?? ($cert_old_parsed['issuer']['O'] ?? '/')); ?></td>
		</tr>

		<tr>
			<td class="text-secondary"><?php print _("Valid from"); ?></td>
			<td><span class='text-secondary'><?php print isset($cert_old_parsed['custom_validFrom']) ? htmlspecialchars($cert_old_parsed['custom_validFrom']) : date("Y-m-d H:i:s", $cert_old_parsed['validFrom_time_t']); ?></span></td>
		</tr>

		<tr>
			<td class="text-secondary"><?php print _("Valid to"); ?></td>
			<td class='text-<?php print $cert_old_days_class; ?>'><?php print isset($cert_old_parsed['custom_validTo']) ? htmlspecialchars($cert_old_parsed['custom_validTo']) : date("Y-m-d H:i:s", $cert_old_parsed['validTo_time_t']); ?></td>
		</tr>

		<tr>
			<td class="text-secondary"><?php print _("Days remaining"); ?></td>
			<td><span class='text-<?php print $cert_old_days_class; ?>'><?php print htmlspecialchars($cert_old_days_valid); ?></span></td>
		</tr>

	</table>
	</div>
</div>
<?php endif; ?>
