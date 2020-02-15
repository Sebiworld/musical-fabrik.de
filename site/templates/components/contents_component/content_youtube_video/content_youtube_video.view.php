<?php

namespace ProcessWire;

?>
<div class="content_youtube_video video-element aspect-ratio ar-16-9 <?= !empty($this->page->classes . '') ? $this->page->classes : ''; ?>" <?= $this->page->depth ? 'data-depth="' . $this->page->depth . '"' : ''; ?> data-youtube-id="<?= $this->page->short_text; ?>">
    <div class="placeholder ar-content">
        <?php
            if ($this->page->image) {
                echo $this->component->getService('ImageService')->getPictureHtml(array(
                    'image'          => $this->page->image,
                    'pictureclasses' => array('placeholder-image'),
                    'loadAsync'      => true,
                    'default'        => array(
                        'width'  => 800,
                        'height' => 450
                    )
                ));
            } else {
                echo $this->component->getService('ImageService')->getPlaceholderPictureHtml(array(
                    'alt'            => sprintf(__('Main-image of %1$s'), $this->page->title),
                    'pictureclasses' => array('placeholder-image'),
                    'loadAsync'      => true,
                    'default'        => array(
                        'width'  => 800,
                        'height' => 450
                    )
                ));
            }
        ?>
        <div class="overlay">
            <div class="title"><span><?= $this->page->title; ?></span></div>
            <div class="play-indicator"></div>
            <div class="description">Video ansehen</div>
        </div>
    </div>
</div>