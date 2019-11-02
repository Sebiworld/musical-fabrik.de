<?php
namespace ProcessWire;

if ($this->mainImage) {
	?>
	<section class="container-fluid section section_hero_image <?= $this->page->highlight ? 'highlight' : ''; ?> <?= !empty($this->page->classes.'') ? $this->page->classes : ''; ?>" <?= $this->sectionId ? 'id="'.$this->sectionId.'"' : ''; ?>>

		<h2 class="section-title sr-only sr-only-focusable"><?= $this->page->title; ?></h2>

		<?php
		if (!empty($this->backgroundImage)) {
			?>
			<div class="background-wrapper">
				<div class="progressive__bg progressive--not-loaded background parallax-background" data-progressive="<?= $this->backgroundImage->width(1500)->url; ?>" data-progressive-sm="<?= $this->backgroundImage->height(600)->url; ?>" style="background-image: url('<?= $this->backgroundImage->width(100)->url; ?>');" data-parallax-speed="-3">
				</div>
			</div>
			<?php
		}
		?>

		<div class="hero-image-wrapper">
			<div class="hero-image aspect-ratio ar-3-1 parallax-element" data-parallax-speed="-1">
				<div class="ar-content">
					<?php
					if ($this->mainImage->ext == 'svg') {
						echo $this->imageService->getBildTag(array(
							'image' => $this->mainImage,
							'outputType' => 'image',
							'classes' => 'main_image',
							'loadAsync' => false,
							'normal' => 'original'
						));
					} else {
						?>
						<figure class="progressive">
							<?php
							echo $this->imageService->getBildTag(
								array(
									'image' => $this->mainImage,
									'outputType' => 'image',
									'classes' => 'main_image',
									'normal' => array(
										'width' => 1400
									),
									'sm' => array(
										'width' => 600
									),
									'xs' => array(
										'width' => 300
									)
								)
							);
							?>
						</figure>
						<?php
					}
					?>
				</div>
			</div>

			<div class="description-wrapper parallax-element" data-parallax-speed="0">
				<div class="description">
					<?= $this->contents; ?>
				</div>
			</div>
		</div>
	</section>

	<?php
}
?>