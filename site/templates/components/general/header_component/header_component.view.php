<?php
namespace ProcessWire;

?>

<header>
	<nav class="navbar navbar-dark navbar-expand-md bg-faded fixed-top highlight-navigation">
	<div class="container-fluid">
		<a class="navbar-brand" href="<?= (string) wire('pages')->get(1)->httpUrl; ?>">
			<img class="logo" src="<?= $this->logo->url; ?>" alt="<?= $this->logo->description; ?>"/>
		</a>

		<button class="nav-link navbar-toggler hamburger hamburger--spring" data-toggle="collapse" data-target="#<?= $this->mainMenuId; ?>" aria-controls="<?= $this->mainMenuId; ?>" aria-expanded="false" aria-label="Toggle navigation">
			<span class="hamburger-box">
				<span class="hamburger-inner"></span>
			</span>
		</button>

		<div class="collapse navbar-collapse has-hamburger" id="<?= $this->mainMenuId; ?>">
			<ul class="nav navbar-nav justify-content-end">
				<?php
				if ($this->navigation && count($this->navigation) > 0) {
					foreach ($this->navigation as $navItem) {
						?>
						<li class="nav-item <?= $navItem->active ? 'active' : ''; ?>" data-scrolltarget="<?= $navItem->link; ?>">
							<a class="nav-link" href="<?= $navItem->link; ?>"><?= $navItem->title; ?></a>
						</li>
						<?php
					}
				}

				// secondary menu
				if ($this->secondaryNavigation && count($this->secondaryNavigation) > 0) {
					// Dropdown for desktop menu:
					?>
					<li class="nav-item dropdown d-none d-xs-none d-md-inline-block has-hamburger" id="#<?= $this->dropdownId; ?>">
						<button class="nav-link dropdown-toggle hamburger hamburger--spring" type="button" aria-label="<?= __('Menu'); ?>" aria-controls="<?= $this->dropdownLabelId; ?>" aria-expanded="false" id="<?= $this->dropdownLabelId; ?>" data-bs-toggle="dropdown">
							<span class="hamburger-box">
								<span class="hamburger-inner"></span>
							</span>
						</button>
						<div class="dropdown-menu dropdown-menu-end" aria-labelledby="<?= $this->dropdownLabelId; ?>">
							<?php
							foreach ($this->secondaryNavigation as $navItem) {
								?>
								<a class="dropdown-item <?= $navItem->active ? 'active' : ''; ?>" href="<?= $navItem->link; ?>"><?= $navItem->title; ?></a>
								<?php
							}
							?>
						</div>
					</li>
					<?php
					// Normal menu items in the Mobile View:
					foreach ($this->secondaryNavigation as $navItem) {
						?>
						<li class="d-sm-inline-block d-md-none nav-item <?= $navItem->active ? 'active' : ''; ?>">
							<a class="nav-link" href="<?= $navItem->link; ?>"><?= $navItem->title; ?></a>
						</li>
						<?php
					}
				}
				?>
			</ul>
			<!-- <div class="float-lg-right">
				<button class="btn align-middle btn-sm btn-outline-primary" type="button">Login</button>
			</div> -->
		</div>
		</div>
	</nav>
</header>