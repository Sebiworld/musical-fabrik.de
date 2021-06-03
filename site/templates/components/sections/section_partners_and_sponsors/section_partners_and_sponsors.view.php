<?php

namespace ProcessWire;

?>
<section class="container-fluid section section_partners_and_sponsors <?= !empty($this->page->classes . '') ? $this->page->classes : ''; ?>" <?= $this->sectionId ? 'id="' . $this->sectionId . '"' : ''; ?>>

	<?php
    if (!empty($this->title)) {
        ?>
		<h2 class="section-title <?= $this->page->hide_title ? 'visually-hidden visually-hidden-focusable' : ''; ?>">
			<?= $this->title; ?>
		</h2>
		<?php
    }
    ?>

	<?php
    if ($this->partners) {
        ?>
		<div class="container-fluid highlight tertiary partners">
			<h3 class="subtitle"><?= __('Our partners:'); ?></h3>
			<div class="row list">
				<?php
                foreach ($this->partners as $listIndex => $partner) {
                    ?>
					<div class="col-12 col-sm-6 col-md-4 col-lg-3 tile">
						<div class="aspect-ratio ar-1-1">
							<div class="ar-content">
								<div class="content-wrapper">
									<?php
                                    if ($partner->main_image) {
										echo $this->component->getService('ImageService')->getPictureHtml(array(
											'image' => $partner->main_image,
											'alt' => $partner->title,
											'loadAsync' => true,
											'default' => array(
												'width' => 800
											)
										));
                                    } else {
                                        ?>
										<span class="title"><?= $partner->title; ?></span>
										<?php
                                    } ?>
								</div>
							</div>
						</div>
					</div>
					<?php
                }
								?>
			</div>
		</div>
		<?php
    }
    ?>

	<?php
    if ($this->sponsors) {
        ?>
		<div class="container-fluid sponsors">
			<h3 class="subtitle"><?= __('Our sponsors'); ?></h3>
			<div class="row list">
				<?php
                foreach ($this->sponsors as $listIndex => $sponsor) {
                    ?>
					<div class="col-6 col-sm-4 col-md-3 col-lg-2 col-xl-2 tile">
						<div class="aspect-ratio ar-1-1">
							<div class="ar-content">
								<div class="content-wrapper">
									<?php
                                    if ($sponsor->main_image) {
                                        echo $this->component->getService('ImageService')->getPictureHtml(array(
											'image' => $sponsor->main_image,
											'alt' => $sponsor->title,
											'loadAsync' => true,
											'default' => array(
												'width' => 500
											)
										));
                                    } else {
                                        ?>
										<span class="title"><?= $sponsor->title; ?></span>
										<?php
                                    } ?>
								</div>
							</div>
						</div>
					</div>
					<?php
                } ?>
			</div>
		</div>
		<?php
    }
    ?>

	<div class="container-fluid highlight tertiary text-block thank-you capital-letter">
		<?= $this->contents; ?>
	</div>
</section>