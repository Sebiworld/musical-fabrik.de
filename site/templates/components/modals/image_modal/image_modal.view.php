<?php
namespace ProcessWire;

if ($this->images && count($this->images) > 0) {
	?>
	<div class="modal fade image-modal viele" id="<?= $this->id ?>" tabindex="-1" role="dialog" aria-labelledby="<?= $this->id ?>Label">
		<div class="modal-dialog modal-xlg" role="document">
			<div class="modal-content">
				<div class="modal-body">
					<h4 class="modal-title visually-hidden" id="<?= $this->id ?>Label"><?= __('Gallery details'); ?></h4>
					<div class="image-gallery">
						<div class="swiper-modal-container" data-align="<?= $this->sliderAlign; ?>">
							<div class="swiper-wrapper">
								<?php
								foreach ($this->images as $image) {
									?>
									<div class="swiper-slide">
										<img class="gallery-image carousel-cell-image swiper-lazy" src="<?= $image->height(150)->url; ?>" alt="<?= $image->description; ?>" data-src="<?= $image->height(1000)->url; ?>" alt="<?= $image->description; ?>" data-caption="<?= $image->caption; ?>" data-fullsize="<?= $image->url; ?>"/>
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
						<button type="button" class="btn btn-link btn-close-modal" data-dismiss="modal"><?= __('Close'); ?></button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
} elseif ($this->singleImage) {
	?>
	<div class="modal fade image-modal single-image" id="<?= $this->id ?>" tabindex="-1" role="dialog" aria-labelledby="<?= $this->id ?>Label">
		<div class="modal-dialog modal-xlg" role="document">
			<div class="modal-content">
				<h4 class="modal-title visually-hidden" id="<?= $this->id ?>Label"><?= __('Image details'); ?></h4>
				<div class="modal-body">
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-link btn-close-modal" data-dismiss="modal"><?= __('Close'); ?></button>
				</div>
			</div>
		</div>
	</div>
	<?php
}
?>