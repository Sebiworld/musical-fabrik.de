<?php
namespace ProcessWire;

/*
Show single actor portrait
*/
?>
<div class="card portrait portrait-trigger" itemscope itemtype="http://schema.org/actor">
	<div class="aspect-ratio card-img-top">
		<?php
		if ($this->page->main_image) {
			echo $this->component->getService('ImageService')->getPictureHtml(array(
				'image' => $this->page->main_image,
				'alt' => sprintf(__('Portrait of %1$s'), $this->page->title),
				'pictureclasses' => array('ar-content', 'portrait-image'),
                'loadAsync' => true,
                'default' => array(
                    'width' => 400
                )
            ));
		} else {
			?>
			<div class="bg-image ar-content portrait-image" style="background-image: url('<?= wire('config')->urls->templates . 'assets/static_img/silhouette_einzel.png'; ?>');"> </div>
			<?php
		}
		?>
	</div>
	<div class="card-block">
		<h4 class="card-title" itemprop="name">
			<?php
			$title = str_replace('_', '&shy;', $this->page->title_separable);
			if (empty($title)) {
				$title = $this->page->title;
			}
			echo $title;
			?>
		</h4>

		<?php
		// Should a subtitle be output?
		if(!empty($this->subtitle)){
			?>
			<p class="card-text project-roles">
				<?= $this->subtitle; ?>
			</p>
			<?php
		}
		?>
		<!-- itemprop="performerIn" -->
	</div>
</div>