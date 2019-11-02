<?php

namespace ProcessWire;

if ($this->images && !empty($this->images)) {
    ?>
	<div class="content_gallery content_gallery_slider <?= !empty($this->page->classes . '') ? $this->page->classes : ''; ?>" <?= $this->page->depth ? 'data-depth="' . $this->page->depth . '"' : ''; ?>>
		<?php
        if (!empty($this->title)) {
            $headingDepth = 2;
            if ($this->page->depth && intval($this->page->depth)) {
                $headingDepth = $headingDepth + intval($this->page->depth);
            } ?>
			<h<?= $headingDepth; ?> class="block-title <?= $this->page->hide_title ? 'sr-only sr-only-focusable' : ''; ?>">
				<?= $this->title; ?>
			</h<?= $headingDepth; ?>>
			<?php
        } ?>
		<?= $this->description ? '<div class="block-description">' . $this->description . '</div>' : ''; ?>

		<ul class="lightslider">
			<?php
            foreach ($this->images as $image) {
                ?>
				<li class="lightslider-item" data-thumb="<?= $image->height(300)->url; ?>" data-src="<?= $image->url; ?>" data-responsive="<?= $image->height(300)->url; ?> 400w, <?= $image->height(1000)->url; ?> 1000w">
					<img class="gallery-image <?= $image->classes ? $image->classes : ''; ?>" src="<?= $image->height(300)->url; ?>" alt="<?= $image->description; ?>"/>
				</li>
				<?php
            } ?>
		</ul>
	</div>
	<?php
}
