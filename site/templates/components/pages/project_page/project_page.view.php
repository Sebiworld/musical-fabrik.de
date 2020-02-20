<?php

namespace ProcessWire;

?>
<div class="project_page">
	<div class="project-headbar">
		<?php
        if ($this->main_image_html) {
            ?>
			<a class="main_image" href="<?= $this->projectPage->url; ?>" <?= $this->projectPage->color ? 'style="background-color: #' . $this->projectPage->color . '"' : ''; ?>>
				<div class="aspect-ratio ar-3-1">
					<?= $this->main_image_html; ?>
				</div>

				<?php
                if ($this->main_image->caption && !empty($this->main_image->caption . '')) {
                    echo '<div class="image-caption-top">' . $this->main_image->caption . '</div>';
				} 
				if ($this->infoOverlay) {
					?>
					<div class="info-overlay">
						<?= $this->infoOverlay; ?>
					</div>
					<?php
				}
				?>
			</a>
			<?php
        } else {
            // No cover picture available?>
			<a class="project-title-link" href="<?= $this->projectPage->url; ?>" <?= $this->projectPage->color ? 'style="background-color: #' . $this->projectPage->color . '"' : ''; ?>>
				<div class="title-text">
					<h1 class="display-1">
						<?= $this->projectPage->title; ?>
					</h1>
					<?= $this->projectPage->short_description ? '<p class="lead">' . $this->projectPage->short_description . '</p>' : ''?>
				</div>
				<?php
				if ($this->infoOverlay) {
					?>
					<div class="info-overlay">
						<?= $this->infoOverlay; ?>
					</div>
					<?php
				}
				?>
			</a>
			<?php
        }
		?>
		<div class="project-title">
			<?= $this->projectPage->title; ?>
			<?= $this->projectPage->short_description ? '<small>' . $this->projectPage->short_description . '</small>' : ''; ?>
		</div>
	
	</div>

	<div class="layout-wrapper">
		<div class="contents-wrapper <?= $this->component->getGlobalComponent('sidebar')->hasComponents(true) ? 'has-sidebar' : 'no-sidebar' ?>">
			<?php
			if ($this->childComponents) {
				foreach ($this->childComponents as $component) {
					echo $component;
				}
			}
			?>
		</div>

		<?php
		if ($this->component->getGlobalComponent('sidebar')->hasComponents(true)) {
			?>
			<div class="sidebar-wrapper">
				<?= $this->component->getGlobalComponent('sidebar'); ?>
			</div>
			<?php
		}
		?>
	</div>
</div>