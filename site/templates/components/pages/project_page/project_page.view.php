<?php

namespace ProcessWire;

if ($this->isProjectPage) {
    if ($this->main_image_html) {
        ?>
		<div class="container-fluid project-title main_image" <?= $this->projectPage->color ? 'style="background-color: #' . $this->projectPage->color . '"' : ''; ?>>
			<a href="<?= $this->projectPage->url; ?>">
				<div class="aspect-ratio ar-3-1">
					<?= $this->main_image_html; ?>
				</div>

				<?php
                if ($this->main_image->caption && !empty($this->main_image->caption . '')) {
                    echo '<div class="image-caption-top">' . $this->main_image->caption . '</div>';
                } ?>
			</a>
			<?php
            if ($this->infoOverlay) {
                ?>
				<div class="info-overlay">
					<?= $this->infoOverlay; ?>
				</div>
				<?php
            } ?>
		</div>
		<?php
    } else {
        // No cover picture available ?>
		<div class="container-fluid project-title">
			<a class="project-title-link" href="<?= $this->projectPage->url; ?>">
				<div class="title-text">
					<h1 class="display-1">
						<?= $this->projectPage->title; ?>
					</h1>
					<?= $this->projectPage->short_description ? '<p class="lead">' . $this->projectPage->short_description . '</p>' : ''?>
				</div>
			</a>
			<?php
            if ($this->infoOverlay) {
                ?>
				<br/><br/>
				<div class="info-overlay">
					<?= $this->infoOverlay; ?>
				</div>
				<?php
            } ?>
		</div>
		<?php
    }
}
?>