<?php
$User->validate_session();

$_tf_error    = null;
$_tf_info     = null;
$_tf_format   = null;   // 'pem' or 'der'
$_tf_pem_b64  = null;
$_tf_der_b64  = null;
$_tf_filename = 'certificate';
$_tf_is_chain = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['certfile']['name'])) {
    $file = $_FILES['certfile'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_tf_error = _("File upload failed.");
    } elseif ($file['size'] > 131072) {
        $_tf_error = _("File too large. Maximum size is 128 KB.");
    } else {
        $raw = file_get_contents($file['tmp_name']);

        if ($raw !== false && strlen($raw) > 0) {
            if (strpos($raw, '-----BEGIN CERTIFICATE-----') !== false) {
                $_tf_format   = 'pem';
                $_tf_is_chain = substr_count($raw, '-----BEGIN CERTIFICATE-----') > 1;

                // Extract only the first certificate from a possible chain
                preg_match('/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s', $raw, $_tf_match);
                $pem = isset($_tf_match[0]) ? trim($_tf_match[0])."\n" : '';

                // PEM → DER
                $pem_body = preg_replace('/-----[^-]+-----/', '', $pem);
                $pem_body = preg_replace('/\s+/', '', $pem_body);
                $der      = base64_decode($pem_body);
            } else {
                // Assume DER binary
                $_tf_format = 'der';
                $der        = $raw;
                $pem        = "-----BEGIN CERTIFICATE-----\n"
                            . chunk_split(base64_encode($der), 64, "\n")
                            . "-----END CERTIFICATE-----\n";
            }

            $_tf_cert_res = @openssl_x509_read($pem);
            if ($_tf_cert_res) {
                $_tf_info    = openssl_x509_parse($_tf_cert_res);
                $_tf_pem_b64 = base64_encode($pem);
                $_tf_der_b64 = base64_encode($der);
                if (!empty($_tf_info['subject']['CN'])) {
                    $_tf_filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $_tf_info['subject']['CN']);
                }
            } else {
                $_tf_error = _("Could not parse certificate. Please upload a valid X.509 certificate.");
            }
        } else {
            $_tf_error = _("Could not read uploaded file.");
        }
    }
}
?>

<div class="page-header">
    <h2 class="page-title"><?php print $url_items['transform']['icon']; ?> <?php print _("Transform certificate"); ?></h2>
    <hr>
</div>

<div>
    <a href="/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg>
        <?php print _("Back"); ?>
    </a>
</div>

<div class="row" style="margin-top:20px;">
<div class="col-xs-12 col-sm-12 col-md-12 col-lg-6">

<div class="card" style="max-width:600px;">
<div class="card-body">

    <?php if ($_tf_error): ?>
    <div class="alert alert-danger mb-3">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4" /><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.871l-8.106 -13.534a1.914 1.914 0 0 0 -3.274 0z" /><path d="M12 16h.01" /></svg>
        <?php print htmlspecialchars($_tf_error); ?>
    </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="space-y">
        <div>
            <label class="form-label"><?php print _("Certificate file"); ?></label>
            <input type="file" name="certfile" class="form-control"
                   accept=".pem,.crt,.cer,.der,.cert"
                   required>
            <div class="form-hint"><?php print _("Supported: PEM (.pem, .crt, .cer) and DER binary (.der, .cer)"); ?></div>
        </div>
        <hr>
        <button type="submit" class="btn btn-info w-100">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="icon icon-tabler icons-tabler-filled icon-tabler-transform"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 14a4 4 0 1 1 -3.995 4.2l-.005 -.2l.005 -.2a4 4 0 0 1 3.995 -3.8z" /><path d="M16.707 2.293a1 1 0 0 1 .083 1.32l-.083 .094l-1.293 1.293h3.586a3 3 0 0 1 2.995 2.824l.005 .176v3a1 1 0 0 1 -1.993 .117l-.007 -.117v-3a1 1 0 0 0 -.883 -.993l-.117 -.007h-3.585l1.292 1.293a1 1 0 0 1 -1.32 1.497l-.094 -.083l-3 -3a.98 .98 0 0 1 -.28 -.872l.036 -.146l.04 -.104c.058 -.126 .14 -.24 .245 -.334l2.959 -2.958a1 1 0 0 1 1.414 0z" /><path d="M3 12a1 1 0 0 1 .993 .883l.007 .117v3a1 1 0 0 0 .883 .993l.117 .007h3.585l-1.292 -1.293a1 1 0 0 1 -.083 -1.32l.083 -.094a1 1 0 0 1 1.32 -.083l.094 .083l3 3a.98 .98 0 0 1 .28 .872l-.036 .146l-.04 .104a1.02 1.02 0 0 1 -.245 .334l-2.959 2.958a1 1 0 0 1 -1.497 -1.32l.083 -.094l1.291 -1.293h-3.584a3 3 0 0 1 -2.995 -2.824l-.005 -.176v-3a1 1 0 0 1 1 -1z" /><path d="M6 2a4 4 0 1 1 -3.995 4.2l-.005 -.2l.005 -.2a4 4 0 0 1 3.995 -3.8z" /></svg>
            <?php print _("Upload and detect"); ?>
        </button>
    </form>

</div>
</div>

<?php if ($_tf_info): ?>

<?php if ($_tf_is_chain): ?>
<div class="alert alert-warning mt-3" style="max-width:600px;">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4" /><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.871l-8.106 -13.534a1.914 1.914 0 0 0 -3.274 0z" /><path d="M12 16h.01" /></svg>
    <?php print _("Certificate chain detected — only the first certificate was processed."); ?>
</div>
<?php endif; ?>

<div class="card mt-3" style="max-width:600px;">
<div class="card-header">
    <h3 class="card-title"><?php print _("Certificate details"); ?></h3>
    <div class="card-options">
        <?php if ($_tf_format === 'pem'): ?>
        <span class="badge badge-outline text-green"><?php print _("PEM (Base64 text)"); ?></span>
        <?php else: ?>
        <span class="badge badge-outline text-blue"><?php print _("DER (Binary)"); ?></span>
        <?php endif; ?>
    </div>
</div>
<div class="card-body">

<?php
$_tf_subject = $_tf_info['subject'] ?? [];
$_tf_issuer  = $_tf_info['issuer']  ?? [];

$_tf_cn        = is_array($_tf_subject['CN'] ?? '') ? implode(', ', $_tf_subject['CN']) : ($_tf_subject['CN'] ?? '—');
$_tf_org       = is_array($_tf_subject['O']  ?? '') ? implode(', ', $_tf_subject['O'])  : ($_tf_subject['O']  ?? '');
$_tf_issuer_cn = is_array($_tf_issuer['CN']  ?? '') ? implode(', ', $_tf_issuer['CN'])  : ($_tf_issuer['CN']  ?? '—');
$_tf_issuer_o  = is_array($_tf_issuer['O']   ?? '') ? implode(', ', $_tf_issuer['O'])   : ($_tf_issuer['O']   ?? '');

$_tf_valid_from  = isset($_tf_info['validFrom_time_t'])  ? date('Y-m-d H:i:s', $_tf_info['validFrom_time_t'])  : '—';
$_tf_valid_until = isset($_tf_info['validTo_time_t'])    ? date('Y-m-d H:i:s', $_tf_info['validTo_time_t'])    : '—';
$_tf_serial      = $_tf_info['serialNumber'] ?? '—';
$_tf_sans        = $_tf_info['extensions']['subjectAltName'] ?? null;
?>

<table class="table table-borderless table-sm table-md table-td-top" style="margin-bottom:0;">
    <tr>
        <td class="text-secondary" style="min-width:140px; width:160px;"><?php print _("Subject"); ?></td>
        <td>
            <b><?php print htmlspecialchars($_tf_cn); ?></b>
            <?php if ($_tf_org): ?>
            <div class="text-secondary" style="font-size:12px;"><?php print htmlspecialchars($_tf_org); ?></div>
            <?php endif; ?>
        </td>
    </tr>
    <tr>
        <td class="text-secondary"><?php print _("Issuer"); ?></td>
        <td>
            <?php print htmlspecialchars($_tf_issuer_cn); ?>
            <?php if ($_tf_issuer_o): ?>
            <div class="text-secondary" style="font-size:12px;"><?php print htmlspecialchars($_tf_issuer_o); ?></div>
            <?php endif; ?>
        </td>
    </tr>
    <tr>
        <td class="text-secondary"><?php print _("Valid from"); ?></td>
        <td><?php print htmlspecialchars($_tf_valid_from); ?></td>
    </tr>
    <tr>
        <td class="text-secondary"><?php print _("Valid until"); ?></td>
        <td><?php print htmlspecialchars($_tf_valid_until); ?></td>
    </tr>
    <tr>
        <td class="text-secondary"><?php print _("Serial number"); ?></td>
        <td><span style="font-size:12px; font-family:monospace;"><?php print htmlspecialchars($_tf_serial); ?></span></td>
    </tr>
    <?php if ($_tf_sans): ?>
    <tr>
        <td class="text-secondary"><?php print _("Alt names"); ?></td>
        <td>
            <div style="font-size:12px; word-break:break-word;">
                <?php
                $sans_list = array_map('trim', explode(',', $_tf_sans));
                foreach ($sans_list as $_san) {
                    print '<span class="badge bg-blue-lt me-1 mb-1">'.htmlspecialchars($_san).'</span>';
                }
                ?>
            </div>
        </td>
    </tr>
    <?php endif; ?>
</table>

<hr>

<div class="d-flex gap-2 flex-wrap">
    <button class="btn btn-sm bg-green-lt" onclick="downloadCert('pem')">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg>
        <?php print _("Download as PEM"); ?>
    </button>
    <button class="btn btn-sm bg-blue-lt" onclick="downloadCert('der')">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg>
        <?php print _("Download as DER"); ?>
    </button>
</div>

</div>
</div>

<script>
(function () {
    var pemB64   = <?php print json_encode($_tf_pem_b64); ?>;
    var derB64   = <?php print json_encode($_tf_der_b64); ?>;
    var filename = <?php print json_encode($_tf_filename); ?>;

    function downloadBlob(b64data, mimeType, ext) {
        var binary = atob(b64data);
        var bytes  = new Uint8Array(binary.length);
        for (var i = 0; i < binary.length; i++) { bytes[i] = binary.charCodeAt(i); }
        var blob = new Blob([bytes], {type: mimeType});
        var a    = document.createElement('a');
        a.href   = URL.createObjectURL(blob);
        a.download = filename + '.' + ext;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(a.href);
    }

    window.downloadCert = function (format) {
        if (format === 'pem') {
            downloadBlob(pemB64, 'application/x-pem-file', 'pem');
        } else {
            downloadBlob(derB64, 'application/octet-stream', 'der');
        }
    };
})();
</script>

<?php endif; ?>

</div>
</div>
