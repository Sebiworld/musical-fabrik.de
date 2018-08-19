<?php
namespace ProcessWire;

if ($this->childComponents && count($this->childComponents) > 0) {
	?>
	<div class="aktuelles-carousel">
		<div class="swiper-container" data-align="<?= $this->sliderAlign; ?>">
			<div class="swiper-wrapper">
				<?php
				foreach ($this->childComponents as $beitrag) {
					?>
					<div class="swiper-slide">
						<?= $beitrag; ?>
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
		<div>
			<br/>
			<a href="<?= $this->aktuellesSeite->httpUrl; ?>" class="btn btn-projekt-primary">Alle aktuellen Meldungen</a>
		</div>
	</div>
	<?php
}
?>
