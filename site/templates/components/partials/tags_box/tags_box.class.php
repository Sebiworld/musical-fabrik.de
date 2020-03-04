<?php
namespace ProcessWire;

class TagsBox extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		if (isset($args['active'])) {
			// Active keywords were transferred.
			if (is_string($args['active'])) {
				$args['active'] = explode(',', $args['active']);
			}
			if (is_array($args['active'])) {
				foreach (array_keys($args['active'], "") as $k) {
					unset($args['active'][$k]); // Remove empty elements
				}
				$this->active = $args['active'];
			}
		}

		$this->selectable = isset($args['selectable']) && !!$args['selectable'];

		$this->tags = $this->getTagsWithWeight($args);

		// usort($this->parameter['tags'], function ($a, $b) {
		// 	return strcmp($a['title'], $b['title']);
		// }); // Schlagwörter alphabetisch sortieren

		// Randomize keywords?
		if (isset($args['inOrder']) && $args['inOrder'] === false) {
			shuffle($this->tags);
		}

		$this->searchPage = wire('pages')->find('template.name=search_page')->first();

		$this->showCount = isset($args['show_count']) && !!$args['show_count'];
		$this->addCssClassForAmount = !isset($args['add_css_class_for_amount']) || !!$args['add_css_class_for_amount'];
	}

	public function getAjax($ajaxArgs = []) {
		$tags = $this->getTagsWithWeight();
		return array(
			'tags' => $tags
		);
	}

	/**
	 * Returns the IDs of the currently active keywords as an array
	 */
	public function getActive() {
		if (!isset($this->active)) {
			return array();
		}
		return $this->active;
	}

	/**
	 * Returns the keywords, weighted by the number of their occurrence on all pages.
	 * @param  array  $args Possible arguments are:
	 *                      'use_page' A ProcessWire page whose keywords are to be output. Alternatively, all keywords are delivered.
	 *                      'maxAmount' The maximum number of keywords to be output.
	 * @return array('id', 'title', 'farbe', 'name', 'maximum', 'limit')
	 */
	private function getTagsWithWeight($args = array()) {
		if (isset($args['use_page']) && $args['use_page'] instanceof Page) {
			$page = $args['use_page'];
		}

		$output = array();
		try {
			if(isset($args['limit'])){
				$args['limit'] = (int) $this->wire('sanitizer')->intUnsigned($args['limit']);
			}

			$query = wire('database')->prepare("
				SELECT
				field_tags.data as id,
				field_title.data as title,
				field_color.data as color,
				pages.name as name,
				pages.status as status,
				(SELECT MAX(amount) FROM (
				SELECT count(pages_id) as amount FROM field_tags GROUP BY data
				) as amount_sub) as maximum,
				count(CASE page.status WHEN page.status < 1025 THEN 1 else NULL end) as amount
				". (isset($page) ? ", SUM(CASE WHEN page.id=:page_id THEN 1 ELSE 0 END) as contained": '') ."
				FROM field_tags
				INNER JOIN field_title ON (field_tags.data=field_title.pages_id)
				INNER JOIN pages ON (field_tags.data=pages.id)
				INNER JOIN pages AS page ON (field_tags.pages_id=page.id)
				LEFT JOIN field_color ON (field_tags.data=field_color.pages_id)
				GROUP BY field_tags.data
				HAVING amount > 0 AND status < 1025
				"
				.(isset($page) ? ' AND contained > 0': '')
				." ORDER BY amount DESC, title ASC"
				.(isset($args['limit']) ? ' LIMIT ' . $args['limit'] : ''));

			$queryParams = [];
			if(isset($page)){
				$queryParams[':page_id'] = $page->id;
			}

			$query->execute($queryParams);

			while ($row = $query->fetch(\PDO::FETCH_ASSOC)) {
				$row['active'] = in_array($row['id'], $this->getActive());
				$row['tags_on_click'] = $row['id'];
				if ($row['active']) {
					// The selected keyword is active, so it should be removed when you click on it.
					$row['tags_on_click'] = array_diff($row['tags_on_click'], [$row['id']]);
				} elseif (!empty($this->getActive())) {
					// The selected keyword is not active, but there are active keywords. When clicking, the keyword must be added.
					$row['tags_on_click'][] = $row['id'];
				}
				$output[] = $row;
			}
		} catch (\Exception $e) {
			Twack::devEcho($e->getMessage());
		}

		return $output;
	}
}
