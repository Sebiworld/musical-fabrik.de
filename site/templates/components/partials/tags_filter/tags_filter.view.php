<?php
namespace ProcessWire;
?>
<div class="box tags_filter tags <?= $this->selectable ? 'selectable' : ''; ?>">
	<div class="inactive-filters <?= empty($this->tags['inactive']) ? 'd-none' : ''; ?>">
		<?php
		foreach ($this->tags['inactive'] as $tag) {
			$tagPage = wire('pages')->get($tag['id']);
			if (!($tagPage instanceof Page) || !$tagPage->id || !$tagPage->listable()) {
				continue;
			}

			$params = $_GET;
			if (!empty($tag['tags_on_click'])) {
				$params['tags'] = $tag['tags_on_click'];
			} else {
				unset($params['tags']);
			}

			$url = $this->page->url . '?' . http_build_query($params); ?>
			<a data-id="<?= $tag['id']; ?>" href="<?= $url; ?>" class="tag badge badge-light"
			<?php
			if (isset($tag['color'])) {
				echo ' data-bgcolor="' . $tag['color'] . '" data-color="' . $tag['color_over'] . '" ';
			} ?>>
				<?= $tag['title']; ?> <?= $this->showCount ? '(' . $tag['amount'] . ')' : ''; ?> <span class="close-indicator">&times;</span>
			</a>
		<?php
		}
		?>
	</div>

	<div class="active-filters <?= empty($this->tags['active']) ? 'd-none' : ''; ?>">
		<label><?= __('Selected filters:'); ?></label>
		<?php
		foreach ($this->tags['active'] as $tag) {
			$tagPage = wire('pages')->get($tag['id']);
			if (!($tagPage instanceof Page) || !$tagPage->id || !$tagPage->listable()) {
				continue;
			}

			$params = $_GET;
			if (!empty($tag['tags_on_click'])) {
				$params['tags'] = $tag['tags_on_click'];
			} else {
				unset($params['tags']);
			}

			$url = $this->page->url . '?' . http_build_query($params); ?>
			<a data-id="<?= $tag['id']; ?>" href="<?= $url; ?>"

			<?php
			if (isset($tag['color'])) {
				echo ' style="background-color: #' . $tag['color'] . '; color: #' . $tag['color_over'] . '" data-bgcolor="' . $tag['color'] . '" data-color="' . $tag['color_over'] . '" ';
			} ?>

			class="active tag badge badge-light <?= isset($tag['color']) ? 'has-background' : ''; ?> tag <?= $this->addCssClassForAmount ? 't-' . ((int)ceil($tag['amount'] / ($tag['maximum'] / 6))) : '' ?> ">
				<?= $tag['title']; ?> <?= $this->showCount ? '(' . $tag['amount'] . ')' : ''; ?> <span class="close-indicator">&times;</span>
			</a>
		<?php
		}
		?>
	</div>
</div>