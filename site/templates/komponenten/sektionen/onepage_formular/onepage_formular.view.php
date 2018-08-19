<?php
namespace ProcessWire;

?>
<section class="container-fluid onepage-sektion sektion-formular <?= $this->page->hervorgehoben ? 'hervorgehoben' : ''; ?> <?= !empty($this->page->klassen.'') ? $this->page->klassen : ''; ?>" <?= $this->sektionID ? 'id="'.$this->sektionID.'"' : ''; ?>>

	<?php
	if (!empty($this->titel)) {
		?>
		<h2 class="sektion-titel <?= $this->page->titel_verstecken ? 'sr-only sr-only-focusable' : ''; ?>">
			<?= $this->titel; ?>
		</h2>
		<?php
	}
	?>

	<?= $this->page->einleitung ? '<div class="einleitung">'.$this->page->einleitung.'</div>' : ''; ?>
	<?= $this->inhalte; ?>

	<?= $this->formular; ?>

	<?= $this->page->freitext; ?>
</section>