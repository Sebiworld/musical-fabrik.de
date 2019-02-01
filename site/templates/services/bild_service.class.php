<?php
namespace ProcessWire;

/**
 * Liefert Bild-Elemente
 */
class BildService extends TwackComponent {

	protected $konfigurationService;

	public function __construct($args) {
		parent::__construct($args);
		$this->konfigurationService = $this->getService('KonfigurationService');
	}

	/**
	 * Liefert das Bild als HTML-Tag
	 * @param  array  $args
	 * @return string
	 */
	public function getBildTag($args = array()) {
		$bild = $this->getBild($args);
		if (!($bild instanceof Pageimage)) {
			return '';
		}

		$bildNormalUrl = $bild->url;

		$attribute = array();
		$styles = array();
		$classes = array();

		if (isset($args['classes'])) {
			if (is_array($args['classes'])) {
				$classes = $args['classes'];
			} elseif (is_string($args['classes'])) {
				$classes = explode(' ', $args['classes']);
			}
		}

		if (isset($args['styles'])) {
			if (is_array($args['styles'])) {
				$styles = $args['styles'];
			} elseif (is_string($args['styles'])) {
				$styleanteile = explode(';', $args['styles']);
				foreach ($styleanteile as $anteil) {
					$style = explode(':', $anteil);
					if (count($style) < 2) {
						continue;
					}
					if (empty(trim($style[0])) || empty(trim($style[1]))) {
						continue;
					}
					$styles[trim($style[0])] = trim($style[1]);
				}
			}
		}

		if (!isset($args['ausgabeAls']) || !in_array($args['ausgabeAls'], ['bg-image', 'background-image'])) {
			if (isset($args['alt'])) {
				$attribute['alt'] = $args['alt'];
			} else {
				$attribute['alt'] = $bild->alt;
			}
		}

		if (isset($args['caption'])) {
			$attribute['data-caption'] = $args['caption'];
		} elseif (isset($bild->caption) && !empty($bild->caption)) {
			$attribute['data-caption'] = $bild->caption;
		}

		if (!isset($args['normal'])) {
			$args['normal'] = array('width' => 1000);
		}
		$bildNormalUrl = $this->getBildWithOptions($bild, $args['normal']);

		$benutzeProgressively = true;
		if (isset($args['benutzeProgressively']) && !$args['benutzeProgressively']) {
			$benutzeProgressively = false;
		}

		// Progressively verwenden?
		if ($benutzeProgressively) {
			if (!isset($args['xs'])) {
				$args['xs'] = array();
			}

			$attribute['data-progressive'] = $bildNormalUrl;
			$bildNormalUrl = $this->getBildWithOptions($bild, $args['xs']);
			$classes[] = 'progressive--not-loaded';

			if (isset($args['sm'])) {
				if (!isset($args['sm']['width']) && !isset($args['sm']['height'])) {
					$args['sm']['width'] = 600;
				}
				$attribute['data-progressive-sm'] = $this->getBildWithOptions($bild, $args['sm']);
			}
		}

		// Modal verwenden?
		if (isset($args['vollbild-modal'])) {
			if (!isset($args['vollbild-modal']['width']) && !isset($args['vollbild-modal']['height'])) {
				$args['vollbild-modal']['width'] = 1400;
			}

			$attribute['data-vollbild-modal'] = $this->getBildWithOptions($bild, $args['vollbild-modal']);
			$attribute['data-vollbild'] = $bild->url;
		}

		if (isset($args['ausgabeAls']) && in_array($args['ausgabeAls'], ['bg-image', 'background-image'])) {
			$styles['background-image'] = 'url("' . $bildNormalUrl . '")';
			if ($benutzeProgressively) {
				$classes[] = 'progressive__bg';
			}
			$bildHTML = '<div class="' . implode($classes, ' ') . '" ' . $this->makeAttributString($attribute) . ' ' . $this->makeStyleString($styles) . ' ></div>';
		} else {
			$attribute['src'] = $bildNormalUrl;
			// $attribute['width'] = $bild->width;
			// $attribute['height'] = $bild->height;

			$classes[] = 'img-fluid';
			if ($benutzeProgressively) {
				$classes[] = 'progressive__img';
			}
			$bildHTML = '<img class="' . implode($classes, ' ') . '" ' . $this->makeAttributString($attribute) . ' ' . $this->makeStyleString($styles) . ' />';
		}
		return $bildHTML;
	}

	/**
	 * Liefert das Bild als PageImage
	 * @param  array  $args
	 * @return PageImage
	 */
	public function getBild($args = array()) {
		if (!is_array($args)) {
			return null;
		}

		if (isset($args['bild']) && $args['bild'] instanceof Pageimage) {
			return $args['bild'];
		}

		if (!isset($args['seite']) || !($args['seite'] instanceof Page) || !$args['seite']->id) {
			$args['seite'] = $this->page;
		}
		if (!isset($args['feld'])) {
			return '';
		}

		$feldWert = $args['seite']->get($args['feld']);
		$bild = $feldWert;
		if ($feldWert instanceof Pageimages) {
			// Es wurden mehrere Bilder geliefert
			$bild = $bild->getRandom();
			if (isset($args['selektiereNach'])) {
				if ($args['selektiereNach'] == 'erstes') {
					$bild = $feldWert->first();
				} elseif ($args['selektiereNach'] == 'letztes') {
					$bild = $feldWert->last();
				}if (is_numeric($args['selektiereNach'])) {
					$bild = $feldWert->get($args['selektiereNach']);
				}
			}
		}

		return $bild;
	}

	protected function makeAttributString($attributArray) {
		$attributString = '';
		foreach ($attributArray as $name => $attribut) {
			if (strlen($attributString) >= 1) {
				$attributString .= ' ';
			}
			$attributString .= $name;
			if (!empty($attribut)) {
				$attributString .= '="' . $attribut . '"';
			}
		}
		return ($attributString);
	}

	protected function makeStyleString($styleArray) {
		if (count($styleArray) < 1) {
			return '';
		}
		$styleString = '';
		foreach ($styleArray as $name => $style) {
			$styleString .= $name . ': ' . $style . '; ';
		}
		return ('style=\''.$styleString.'\'');
	}

	protected function getBildWithOptions(Pageimage $bild, $options = array()) {
		if (!is_array($options)) {
			if ($options === 'original') {
				return $bild->url;
			}
			$options = array();
		}

		if (!isset($options['width']) && !isset($options['height'])) {
			$options['width'] = 100;
		}
		if (!isset($options['options'])) {
			$options['options'] = array(
				'cropping' => '',
				'cleanFilename' => true,
				'upscaling' => false
			);
		}

		if (isset($options['bild']) && $options['bild'] instanceof Pageimage) {
			$bild = $options['bild'];
		}

		if (!isset($options['height'])) {
			return $bild->width($options['width'], $options['options'])->url;
		} elseif (!isset($options['width'])) {
			return $bild->height($options['height'], $options['options'])->url;
		}
		return $bild->size($options['width'], $options['height'], $options['options'])->url;
	}

	public function getPlatzhalterbilder() {
		$bilder = new WireArray();
		$konfigurationsseite = $this->konfigurationService->getKonfigurationsseite();
		if ($konfigurationsseite->template->hasField('bilder')) {
			$bilder = $konfigurationsseite->bilder;
		}
		return $bilder;
	}

	public function getPlatzhalterbild() {
		$bilder = $this->getPlatzhalterbilder();
		if ($bilder instanceof WireArray && $bilder->count > 0) {
			return $bilder->getRandom();
		}
		return null;
	}

	public function getPlatzhalterBildTag($args = array()) {
		$args['bild'] = $this->getPlatzhalterbild();
		return $this->getBildTag($args);
	}
}
