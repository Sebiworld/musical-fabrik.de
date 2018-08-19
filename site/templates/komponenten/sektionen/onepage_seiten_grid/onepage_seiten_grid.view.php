<?php
namespace ProcessWire;

?>
<section class="container-fluid onepage-sektion sektion-seiten-grid <?= $this->page->hervorgehoben ? 'hervorgehoben' : ''; ?> <?= !empty($this->page->klassen.'') ? $this->page->klassen : ''; ?>" <?= $this->sektionID ? 'id="'.$this->sektionID.'"' : ''; ?>>

	<?php
	if (!empty($this->titel)) {
		?>
		<h2 class="sektion-titel <?= $this->page->titel_verstecken ? 'sr-only sr-only-focusable' : ''; ?>">
		<?= $this->titel; ?>
	</h2>
	<?php
	}
	?>

	<?= $this->inhalte; ?>
	<div class="row seiten-reihe">
		<?php
		if ($seiten) {
			foreach ($seiten as $listenIndex => $seite) {
				?>
				<article class="<?= $this->gridKlassen; ?> seite">
					<div class="card card-dark text-white <?= $this->cardKlasse; ?>">
						<div class="card-inhalt-wrapper">
							<div class="card-img seitenverhaeltnis sv-<?= $this->bildFormat; ?>">
								<?php
								echo $this->bildProvider->getBildTag(array(
									'bild' => $seite->gridbild,
									'ausgabeAls' => 'bg-image',
									'styles' => 'bg-color: #'.$seite->farbe.';',
									'classes' => 'titelbild sv-inhalt',
									'normal' => array(
										'width' => 1000
										),
									'sm' => array(
										'width' => 500
									)
								));
								?>
							</div>
							<div class="card-img-overlay">
								<div class="top">
									<h4 class="card-title"><?= $seite->title; ?></h4>
									<div class="card-text"><?= $seite->beschreibung; ?></div>
								</div>

								<?php
								if (!$seite->keine_detailansicht) {
									$btnText = 'Mehr dazu...';
									if ($seite->template->hasField('btn_text') && !empty($seite->btn_text)) {
										$btnText = $seite->btn_text;
									} elseif ($seite->template->name == 'projekt') {
										$btnText = 'zum Projekt';
									}
									?>
									<a class="card-link btn btn-primary btn-inlinecolor hvr-grow" href="<?= $seite->url; ?>" <?= $seite->farbe ? 'style="background-color: #'.$seite->farbe.'; border-color: #'.$seite->farbe.'"' : ''; ?>><?= $btnText ?></a>
									<?php
								}
								?>
							</div>
						</div>
					</div>
				</article>

				<?php
				if (($listenIndex + 1) % 2 === 0) {
					echo '<div class="clearfix hidden-sm-down.hidden-sm-up"></div>';
				}
				if (($listenIndex + 1) % 3 === 0) {
					echo '<div class="clearfix hidden-md-down.hidden-md-up"></div>';
				}
				if (($listenIndex + 1) % 4 === 0) {
					echo '<div class="clearfix hidden-lg-down"></div>';
				}
			}
		}
		?>
	</div>
</section>