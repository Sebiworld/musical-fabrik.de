<?php

namespace ProcessWire;

?>
<section class="container-fluid section section_pages_grid <?= $this->page->highlight ? 'highlight' : ''; ?> <?= !empty($this->page->classes . '') ? $this->page->classes : ''; ?>" <?= $this->sectionId ? 'id="' . $this->sectionId . '"' : ''; ?>>

	<?php
    if (!empty($this->title)) {
        ?>
		<h2 class="section-title <?= $this->page->hide_title ? 'sr-only sr-only-focusable' : ''; ?>">
		<?= $this->title; ?>
	</h2>
	<?php
    }
    ?>

	<?= $this->contents; ?>

	<div class="row pages-row">
		<?php
        if ($pages) {
            foreach ($pages as $listindex => $page) {
                ?>
				<article class="<?= $this->gridClasses; ?> page">
					<div class="card card-dark text-white <?= $this->cardClasses; ?>">
						<div class="card-content-wrapper">
							<div class="card-img aspect-ratio ar-<?= $this->imageRatio; ?>">
                                <?php
                                echo $this->imageService->getImgHtml(array(
                                    'image' => $page->gridImage,
                                    'classes' => array('ar-content', 'main_image'),
                                    'outputType' => 'image',
                                    'styles'     => $page->color ? 'background-color: #' . $page->color . ';' : '',
                                    'loadAsync' => true,
                                    'default' => array(
                                        'width' => 600
                                    ),
                                    'srcset' => array(
                                        '300w' => array(
                                            'width' => 300
                                        ),
                                        '600w' => array(
                                            'width' => 600
                                        ),
                                        '900w' => array(
                                            'width' => 900
                                        ),
                                        '1200w' => array(
                                            'width' => 1200
                                        )
                                    )
                                ));
                                 ?>
							</div>
							<div class="card-img-overlay">
								<div class="top">
									<h4 class="card-title"><?= $page->title; ?></h4>
									<div class="card-text"><?= $page->freetext; ?></div>
								</div>

								<?php
                                if (!$page->template->hasField('no_details_view') || !$page->no_details_view) {
                                    $btnText = __('More...');
                                    if ($page->template->hasField('btn_text') && !empty($page->btn_text)) {
                                        $btnText = $page->btn_text;
                                    } elseif ($page->template->name == 'project') {
                                        $btnText = __('Jump the project');
                                    } ?>
									<a class="card-link btn btn-primary btn-inlinecolor hvr-grow" href="<?= $page->url; ?>" <?= $page->color ? 'style="background-color: #' . $page->color . '; border-color: #' . $page->color . '"' : ''; ?>><?= $btnText ?></a>
									<?php
                                } ?>
							</div>
						</div>
					</div>
				</article>

				<?php
                if (($listindex + 1) % 2 === 0) {
                    echo '<div class="clearfix hidden-sm-down.hidden-sm-up"></div>';
                }
                if (($listindex + 1) % 3 === 0) {
                    echo '<div class="clearfix hidden-md-down.hidden-md-up"></div>';
                }
                if (($listindex + 1) % 4 === 0) {
                    echo '<div class="clearfix hidden-lg-down"></div>';
                }
            }
        }
        ?>
	</div>
</section>