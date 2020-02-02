<?php

namespace ProcessWire;

?>
<section class="container-fluid section section_hero_image <?= $this->page->highlight ? 'highlight' : ''; ?> <?= !empty($this->page->classes . '') ? $this->page->classes : ''; ?>" <?= $this->sectionId ? 'id="' . $this->sectionId . '"' : ''; ?>>

	<h2 class="section-title sr-only sr-only-focusable"><?= $this->page->title; ?></h2>

	<div class="hero-image-wrapper">
		<div class="hero-image">
			<?php
            if ($this->mainImage) {
                echo $this->component->getService('ImageService')->getPictureHtml(array(
                    'image'          => $this->mainImage,
                    'alt'            => $this->page->title,
                    'pictureclasses' => array('img-fluid'),
                    'loadAsync'      => true,
                    'default'        => array(
                        'width'  => 1000,
                        'height' => 500
                    ),
                    'media' => array(
                        '(max-width: 500px)' => array(
                            'width'  => 500,
                            'height' => 350
                        ),
                        '(min-width: 1100px)' => array(
                            'width'  => 1100,
                            'height' => 400
                        )
                    )
                ));
            }
            ?>
			<img alt="MF Logo Spacer" src="<?= wire('config')->urls->templates . 'assets/static_img/section-spacer-white-logo-mobile.svg'; ?>" class="img-fluid spacer-image d-block d-md-none"/>
			<img alt="MF Logo Spacer" src="<?= wire('config')->urls->templates . 'assets/static_img/section-spacer-white-logo.svg'; ?>" class="img-fluid spacer-image d-none d-md-block"/>
		</div>

		<div class="description-wrapper">
			<div class="description">
				<?= $this->contents; ?>
			</div>
		</div>
	</div>
</section>