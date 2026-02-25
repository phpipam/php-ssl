<?php

$icon_mail = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-mail"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z" /><path d="M3 7l9 6l9 -6" /></svg>';

?>

<div class='row'>
	<div class='col-6' style='margin-top:10px;'>
		<div class='card'>
			<div class='card-header'><?php print $icon_mail; ?> <?php print _("Notification recipients"); ?></div>
			<div>
			<table class='table table-borderless table-md table-hover table-zones-details'>

				<tr>
					<td class='text-secondary' style='min-width:160px;width:180px;vertical-align:top;'><?php print _("Tenant recipients"); ?></td>
					<td>
						<?php if (empty($tenant_recipients)): ?>
						<span class='text-secondary'><?php print _("None configured"); ?></span>
						<?php else: ?>
						<?php foreach ($tenant_recipients as $r): ?>
						<div><?php print htmlspecialchars(trim($r)); ?></div>
						<?php endforeach; ?>
						<?php endif; ?>
					</td>
					<td class='text-secondary' style='font-size:0.85em;'><?php print _("Receive notifications for all hosts in this tenant"); ?></td>
				</tr>

				<tr>
					<td class='text-secondary' style='vertical-align:top;'><?php print _("Host recipients"); ?></td>
					<td>
						<?php if (empty($recipients)): ?>
						<span class='text-secondary'><?php print _("None configured"); ?></span>
						<?php else: ?>
						<?php foreach ($recipients as $r): ?>
						<div><?php print htmlspecialchars(trim($r)); ?></div>
						<?php endforeach; ?>
						<?php endif; ?>
					</td>
					<td class='text-secondary' style='font-size:0.85em;'><?php print _("Receive notifications only for this host"); ?></td>
				</tr>

			</table>
			</div>
		</div>
	</div>
</div>
