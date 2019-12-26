<?php
namespace ProcessWire;

/*
Show single actor portrait
 */
// <div class="vcard">
//    <p><strong class="fn">Kevin Grendelzilla</strong></p>
//    <p><span class="title">Technical writer</span> at <span class="org">Google</span></p>
//    <p><span class="adr">
//       <span class="street-address">555 Search Parkway</span>
//       <span class="locality">Googlelandia</span>, <span class="region">CA</span>
//       <span class="postcode">94043</span>
//       </span></p>
// </div>
// https://developers.google.com/search/docs/guides/
// https://www.w3.org/TR/microdata/
//
// TODO: https://codyhouse.co/gem/css-product-quick-view
?>
<div class="card portrait portrait-trigger" itemscope itemtype="http://schema.org/actor">
	<div class="aspect-ratio card-img-top">
		<?php
		if ($this->page->main_image) {
			echo $this->component->getService('ImageService')->getImgHtml(array(
                'image' => $this->page->main_image,
                'classes' => array('ar-content', 'portrait-image'),
                'outputType' => 'image',
                'loadAsync' => true,
                'default' => array(
                    'width' => 400,
					'height' => 400
                ),
                'srcset' => array(
                    '320w' => array(
						'width' => 320,
						'height' => 320
                    ),
                    '640w' => array(
                        'width' => 640,
						'height' => 640
                    ),
                    '720w' => array(
                        'width' => 720,
						'height' => 720
                    ),
                    '800w' => array(
                        'width' => 800,
						'height' => 800
                    ),
                    '960w' => array(
                        'width' => 960,
						'height' => 960
                    )
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