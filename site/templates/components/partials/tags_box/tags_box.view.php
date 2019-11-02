<?php
namespace ProcessWire;

if ($this->tags) {
	?>
	<div class="box tags_box tags <?= $this->selectable ? 'selectable' : ''; ?>">
		<div class="tag-cloud">
			<?php
			$currentURL = $this->articlesPage->url;

			foreach ($this->tags as $tag) {

				$tagPage = wire('pages')->get($tag['id']);
				if(!($tagPage instanceof Page) || !$tagPage->id || !$tagPage->listable()){
					continue;
				}

				$params = $_GET;
				if (!empty($tag['tags_on_click'])) {
					$params['tags'] = $tag['tags_on_click'];
				} else {
					unset($params['tags']);
				}

				$url = $currentURL . '?' . http_build_query($params);
				?>
				<a
				data-id="<?= $tag['id']; ?>"
				href="<?= $url; ?>"

				<?php
				if (isset($tag['color'])) {
					echo "style=\"background-color: #{$tag['color']}\"";
				}
				?>

				class="badge <?= isset($tag['color']) ? 'has-background' : ''; ?> tag <?= $this->addCssClassForAmount ? 't-' . ((int)ceil($tag['amount'] / ($tag['maximum'] / 6))) : '' ?> <?= $tag['active'] ? 'active' : '' ?>">
				<?= $tag['title']; ?> <?= $this->showCount ? '('.$tag['amount'].')' : ''; ?>
			</a>
			<?php
			}
		?>
	</div>
</div>
<?php
}
?>