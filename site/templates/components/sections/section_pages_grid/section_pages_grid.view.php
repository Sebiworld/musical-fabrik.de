<?php

namespace ProcessWire;

?>
<section
	class="container-fluid section section_pages_grid <?= $this->page->highlight ? 'highlight' : ''; ?> <?= !empty($this->page->classes . '') ? $this->page->classes : ''; ?>"
	<?= $this->sectionId ? 'id="' . $this->sectionId . '"' : ''; ?>>

	<?php
    if (!empty($this->title)) {
        ?>
	<h2
		class="section-title <?= $this->page->hide_title ? 'visually-hidden visually-hidden-focusable' : ''; ?>">
		<?= $this->title; ?>
	</h2>
	<?php
    }
  ?>

	<?= $this->contents; ?>

	<div class="row pages-row">
		<?php
    if ($this->pages) {
      foreach ($this->pages as $listindex => $page) {
        ?>
		<article class="col <?= $this->gridClasses; ?> page">
			<figure class="io42-img-overlay io42-border-bottom-left io42-gradient-bottom-right w-100" tabindex="0">
				<?php
					echo $this->imageService->getPictureHtml([
							'image' => $page->gridImage,
							'alt' => $page->title,
							'pictureclasses' => ['aspect-ratio', 'ar-' . $this->imageRatio],
							'classes' => ['ar-content'],
							'styles' => $page->color ? 'background-color: #' . $page->color . ';' : '',
							'loadAsync' => true,
							'default' => [
									'width' => 500,
									'height' => 500 * $this->imageFactor
							]
					]);
				?>
				<figcaption class="">
					<div class="io42-fade-up io42-delay-100 d-block mb-1">
						<h4 class="mb-2 card-title"><?= $page->title; ?>
						</h4>
						<?= !empty($page->desctext) ? '<div class="mb-2 small d-block">' . $page->desctext . '</div>' : ''; ?>
					</div>
					<div class="io42-delay-200">
						<?php
							if (!$page->template->hasField('no_details_view') || !$page->no_details_view) {
								$btnText = __('More...');
								if ($page->template->hasField('btn_text') && !empty($page->btn_text)) {
										$btnText = $page->btn_text;
								} elseif ($page->template->name == 'project') {
										$btnText = __('Jump the project');
								}
							?>
						<a class="btn btn-primary btn-inlinecolor hvr-grow"
							href="<?= $page->url; ?>" <?= $page->color ? 'style="background-color: #' . $page->color . '; border-color: #' . $page->color . '; color: ' . $this->projectService->getTextColorOver($page->color) . '"' : ''; ?>><?= $btnText ?></a>
						<?php
            }
						?>
					</div>
				</figcaption>
			</figure>
		</article>
		<?php
		}
	}
	?>
	</div>
</section>