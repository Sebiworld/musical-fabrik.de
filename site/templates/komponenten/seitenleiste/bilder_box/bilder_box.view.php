<?php
namespace ProcessWire;

if ($this->bilder && count($this->bilder) > 0) {
	?>
	<div class="box bilder-box">
		<?php
		if ($this->titel) {
			?>
			<h4 class="titel"><?= $this->titel; ?></h4>
			<?php
		}
		?>

		<div id="bilder-box-slider-<?= $this->sliderIndex; ?>" class="carousel slide" data-ride="carousel">
			<div class="carousel-inner" role="listbox">
				<?php
				foreach ($this->bilder as $bild) {
					?>
					<div class="carousel-item active">
						<img class="d-block img-fluid" src="<?= $bild->url; ?>" alt="<?= $bild->description; ?>">
					</div>
					<?php
				}
				?>
			</div>
			<a class="carousel-control-prev" href="#bilder-box-slider-<?= $this->sliderIndex; ?>" role="button" data-slide="prev">
				<span class="carousel-control-prev-icon" aria-hidden="true"></span>
				<span class="sr-only">Zurück</span>
			</a>
			<a class="carousel-control-next" href="#bilder-box-slider-<?= $this->sliderIndex; ?>" role="button" data-slide="next">
				<span class="carousel-control-next-icon" aria-hidden="true"></span>
				<span class="sr-only">Weiter</span>
			</a>
		</div>
	</div>
	<?php
}
?>