<?php
namespace ProcessWire;

if ($this->tags) {
	?>
	<div class="tags_field tags">
		<?php
		foreach ($this->tags as $tag) {
			$params = [
				'tags' => [$tag->id]
			];
			$url = $this->searchPage->url . '?' . http_build_query($params);

			if (!empty($url)) {
				?>
				<a href="<?= $url; ?>">
					<span class="badge badge-primary">
						<?= $tag->title; ?>
					</span>
				</a>
				<?php
			} else {
				?>
				<span class="badge badge-primary">
					<?= $tag->title; ?>
				</span>
				<?php
			}
		}
		?>
	</div>
	<?php
}
?>