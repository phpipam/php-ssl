<?php

/**
 *
 * Expired certificates - cronjob
 *
 * $j object is passed to scrip :: $j->t_id is tenant id
 *
 */

# load classes
$Result       = new Result ();
$Common       = new Common ();
$URL          = new URL ();
$Database     = new Database_PDO ();


# script can only be run from cli
if(php_sapi_name()!="cli") {
    $Common->errors[] = "This script can only be run from cli!";
    $Common->result_die ();
}

# save tenant id
$tenant_id = $j->t_id;

# fake user
$cron_user = new stdClass ();
$cron_user->t_id = $tenant_id;

#
# execute
#
try {
    // init certs
    $Certificates = new Certificates ($Database, $cron_user);

    // get config for $expired_days and $expired_after_days
    include(dirname(__FILE__) . "/../../config/config.php");

    // fetch all certificates that will expire (exclude muted hosts)
    $expired_certificates = $Certificates->get_expired ($expired_days, $expired_after_days, true);

    // load ignored issuers for this tenant
    $Certificates->get_all_ignored_issuers ($tenant_id);

    // mail diff
    if(sizeof($expired_certificates)>0) {

        // all users
        $User = new User ($Database);
        $all_users = $User->get_all ("email");

        // new array for expired and expire soon
        $all_expired_certs = [
                        "expire_soon" =>[],
                        "expired"     =>[]
                        ];

        // save to new array
        foreach ($expired_certificates as $c) {
            // skip certs whose issuer is marked as ignored for expiry notifications
            $cert_parsed_check = $Certificates->parse_cert ($c->certificate);
            $aki = str_replace("keyid:", "", $cert_parsed_check['extensions']['authorityKeyIdentifier'] ?? "");
            if ($Certificates->is_issuer_ignored ($aki, $tenant_id, 'expired') === true) {
                continue;
            }
            if(date("Y-m-d H:i:s")>$c->expires) {
                $all_expired_certs["expired"][] = $c;
            }
            else {
                $all_expired_certs["expire_soon"][] = $c;
            }
        }

        // reverse second array
        $all_expired_certs["expired"] = array_reverse($all_expired_certs["expired"]);

        // init mailer
        $Mail = new mailer ();
        // mail
        global $mail_sender_settings;

        // tenant
        $tenant = $Database->getObject("tenants", $tenant_id);

        // tenant recipients
        $email_to_tenant_recipents = array_values(array_filter(
            array_map('trim', explode(";", str_replace(",", ";", $tenant->recipients))),
            fn($e) => $Common->validate_mail($e)));

        $use_list = ($tenant->mail_style == "list");

        //
        // 1. collect certificates and who has to be notified about each of them.
        //    Mail content is rendered later, separately for each recipient language.
        //
        $notify_certs = [];                                         // [idx => ['c','cert','all_hosts','zone_name','zone_raw','type']]
        $tenant_idx   = ["expire_soon" => [], "expired" => []];     // public certs -> tenant recipients, per section
        $individual   = [];                                         // $email => ['certs'=>[idx], 'private'=>bool]

        foreach ($all_expired_certs as $type => $certificates) {
            foreach ($certificates as $c) {
                // parse cert
                $cert_parsed = $Certificates->parse_cert ($c->certificate);

                // CN - array ?
                if (is_array($cert_parsed['subject']['CN'])) {
                    $cert_parsed['subject']['CN'] = implode("<br>", $cert_parsed['subject']['CN']);
                }

                // get hosts
                $hosts = $Certificates->get_certificate_hosts ($c->id);
                $all_hosts = [];
                if (sizeof($hosts) > 0) {
                    foreach ($hosts as $h) {
                        $h->ip = $Certificates->validate_ip ($h->hostname) ? $h->hostname : $h->ip;
                        if ($Certificates->validate_ip ($h->hostname)) {
                            $all_hosts[] = $h->hostname;
                        }
                        else {
                            $all_hosts[] = $h->hostname." [".$h->ip."]";
                        }
                    }
                }
                else {
                    $all_hosts[] = "/";
                }

                // try to prevent linkable text
                $all_hosts                                   = array_map([$Mail, 'prevent_linkable_text'], $all_hosts);
                $cert_parsed['subject']['CN']                = $Mail->prevent_linkable_text($cert_parsed['subject']['CN']);
                $cert_parsed['extensions']['subjectAltName'] = $Mail->prevent_linkable_text($cert_parsed['extensions']['subjectAltName']);
                $cert_parsed['issuer']['O']                  = $Mail->prevent_linkable_text($cert_parsed['issuer']['O']);

                $idx = sizeof($notify_certs);
                $notify_certs[$idx] = [
                    'c'         => $c,
                    'cert'      => $cert_parsed,
                    'all_hosts' => $all_hosts,
                    'zone_name' => $Mail->prevent_linkable_text($c->zone_name ?? "/"),
                    'zone_raw'  => $c->zone_name ?? '',
                    'type'      => $type,
                ];

                // private zone: only notify the zone creator, not tenant recipients
                if (!empty($c->private_zone_uid)) {
                    $creator = $Database->getObject("users", $c->private_zone_uid);
                    if ($creator && $Common->validate_mail($creator->email)) {
                        if (!isset($individual[$creator->email])) { $individual[$creator->email] = ['certs'=>[], 'private'=>false]; }
                        $individual[$creator->email]['certs'][] = $idx;
                        $individual[$creator->email]['private'] = true;
                    }
                }
                else {
                    // tenant recipients
                    $tenant_idx[$type][] = $idx;

                    // per-cert recipients (tenant recipients already get this cert in the tenant mail)
                    foreach (explode(";", $c->h_recipients ?? '') as $r) {
                        $r = trim($r);
                        if ($Common->validate_mail($r) && !in_array($r, $email_to_tenant_recipents)) {
                            if (!isset($individual[$r])) { $individual[$r] = ['certs'=>[], 'private'=>false]; }
                            $individual[$r]['certs'][] = $idx;
                        }
                    }
                }
            }
        }

        //
        // 2. render + send. All _() calls below run inside with_locale(), so every
        //    mail is written in the language of its recipient(s).
        //

        // renders mail body in currently active language
        //   $sections : [type => [idx, ...]]
        //   $titles   : print section titles + table headers (tenant mail) or only the rows (individual mail)
        $render_mail = function (array $sections, bool $as_list, bool $titles) use ($Mail, $notify_certs, $mail_sender_settings, $tenant) {

            $td_style_title = "vertical-align:top;padding:1px 5px;white-space:nowrap;padding-left:0px;padding-bottom: 7px;padding-top:20px;";
            $td_style       = "border-left:1px solid #ddd;vertical-align:top;padding:1px 5px;white-space:nowrap;padding-left:10px;";

            $rows = ["<table border='0' cellpadding='3' cellspacing='0'>"];

            $m = 0;
            foreach ($sections as $type => $idx_list) {
                if (sizeof($idx_list) == 0) { continue; }

                $title    = $type == "expired" ? _("Expired certificates") : _("Certificates that will expire soon");
                $color    = $type == "expired" ? "#E74C3C" : "#FF5733";
                $color_bg = $type == "expired" ? "rgba(255,87,51,0.1)" : "rgba(248,196,113,0.1)";
                $status   = $type == "expired" ? _("Expired") : _("Expires soon");
                $padding  = $m == 0 ? "0" : "40";

                // section header
                if ($titles) {
                    if (!$as_list) {
                        array_push($rows,
                            "<tr>",
                            "  <td colspan='6' style='padding-top:20px;padding-bottom: 20px;'>".$Mail->font_title.$title."</font></td>",
                            "</tr>",
                            "<tr>",
                            "  <th style='border-bottom:2px solid #003551; text-align:left'>".$Mail->font_norm._("Certificate")."</font></th>",
                            "  <th style='border-bottom:2px solid #003551; text-align:left'>".$Mail->font_norm._("Serial")." / "._("Issuer")."</font></th>",
                            "  <th style='border-bottom:2px solid #003551; text-align:left'>".$Mail->font_norm._("Zone")."</font></th>",
                            "  <th style='border-bottom:2px solid #003551; text-align:left'>".$Mail->font_norm._("Valid untill")."</font></th>",
                            "  <th style='border-bottom:2px solid #003551; text-align:center'>".$Mail->font_norm._("Expires")."</font></th>",
                            "  <th style='border-bottom:2px solid #003551; text-align:left'>".$Mail->font_norm._("Hosts")."</font></th>",
                            "</tr>"
                        );
                    }
                    else {
                        array_push($rows,
                            "<tr>",
                            "  <td style='padding-top:{$padding}px;padding-left:0px;'>".$Mail->font_title.$title.":</font></td>",
                            "</tr>"
                        );
                    }
                }

                foreach ($idx_list as $idx) {
                    $c           = $notify_certs[$idx]['c'];
                    $cert_parsed = $notify_certs[$idx]['cert'];
                    $all_hosts   = $notify_certs[$idx]['all_hosts'];
                    $zone_name   = $notify_certs[$idx]['zone_name'];
                    $cert_link   = "https://".$mail_sender_settings->url."/".$tenant->href."/certificates/".rawurlencode($notify_certs[$idx]['zone_raw'])."/".$cert_parsed['serialNumber']."/";

                    if (!$as_list) {
                        array_push($rows,
                            "<tr>",
                            "  <td style='border-bottom:1px solid #ddd;vertical-align:top;padding:2px 5px;white-space:nowrap;padding-left: 5px;'>".$Mail->font_bold.$cert_parsed['subject']['CN']."</font></td>",
                            "  <td style='border-bottom:1px solid #ddd;vertical-align:top;padding:2px 5px;'><strong><a href='".$cert_link."'>".$Mail->font_bold.$cert_parsed['serialNumberHex']."</strong></font></a><br>".$Mail->font_norm.$cert_parsed['issuer']['O']."</font></td>",
                            "  <td style='border-bottom:1px solid #ddd;vertical-align:top;padding:2px 5px;white-space:nowrap;padding-left: 5px;'>".$Mail->font_norm.$zone_name."</font></td>",
                            "  <td style='border-bottom:1px solid #ddd;vertical-align:top;padding:2px 5px;white-space:nowrap;padding-left: 5px;'>".$Mail->font_norm.str_replace(" ", "<br>", $cert_parsed['custom_validTo'])."</font></td>",
                            "  <td style='border-bottom:1px solid #ddd;vertical-align:top;padding:2px 5px;'>".$Mail->font_norm."<div style='color:$color;border-radius:4px;margin-top:5px;padding:2px 6px;border:1px solid $color;background:$color_bg;text-align: center;white-space:nowrap;'>".$cert_parsed['custom_validDays']." "._("days")."</div></font></td>",
                            "  <td style='border-bottom:1px solid #ddd;vertical-align:top;padding:2px 5px;'>".$Mail->font_ligh.implode("<br>", $all_hosts)."</font></td>",
                            "</tr>"
                        );
                    }
                    else {
                        array_push($rows,
                            "<tr><td style='$td_style_title'>".$Mail->font_bold.$cert_parsed['subject']['CN']."</font></td></tr>",
                            "<tr><td style='$td_style'>".$Mail->font_norm._("Subject").": ".$cert_parsed['subject']['CN']."</font></td></tr>",
                            "<tr><td style='$td_style'>".$Mail->font_norm._("Zone").": ".$zone_name."</font></td></tr>",
                            "<tr><td style='$td_style'>".$Mail->font_norm._("Status").": <span style='color:$color;padding:0px;margin:0px;'>".$status." (".$cert_parsed['custom_validDays']." "._("days").")</span> </font></td></tr>",
                            "<tr><td style='$td_style'>".$Mail->font_norm._("Issuer").": ".$cert_parsed['issuer']['O']."</font></td></tr>",
                            "<tr><td style='$td_style'>".$Mail->font_norm._("Serial").": <a href='".$cert_link."' style='text-decoration:none;color:#333'>".$cert_parsed['serialNumberHex']."</a></font></td></tr>",
                            "<tr><td style='$td_style'>".$Mail->font_norm._("Expires").": ".$cert_parsed['custom_validTo']."</font></td></tr>",
                            "<tr><td style='$td_style'>".$Mail->font_norm._("Hosts").":<br><span style='padding:2px;padding-left:15px;'>".implode("</span><br><span style='padding:2px;padding-left:15px;'>", $all_hosts)."</span></font></td></tr>"
                        );
                        if (strlen($cert_parsed['extensions']['subjectAltName']) > 0)
                            $rows[] = "<tr><td style='$td_style'>".$Mail->font_norm._("Altnames").":<br><span style='padding:2px;padding-left:15px;'>".str_replace(",", "</span><br><span style='padding:2px;padding-left:15px;'>", $cert_parsed['extensions']['subjectAltName'])."</span></font></td></tr>";
                    }
                }
                $m++;
            }

            // close table + footer
            $rows[] = "</table>";
            $rows[] = "<br><br>".$Mail->font_norm._("Visit")." <a href='".$mail_sender_settings->www."' style='color:#003551;'>".$mail_sender_settings->www."</a></font>";

            return $rows;
        };

        $Log = new Log ($Database);

        // send to tenant recipients together - one mail per recipient language
        if (!empty($email_to_tenant_recipents) && (sizeof($tenant_idx["expire_soon"]) + sizeof($tenant_idx["expired"])) > 0) {
            foreach (group_recipients_by_lang ($email_to_tenant_recipents, $tenant_id, $Database) as $group) {
                with_locale ($group['lang_id'], $Database, function () use ($group, $render_mail, $tenant_idx, $use_list, $Mail, $Log, $tenant) {
                    $title   = "Telemach php-ssl :: "._("certificate expiration")." [".$tenant->name."]";
                    $content = implode("\n", $render_mail ($tenant_idx, $use_list, true));
                    $sent    = $Mail->send ($title, $group['emails'], [], [], $content, false);
                    if ($sent) {
                        $Log->write ("users", NULL, $tenant->id, null, "notification", true, "Certificate expiry notification email sent to tenant recipients", json_encode($group['emails']), json_encode(["title"=>$title, "data"=>$content]), false);
                    } else {
                        $Log->write ("users", NULL, $tenant->id, null, "notification", true, "Certificate expiry notification email FAILED for tenant recipients", json_encode($group['emails']), null, false);
                    }
                });
            }
        }

        // send to per-cert recipients individually, in their language; private zone creators get no BCC to tenant recipients
        foreach ($individual as $email => $item) {
            // keep section order (expire soon first, then expired)
            $sections = ["expire_soon" => [], "expired" => []];
            foreach ($item['certs'] as $idx) { $sections[$notify_certs[$idx]['type']][] = $idx; }

            $bcc = $item['private'] ? [] : $email_to_tenant_recipents;
            with_locale (recipient_lang_id ($email, $tenant_id, $Database), $Database, function () use ($email, $sections, $bcc, $render_mail, $Mail, $Log, $tenant, $all_users) {
                $title = "Telemach php-ssl :: "._("certificate expiration");
                $rows  = $render_mail ($sections, true, false);
                $sent  = $Mail->send ($title, [$email], [], $bcc, implode("\n", $rows), false);
                if ($sent) {
                    $Log->write ("users", $all_users[$email]->id ?? null, $tenant->id, null, "notification", true, "Certificate expiry notification email sent to user ".(isset($all_users[$email]) ? $all_users[$email]->name : $email)." (".$email.")", json_encode([$email]), json_encode(["title"=>$title, "data"=>$rows]), false);
                } else {
                    $Log->write ("users", $all_users[$email]->id ?? null, $tenant->id, null, "notification", true, "Certificate expiry notification email FAILED for user ".(isset($all_users[$email]) ? $all_users[$email]->name : $email)." (".$email.")", json_encode([$email]), null, false);
                }
            });
        }
    }
} catch (Exception $e) {
    // print error
    $Common->errors[] = $e->getMessage();
    $Common->show_cli ($Common->get_last_error());
}
