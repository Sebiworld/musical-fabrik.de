<?php
namespace ProcessWire;

?>
<div class="card event_card" data-id="<?= $this->page->id; ?>">
	<?php
	if ($this->page->main_image) {
		?>
		<div class="aspect-ratio card-img-top ar-2-1">
			<?php
			echo $this->component->getService('ImageService')->getImgHtml(array(
				'image' => $this->page->main_image,
				'outputType' => 'bg-image',
				'classes' => 'ar-content event-image',
				'normal' => array(
					'height' => 300
				)
			));
			?>
		</div>
		<?php
	} else {
		?>
		<div class="aspect-ratio card-img-top ar-2-1">
			<?php
			echo $this->component->getService('ImageService')->getImgHtml(array(
				'outputType' => 'bg-image',
				'classes' => 'ar-content event-image',
				'normal' => array(
					'height' => 300
				)
			));
			?>
		</div>
		<?php
	}
	?>
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
