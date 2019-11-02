<?php
namespace ProcessWire;

if ($this->images && !empty($this->images)) {
	?>
	<div class="content_gallery content_gallery_swiper <?= !empty($this->page->classes.'') ? $this->page->classes : ''; ?>" <?= $this->page->depth ? 'data-depth="' . $this->page->depth . '"' : ''; ?>>
		<?php
		if (!empty($this->title)) {
			$headingDepth = 2;
			if ($this->page->depth && intval($this->page->depth)) {
				$headingDepth = $headingDepth + intval($this->page->depth);
			}
			?>
			<h<?= $headingDepth; ?> class="block-title <?= $this->page->hide_title ? 'sr-only sr-only-focusable' : ''; ?>">
				<?= $this->title; ?>
			</h<?= $headingDepth; ?>>
			<?php
		}
		?>
		<?= $this->description ? '<div class="block-description">'.$this->description	.'</div>' : ''; ?>

		<div class="swiper-container" data-align="<?= $this->sliderAlign; ?>">
			<div class="swiper-wrapper">
				<?php
				$counter = 0;
				foreach ($this->images as $image) {
					?>
					<div class="swiper-slide">
						<?php
						if ($this->imgModalId && !empty($this->imgModalId)) {
							?>
							<a class="image-modal-link" data-toggle="modal" data-index="<?= $counter; ?>" data-target="#<?= $this->imgModalId; ?>">
								<img class="gallery-image swiper-lazy" src="<?= $image->height(200)->url; ?>" data-src="<?= $image->height(400)->url; ?>" alt="<?= $image->description; ?>"/>
								<div class="swiper-lazy-preloader"></div>
							</a>
							<?php
						} else {
							?>
							<img class="gallery-image swiper-lazy" src="<?= $image->height(200)->url; ?>" data-src="<?= $image->height(400)->url; ?>" alt="<?= $image->description; ?>"/>
							<div class="swiper-lazy-preloader"></div>
							<?php
						}
						?>
					</div>
					<?php
					$counter++;
				}
				?>
			</div>

			<!-- Add Pagination -->
			<div class="swiper-pagination"></div>

			<!-- Navigation -->
			<div class="swiper-button-next swiper-button-white"></div>
			<div class="swiper-button-prev swiper-button-white"></div>
		</div>
	</div>
	<?php
}
