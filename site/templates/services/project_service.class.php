<?php
namespace ProcessWire;

class ProjectService extends TwackComponent {
	public function __construct($args) {
		parent::__construct($args);

		$this->projectPage = $this->getProjectPage();
		if (isset($args['projectPage']) && $args['projectPage'] instanceof Page && $args['projectPage']->id) {
			$this->projectPage = $args['projectPage'];
		}

		if (!($this->projectPage instanceof Page) || !$this->projectPage->id) {
			$this->projectPage = wire('pages')->get('/');
		}
	}

	public function getProjectPage($page = false) {
		if (!($page instanceof Page)) {
			$page = $this->page;
		}

		if (!($page instanceof Page) || !$page->id) {
			return new NullPage();
		}

		if ($this->isProjectPage($page)) {
			return $page;
		}

		$page = $page->closest('template.name=project');
		if (!($page instanceof Page || !$page->id)) {
			return new NullPage();
		}

		return $page;
	}

	public function getProjectPageWithFallback($page = false) {
		$page = $this->getProjectPage($page);
		if ($page instanceof Page && $page->id) {
			return $page;
		}
		return wire('pages')->get('/');
	}

	public function isProjectPage($page = false) {
		if (!($page instanceof Page)) {
			$page = $this->projectPage;
		}

		return $page instanceof Page && substr($page->template->name, 0, 7) === 'project' && $page->template->name !== 'project_role' && $page->template->name !== 'project_roles_container' && $page->template->name !== 'projects_container';
	}

	public function getProjectAjax($page = false) {
		$projectPage = $this->getProjectPage($page);

		if (!$this->isProjectPage($projectPage)) {
			return null;
		}

		$output = AppApi::getAjaxOf($projectPage);

		if ($projectPage->template->hasField('main_image') && $projectPage->main_image) {
			$output['main_image'] = AppApi::getAjaxOf($projectPage->main_image);
		}

		if ($projectPage->template->hasField('logo_square') && $projectPage->logo_square) {
			$output['logo_square'] = AppApi::getAjaxOf($projectPage->logo_square);
		}

		if ($projectPage->template->hasField('color') && $projectPage->color) {
			$output['color'] = $projectPage->color;
		}

		if ($projectPage->template->hasField('theme_vars') && $projectPage->theme_vars) {
			$output['theme_vars'] = AppApi::getAjaxOf($projectPage->theme_vars);
		}

		if ($projectPage->template->hasField('info_overlay') && $projectPage->info_overlay) {
			$output['info_overlay'] = $projectPage->info_overlay;
		}

		if ($projectPage->template->hasField('short_description') && $projectPage->short_description) {
			$output['short_description'] = $projectPage->short_description;
		}

		$output['hash'] = md5(json_encode($output));

		return $output;
	}

	public function getProjectDetailAjax($page = false) {
		$projectPage = $this->getProjectPage($page);

		if (!$this->isProjectPage($projectPage)) {
			return null;
		}

		$output = $this->getProjectAjax($page);
		if (!is_array($output)) {
			$output = [];
		}

		$component = $this->addComponent('GeneralDataBox', ['directory' => 'sidebar_component', 'page' => $page]);
		if (!($component instanceof TwackNullComponent)) {
			$output['general'] = $component->getAjax([]);
		}

		$component = $this->addComponent('ImagesBox', ['directory' => 'sidebar_component', 'page' => $page]);
		if (!($component instanceof TwackNullComponent)) {
			$output['images'] = $component->getAjax([]);
		}

		$component = $this->addComponent('EventsBox', ['directory' => 'sidebar_component', 'page' => $page]);
		if (!($component instanceof TwackNullComponent)) {
			$output['events'] = $component->getAjax([]);
		}

		$component = $this->addComponent('SharingBox', ['directory' => 'sidebar_component', 'page' => $page]);
		if (!($component instanceof TwackNullComponent)) {
			$output['sharing'] = $component->getAjax([]);
		}

		$component = $this->addComponent('SponsorsBox', ['directory' => 'sidebar_component', 'page' => $page, 'useField' => 'partners']);
		if (!($component instanceof TwackNullComponent)) {
			$output['partners'] = $component->getAjax([]);
		}

		$component = $this->addComponent('SponsorsBox', ['directory' => 'sidebar_component', 'page' => $page, 'useField' => 'sponsors']);
		if (!($component instanceof TwackNullComponent)) {
			$output['sponsors'] = $component->getAjax([]);
		}

		if (!empty($output['hash'])) {
			unset($output['hash']);
		}

		$output['hash'] = md5(json_encode($output));

		// $generalData = self::getGeneralData($projectPage);
		// if (!empty($generalData)) {
		// 	$output['data']['general'] = $generalData;
		// }

		// $imagesData = self::getImagesData($projectPage);
		// if (!empty($imagesData)) {
		// 	$output['data']['iamges'] = $imagesData;
		// }

		return $output;
	}

	public function getPortraitsContainer($page = false) {
		$projectPage = $this->getProjectPage($page);

		if (!($projectPage instanceof Page || !$projectPage->id)) {
			if ($this->projectPage instanceof Page && $this->projectPage->id) {
				$projectPage = $this->projectPage;
			} else {
				return new NullPage();
			}
		}

		return wire('pages')->findOne('template.name=portraits_container, include=hidden, has_parent=' . $projectPage->id);
	}

	public function getSeasonsContainer($page = false) {
		$projectPage = $this->getProjectPage($page);

		if (!($projectPage instanceof Page || !$projectPage->id)) {
			if ($this->projectPage instanceof Page && $this->projectPage->id) {
				$projectPage = $this->projectPage;
			} else {
				return new NullPage();
			}
		}

		return wire('pages')->findOne('template.name=seasons_container, include=hidden, has_parent=' . $projectPage->id);
	}

	public function getCastsContainer($page = false) {
		$projectPage = $this->getProjectPage($page);

		if (!($projectPage instanceof Page || !$projectPage->id)) {
			if ($this->projectPage instanceof Page && $this->projectPage->id) {
				$projectPage = $this->projectPage;
			} else {
				return new NullPage();
			}
		}

		return wire('pages')->findOne('template.name=casts_container, include=hidden, has_parent=' . $projectPage->id);
	}

	public function getTextColorOver($color) {
		return $this->isColorLight($color) ? '#333' : '#f1f1f1';
	}

	public function isColorLight($color) {
		if (!$color || !is_string($color) || empty($color)) {
			return true;
		}

		$rgb = $this->htmlToRgb($color);
		$hsl = $this->rgbToHsl($rgb);
		if (!$hsl) {
			return true;
		}

		return $hsl->lightness > 200;
	}

	public function htmlToRgb($htmlCode) {
		if ($htmlCode[0] == '#') {
			$htmlCode = substr($htmlCode, 1);
		}

		if (strlen($htmlCode) == 3) {
			$htmlCode = $htmlCode[0] . $htmlCode[0] . $htmlCode[1] . $htmlCode[1] . $htmlCode[2] . $htmlCode[2];
		}

		$r = hexdec($htmlCode[0] . $htmlCode[1]);
		$g = hexdec($htmlCode[2] . $htmlCode[3]);
		$b = hexdec($htmlCode[4] . $htmlCode[5]);

		return $b + ($g << 0x8) + ($r << 0x10);
	}

	public function rgbToHsl($RGB) {
		$r = 0xFF & ($RGB >> 0x10);
		$g = 0xFF & ($RGB >> 0x8);
		$b = 0xFF & $RGB;

		$r = ((float)$r) / 255.0;
		$g = ((float)$g) / 255.0;
		$b = ((float)$b) / 255.0;

		$maxC = max($r, $g, $b);
		$minC = min($r, $g, $b);

		$l = ($maxC + $minC) / 2.0;

		if ($maxC == $minC) {
			$s = 0;
			$h = 0;
		} else {
			if ($l < .5) {
				$s = ($maxC - $minC) / ($maxC + $minC);
			} else {
				$s = ($maxC - $minC) / (2.0 - $maxC - $minC);
			}
			if ($r == $maxC) {
				$h = ($g - $b) / ($maxC - $minC);
			}
			if ($g == $maxC) {
				$h = 2.0 + ($b - $r) / ($maxC - $minC);
			}
			if ($b == $maxC) {
				$h = 4.0 + ($r - $g) / ($maxC - $minC);
			}

			$h = $h / 6.0;
		}

		$h = (int)round(255.0 * $h);
		$s = (int)round(255.0 * $s);
		$l = (int)round(255.0 * $l);

		return (object) ['hue' => $h, 'saturation' => $s, 'lightness' => $l];
	}
}
