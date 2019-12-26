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
            echo $this->component->getService('ImageService')->getImgHtml(array(
                'image' => $this->page->image,
                'classes' => array('ar-content'),
                'outputType' => 'image',
                'loadAsync' => true,
                'default' => array(
                    'width' => 800
                ),
                'srcset' => array(
                    '320w' => array(
                        'width' => 320
                    ),
                    '640w' => array(
                        'width' => 640
                    ),
                    '720w' => array(
                        'width' => 720
                    ),
                    '800w' => array(
                        'width' => 800
                    ),
                    '960w' => array(
                        'width' => 960
                    ),
                    '1600w' => array(
                        'width' => 1600
                    )
                )
            ));

            if ($this->page->image->caption && !empty($this->page->image->caption . '')) {
                echo '<div class="image-caption">' . $this->page->image->caption . '</div>';
            } 
        ?>
		</div>
	</div>
	<?php
}
?>
