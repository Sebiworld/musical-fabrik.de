<?php
namespace ProcessWire;

?>

<div class="aspect-ratio card-img-top ar-2-1">
	<?php
	if ($this->page->main_image) {
		echo $this->component->getService('ImageService')->getPictureHtml([
			'image' => $this->page->main_image,
			'alt' => sprintf(__('Main-image of %1$s'), $this->page->title),
			'classes' => ['article-image'],
			'pictureclasses' => ['ar-content'],
			'loadAsync' => true,
			'default' => [
				'width' => 320,
				'height' => 160
			],
		]);
	} else {
		echo $this->component->getService('ImageService')->getPlaceholderPictureHtml([
			'alt' => sprintf(__('Main-image of %1$s'), $this->page->title),
			'classes' => ['article-image'],
			'pictureclasses' => ['ar-content'],
			'loadAsync' => true,
			'default' => [
				'width' => 320,
				'height' => 160
			]
		]);
	}
?>
</div>

<div class="card-block">
	<div class="card-meta" <?= $this->page->color ? 'style="background-color: #' . $this->page->color . '; border-color: #' . $this->page->color . '"' : ''; ?>>
		vom <?= date('d.m.Y', $this->date); ?>
	</div>

	<?php if ($this->page->external_type === 'Facebook') {
		?>
	<h4 class="card-title subtle">
		<?= __('Musical-Fabrik e.V. on Facebook: '); ?>
	</h4>
	<?php
	} else {
		?>
	<h4 class="card-title"><?= $this->page->title; ?></h4>
	<?php
	}
?>

	<p class="card-text">
		<?= $this->page->intro; ?>
	</p>

	<?php if ($this->page->external_type === 'Facebook') {
		if (!empty($this->page->external_link)) {
			?>
	<a href="<?= $this->page->external_link; ?>" target="_blank"
		rel=“nofollow” class="btn background facebook btn-inlinecolor hvr-grow">
		<i class="icon ion-logo-facebook"></i>
		<?= __('More on Facebook...'); ?>
	</a>
	<?php
		}
	} else {
		?>
	<a href="<?= $this->page->url; ?>"
		class="btn btn-light btn-inlinecolor hvr-grow"><?= __('More...'); ?></a>
	<?php
	}
?>
</div>