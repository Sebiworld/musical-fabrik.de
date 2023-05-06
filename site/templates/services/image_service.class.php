<?php
namespace ProcessWire;

/**
 * Returns image elements
 */
class ImageService extends TwackComponent {
	protected $configurationService;
	protected $webpSupported = true;

	public function __construct($args) {
		parent::__construct($args);
		$this->configurationService = $this->getService('ConfigurationService');
	}

	/**
	 * Returns <img> html for an image with the given args. Automatically creates srcsets for multiple resolutions
	 */
	public function getImgHtml($args = []) {
		$output = '';

		$image = $this->getImage($args);
		if (!($image instanceof Pageimage)) {
			return '';
		}

		$attributes        = [
			'data-caption' => $this->mergeArgs('caption', $args, $image)
		];

		$attributes['alt'] = $this->mergeArgs('alt', $image, $attributes['data-caption'], $args);

		$styles            = [];
		if (isset($args['styles'])) {
			$styles = $this->getStylesArray($args['styles']);
		}

		$classes = [];
		if (isset($args['classes'])) {
			$classes = $this->getClassArray($args['classes']);
		}

		$srcset     = $this->getSrcset($image, $args);

		$src = $image->url;
		if ($image->ext !== 'svg') {
			if (!isset($args['default'])) {
				$args['default'] = ['width' => 1000];
			}
			$src = $this->getImageWithOptions($image, $args['default']);
		}

		if ($this->shouldLoadAsync($image, $args)) {
			// Load full sized image async:

			$classes[]                        = 'lazyload';
			$classes[]                        = 'lazy-image';

			$attributes['data-src'] = $src;
			$attributes['src']      = $this->getLoadingImage($image, $args);
			$attributes['class']    = implode(' ', $classes);

			if (!empty($srcset)) {
				$attributes['data-srcset'] = implode(', ', $srcset);
			}

			return '<img ' . $this->makeAttributeString($attributes) . ' ' . $this->makeStyleString($styles) . ' />';
		}

		$attributes['src']      = $src;
		$attributes['class']    = implode(' ', $classes);

		if (!empty($srcset)) {
			$attributes['srcset'] = implode(', ', $srcset);
		}

		return '<img ' . $this->makeAttributeString($attributes) . ' ' . $this->makeStyleString($styles) . ' />';
	}

	/**
	 * Returns <picture> html for an image with the given args. Automatically creates srcsets for multiple resolutions and webp alternatives
	 */
	public function getPictureHtml($args = []) {
		$output = '';

		$image = $this->getImage($args);
		if (!($image instanceof Pageimage)) {
			return '';
		}

		$attributes = [];
		if (isset($args['attributes'])) {
			$attributes = $this->getClassArray($args['attributes']);
		}
		$attributes['data-caption'] = $this->mergeArgs('caption', $args, $image);
		$attributes['alt'] = $this->mergeArgs('alt', $image, $attributes['data-caption'], $args);

		$styles            = [];
		if (isset($args['styles'])) {
			$styles = $this->getStylesArray($args['styles']);
		}

		$classes = [];
		if (isset($args['classes'])) {
			$classes = $this->getClassArray($args['classes']);
		}

		$pictureclasses = [];
		if (isset($args['pictureclasses'])) {
			$pictureclasses = $this->getClassArray($args['pictureclasses']);
		}

		$medias = $this->getMedia($args);

		$src = $image->url;
		if ($image->ext !== 'svg') {
			if (!isset($args['default'])) {
				$args['default'] = ['width' => 1000];
			}
			$src = $this->getImageWithOptions($image, $args['default']);
		}

		// Add Default-image as last media-option:
		$medias[] = $args['default'];

		if ($this->shouldLoadAsync($image, $args)) {
			// Load full sized image async:

			$classes[]                        = 'lazyload';
			$classes[]                        = 'lazy-image';

			// Only generate different sizes if the image is no svg:
			if ($image->ext !== 'svg') {
				foreach ($medias as $mediaquery => $options) {
					$mediastring = '';
					if (!empty($mediaquery) && is_string($mediaquery)) {
						$mediastring = 'media="' . $mediaquery . '"';
					}

					if ($this->isWebpSupported($image)) {
						$webpsrcset = $this->getSrcset($image, $options, true);

						if (!empty($webpsrcset)) {
							$output .= '<source ' . $mediastring . ' srcset="' . $this->getLoadingImage($image, $options, true, 'webp') . ' 1x" data-srcset="' . implode(', ', $webpsrcset) . '" type="image/webp">';
						}
					}

					$srcset     = $this->getSrcset($image, $options);
					if (!empty($srcset)) {
						$output .= '<source ' . $mediastring . ' srcset="' . $this->getLoadingImage($image, $options, true, 'jpg') . ' 1x" data-srcset="' . implode(', ', $srcset) . '" type="image/jpeg">';
					}
				}
			}

			$attributes['data-src'] = $src;
			$attributes['src']      = $this->getLoadingImage($image, $args);
			$attributes['class']    = implode(' ', $classes);

			$output .= '<img ' . $this->makeAttributeString($attributes) . ' ' . $this->makeStyleString($styles) . ' />';

			// Wrap everything in a <picture> element:
			$output = '<picture class="' . implode(' ', $pictureclasses) . '">' . $output;
			$output .= '</picture>';

			return $output;
		}

		// Only generate different sizes if the image is no svg:
		if ($image->ext !== 'svg') {
			foreach ($medias as $mediaquery => $options) {
				if ($this->isWebpSupported($image)) {
					$webpsrcset = $this->getSrcset($image, $options, true);

					if (!empty($webpsrcset)) {
						$output .= '<source srcset="' . implode(', ', $webpsrcset) . '" type="image/webp">';
					}
				}

				$srcset     = $this->getSrcset($image, $options);
				if (!empty($srcset)) {
					$output .= '<source srcset="' . implode(', ', $srcset) . '" type="image/jpeg">';
				}
			}
		}

		$attributes['src']      = $src;
		$attributes['class']    = implode(' ', $classes);

		$output .= '<img ' . $this->makeAttributeString($attributes) . ' ' . $this->makeStyleString($styles) . ' />';

		// Wrap everything in a <picture> element:
		$output = '<picture class="' . implode(' ', $pictureclasses) . '">' . $output;
		$output .= '</picture>';

		return $output;
	}

	/**
	 * Check if server can build webp images
	 */
	protected function isWebpSupported(Pageimage $image) {
		if (isset(wire('config')->webpSupported) && !wire('config')->webpSupported) {
			return false;
		}

		if ($image->ext === 'svg') {
			return false;
		}

		if ($image->url === $image->webp->url) {
			return false;
		}

		return true;
	}

	protected function shouldLoadAsync(Pageimage $image, $args = []) {
		if (isset($args['loadAsync']) && !$args['loadAsync']) {
			return false;
		}

		if ($image->ext === 'svg') {
			return false;
		}

		return true;
	}

	/**
	 * Encodes a svg-string for use in image-src
	 */
	protected function svgUrlEncode($data) {
		$data = \preg_replace('/\v(?:[\v\h]+)/', ' ', $data);
		$data = \str_replace('"', "'", $data);
		$data = \rawurlencode($data);
		// re-decode a few characters understood by browsers to improve compression
		// $data = \str_replace('%20', ' ', $data);
		$data = \str_replace('%3D', '=', $data);
		$data = \str_replace('%3A', ':', $data);
		$data = \str_replace('%2F', '/', $data);
		return $data;
	}

	protected function mergeArgs() {
		if (!func_num_args()) {
			return null;
		}

		$args      = func_get_args();
		if (!is_array($args) || count($args) <= 1) {
			return null;
		}
		$key = array_shift($args);

		foreach ($args as $options) {
			if ($options instanceof Pageimage && isset($options->{$key}) && !empty($options->{$key})) {
				return $options->{$key};
			} elseif (is_array($options) && isset($options[$key])) {
				return $options[$key];
			} elseif (is_string($options) && strlen($options) > 0) {
				return $options;
			}
		}

		return null;
	}

	/**
	 * Exctracts class-information from a variable
	 */
	protected function getClassArray($classes) {
		if (is_array($classes)) {
			$output = [];
			foreach ($classes as $cssclass) {
				if (!is_string($cssclass)) {
					continue;
				}
				$parts = explode(' ', $cssclass);
				if (count($parts) < 1) {
					continue;
				}
				if (count($parts) === 1) {
					$output[] = $cssclass;
					continue;
				}

				// Mutliple classes found, add each individually
				foreach ($parts as $part) {
					$output[] = $part;
				}
			}
			return array_unique($output);
		}

		if (is_string($classes)) {
			return array_unique(explode(' ', $classes));
		}
		return [];
	}

	/**
	 * Exctracts css-style-information from a variable
	 */
	protected function getStylesArray($styles) {
		if (is_array($styles)) {
			return $styles;
		}

		if (is_string($styles)) {
			$output     = [];
			$styleparts = explode(';', $styles);
			foreach ($styleparts as $stylepart) {
				$style = explode(':', $stylepart);
				if (count($style) < 2) {
					continue;
				}
				if (empty(trim($style[0])) || empty(trim($style[1]))) {
					continue;
				}
				$output[trim($style[0])] = trim($style[1]);
			}
		}

		return [];
	}

	/**
	 * Combines an array of css-style-properties to a string that can be used in html
	 */
	protected function makeStyleString($styleArray) {
		if (count($styleArray) < 1) {
			return '';
		}
		$styleString = '';
		foreach ($styleArray as $name => $style) {
			$styleString .= $name . ': ' . $style . '; ';
		}
		return ('style=\'' . $styleString . '\'');
	}

	/**
	 * Combines an array of attribute-properties to a string that can be used in html
	 */
	protected function makeAttributeString($attributArray) {
		$output = '';
		foreach ($attributArray as $name => $value) {
			if ($value === null) {
				continue;
			}

			if (strlen($output) >= 1) {
				$output .= ' ';
			}

			$output .= $name;
			if ($value !== '') {
				$output .= '="' . $value . '"';
			}
		}
		return ($output);
	}

	/**
	 * Retrieves a small image that can be shown while the full sized image is been loaded
	 */
	protected function getLoadingImage($image, $args = [], $inline = false, $ext = false) {
		if ($ext === 'webp') {
			$args['webp'] = true;
		}

		if ($image instanceof Pageimage) {
			if ($inline) {
				if (is_array($args) && isset($args['default']['width']) && isset($args['default']['height'])) {
					// Width and height are set -> preserve aspect ratio:
					return $this->getImageWithOptions($image, ['width' => 10, 'height' => round($args['default']['width'] / $args['default']['height']) * 10]);
				} else {
					return $this->getImageWithOptions($image, ['width' => 10]);
				}
			} else {
				if (is_array($args) && isset($args['default']['width']) && isset($args['default']['height'])) {
					// Width and height are set -> preserve aspect ratio:
					return $this->getImageWithOptions($image, ['width' => 100, 'height' => round($args['default']['width'] / $args['default']['height']) * 100]);
				} else {
					return $this->getImageWithOptions($image, ['width' => 100]);
				}
			}
		}

		// Empty images in different formats::
		if ($ext === 'png') {
			return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVQYV2NgYAAAAAMAAWgmWQ0AAAAASUVORK5CYII=';
		} elseif ($ext === 'jpg' || $ext === 'jpeg') {
			return 'data:image/jpeg;base64,/9j/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wAALCAABAAEBAREA/8QAFAABAAAAAAAAAAAAAAAAAAAAA//EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AN//Z';
		} elseif ($ext === 'webp') {
			return 'data:image/webp;base64,UklGRh4AAABXRUJQVlA4TBEAAAAvAAAAAAfQ//73v/+BiOh/AAA=';
		}

		return 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACwAAAAAAQABAAACAkQBADs=';
	}

	protected function getSrcset(Pageimage $image, $args = [], $webp = false) {
		if ((empty($args['width']) || !is_integer($args['width'])) && (empty($args['height']) || !is_integer($args['height']))) {
			return [];
		}

		if ($webp) {
			$args['webp'] = true;
		}

		return [
			$this->getImageWithOptions($image, $args) . ' 1x',
			$this->getImageWithOptions($image, $this->getMultipliedSizeoptions($args, 2)) . ' 2x'
		];
	}

	protected function getMultipliedSizeoptions($args, $factor) {
		if (!is_array($args)) {
			return [];
		}

		if (!is_numeric($factor)) {
			return $args;
		}

		if (!empty($args['width']) && is_numeric($args['width'])) {
			$args['width'] = $args['width'] * $factor;
		}
		if (!empty($args['height']) && is_numeric($args['height'])) {
			$args['height'] = $args['height'] * $factor;
		}

		return $args;
	}

	/**
	 * Returns an array with media-attributes for generating different <source>-Tags in a picture element.
	 */
	protected function getMedia($args = []) {
		if (!is_array($args) || !isset($args['media']) || !is_array($args['media'])) {
			return [];
		}

		// Only allow valid values:
		$output = [];
		foreach ($args['media'] as $key => $value) {
			if (empty($key) || !is_string($key)) {
				continue;
			}

			if (empty($value) || !is_array($value)) {
				continue;
			}

			if ((empty($value['width']) || !is_integer($value['width'])) && (empty($value['height']) || !is_integer($value['height']))) {
				continue;
			}

			$output[$key] = $value;
		}
		return $output;
	}

	/**
	 * Returns an image as PageImage.
	 * @param  array  $args
	 * @return PageImage
	 */
	public function getImage($args = []) {
		if (!is_array($args)) {
			return null;
		}

		if (isset($args['image']) && $args['image'] instanceof Pageimage) {
			return $args['image'];
		}

		if (!isset($args['page']) || !($args['page'] instanceof Page) || !$args['page']->id) {
			$args['page'] = $this->page;
		}
		if (!isset($args['field'])) {
			return '';
		}

		$fieldValue = $args['page']->get($args['field']);
		$image      = $fieldValue;
		if ($fieldValue instanceof Pageimages) {
			// Several pictures were delivered
			$image = $image->getRandom();
			if (isset($args['selectBy'])) {
				if ($args['selectBy'] == 'first') {
					$image = $fieldValue->first();
				} elseif ($args['selectBy'] == 'last') {
					$image = $fieldValue->last();
				} elseif (is_numeric($args['selectBy'])) {
					$image = $fieldValue->get($args['selectBy']);
				}
			}
		}

		return $image;
	}

	/**
	 * Returns a pageimage with options
	 */
	protected function getImageWithOptions(Pageimage $image, $options = []) {
		if (!is_array($options)) {
			if ($options === 'original') {
				return $image->url;
			}
			$options = [];
		}

		if (!isset($options['width']) && !isset($options['height'])) {
			$options['width'] = 100;
		}
		if (!isset($options['options'])) {
			$options['options'] = [
				'cropping'      => true,
				'cleanFilename' => true,
				// 'forceNew' => true,
				// 'upscaling'     => false
			];
		}

		if (isset($options['image']) && $options['image'] instanceof Pageimage) {
			$image = $options['image'];
		}

		if (!isset($options['height'])) {
			if (isset($options['webp']) && $options['webp']) {
				return $image->width($options['width'], $options['options'])->webp->url;
			}
			return $image->width($options['width'], $options['options'])->url;
		} elseif (!isset($options['width'])) {
			if (isset($options['webp']) && $options['webp']) {
				return $image->height($options['height'], $options['options'])->webp->url;
			}
			return $image->height($options['height'], $options['options'])->url;
		}

		if (isset($options['webp']) && $options['webp']) {
			return $image->size($options['width'], $options['height'], $options['options'])->webp->url;
		}
		return $image->size($options['width'], $options['height'], $options['options'])->url;
	}

	public function getPlaceholderImages() {
		$images              = new WireArray();
		$configPage          = $this->configurationService->getConfigurationPage();
		if ($configPage->template->hasField('images')) {
			$images = $configPage->images;
		}
		return $images;
	}

	/**
	 * Returns a placeholder-image. If multiple images are configured it returns a random one.
	 */
	public function getPlaceholderImage() {
		$images = $this->getPlaceholderImages();
		if ($images instanceof WireArray && $images->count > 0) {
			return $images->getRandom();
		}
		return null;
	}

	public function getPlaceholderImageHtml($args = []) {
		$args['image'] = $this->getPlaceholderImage();
		return $this->getImgHtml($args);
	}

	public function getPlaceholderPictureHtml($args = []) {
		$args['image'] = $this->getPlaceholderImage();
		return $this->getPictureHtml($args);
	}
}
