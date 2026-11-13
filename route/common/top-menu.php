<?php

/**
 * Horizontal top menu (layout option "Navigation: Navbar").
 *
 * Built from the same $url_items as the sidebar (left-menu.php):
 *  - items before the first section title (e.g. Dashboard) → top-level links
 *  - each section title (mtitle) → top-level dropdown holding that section's items
 *  - items with a submenu → nested dropend dropdown inside the section
 */

# group items into sections
$_menu_groups = [];
$_menu_group  = null;

foreach ($url_items as $route => $items) {
	if (isset($items['show']) && $items['show'] == false) {
		continue;
	}
	if (isset($items['mtitle'])) {
		$_menu_groups[] = ['title' => $items['mtitle'], 'icon' => $items['icon'], 'items' => []];
		$_menu_group    = array_key_last($_menu_groups);
	}
	if ($_menu_group === null) {
		$_menu_groups[] = ['title' => null, 'items' => [$route => $items]];
	}
	else {
		$_menu_groups[$_menu_group]['items'][$route] = $items;
	}
}

$_menu_href = '/' . htmlspecialchars($user->href) . '/';
$_menu_app  = $_params['app'] ?? null;
?>

<div class="navbar-expand-md">
	<div class="collapse navbar-collapse" id="navbar-menu">
		<div class="navbar">
			<div class="container-fluid">
				<ul class="navbar-nav">
				<?php foreach ($_menu_groups as $group) { ?>

					<?php if ($group['title'] === null) {
						// single top-level link
						$route = array_key_first($group['items']);
						$items = $group['items'][$route];
					?>
					<li class="nav-item<?php print $_params['route'] == $route ? ' active' : ''; ?>">
						<a class="nav-link" href="<?php print $_menu_href . $items['href']; ?>/">
							<span class="nav-link-icon d-md-none d-lg-inline-block"><?php print $items['icon']; ?></span>
							<span class="nav-link-title"><?php print $items['title']; ?></span>
						</a>
					</li>

					<?php } else {
						// section dropdown
						$_group_active = array_key_exists($_params['route'], $group['items']);
					?>
					<li class="nav-item dropdown<?php print $_group_active ? ' active' : ''; ?>">
						<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false">
							<span class="nav-link-icon d-md-none d-lg-inline-block"><?php print $group['icon']; ?></span>
							<span class="nav-link-title"><?php print $group['title']; ?></span>
						</a>
						<div class="dropdown-menu">
						<?php foreach ($group['items'] as $route => $items) {
							$_item_active = $_params['route'] == $route ? ' active' : '';
						?>
							<?php if (!isset($items['submenu'])) { ?>
							<a class="dropdown-item<?php print $_item_active; ?>" href="<?php print $_menu_href . $items['href']; ?>/">
								<?php print $items['icon']; ?> <?php print $items['title']; ?>
							</a>
							<?php } else { ?>
							<div class="dropend">
								<a class="dropdown-item dropdown-toggle<?php print $_item_active; ?>" href="#" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false">
									<?php print $items['icon']; ?> <?php print $items['title']; ?>
								</a>
								<div class="dropdown-menu">
								<?php foreach ($items['submenu'] as $link => $sm) {
									$_sub_active = ($_item_active && $_menu_app == $link) ? ' active' : '';
								?>
									<a class="dropdown-item<?php print $_sub_active; ?>" href="<?php print $_menu_href . $items['href'] . '/' . $link; ?>/">
										<?php print $sm['icon'] ?? ''; ?> <?php print $sm['title']; ?>
									</a>
								<?php } ?>
								</div>
							</div>
							<?php } ?>
						<?php } ?>
						</div>
					</li>
					<?php } ?>

				<?php } ?>
				</ul>
			</div>
		</div>
	</div>
</div>
