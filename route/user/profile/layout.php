<?php
// Variables available from profile/index.php: $view_user, $user, $Database, $User
// $Layout comes from functions/autoload.php

$_layout_values   = $Layout->get_all();
$_layout_previews = include(__DIR__ . "/layout-previews.php");

// option groups, rendered in this order (same controls as the Tabler preview settings panel)
$_layout_sections = [
	_("Appearance") => [
		'scheme' => [
			'label'   => _("Color scheme"),
			'help'    => _("The page and its navigation, in one pick."),
			'options' => ['auto' => _("Auto"), 'light' => _("Light"), 'dark' => _("Dark"), 'dark-nav' => _("Dark nav"), 'colored' => _("Colored")],
		],
		'theme-primary' => [
			'label'   => _("Accent color"),
			'help'    => _("The accent color used across the app."),
			'type'    => 'color',
			'options' => ['blue' => _("Blue"), 'azure' => _("Azure"), 'indigo' => _("Indigo"), 'purple' => _("Purple"), 'pink' => _("Pink"), 'red' => _("Red"), 'orange' => _("Orange"), 'yellow' => _("Yellow"), 'lime' => _("Lime"), 'green' => _("Green"), 'teal' => _("Teal"), 'cyan' => _("Cyan"), 'inverted' => _("Inverted")],
		],
		'theme-base' => [
			'label'   => _("Theme base"),
			'help'    => _("The gray palette behind every surface."),
			'options' => ['slate' => _("Slate"), 'gray' => _("Gray"), 'zinc' => _("Zinc"), 'neutral' => _("Neutral"), 'stone' => _("Stone"), 'mist' => _("Mist")],
		],
		'theme-radius' => [
			'label'   => _("Corner radius"),
			'help'    => _("How round corners are drawn."),
			'options' => ['0' => '0', '0.5' => '0.5', '1' => '1', '1.5' => '1.5', '2' => '2'],
		],
	],
	_("Layout") => [
		'navbar-position' => [
			'label'   => _("Navigation"),
			'help'    => _("Where the main menu lives."),
			'options' => ['horizontal' => _("Navbar"), 'vertical' => _("Sidebar")],
		],
		'layout' => [
			'label'   => _("Container width"),
			'help'    => _("How wide the page content runs."),
			'options' => ['default' => _("Default"), 'fluid' => _("Fluid"), 'boxed' => _("Boxed")],
		],
		'navbar' => [
			'label'   => _("Navbar behavior"),
			'help'    => _("Keep the navbar visible while scrolling."),
			'options' => ['default' => _("Default"), 'sticky' => _("Sticky")],
		],
		'navbar-style' => [
			'label'   => _("Navigation style"),
			'help'    => _("Attach the navigation to the edges, or let it float."),
			'options' => ['default' => _("Attached"), 'floating' => _("Floating")],
		],
		'offcanvas-style' => [
			'label'   => _("Offcanvas style"),
			'help'    => _("Attach side panels to the edges, or let them float."),
			'options' => ['default' => _("Attached"), 'floating' => _("Floating")],
		],
		'sidebar' => [
			'label'   => _("Sidebar"),
			'help'    => _("How the sidebar behaves."),
			'options' => ['default' => _("Expanded"), 'folded' => _("Folded"), 'folded-hover' => _("On hover")],
		],
	],
];
?>

<div class="card-body">
	<h3 class="card-title d-flex align-items-center gap-2">
		<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 4m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z" /><path d="M4 9h16" /><path d="M9 9v11" /></svg>
		<?php print _("Layout"); ?>
		<span id="layout-status" class="small fw-normal ms-2"></span>
	</h3>
	<div class="text-secondary"><?php print _("Pick the look and layout of the application. Changes are applied and saved immediately."); ?></div>
	<?php if (isset($_SESSION['impersonate_original'])) { ?>
	<div class="text-warning small mt-1"><?php print _("You are impersonating this user: changes apply to this session only and are not saved to the user's settings."); ?></div>
	<?php } ?>
</div>

<form id="layout-settings" autocomplete="off" onsubmit="return false;">

	<?php foreach ($_layout_sections as $_section_title => $_groups) { ?>
	<div class="card-body">
		<h3 class="subheader mb-3"><?php print $_section_title; ?></h3>

		<div class="space-y-4">
		<?php foreach ($_groups as $_key => $_group) {
			$_gid = 'layout-setting-' . $_key;
		?>
			<div role="group" aria-labelledby="<?php print $_gid; ?>" data-theme-setting="<?php print $_key; ?>">
				<div class="form-label mb-1" id="<?php print $_gid; ?>"><?php print $_group['label']; ?></div>
				<div class="form-text mb-3"><?php print $_group['help']; ?></div>

				<?php if (($_group['type'] ?? '') === 'color') { ?>
				<div class="row g-2">
					<?php foreach ($_group['options'] as $_val => $_caption) { ?>
					<div class="col-auto">
						<label class="form-colorinput" title="<?php print htmlspecialchars($_caption); ?>">
							<input type="radio" name="layout-<?php print $_key; ?>" data-key="<?php print $_key; ?>" value="<?php print $_val; ?>" class="form-colorinput-input" aria-label="<?php print htmlspecialchars($_caption); ?>"<?php print $_layout_values[$_key] === (string)$_val ? ' checked' : ''; ?>>
							<span class="form-colorinput-color bg-<?php print $_val; ?>"></span>
						</label>
					</div>
					<?php } ?>
				</div>
				<?php } else { ?>
				<div class="row g-3">
					<?php foreach ($_group['options'] as $_val => $_caption) { ?>
					<div class="col-6 col-sm-4 col-lg-3 col-xxl-2">
						<label class="form-imagecheck mb-0">
							<input type="radio" name="layout-<?php print $_key; ?>" data-key="<?php print $_key; ?>" value="<?php print $_val; ?>" class="form-imagecheck-input"<?php print $_layout_values[$_key] === (string)$_val ? ' checked' : ''; ?>>
							<span class="form-imagecheck-figure p-1">
								<?php print $_layout_previews[$_key][$_val] ?? ''; ?>
								<span class="form-imagecheck-caption"><?php print $_caption; ?></span>
							</span>
						</label>
					</div>
					<?php } ?>
				</div>
				<?php } ?>
			</div>
		<?php } ?>
		</div>
	</div>
	<?php } ?>

	<div class="card-body border-top">
		<button type="button" class="btn" id="layout-reset">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" class="icon"><path d="M19.95 11a8 8 0 1 0 -.5 4m.5 5v-5h-5" /></svg>
			<?php print _("Reset to defaults"); ?>
		</button>
	</div>
</form>

<script>
(function () {
	var csrf    = <?php print json_encode($User->create_csrf_token()); ?>;
	var schemes = <?php print json_encode(Layout::SCHEMES); ?>;
	var html    = document.documentElement;
	var form    = document.getElementById('layout-settings');
	var status  = document.getElementById('layout-status');
	var current = <?php print json_encode($_layout_values); ?>;
	var txt     = {
		saved: <?php print json_encode(_("Saved")); ?>,
		error: <?php print json_encode(_("Saving failed")); ?>
	};
	var statusTimer = null;

	function showStatus(ok, msg) {
		status.className = 'small fw-normal ms-2 ' + (ok ? 'text-success' : 'text-danger');
		status.textContent = msg;
		clearTimeout(statusTimer);
		if (ok) { statusTimer = setTimeout(function () { status.textContent = ''; }, 1500); }
	}

	function setAttr(name, value) {
		if (value === null || value === undefined) { html.removeAttribute(name); }
		else { html.setAttribute(name, value); }
	}

	// apply one option to <html> immediately (same attributes Layout::html_attributes() renders)
	function apply(key, value) {
		if (key === 'scheme') {
			var s     = schemes[value];
			var theme = s.theme === 'auto'
				? (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
				: s.theme;
			setAttr('data-bs-theme', theme);
			setAttr('data-theme', theme);
			setAttr('data-bs-theme-mode', s.theme);
			setAttr('data-bs-navbar-theme', s['navbar-theme']);
			html.style.colorScheme = theme;
		}
		else if (key === 'navbar') {
			setAttr('data-bs-navbar', value === 'default' ? null : value);
		}
		else if (key === 'sidebar') {
			setAttr('data-bs-sidebar', (value === 'default' || current['navbar-position'] !== 'vertical') ? null : value);
		}
		else if (key !== 'navbar-position') {
			setAttr('data-bs-' + key, value);
		}
		current[key] = value;
		toggleGroups();
		// plugins that measured the old width (tables) need a nudge
		window.dispatchEvent(new Event('resize'));
	}

	// sidebar options only make sense with the sidebar navigation
	function toggleGroups() {
		var sb = form.querySelector('[data-theme-setting="sidebar"]');
		if (sb) { sb.hidden = current['navbar-position'] !== 'vertical'; }
	}

	function post(data, done) {
		data.csrf_token = csrf;
		$.post('/route/ajax/layout-save.php', data, function (r) {
			if (r && r.success) { showStatus(true, txt.saved); if (done) { done(); } }
			else { showStatus(false, (r && r.error) ? r.error : txt.error); }
		}, 'json').fail(function () { showStatus(false, txt.error); });
	}

	form.addEventListener('change', function (e) {
		var key = e.target.getAttribute('data-key');
		if (!key) { return; }
		var value = e.target.value;
		apply(key, value);
		// switching navbar <-> sidebar changes page structure → reload after save
		post({ key: key, value: value }, key === 'navbar-position' ? function () { location.reload(); } : null);
	});

	document.getElementById('layout-reset').addEventListener('click', function () {
		post({ reset: 1 }, function () { location.reload(); });
	});

	toggleGroups();
})();
</script>
