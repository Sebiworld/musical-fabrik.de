<?php
namespace ProcessWire;

/*
Einzelnes Darstellerportrait anzeigen
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
<div class="card mitwirkenden-portrait portrait-trigger" itemscope itemtype="http://schema.org/actor">
	<div class="seitenverhaeltnis card-img-top">
		<?php
		if ($this->page->titelbild) {
			echo $this->bildProvider->getBildTag(array(
				'bild' => $this->page->titelbild,
				'ausgabeAls' => 'bg-image',
				'classes' => 'bg-bild portrait-bild sv-inhalt',
				'normal' => array(
					'height' => 800
				),
				'sm' => array(
					'height' => 600
				)
			));
		} else {
			?>
			<div class="bg-bild sv-inhalt portrait-bild" style="background-image: url('<?= wire('config')->urls->templates . 'assets/static_img/silhouette_einzel.png'; ?>');"> </div>
			<?php
		}
		?>
	</div>
	<div class="card-block">
		<h4 class="card-title" itemprop="name">
			<?php
			$title = str_replace('_', '&shy;', $this->page->title_trennbar);
			if (empty($title)) {
				$title = $this->page->title;
			}
			echo $title;
			?>
		</h4>

		<?php
		// Soll ein Untertitel ausgegeben werden?
		if(!empty($this->subtitle)){
			?>
			<p class="card-text rollen">
				<?= $this->subtitle; ?>
			</p>
			<?php
		}
		?>
		<!-- itemprop="performerIn" -->
	</div>
</div>