<?php
namespace ProcessWire;

if ($this->images && !empty($this->images)) {
	?>
	<div class="content_gallery content_gallery_masonry <?= !empty($this->page->classes.'') ? $this->page->classes : ''; ?>" <?= $this->page->depth ? 'data-depth="' . $this->page->depth . '"' : ''; ?>>
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

		<div class="masonry-grid lightgallery">
			<div class="masonry-grid-sizer"></div>
			<?php
			$counter = 0;
			foreach ($this->images as $listenIndex => $image) {
				?>
				<div class="masonry-grid-item">
					<a class="lightgallery-item" href="<?= $image->url; ?>">
						<?php
						echo $this->component->getService('ImageService')->getImgHtml(
							array(
								'image' => $image,
								'outputType' => 'image',
								'loadAsync' => true,
								'classes' => 'bild',
								'normal' => array(
									'width' => 800
								),
								'sm' => array(
									'width' => 500
								)
							)
						);
						?>
					</a>
					<?php
					if ($image->caption && !empty($image->caption.'')) {
						echo '<div class="image-caption">'.$image->caption.'</div>';
					}
					?>
				</div>
				<?php
				$counter++;
			}
			?>
		</div>
	</div>
	<?php
}
