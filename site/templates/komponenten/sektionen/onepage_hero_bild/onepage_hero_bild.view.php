<?php
namespace ProcessWire;

if ($this->titelbild) {
	?>
	<section class="container-fluid onepage-sektion sektion-hero-bild <?= $this->page->hervorgehoben ? 'hervorgehoben' : ''; ?> <?= !empty($this->page->klassen.'') ? $this->page->klassen : ''; ?>" <?= $this->sektionID ? 'id="'.$this->sektionID.'"' : ''; ?>>

		<h2 class="sektion-titel sr-only sr-only-focusable"><?= $this->page->title; ?></h2>

		<?php
		if (!empty($this->hintergrundbild)) {
			?>
			<div class="hintergrundbild-wrapper">
				<div class="progressive__bg progressive--not-loaded hintergrundbild parallax-hintergrund" data-progressive="<?= $this->hintergrundbild->width(1500)->url; ?>" data-progressive-sm="<?= $this->hintergrundbild->height(600)->url; ?>" style="background-image: url('<?= $this->hintergrundbild->width(100)->url; ?>');" data-parallax-speed="-3">
				</div>
			</div>
			<?php
		}
		?>

		<div class="hero-bild-wrapper">
			<div class="hero-bild seitenverhaeltnis sv-3-1 parallax-element" data-parallax-speed="-1">
				<div class="sv-inhalt">
					<?php
					if ($this->titelbild->ext == 'svg') {
						echo $this->bildProvider->getBildTag(array(
							'bild' => $this->titelbild,
							'ausgabeAls' => 'image',
							'classes' => 'titelbild',
							'benutzeProgressively' => false,
							'normal' => 'original'
						));
					} else {
						?>
						<figure class="progressive">
							<?php
							echo $this->bildProvider->getBildTag(array(
								'bild' => $this->titelbild,
								'ausgabeAls' => 'image',
								'classes' => 'titelbild',
								'normal' => array(
									'width' => 1400
								),
								'sm' => array(
									'width' => 600
								),
								'xs' => array(
									'width' => 300
								)
							));
						?>
					</figure>
					<?php
					}
				?>
			</div>
		</div>

		<div class="beschreibung-wrapper parallax-element" data-parallax-speed="0">
			<div class="beschreibung">
				<?= $this->inhalte; ?>
			</div>
		</div>
	</div>
</section>

<?php
}
?>