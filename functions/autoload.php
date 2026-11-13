<?php

/**
 *
 * Class, config etc autoloading
 *
 */

/**
 * Safe ucwords translation helper.
 * Wraps _(ucwords($s)) but returns '' for empty strings to avoid
 * gettext returning the PO file header when passed an empty msgid.
 */
function _u (string $s): string {
	return $s !== '' ? _(ucwords($s)) : '';
}

/**
 * Initialise PHP gettext locale for this request.
 *
 * Resolution order (highest priority first):
 *  1. $_SESSION['lang_id']   – in-session override (user clicked language switch)
 *  2. users.lang_id          – per-user preference
 *  3. tenants.lang_id        – tenant default
 *  4. English (no gettext)   – built-in fallback, _() returns msgid as-is
 *
 * @param object|null $user     Current user object from $User->get_current_user()
 * @param object      $Database Database_PDO instance
 */
function init_locale ($user, $Database)
{
	$lang_id = null;

	// 1. In-session override
	if (isset($_SESSION['lang_id']) && is_numeric($_SESSION['lang_id'])) {
		$lang_id = (int) $_SESSION['lang_id'];
	}
	// 2. User preference (query directly to avoid JOIN column ambiguity)
	elseif ($user !== null && !empty($user->id)) {
		try {
			$row = $Database->getObjectQuery ("SELECT lang_id FROM users WHERE id = ?", [$user->id]);
			if ($row && !empty($row->lang_id)) {
				$lang_id = (int) $row->lang_id;
			}
		} catch (Exception $e) { /* non-fatal */ }
	}
	// 3. Tenant default
	if ($lang_id === null && $user !== null && !empty($user->t_id)) {
		try {
			$row = $Database->getObjectQuery ("SELECT lang_id FROM tenants WHERE id = ?", [$user->t_id]);
			if ($row && !empty($row->lang_id)) {
				$lang_id = (int) $row->lang_id;
			}
		} catch (Exception $e) { /* non-fatal */ }
	}

	// 4. English fallback (null) is handled by apply_locale()
	apply_locale ($lang_id, $Database);
}

/**
 * Switch the active gettext language to the given translations.id.
 *
 * null, 1 (English), an unknown/disabled id or an English locale all select
 * the English fallback, so _() returns the msgid as-is. Can be called any
 * number of times per process (e.g. once per email recipient in cron).
 *
 * @param int|null $lang_id  translations.id
 * @param object   $Database Database_PDO instance
 * @return void
 */
function apply_locale ($lang_id, $Database)
{
	static $locale_codes = [];

	$lang_id = is_numeric($lang_id) ? (int) $lang_id : null;
	$locale  = null;

	// Fetch locale_code from DB (cached per process)
	if ($lang_id !== null && $lang_id !== 1) {
		if (!array_key_exists($lang_id, $locale_codes)) {
			try {
				$tr = $Database->getObjectQuery ("SELECT locale_code FROM translations WHERE id = ? AND enabled = 1", [$lang_id]);
				$locale_codes[$lang_id] = ($tr && !empty($tr->locale_code)) ? $tr->locale_code : null;
			} catch (Exception $e) {
				$locale_codes[$lang_id] = null;
			}
		}
		$locale = $locale_codes[$lang_id];
	}

	// No preference, English, or unknown language: explicitly reset the textdomain.
	// Without this, a PHP-FPM worker (or a cron run) that previously used a
	// non-English locale still has e.g. textdomain("messages_2") active, so
	// English output in the same process would contain translated (wrong) strings.
	// Setting a domain with no .mo file causes gettext to return the original
	// msgid strings, which are in English — the correct behaviour.
	if ($locale === null || strpos($locale, 'en_') === 0) {
		bindtextdomain ("messages_1", dirname(__FILE__) . "/../functions/locale");
		textdomain ("messages_1");
		$GLOBALS['active_lang_id'] = null;
		return;
	}

	// Initialise gettext
	putenv ("LANG={$locale}");
	putenv ("LANGUAGE={$locale}");
	// Try multiple locale variants for cross-platform compatibility
	setlocale (LC_ALL, [$locale, str_replace('.UTF-8', '.utf8', $locale), str_replace('.UTF-8', '', $locale)]);
	// Use a locale-specific domain name to bypass PHP-FPM's per-process
	// gettext .mo cache — the C library caches by domain name, so reusing
	// "messages" across locales in the same worker process serves stale
	// translations.  Each locale gets its own domain → its own cache slot.
	$domain = "messages_{$lang_id}";
	bindtextdomain ($domain, dirname(__FILE__) . "/../functions/locale");
	bind_textdomain_codeset ($domain, "UTF-8");
	textdomain ($domain);
	$GLOBALS['active_lang_id'] = $lang_id;
}

/**
 * Resolve the language an email to $email should be written in.
 *
 * Resolution order:
 *  1. users.lang_id of the user with this email (a user of tenant $t_id wins
 *     over a user with the same email in another tenant)
 *  2. tenants.lang_id of tenant $t_id (also used for addresses that are not users)
 *  3. null — English
 *
 * @param string   $email
 * @param int|null $t_id     tenant the notification belongs to
 * @param object   $Database Database_PDO instance
 * @return int|null translations.id
 */
function recipient_lang_id ($email, $t_id, $Database)
{
	static $cache = [];

	$email = strtolower(trim((string) $email));
	$t_id  = is_numeric($t_id) ? (int) $t_id : 0;
	$key   = $t_id . "|" . $email;
	if (array_key_exists($key, $cache)) { return $cache[$key]; }

	$lang_id = null;
	// 1. user preference
	if ($email !== "") {
		try {
			$row = $Database->getObjectQuery ("SELECT lang_id FROM users WHERE LOWER(email) = ? AND lang_id IS NOT NULL ORDER BY (t_id = ?) DESC, id ASC LIMIT 1", [$email, $t_id]);
			if ($row && !empty($row->lang_id)) { $lang_id = (int) $row->lang_id; }
		} catch (Exception $e) { /* non-fatal */ }
	}
	// 2. tenant default
	if ($lang_id === null && $t_id > 0) {
		try {
			$row = $Database->getObjectQuery ("SELECT lang_id FROM tenants WHERE id = ?", [$t_id]);
			if ($row && !empty($row->lang_id)) { $lang_id = (int) $row->lang_id; }
		} catch (Exception $e) { /* non-fatal */ }
	}

	return $cache[$key] = $lang_id;
}

/**
 * Group email addresses by the language they should receive mail in.
 *
 * @param array    $emails
 * @param int|null $t_id
 * @param object   $Database
 * @return array   [ lang_key => ['lang_id' => int|null, 'emails' => [..]] ]
 */
function group_recipients_by_lang (array $emails, $t_id, $Database)
{
	$groups = [];
	foreach ($emails as $email) {
		$lang_id = recipient_lang_id ($email, $t_id, $Database);
		$k = $lang_id === null ? "default" : "lang_".$lang_id;
		if (!isset($groups[$k])) { $groups[$k] = ['lang_id' => $lang_id, 'emails' => []]; }
		$groups[$k]['emails'][] = $email;
	}
	return $groups;
}

/**
 * Run $fn with gettext switched to $lang_id, then restore the previously
 * active language (e.g. the logged-in user's UI language).
 *
 * @param int|null $lang_id
 * @param object   $Database
 * @param callable $fn
 * @return mixed   whatever $fn returns
 */
function with_locale ($lang_id, $Database, callable $fn)
{
	$previous = $GLOBALS['active_lang_id'] ?? null;
	apply_locale ($lang_id, $Database);
	try {
		return $fn();
	} finally {
		apply_locale ($previous, $Database);
	}
}

/**
 * Returns all enabled translations, ordered by name.
 * Returns an empty array if the translations table does not exist yet.
 *
 * @param  object $Database
 * @return array
 */
function load_translations ($Database)
{
	try {
		$rows = $Database->getObjectsQuery ("SELECT * FROM translations WHERE enabled = 1 ORDER BY name ASC", []);
		return $rows ?: [];
	} catch (Exception $e) {
		return [];
	}
}

# config
include (__DIR__ . "/../config/config.php");

# version
include (dirname(__FILE__)."/../version.php");

# include classes
include (__DIR__ . "/classes/class.PDO.php");
include (__DIR__ . "/classes/class.Result.php");
include (__DIR__ . "/classes/class.Validate.php");
include (__DIR__ . "/classes/class.Common.php");
include (__DIR__ . "/classes/class.URL.php");
include (__DIR__ . "/classes/class.Config.php");
include (__DIR__ . "/classes/class.SSL.php");
include (__DIR__ . "/classes/class.Cron.php");
include (__DIR__ . "/classes/class.Tenants.php");
include (__DIR__ . "/classes/class.Zones.php");
include (__DIR__ . "/classes/class.Certificates.php");
include (__DIR__ . "/classes/class.User.php");
include (__DIR__ . "/classes/class.Modal.php");
include (__DIR__ . "/classes/class.Mail.php");
include (__DIR__ . "/classes/class.Thread.php");
include (__DIR__ . "/classes/class.AXFR.php");
include (__DIR__ . "/classes/class.Agent.php");
include (__DIR__ . "/classes/class.Log.php");
include (__DIR__ . "/classes/class.ADsync.php");
include (__DIR__ . "/classes/class.Migration.php");
include (__DIR__ . "/classes/class.WebAuthn.php");
include (__DIR__ . "/classes/class.testssl.php");
include (__DIR__ . "/classes/class.Nmap.php");
include (__DIR__ . "/classes/class.Layout.php");

# testssl submodule availability flag
$testssl_available = file_exists(dirname(__FILE__)."/testSSL/testssl.sh");

# required extensions
$required_extensions = ['curl', 'openssl', 'posix', 'pdo', 'pdo_mysql', 'session', 'hash'];
$missing_extensions = php_feature_missing_all ($required_extensions);

if (sizeof($missing_extensions)>0)
{
	// html
	$title   = "php-ssl requirements error";
	$url     = isset($_SERVER['HTTPS']) ? "https://" : "http://" .$_SERVER['SERVER_NAME'];

	// errors
	$error_title = "Required php extensions are missing";
	$error_text  = "php-ssl requires some php extensions to be loaded to work properly, some are missing:";

	// table
	$error_text .= "<table class='table table-sm table-borderless table-extensions'>";
	foreach ($required_extensions as $req) {
		$error_text .= '<tr>';
		$error_text .= '<td>';
		$error_text .= !in_array($req, $missing_extensions) ? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-check text-green"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-x text-red"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>';
		$error_text .= '</td>';
		$error_text .= '<td>'.$req.'</td>';
		$error_text .= '<td class="text-secondary">'.php_extension_purpose ($req).'</td>';
		$error_text .= '</tr>';

	}
	$error_text .= "</table>";

	// load content - error
	$_params = ['tenant'=>'error', "route"=>"generic"];
}
else
{
	# load classes
	try {
		$Result       = new Result ();
		$Common       = new Common ();
		$URL          = new URL ();
		$Database     = new Database_PDO ();
		$Modal 		  = new Modal ();
		$Config       = new Config ($Database);
		$User         = new User ($Database);
		$SSL          = new SSL ($Database);
		$Cron         = new Cron ($Database, $User->get_current_user());
		$Tenants      = new Tenants ($Database);
		$Zones        = new Zones ($Database, $User->get_current_user());
		$Certificates = new Certificates ($Database, $User->get_current_user());
		$Log 		  = new Log ($Database);
		$Migration    = new Migrations ($Database);
		$Layout       = new Layout ($Database, $User->get_current_user());

		# save user to local var
		$user = $User->get_current_user();

		# initialise gettext locale for this request
		init_locale ($user, $Database);

		# load available translations for language switcher
		$all_translations = load_translations ($Database);

		# validate requested path
		$URL->validate_path ($user);

		# set params from GET and args
		$_params = $URL->get_params ();

		# menu
		include (__DIR__ . "/config.menu.php");
	}
	catch (Exception $e) {
		// SQL error ?
		if(strpos($e->getMessage(), "SQLSTATE")!==false) {
			$error_title = "Failed to connect to SQL database";
			$error_text = str_replace("Stack trace", "<br><br>Stack trace", $e->getMessage());
			$error_text = str_replace("\n", "<br>", $error_text);

			// do we need to install product ?
			if (@$installed!==true) {

				if (strpos($_SERVER['REQUEST_URI'], "/install")!==0) {
					header('Location: /install/');
					die();
				}

				// html
				$title   = "php-ssl installation";
				$url     = isset($_SERVER['HTTPS']) ? "https://" : "http://" .$_SERVER['SERVER_NAME'];
				// load content - error
				$_params = ['tenant'=>'install'];
			}
			else {
				// load content - error
				$_params = ['tenant'=>'error', "route"=>"generic"];
			}
		}
		// generic
		else {
			$error_title = "Error";
			$error_text = str_replace("\n", "<br>", $e->getMessage());

			// load content - error
			$_params = ['tenant'=>'error', "route"=>"generic"];
		}
	}
}
