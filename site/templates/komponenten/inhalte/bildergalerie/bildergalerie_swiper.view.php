<?php
namespace ProcessWire;

if ($this->bilder && !empty($this->bilder)) {
	?>
	<div class="bildergalerie bilder-swiper <?= !empty($this->page->klassen.'') ? $this->page->klassen : ''; ?>" <?= $this->page->depth ? 'data-tiefe="' . $this->page->depth . '"' : ''; ?>>
		<?php
		if (!empty($this->titel)) {
			$headingDepth = 2;
			if ($this->page->depth && intval($this->page->depth)) {
				$headingDepth = $headingDepth + intval($this->page->depth);
			}
			?>
			<h<?= $headingDepth; ?> class="block-titel <?= $this->page->titel_verstecken ? 'sr-only sr-only-focusable' : ''; ?>">
				<?= $this->titel; ?>
			</h<?= $headingDepth; ?>>
			<?php
		}
		?>
		<?= $this->beschreibung ? '<div>'.$this->beschreibung	.'</div>' : ''; ?>

		<div class="swiper-container" data-align="<?= $this->sliderAlign; ?>">
			<div class="swiper-wrapper">
				<?php
				$counter = 0;
				foreach ($this->bilder as $bild) {
					?>
					<div class="swiper-slide">
						<?php
						if ($this->bilderModalID && !empty($this->bilderModalID)) {
							?>
							<a class="bilder-modal-link" data-toggle="modal" data-index="<?= $counter; ?>" data-target="#<?= $this->bilderModalID; ?>">
								<img class="bildergalerie-bild swiper-lazy" src="<?= $bild->height(200)->url; ?>" data-src="<?= $bild->height(400)->url; ?>" alt="<?= $bild->description; ?>"/>
								<div class="swiper-lazy-preloader"></div>
							</a>
							<?php
						} else {
							?>
							<img class="bildergalerie-bild swiper-lazy" src="<?= $bild->height(200)->url; ?>" data-src="<?= $bild->height(400)->url; ?>" alt="<?= $bild->description; ?>"/>
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
