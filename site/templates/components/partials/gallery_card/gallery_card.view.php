<?php
namespace ProcessWire;

?>
<a class="card gallery_card" data-id="<?= $this->page->id; ?>" href="<?= $this->page->url; ?>">
<?php
	if ($this->images && count($this->images) > 0) {
		?>
		<div class="swiper-container card-img-top" <?= $this->autoplay ? 'data-autoplay="true"' : ''; ?> <?= $this->loop ? 'data-loop="true"' : ''; ?>>
			<div class="swiper-wrapper">
				<?php
				foreach ($this->images as $image) {
					if (!($image instanceof Pageimage)) {
						continue;
					} ?>
					<div class="swiper-slide">
						<div class="aspect-ratio ar-8-5">
							<img class="ar-content gallery-image swiper-lazy img-fluid" data-src="<?= $image->size(400, 250, [
								'quality'   => 90,
								'upscaling' => true,
								'cropping'  => true,
								'cleanFilename' => true
							])->url; ?>" data-srcset="<?= $image->size(400, 250, [
								'quality'   => 90,
								'upscaling' => true,
								'cropping'  => true,
								'cleanFilename' => true
							])->url; ?> 1x, <?= $image->size(800, 500, [
								'quality'   => 90,
								'upscaling' => true,
								'cropping'  => true,
								'cleanFilename' => true
							])->url; ?> 2x" alt="<?= $image->description; ?>"/>
						</div>
						<div class="swiper-lazy-preloader"></div>
					</div>
					<?php
				} ?>
			</div>
			<!-- Add Pagination -->
			<div class="swiper-pagination"></div>

			<!-- Navigation -->
			<div class="swiper-button-next swiper-button-black"></div>
			<div class="swiper-button-prev swiper-button-black"></div>
		</div>
		<?php
	}
	?>
	<h4 class="card-title" <?= $this->page->color ? 'style="background-color: #'.$this->page->color.'; border-color: #'.$this->page->color.'"' : ''; ?>>
		<?= $this->page->title; ?>
	</h4>
</a>
