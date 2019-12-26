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
				<div class="masonry-grid-item <?= ($image->width / $image->height) > 2  ? 'double-width' : ''; ?>">
					<a class="lightgallery-item" href="<?= $image->url; ?>">
						<?php
						echo $this->component->getService('ImageService')->getImgHtml(array(
							'image' => $image,
							'classes' => array('ar-content'),
							'outputType' => 'image',
							'loadAsync' => true,
							'default' => array(
								'width' => 640
							),
							'srcset' => array(
								'320w' => array(
									'width' => 320
								),
								'640w' => array(
									'width' => 640
								),
								'960w' => array(
									'width' => 960
								),
								'1280w' => array(
									'width' => 1280
								),
								'1920w' => array(
									'width' => 1920
								)
							)
						));
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
