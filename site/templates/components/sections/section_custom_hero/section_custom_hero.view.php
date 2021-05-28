<?php

namespace ProcessWire;

?>
<section class="container-fluid section section_custom_hero <?= !empty($this->page->classes . '') ? $this->page->classes : ''; ?>" <?= $this->sectionId ? 'id="' . $this->sectionId . '"' : ''; ?>>

	<h2 class="section-title visually-hidden visually-hidden-focusable"><?= $this->page->title; ?></h2>
	<div class="background aspect-ratio ar-2-1 vue-comp">
		<div class="ar-content"></div>
	</div>
	<div class="hero-image-wrapper">
		<div class="hero-image aspect-ratio ar-3-1 parallax-element" data-parallax-speed="-1">
			<div class="ar-content">
				<a href="<?= (string) wire('pages')->get(1)->httpUrl; ?>" class="title-link">
					<img class="main_image img-fluid" src="<?= wire('config')->urls->templates; ?>assets/static_img/logo_randlos_weiss.svg" alt="Musical-Fabrik-Logo" />
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
			<div class="info-text">
				<span class="font-normal">Aufführungen vom 19. bis zum 29.&nbsp;März&nbsp;2020</span>
				Der Kartenvorverkauf startet am&nbsp;21.09. um&nbsp;9:00&nbsp;Uhr
			</div>
			<a class="btn background medicus" href="/projekte/der-medicus/tickets-infos/">Tickets & Infos</a> <a class="btn background medicus" href="/projekte/der-medicus/">zur Projektseite</a>
		</div>
	</div>
</section>