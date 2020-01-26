<?php
namespace ProcessWire;

?>
<div class="card event_card" data-id="<?= $this->page->id; ?>">
<div class="aspect-ratio card-img-top ar-2-1">
		<?php
		if ($this->page->main_image) {
			echo $this->component->getService('ImageService')->getPictureHtml(array(
                'image' => $this->page->main_image,
                'classes' => array('event-image'),
                'pictureclasses' => array('ar-content'),
                'loadAsync' => true,
                'default' => array(
                    'width' => 320,
					'height' => 160
                )
            ));
		} else {
			echo $this->component->getService('ImageService')->getPlaceholderPictureHtml(array(
                'classes' => array('event-image'),
                'pictureclasses' => array('ar-content'),
                'loadAsync' => true,
                'default' => array(
                    'width' => 320,
					'height' => 160
                )
			));
		}
		?>
	</div>
	<div class="card-block">
		<div class="card-meta" <?= $this->page->color ? 'style="background-color: #'.$this->page->color.'; border-color: #'.$this->page->color.'"' : ''; ?>>
			vom <?= date('d.m.Y', $this->page->getUnformatted('datetime_from')); ?>
		</div>

		<h4 class="card-title"><?= $this->page->title; ?></h4>
		<p class="card-text">
			<?= $this->page->intro; ?>
		</p>

		<a href="<?= $this->page->url; ?>" class="btn btn-light btn-inlinecolor hvr-grow"><?= __('More...'); ?></a>
	</div>
</div>
