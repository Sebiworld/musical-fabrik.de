<?php

namespace ProcessWire;

if ($this->page->image) {
    ?>
	<div class="content_image single_image" <?= $this->page->depth ? 'data-depth="' . $this->page->depth . '"' : ''; ?>>
		<?php
        if (!empty($this->page->title)) {
            $headingDepth = 2;
            if ($this->page->depth && intval($this->page->depth)) {
                $headingDepth = $headingDepth + intval($this->page->depth);
            } ?>
			<h<?= $headingDepth; ?> class="block-title <?= $this->page->hide_title ? 'sr-only sr-only-focusable' : ''; ?>">
				<?= $this->page->title; ?>
			</h<?= $headingDepth; ?>>
			<?php
        } ?>

		<div class="<?= !empty($this->page->classes . '') ? $this->page->classes : ''; ?>">
			<?php
            if ($this->page->image->ext == 'svg') {
                echo $this->component->getService('ImageService')->getImgHtml(array(
                    'image'                 => $this->page->image,
                    'outputType'            => 'image',
                    'loadAsync'             => false,
                    'normal'                => 'original'
                ));
            } else {
                echo $this->component->getService('ImageService')->getImgHtml(array(
                    'image'       => $this->page->image,
                    'outputType'  => 'image',
                    'normal'      => array(
                        'width' => 1400
                    ),
                    'sm' => array(
                        'width' => 600
                    ),
                    'fullsize-modal' => array(
                        'width' => 1400
                    )
                ));
            }

		if ($this->page->image->caption && !empty($this->page->image->caption . '')) {
			echo '<div class="image-caption">' . $this->page->image->caption . '</div>';
		} ?>
		</div>
	</div>
	<?php
}
?>
