<?php
namespace ProcessWire;

?>
<div class="card article_card" data-id="<?= $this->page->id; ?>">
	<div class="aspect-ratio card-img-top ar-2-1">
		<?php
		if ($this->page->main_image) {
			echo $this->component->getService('ImageService')->getImgHtml(array(
                'image' => $this->page->main_image,
                'classes' => array('ar-content', 'article-image'),
                'outputType' => 'image',
                'loadAsync' => true,
                'default' => array(
                    'width' => 320,
					'height' => 160
                ),
                'srcset' => array(
                    '320w' => array(
						'width' => 320,
						'height' => 160
                    ),
                    '640w' => array(
                        'width' => 640,
						'height' => 320
                    ),
                    '960w' => array(
                        'width' => 960,
						'height' => 480
                    ),
                    '1280w' => array(
                        'width' => 1280,
						'height' => 640
                    )
                )
            ));
		} else {
			echo $this->component->getService('ImageService')->getPlaceholderImageHtml(array(
				'classes' => array('ar-content', 'article-image'),
                'outputType' => 'image',
                'loadAsync' => true,
                'default' => array(
                    'width' => 320,
					'height' => 160
                ),
                'srcset' => array(
                    '320w' => array(
						'width' => 320,
						'height' => 160
                    ),
                    '640w' => array(
                        'width' => 640,
						'height' => 320
                    ),
                    '960w' => array(
                        'width' => 960,
						'height' => 480
                    ),
                    '1280w' => array(
                        'width' => 1280,
						'height' => 640
                    )
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
