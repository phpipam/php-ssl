<?php

$icon_cert = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-certificate"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 15m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M13 17.5v4.5l2 -1.5l2 1.5v-4.5" /><path d="M10 19h-5a2 2 0 0 1 -2 -2v-10c0 -1.1 .9 -2 2 -2h14a2 2 0 0 1 2 2v3.5" /></svg>';

$cards = [
	[
		'title'        => _("Current certificate"),
		'header_class' => '',
		'card_style'   => '',
		'is_current'   => true,
		'cert_parsed'  => $cert_parsed,
		'status_text'  => $status['text'],
		'san_list'     => $san_list,
		'days_class'   => $days_class,
		'days_valid'   => $days_valid,
	],
];

if (!empty($host->c_id_old)) {
	$cards[] = [
		'title'        => _("Previous certificate"),
		'header_class' => 'text-secondary',
		'card_style'   => 'margin-top:10px;',
		'is_current'   => false,
		'cert_parsed'  => $cert_old_parsed,
		'status_text'  => $cert_old_status['text'],
		'san_list'     => $cert_old_san_list,
		'days_class'   => $cert_old_days_class,
		'days_valid'   => $cert_old_days_valid,
	];
}

foreach ($cards as $c):

	$has_cert    = !($c['cert_parsed'] === null || empty($c['cert_parsed']) || $c['cert_parsed']['serialNumberHex']==="/");
	$table_class = 'table table-borderless table-md table-hover table-zones-details';
	if ($c['is_current']) {
		$table_class .= ' table-td-top';
		if ($has_cert) { $table_class .= ' table-condensed'; }
	}
	?>
	<div class='card'<?php if ($c['card_style']) { print " style='" . $c['card_style'] . "'"; } ?>>
		<div class='card-header<?php if ($c['header_class']) { print ' ' . $c['header_class']; } ?>'><?php print $icon_cert; ?> <?php print $c['title']; ?></div>
		<div>


		<?php if ($c['is_current'] && !$has_cert): ?>
		<div class='alert alert-info' style='margin:10px;'>
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-info-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path><path d="M12 9h.01"></path><path d="M11 12h1v4h1"></path></svg>
			<?php print _("No certificate found."); ?>
		</div>

		<?php elseif (!$has_cert && $host->c_id_old!==NULL): ?>
		<div class='alert alert-info' style='margin:10px;'>
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-info-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path><path d="M12 9h.01"></path><path d="M11 12h1v4h1"></path></svg>
			<?php print _("Previous certificate already removed from database."); ?>
		</div>

		<?php else: ?>
		<table class='<?php print $table_class; ?>'>

			<tr>
				<td class="text-secondary" style='min-width:160px;width:180px;'><?php print _("Status"); ?></td>
				<td>
					<?php print $c['status_text']; ?>
				</td>
			</tr>

			<tr>
				<td class="text-secondary"><?php print _("Serial"); ?></td>
				<td>
					<a class='btn btn-sm' target="_blank" href='/<?php print $_params['tenant']; ?>/certificates/<?php print $zone->name; ?>/<?php print $c['cert_parsed']['serialNumber']; ?>/'>
						<?php print htmlspecialchars(chunk_split($c['cert_parsed']['serialNumber'])); ?>
					</a>
				</td>
			</tr>

			<tr>
				<td class="text-secondary"><?php print _("Common name"); ?></td>
				<td><?php print htmlspecialchars($c['cert_parsed']['subject']['CN'] ?? '/'); ?></td>
			</tr>

			<?php if (!empty($c['san_list'])): ?>
			<tr>
				<td class="text-secondary"><?php print _("SANs"); ?></td>
				<td><?php print implode("<br>", array_map('htmlspecialchars', $c['san_list'])); ?></td>
			</tr>
			<?php endif; ?>

			<tr>
				<td class="text-secondary"><?php print _("Issuer"); ?></td>
				<td><?php print htmlspecialchars($c['cert_parsed']['issuer']['CN'] ?? ($c['cert_parsed']['issuer']['O'] ?? '/')); ?></td>
			</tr>

			<tr>
				<td class="text-secondary"><?php print _("Valid from/to"); ?></td>
				<td>
					<span class='text-secondary'>
						<?php print isset($c['cert_parsed']['custom_validFrom']) ? htmlspecialchars($c['cert_parsed']['custom_validFrom']) : date("Y-m-d H:i:s", $c['cert_parsed']['validFrom_time_t']); ?>
					</span> -
					<span class='text-<?php print $c['days_class']; ?>'>
						<?php print isset($c['cert_parsed']['custom_validTo']) ? htmlspecialchars($c['cert_parsed']['custom_validTo']) : date("Y-m-d H:i:s", $c['cert_parsed']['validTo_time_t']); ?>
					</span>
					<span class='badge text-<?php print $c['days_class']; ?>'><?php print htmlspecialchars($c['days_valid']); ?> <?php print _("Days"); ?></span>
				</td>
			</tr>

		</table>
		<?php endif; ?>
		</div>
	</div>
	<?php endforeach; ?>
