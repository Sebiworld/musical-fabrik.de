<?php
namespace ProcessWire;

?>
<div class="card beitrag-card" data-id="<?= $this->page->id; ?>">
	<?php
	if ($this->page->titelbild) {
		?>
		<div class="seitenverhaeltnis card-img-top sv-2-1">
			<?php
			echo $this->component->getService('BildService')->getBildTag(array(
				'bild' => $this->page->titelbild,
				'ausgabeAls' => 'bg-image',
				'classes' => 'sv-inhalt beitrag-bild',
				'normal' => array(
					'height' => 300
				)
			));
			?>
		</div>
		<?php
	} else {
		?>
		<div class="seitenverhaeltnis card-img-top sv-2-1">
			<?php
			echo $this->component->getService('BildService')->getPlatzhalterBildTag(array(
				'ausgabeAls' => 'bg-image',
				'classes' => 'sv-inhalt beitrag-bild',
				'normal' => array(
					'height' => 300
				)
			));
			?>
		</div>
		<?php
	}
	?>
	<div class="card-block">
		<div class="card-meta" <?= $this->page->farbe ? 'style="background-color: #'.$this->page->farbe.'; border-color: #'.$this->page->farbe.'"' : ''; ?>>
			vom <?= date('d.m.Y', $this->page->getUnformatted('zeitpunkt_von')); ?>
		</div>

		<h4 class="card-title"><?= $this->page->title; ?></h4>
		<p class="card-text">
			<?= $this->page->einleitung; ?>
		</p>

		<a href="<?= $this->page->url; ?>" class="btn btn-light btn-inlinecolor hvr-grow">Mehr dazu...</a>
	</div>
</div>
