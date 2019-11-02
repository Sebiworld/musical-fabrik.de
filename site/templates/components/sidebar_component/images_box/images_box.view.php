<?php
namespace ProcessWire;

if ($this->images && count($this->images) > 0) {
	?>
	<div class="box images_box">
		<?php
		if ($this->title) {
			?>
			<h4 class="title"><?= $this->title; ?></h4>
			<?php
		}
		?>

		<div id="images-box-slider-<?= $this->sliderIndex; ?>" class="carousel slide" data-ride="carousel">
			<div class="carousel-inner" role="listbox">
				<?php
				foreach ($this->images as $image) {
					?>
					<div class="carousel-item active">
						<img class="d-block img-fluid" src="<?= $image->url; ?>" alt="<?= $image->description; ?>">
					</div>
					<?php
				}
				?>
			</div>
			<a class="carousel-control-prev" href="#images-box-slider-<?= $this->sliderIndex; ?>" role="button" data-slide="prev">
				<span class="carousel-control-prev-icon" aria-hidden="true"></span>
				<span class="sr-only"><?= __('Previous'); ?></span>
			</a>
			<a class="carousel-control-next" href="#images-box-slider-<?= $this->sliderIndex; ?>" role="button" data-slide="next">
				<span class="carousel-control-next-icon" aria-hidden="true"></span>
				<span class="sr-only"><?= __('Next'); ?></span>
			</a>
		</div>
	</div>
	<?php
}
?>