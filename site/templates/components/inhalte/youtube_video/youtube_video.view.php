<?php
namespace ProcessWire;

?>
<div class="inhalt-youtube-video <?= !empty($this->page->klassen . '') ? $this->page->klassen : ''; ?>" <?= $this->page->depth ? 'data-tiefe="' . $this->page->depth . '"' : ''; ?>>
	<?php
	if (!empty($this->page->title)) {
		$headingDepth = 2;
		if ($this->page->depth && intval($this->page->depth)) {
			$headingDepth = $headingDepth + intval($this->page->depth);
		}
		?>
		<h<?= $headingDepth; ?> class="block-titel <?= $this->page->titel_verstecken ? 'sr-only sr-only-focusable' : ''; ?>">
			<?= $this->page->title; ?>
		</h<?= $headingDepth; ?>>
	<?php
}
/*
<video-player>
	<source src="//vjs.zencdn.net/v/oceans.mp4" type="video/mp4">
	<source src="//vjs.zencdn.net/v/oceans.webm" type="video/webm">
</video-player>
*/
?>
	<div class="video-wrapper">
		<video-player class="youtube-player" data-youtube-id="<?= $this->page->kurztext; ?>"></video-player>
	</div>
</div>