<?php
namespace ProcessWire;

?>

<header>
	<nav class="navbar navbar-dark navbar-expand-md bg-faded fixed-top highlight-navigation">
		<a class="navbar-brand" href="<?= (string) wire('pages')->get(1)->httpUrl; ?>">
			<img class="logo" src="<?= $this->logo->url; ?>" alt="<?= $this->logo->description; ?>"/>
		</a>

		<button class="nav-link navbar-toggler hamburger hamburger--spring" data-toggle="collapse" data-target="#<?= $this->hauptmenueID; ?>" aria-controls="<?= $this->hauptmenueID; ?>" aria-expanded="false" aria-label="Toggle navigation">
			<span class="hamburger-box">
				<span class="hamburger-inner"></span>
			</span>
		</button>

		<div class="collapse navbar-collapse has-hamburger" id="<?= $this->hauptmenueID; ?>">
			<ul class="nav navbar-nav justify-content-end">
				<?php
				if ($this->navigation && count($this->navigation) > 0) {
					foreach ($this->navigation as $navigationsitem) {
						?>
						<li class="nav-item <?= $navigationsitem->aktiv ? 'active' : ''; ?>">
							<a class="nav-link" href="<?= $navigationsitem->link; ?>"><?= $navigationsitem->title; ?></a>
						</li>
						<?php
					}
				}

				// Sekundäres-Menü
				if ($this->sekundaere_navigation && count($this->sekundaere_navigation) > 0) {
					// Dropdown für Desktop-Menü:
					?>
					<li class="nav-item dropdown d-none d-xs-none d-md-inline-block has-hamburger" id="#<?= $this->dropdownID; ?>">
						<button class="nav-link dropdown-toggle hamburger hamburger--spring" type="button" aria-label="Menü" aria-controls="<?= $this->dropdownLabelID; ?>" aria-expanded="false" id="<?= $this->dropdownLabelID; ?>" data-toggle="dropdown" >
							<span class="hamburger-box">
								<span class="hamburger-inner"></span>
							</span>
						</button>
						<div class="dropdown-menu" aria-labelledby="<?= $this->dropdownLabelID; ?>">
							<?php
							foreach ($this->sekundaere_navigation as $navigationsitem) {
								?>
								<a class="dropdown-item <?= $navigationsitem->aktiv ? 'active' : ''; ?>" href="<?= $navigationsitem->link; ?>"><?= $navigationsitem->title; ?></a>
								<?php
							}
							?>
						</div>
					</li>
					<?php
					// Normale Menüpunkte in der Mobile-Ansicht:
					foreach ($this->sekundaere_navigation as $navigationsitem) {
						?>
						<li class="d-sm-inline-block d-md-none nav-item <?= $navigationsitem->aktiv ? 'active' : ''; ?>">
							<a class="nav-link" href="<?= $navigationsitem->link; ?>"><?= $navigationsitem->title; ?></a>
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
	</nav>
</header>