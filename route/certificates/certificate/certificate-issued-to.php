<?php

// Fetch pkey record for this certificate (may be null)
$_pkey = $certificate->pkey_id
    ? $Database->getObjectQuery("SELECT * FROM pkey WHERE id = ?", [$certificate->pkey_id])
    : null;
$_has_private_key = $_pkey && !empty($_pkey->private_key_enc);

print "<table class='table table-cert-details table-borderless table-auto table-details table-md' style='width:auto;margin:10px'>";

print "<tr>";
print "	<td class='text-secondary' style='min-width:$td_min_width'>"._("Common name")."</td>";
print "	<td>".$certificate_details['subject']['CN_all']."</td>";
print "</tr>";
print "	<td class='text-secondary' style='min-width:$td_min_width'>"._("Valid for domains")."</td>";
print "	<td>".str_replace(",","<br>",$certificate_details['extensions']['subjectAltName'])."</td>";
print "</tr>";
print "<tr>";
print "	<td class='text-secondary' style='min-width:$td_min_width'>"._("Status")."</td>";
print "	<td>".$status['text']."$valid_period";
if($_has_private_key)
print  '<span class="badge bg-green-lt" data-bs-toggle="tooltip" title="'._("Private key available").'">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16.555 3.843l3.602 3.602a2.877 2.877 0 0 1 0 4.069l-2.643 2.643a2.877 2.877 0 0 1 -4.069 0l-.301 -.301l-6.558 6.558a2 2 0 0 1 -1.239 .578l-.175 .008h-1.172a1 1 0 0 1 -.993 -.883l-.007 -.117v-1.172a2 2 0 0 1 .467 -1.284l.119 -.13l.414 -.414h2v-2h2v-2l2.144 -2.144l-.301 -.301a2.877 2.877 0 0 1 0 -4.069l2.643 -2.643a2.877 2.877 0 0 1 4.069 0z" /><circle cx="15" cy="9" r="1" fill="currentColor" stroke="none" /></svg></span>';
print "</td>";
print "</tr>";
print "<tr>";
print "	<td class='text-secondary' style='min-width:$td_min_width'>"._("Discovered")."</td>";
print "	<td>".$certificate->created."</td>";
print "</tr>";
?>
<?php if (!isset($is_from_fetch)): ?>
<tr>
    <td></td>
    <td style="padding-top:10px;">

        <?php // ── Row 1: download / upload actions ──────────────────────── ?>
        <br>
        <div style="margin-bottom:6px; display:flex; flex-wrap:wrap; gap:4px;">

            <a class="btn btn-outline text-info bg-info-lt btn-sm"
               href="/route/modals/certificates/download.php?certificate=<?php print base64_encode($certificate->certificate); ?>"
               data-bs-toggle="modal" data-bs-target="#modal1">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg>
                <?php print _("Download certificate"); ?>
            </a>

            <?php if (!empty($certificate->chain)): ?>
            <a class="btn btn-outline text-info bg-info-lt btn-sm"
               href="/route/ajax/chain-download.php?cert_id=<?php print (int)$certificate->id; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg>
                <?php print _("Download chain"); ?>
            </a>
            <?php endif; ?>

            <?php if ($_has_private_key): ?>
            <a class="btn btn-outline text-info bg-info-lt btn-sm"
               href="/route/modals/certificates/pkey-export.php?cert_id=<?php print (int)$certificate->id; ?>"
               data-bs-toggle="modal" data-bs-target="#modal1">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16.555 3.843l3.602 3.602a2.877 2.877 0 0 1 0 4.069l-2.643 2.643a2.877 2.877 0 0 1 -4.069 0l-.301 -.301l-6.558 6.558a2 2 0 0 1 -1.239 .578l-.175 .008h-1.172a1 1 0 0 1 -.993 -.883l-.007 -.117v-1.172a2 2 0 0 1 .467 -1.284l.119 -.13l.414 -.414h2v-2h2v-2l2.144 -2.144l-.301 -.301a2.877 2.877 0 0 1 0 -4.069l2.643 -2.643a2.877 2.877 0 0 1 4.069 0z" /><circle cx="15" cy="9" r="1" fill="currentColor" stroke="none" /></svg>
                <?php print _("Download key"); ?>
            </a>
            <?php else: ?>
            <a class="btn btn-sm  text-white"
               href="/route/modals/certificates/pkey-upload.php?cert_id=<?php print (int)$certificate->id; ?>"
               data-bs-toggle="modal" data-bs-target="#modal1">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 7l5 -5l5 5" /><path d="M12 4l0 12" /></svg>
                <?php print _("Upload private key"); ?>
            </a>
            <?php endif; ?>

        </div>

        <?php // ── Row 2: destructive actions ─────────────────────────────── ?>
        <div style="display:flex; flex-wrap:wrap; gap:4px;">
            <a class="btn btn-sm bg-danger-lt text-danger"
               href="/route/modals/certificates/delete.php?tenant=<?php print $_params['tenant']; ?>&serial=<?php print $certificate_details['serialNumber']; ?>"
               data-bs-toggle="modal" data-bs-target="#modal1">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                <?php print _("Remove certificate"); ?>
            </a>

            <?php if ($_has_private_key): ?>
            <button type="button"
                    class="btn btn-sm bg-danger-lt text-danger btn-pkey-delete"
                    data-cert-id="<?php print (int)$certificate->id; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16.555 3.843l3.602 3.602a2.877 2.877 0 0 1 0 4.069l-2.643 2.643a2.877 2.877 0 0 1 -4.069 0l-.301 -.301l-6.558 6.558a2 2 0 0 1 -1.239 .578l-.175 .008h-1.172a1 1 0 0 1 -.993 -.883l-.007 -.117v-1.172a2 2 0 0 1 .467 -1.284l.119 -.13l.414 -.414h2v-2h2v-2l2.144 -2.144l-.301 -.301a2.877 2.877 0 0 1 0 -4.069l2.643 -2.643a2.877 2.877 0 0 1 4.069 0z" /><circle cx="15" cy="9" r="1" fill="currentColor" stroke="none" /></svg>
                <?php print _("Remove key"); ?>
            </button>
            <?php endif; ?>

        </div>

    </td>
</tr>
<?php endif; ?>

<?php
print "</table>";
?>

<script>
(function () {
    document.querySelectorAll('.btn-pkey-delete').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirm(<?php print json_encode(_("Remove stored private key for this certificate?")); ?>)) return;
            var certId = parseInt(this.dataset.certId);
            fetch('/route/ajax/pkey-delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ certificate_id: certId })
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.status === 'ok') { location.reload(); }
                else { alert(data.message || <?php print json_encode(_("Error")); ?>); }
            })
            .catch(function () { alert(<?php print json_encode(_("Error")); ?>); });
        });
    });
})();
</script>
