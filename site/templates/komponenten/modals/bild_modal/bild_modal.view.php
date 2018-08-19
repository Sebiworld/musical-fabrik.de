<?php
namespace ProcessWire;

if ($this->bilder && count($this->bilder) > 0) {
	?>
	<div class="modal fade bild-modal viele" id="<?= $this->id ?>" tabindex="-1" role="dialog" aria-labelledby="<?= $this->id ?>Label">
		<div class="modal-dialog modal-xlg" role="document">
			<div class="modal-content">
				<div class="modal-body">
					<h4 class="modal-title sr-only" id="<?= $this->id ?>Label">Bildergalerie-Detailansicht</h4>
					<div class="bildergalerie">
						<div class="swiper-modal-container" data-align="<?= $this->sliderAlign; ?>">
							<div class="swiper-wrapper">
								<?php
								foreach ($this->bilder as $bild) {
									?>
									<div class="swiper-slide">
										<img class="bildergalerie-bild carousel-cell-image swiper-lazy" src="<?= $bild->height(150)->url; ?>" alt="<?= $bild->description; ?>" data-src="<?= $bild->height(1000)->url; ?>" alt="<?= $bild->description; ?>" data-caption="<?= $bild->caption; ?>" data-vollbild="<?= $bild->url; ?>"/>
										<div class="swiper-lazy-preloader"></div>
									</div>
									<?php
								}
								?>
							</div>

							<!-- Add Pagination -->
							<div class="swiper-pagination"></div>

							<!-- Navigation -->
							<div class="swiper-button-next swiper-button-black"></div>
							<div class="swiper-button-prev swiper-button-black"></div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<div class="caption">
					</div>
					<div class="buttons">
						<button type="button" class="btn btn-link btn-close-modal" data-dismiss="modal">Schließen</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
} elseif ($this->einzelbild) {
	?>
	<div class="modal fade bild-modal einzeln" id="<?= $this->id ?>" tabindex="-1" role="dialog" aria-labelledby="<?= $this->id ?>Label">
		<div class="modal-dialog modal-xlg" role="document">
			<div class="modal-content">
				<h4 class="modal-title sr-only" id="<?= $this->id ?>Label">Bild-Detailansicht</h4>
				<div class="modal-body">
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-link btn-close-modal" data-dismiss="modal">Schließen</button>
				</div>
			</div>
		</div>
	</div>
	<?php
}
?>