<?php

/**
 *
 * Per-user UI layout / theme preferences (Tabler theme settings).
 *
 * Every option maps to a data-bs-* attribute on <html>, which Tabler 1.7.5
 * CSS (tabler.css + tabler-themes.css) reacts to.
 *
 * Storage: users.layout (JSON, only explicitly chosen values; NULL = defaults).
 *  - while an admin impersonates a user, changes are kept in the session only
 *    ($_SESSION['layout_impersonate']) so the impersonated user's settings stay untouched
 *  - if the users.layout column does not exist yet (migration 0031 not applied)
 *    or nobody is logged in, values are kept in the session ($_SESSION['layout'])
 *
 */
class Layout
{
	/**
	 * Allowed values per option. First value is NOT the default — see $defaults.
	 * @var array
	 */
	public const OPTIONS = [
		'scheme'          => ['auto', 'light', 'dark', 'dark-nav', 'colored'],
		'theme-primary'   => ['blue', 'azure', 'indigo', 'purple', 'pink', 'red', 'orange', 'yellow', 'lime', 'green', 'teal', 'cyan', 'inverted'],
		'theme-base'      => ['slate', 'gray', 'zinc', 'neutral', 'stone', 'mist'],
		'theme-radius'    => ['0', '0.5', '1', '1.5', '2'],
		'navbar-position' => ['horizontal', 'vertical'],
		'layout'          => ['default', 'fluid', 'boxed'],
		'navbar'          => ['default', 'sticky'],
		'navbar-style'    => ['default', 'floating'],
		'offcanvas-style' => ['default', 'floating'],
		'sidebar'         => ['default', 'folded', 'folded-hover'],
	];

	/**
	 * Defaults for users with no saved preferences.
	 * @var array
	 */
	public const DEFAULTS = [
		'scheme'          => 'dark',
		'theme-primary'   => 'blue',
		'theme-base'      => 'slate',
		'theme-radius'    => '1',
		'navbar-position' => 'vertical',
		'layout'          => 'fluid',
		'navbar'          => 'sticky',
		'navbar-style'    => 'floating',
		'offcanvas-style' => 'floating',
		'sidebar'         => 'folded-hover',
	];

	/**
	 * Color scheme presets: one scheme pick sets the page theme and the navbar theme.
	 * @var array
	 */
	public const SCHEMES = [
		'auto'     => ['theme' => 'auto',  'navbar-theme' => null],
		'light'    => ['theme' => 'light', 'navbar-theme' => null],
		'dark'     => ['theme' => 'dark',  'navbar-theme' => null],
		'dark-nav' => ['theme' => 'light', 'navbar-theme' => 'dark'],
		'colored'  => ['theme' => 'light', 'navbar-theme' => 'primary'],
	];

	/**
	 * Current values (defaults merged with saved values)
	 * @var array|null
	 */
	private $values = null;

	/**
	 * Database object
	 * @var Database_PDO|null
	 */
	private $Database = null;

	/**
	 * Current user (row from users table) or null if not logged in
	 * @var object|null
	 */
	private $user = null;

	/**
	 * Constructor
	 * @method __construct
	 * @param  Database_PDO|null $Database
	 * @param  object|null $user
	 */
	public function __construct($Database = null, $user = null)
	{
		$this->Database = $Database;
		$this->user     = is_object($user) ? $user : null;
	}

	/**
	 * Returns all current values
	 * @method get_all
	 * @return array
	 */
	public function get_all(): array
	{
		if ($this->values === null) {
			$this->values = array_merge(self::DEFAULTS, $this->load());
		}
		return $this->values;
	}

	/**
	 * Returns a single current value
	 * @method get
	 * @param  string $key
	 * @return string
	 */
	public function get(string $key): string
	{
		return $this->get_all()[$key] ?? '';
	}

	/**
	 * Validates and stores one option
	 * @method set
	 * @param  string $key
	 * @param  string $value
	 * @return bool  false if key or value is invalid
	 */
	public function set(string $key, string $value): bool
	{
		if (!$this->is_valid($key, $value)) {
			return false;
		}
		$saved       = $this->load();
		$saved[$key] = $value;
		$this->save($saved);
		$this->values = null;
		return true;
	}

	/**
	 * Clears all saved values (back to defaults)
	 * @method reset
	 * @return void
	 */
	public function reset(): void
	{
		$this->save([]);
		$this->values = null;
	}

	/**
	 * Checks if key / value pair is allowed
	 * @method is_valid
	 * @param  string $key
	 * @param  string $value
	 * @return bool
	 */
	public function is_valid(string $key, string $value): bool
	{
		return isset(self::OPTIONS[$key]) && in_array($value, self::OPTIONS[$key], true);
	}

	/**
	 * Page theme for the selected scheme: light, dark or auto
	 * @method theme
	 * @return string
	 */
	public function theme(): string
	{
		return self::SCHEMES[$this->get('scheme')]['theme'];
	}

	/**
	 * Is the horizontal navbar (top menu) selected instead of the sidebar?
	 * @method is_horizontal
	 * @return bool
	 */
	public function is_horizontal(): bool
	{
		return $this->get('navbar-position') === 'horizontal';
	}

	/**
	 * Returns data-bs-* attributes for the <html> tag.
	 *
	 * Theme attributes (scheme, accent, base, radius) are always returned.
	 * Layout attributes (navigation, container, navbar, sidebar…) only when
	 * $app_layout is true — login, install and error pages have no navigation.
	 *
	 * For scheme "auto" the server renders light; an inline script in <head>
	 * switches to dark before first paint if the OS prefers dark.
	 *
	 * @method html_attributes
	 * @param  bool $app_layout
	 * @return string
	 */
	public function html_attributes(bool $app_layout = true): string
	{
		$v      = $this->get_all();
		$scheme = self::SCHEMES[$v['scheme']];
		$theme  = $scheme['theme'] === 'auto' ? 'light' : $scheme['theme'];

		$attr = [
			'data-bs-theme'        => $theme,
			'data-theme'           => $theme,
			'data-bs-theme-mode'   => $scheme['theme'],
			'data-bs-theme-primary'=> $v['theme-primary'],
			'data-bs-theme-base'   => $v['theme-base'],
			'data-bs-theme-radius' => $v['theme-radius'],
		];

		if ($app_layout) {
			if ($scheme['navbar-theme'] !== null) {
				$attr['data-bs-navbar-theme'] = $scheme['navbar-theme'];
			}
			// "combined" = sidebar + php-ssl top header (Tabler hides the header for plain "vertical")
			$attr['data-bs-navbar-position'] = $v['navbar-position'] === 'vertical' ? 'combined' : 'horizontal';
			$attr['data-bs-layout']          = $v['layout'];
			$attr['data-bs-navbar-style']    = $v['navbar-style'];
			$attr['data-bs-offcanvas-style'] = $v['offcanvas-style'];
			if ($v['navbar'] !== 'default') {
				$attr['data-bs-navbar'] = $v['navbar'];
			}
			if ($v['navbar-position'] === 'vertical' && $v['sidebar'] !== 'default') {
				$attr['data-bs-sidebar'] = $v['sidebar'];
			}
		}

		$out = [];
		foreach ($attr as $k => $val) {
			$out[] = $k.'="'.htmlspecialchars($val, ENT_QUOTES).'"';
		}
		$out[] = 'style="color-scheme: '.$theme.';"';

		return implode(' ', $out);
	}

	/**
	 * Where values are stored for this request: db, impersonate or session
	 * @method storage
	 * @return string
	 */
	private function storage(): string
	{
		if ($this->user === null || $this->Database === null || !property_exists($this->user, 'layout')) {
			return 'session';
		}
		return isset($_SESSION['impersonate_original']) ? 'impersonate' : 'db';
	}

	/**
	 * Keeps only valid key / value pairs
	 * @method sanitize
	 * @param  mixed $values
	 * @return array
	 */
	private function sanitize($values): array
	{
		$saved = [];
		if (is_array($values)) {
			foreach ($values as $k => $val) {
				if (is_scalar($val) && $this->is_valid((string)$k, (string)$val)) {
					$saved[(string)$k] = (string)$val;
				}
			}
		}
		return $saved;
	}

	/**
	 * Loads saved (non-default) values from storage
	 * @method load
	 * @return array
	 */
	private function load(): array
	{
		$storage = $this->storage();

		if ($storage === 'session') {
			return $this->sanitize($_SESSION['layout'] ?? []);
		}
		if ($storage === 'impersonate' && isset($_SESSION['layout_impersonate'])) {
			return $this->sanitize($_SESSION['layout_impersonate']);
		}
		// db (also the starting point while impersonating)
		$json = $this->user->layout;
		return is_string($json) && $json !== '' ? $this->sanitize(json_decode($json, true)) : [];
	}

	/**
	 * Writes saved values to storage
	 * @method save
	 * @param  array $saved
	 * @return void
	 */
	private function save(array $saved): void
	{
		$saved   = $this->sanitize($saved);
		$storage = $this->storage();

		if ($storage === 'session') {
			$_SESSION['layout'] = $saved;
		}
		elseif ($storage === 'impersonate') {
			$_SESSION['layout_impersonate'] = $saved;
		}
		else {
			$json = sizeof($saved) > 0 ? json_encode($saved) : null;
			$this->Database->runQuery("update users set `layout` = ? where `id` = ?", [$json, $this->user->id]);
			$this->user->layout = $json;
		}
	}
}
