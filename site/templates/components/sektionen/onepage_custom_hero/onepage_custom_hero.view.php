<?php
namespace ProcessWire;
?>
<section class="container-fluid onepage-sektion sektion-custom-hero <?= !empty($this->page->klassen . '') ? $this->page->klassen : ''; ?>" <?= $this->sektionID ? 'id="' . $this->sektionID . '"' : ''; ?>>

	<h2 class="sektion-titel sr-only sr-only-focusable"><?= $this->page->title; ?></h2>
	<div class="hintergrund seitenverhaeltnis sv-2-1 vue-comp">
		<div class="sv-inhalt"></div>
	</div>
	<div class="hero-bild-wrapper">
		<div class="hero-bild seitenverhaeltnis sv-3-1 parallax-element" data-parallax-speed="-1">
			<div class="sv-inhalt">
				<a href="<?= (string)wire('pages')->get(1)->httpUrl; ?>" class="titel-link">
					<img class="titelbild img-fluid" src="<?= wire('config')->urls->templates; ?>assets/static_img/logo_randlos_weiss.svg" alt="Musical-Fabrik-Logo" />
				</a>
			</div>
		</div>
	</div>
	<div class="goldinfo">
		<div class="medicus-logo">
			<a href="/projekte/der-medicus/">
				<img src="<?= wire('config')->urls->templates; ?>assets/static_img/medicus_logo_white.png">
			</a>
		</div>
		<div class="medicus-teaser">
			<div class="info-text">Aufführungen vom 19. bis zum 29.&nbsp;März&nbsp;2020</div>
			<a class="btn hintergrund medicus" href="/projekte/der-medicus/">zur Projektseite</a>
		</div>
	</div>
</section>