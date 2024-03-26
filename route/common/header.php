<header class="navbar navbar-dark fixed-top bg-dark flex-md-nowrap">
	<a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="/">php-ssl-scan</a>
	<button class="navbar-toggler align-items-end d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
		<span class="navbar-toggler-icon"></span>
	</button>
	<div class="nav-item text-nowrap">
		<?php print $user->name; ?> <span class='text-muted'>[<?php print $user->t_name; ?>]</span><a class="px-3" href="/logout/"><i class='fa fa-power-off'></i></a>
	</div>
</header>