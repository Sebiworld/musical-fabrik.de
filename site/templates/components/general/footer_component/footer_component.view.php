<?php
namespace ProcessWire;

?>

<footer>
	<div class="container-fluid highlight light top">
		<?= $this->breadcrumbs; ?>
		<div class="row">
			<div class="col col-12">
				<?= $this->contents; ?>
			</div>
		</div>
	</div>

	<div class="container-fluid highlight dark mid">
		<div class="row">
			<?php
			if ($this->menu && count($this->menu) > 0) {
				?>
				<div class="col col-12 col-sm-4">
					<h4 class="title"><?= __('Menu:'); ?></h4>
					<ul class="nav footer-nav-primary flex-column">
						<?php
						foreach ($this->menu as $navItem) {
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

			if ($this->address) {
				?>
				<div class="col col-12 col-sm-4">
					<div class="block">
						<?= $this->address; ?>
					</div>
				</div>
				<?php
			}

			if ($this->tags) {
				?>
				<div class="col col-12 col-sm-4">
					<h4><?= __('Tags:'); ?></h4>
					<?= $this->tags; ?>
				</div>
				<?php
			}
			?>
		</div>

		<div class="row">
			<?php
			if ($this->searchPage) {
				?>
				<div class="col col-12 col-md-6 footer-search">
					<form action="<?= $this->searchPage->url; ?>" method="GET">
						<div class="input-group">
							<input type="text" class="form-control" placeholder="<?= __('Search for...'); ?>" aria-label="<?= __('Search for...'); ?>" name="q">
							<span class="input-group-btn">
								<input class="btn btn-primary" type="submit" value="<?= __('Search!'); ?>">
							</span>
						</div>
					</form>
				</div>
				<?php
			}

			if ($this->socialmedia_links) {
				?>
				<div class="col col-12 col-md-6 socialmedia-links">
					<h4 class="title">Social-Media:</h4>
					<?php
					foreach ($this->socialmedia_links as $mediaitem) {
						?>
						<a href="<?= $mediaitem->link; ?>" target="_blank" title="<?= $mediaitem->title; ?>" class="social-button <?= $mediaitem->classes; ?>" rel="noopener">
							<?php
							if ($mediaitem->type === 'ionicon') {
								?>
								<i class="icon ion-<?= $mediaitem->ionicon; ?>"></i>
								<?php
							} elseif ($mediaitem->type === 'icon') {
								?>
								<img class="icon" src="<?= $mediaitem->image->size(60, 60)->url; ?>" alt="<?= $mediaitem->title; ?>"/>
								<?php
							} elseif ($mediaitem->type === 'label') {
								?>
								<span class="label"><?= $mediaitem->short_text; ?></span>
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

	<div class="container-fluid bottom">
		<div class="row">
			<div class="col col-12 col-sm-7">
				<ul class="nav footer-nav-tertiary">
					<?php
					if ($this->tertiary_navigation) {
						foreach ($this->tertiary_navigation as $navItem) {
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
			<div class="col col-12 col-sm-5">
				<span class="copyright">
					<?= $this->copyright; ?>
				</span>
			</div>
		</div>
	</div>
</footer>