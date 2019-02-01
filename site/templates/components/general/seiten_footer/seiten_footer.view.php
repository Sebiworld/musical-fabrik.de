<?php
namespace ProcessWire;

?>

<footer>
	<div class="container-fluid text-right">
		<?= $this->breadcrumbs; ?>
	</div>
	<div class="container-fluid hervorgehoben light oben">
		<div class="row">
			<div class="col-12">
				<?= $this->inhalte; ?>
			</div>
		</div>
	</div>
	<div class="container-fluid hervorgehoben primary mitte-oben">

	</div>
	<div class="container-fluid hervorgehoben dark mitte">
		<div class="row">
			<?php
			if ($this->menue && count($this->menue) > 0) {
				?>
				<div class="col-xs-12 col-sm-4">
					<h4 class="titel">Menü:</h4>
					<ul class="nav hauptmenue-footer flex-column">
						<?php
						foreach ($this->menue as $navItem) {
							?>
							<li class="nav-item">
								<a class="nav-link <?= ($this->page->id == $navItem->id) ? 'active' : ''; ?>" href="<?= $navItem->link; ?>">
									<?= $navItem->title; ?>
								</a>
							</li>
							<?php
						}
						?>
					</ul>
				</div>
				<?php
			}

			if ($this->adresse) {
				?>
				<div class="col-xs-12 col-sm-4">
					<div class="block">
						<?= $this->adresse; ?>
					</div>
				</div>
				<?php
			}

			if ($this->schlagwoerter) {
				?>
				<div class="col-xs-12 col-sm-4">
					<h4>Schlagwörter:</h4>
					<?= $this->schlagwoerter; ?>
				</div>
				<?php
			}
			?>
		</div>

		<div class="row mit-abgrenzung">
			<?php
			if ($this->suchseite) {
				?>
				<div class="col-12 col-md-6 footer-suche">
					<form action="<?= $this->suchseite->url; ?>" method="GET">
						<div class="input-group">
							<input type="text" class="form-control" placeholder="Suche nach..." aria-label="Suche nach..." name="suchbegriff">
							<span class="input-group-btn">
								<input class="btn btn-projekt-primary" type="submit" value="Suchen!">
							</span>
						</div>
					</form>
				</div>
				<?php
			}

			if ($this->socialmedia_links) {
				?>
				<div class="col-12 col-md-6 socialmedia-links">
					<h4 class="titel">Social-Media:</h4>
					<?php
					foreach ($this->socialmedia_links as $mediaitem) {
						?>
						<a href="<?= $mediaitem->link; ?>" target="_blank" title="<?= $mediaitem->title; ?>" class="social-button <?= $mediaitem->klassen; ?>" rel="noopener">
							<?php
							if ($mediaitem->type === 'ionicon') {
								?>
								<i class="icon ion-<?= $mediaitem->ionicon; ?>"></i>
								<?php
							} elseif ($mediaitem->type === 'icon') {
								?>
								<img class="icon" src="<?= $mediaitem->bild->size(60, 60)->url; ?>" alt="<?= $mediaitem->title; ?>"/>
								<?php
							} elseif ($mediaitem->type === 'label') {
								?>
								<span class="label"><?= $mediaitem->kurztext; ?></span>
								<?php
							}
							?>
						</a>
						<?php
					}
					?>
				</div>
				<?php
			}
			?>
		</div>
	</div>

	<div class="container-fluid unten">
		<div class="row">
			<div class="col-12 col-sm-7">
				<ul class="nav tertiaere-navigation">
					<?php
					if ($this->tertiaere_navigation) {
						foreach ($this->tertiaere_navigation as $navItem) {
							?>
							<li class="nav-item">
								<a class="nav-link <?= ($this->page->id == $navItem->id) ? 'active' : ''; ?>" href="<?= $navItem->link; ?>">
								<?= $navItem->title; ?>
								</a>
							</li>
						<?php
						}
					}
					?>
				</ul>
			</div>
			<div class="col-12 col-sm-5">
				<span class="copyright">
					<?= $this->copyright; ?>
				</span>
			</div>
		</div>
	</div>
</footer>