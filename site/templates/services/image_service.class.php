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

    public function getImgHtml($args = array()) {
        $image = $this->getImage($args);
        if (!($image instanceof Pageimage)) {
            return '';
        }

        $attributes        = array();
        $styles            = array();
        $classes           = array();
        $pictureclasses    = array();

        $src                 = $image->url;
        $this->webpSupported = true;
        // Check if server can build webp images:
        if ($image->ext !== 'svg' && isset($args['outputType']) && $args['outputType'] === 'picture' && $src === $image->webp->url) {
            $this->webpSupported = false;
        }

        if (isset($args['classes'])) {
            if (is_array($args['classes'])) {
                $classes = $args['classes'];
            } elseif (is_string($args['classes'])) {
                $classes = explode(' ', $args['classes']);
            }
        }
        if (isset($args['pictureclasses'])) {
            if (is_array($args['pictureclasses'])) {
                $pictureclasses = $args['pictureclasses'];
            } elseif (is_string($args['pictureclasses'])) {
                $pictureclasses = explode(' ', $args['pictureclasses']);
            }
        }

        if (isset($args['styles'])) {
            if (is_array($args['styles'])) {
                $styles = $args['styles'];
            } elseif (is_string($args['styles'])) {
                $styleparts = explode(';', $args['styles']);
                foreach ($styleparts as $stylepart) {
                    $style = explode(':', $stylepart);
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

        if (!isset($args['outputType']) || !in_array($args['outputType'], ['bg-image', 'background-image'])) {
            if (isset($args['alt'])) {
                $attributes['alt'] = $args['alt'];
            } else {
                $attributes['alt'] = $image->alt;
            }
        }

        if (isset($args['caption'])) {
            $attributes['data-caption'] = $args['caption'];
        } elseif (isset($image->caption) && !empty($image->caption)) {
            $attributes['data-caption'] = $image->caption;
        }

        $loadAsync = true;
        if (isset($args['loadAsync']) && !$args['loadAsync']) {
            $loadAsync = false;
        }

        if ($loadAsync && $image->ext === 'svg' && empty($image->placeholder_svg)) {
            $loadAsync = false;
        }

        if (isset($args['sizes']) && is_array($args['sizes'])) {
            $attributes['sizes'] = $args['sizes'];
        }

        if ($image->ext !== 'svg') {
            if (!isset($args['default'])) {
                $args['default'] = array('width' => 1000);
            }
            $src = $this->getImageWithOptions($image, $args['default']);
        }

        $srcset     = array();
        $webpsrcset = array();
        if (isset($args['srcset']) && is_array($args['srcset'])) {
            foreach ($args['srcset'] as $key => $value) {
                if (empty($value)) {
                    continue;
                }
                if (is_string($value)) {
                    $srcset[] = $value . ' ' . $key;
                    if ($this->webpSupported && isset($args['outputType']) && $args['outputType'] === 'picture') {
                        $webpsrcset[] = $value . ' ' . $key;
                    }
                } elseif ($image->ext !== 'svg' && is_array($value)) {
                    $srcset[] = $this->getImageWithOptions($image, $value) . ' ' . $key;
                    if ($this->webpSupported && isset($args['outputType']) && $args['outputType'] === 'picture') {
                        $webpsrcset[] = $this->getImageWithOptions($image, array_merge($value, ['webp' => true])) . ' ' . $key;
                    }
                }
            }
        }

        $imgHtml = '';
        if (isset($args['outputType']) && in_array($args['outputType'], ['bg-image', 'background-image'])) {
            // Get image as background of a div element:

            // Load a bigger image size asynchronously?
            if ($loadAsync) {
                $classes[]                        = 'lazyload';
                $classes[]                        = 'lazy-image';

                $attributes['data-bgset'] = implode(', ', $srcset);

                if (isset($attributes['sizes'])) {
                    $attributes['data-sizes'] = $attributes['sizes'];
                    unset($attributes['sizes']);
                } else {
                    $attributes['data-sizes'] = 'auto';
                }
            }

            $styles['background-image'] = 'url("' . $src . '")';
            $imgHtml                    = '<div class="' . implode(' ', $classes) . '" ' . $this->makeAttributeString($attributes) . ' ' . $this->makeStyleString($styles) . ' ></div>';
        } elseif (isset($args['outputType']) && $args['outputType'] === 'picture') {
            // Get image as <picture> element with webp-support

            $classes[] = 'img-fluid';

            $placeholder = 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
            if ($loadAsync) {
                if (!empty($image->placeholder_svg)) {
                    // There is a svg-placeholder defined
                    $placeholder = 'data:image/svg+xml;utf8,' . $this->svgUrlEncode($image->placeholder_svg);
                } elseif (isset($args['default']['width']) && isset($args['default']['height'])) {
                    // Width and height are set -> preserve aspect ratio:
                    $placeholder = $this->getImageWithOptions($image, array('width' => 100, 'height' => round($args['default']['width'] / $args['default']['height']) * 100));
                } else {
                    $placeholder = $this->getImageWithOptions($image, array('width' => 100));
                }
            }

            if (!empty($webpsrcset)) {
                if ($loadAsync) {
                    $imgHtml .= '<source srcset="' . $placeholder . '" data-srcset="' . implode(', ', $webpsrcset) . '" type="image/webp">';
                } else {
                    $imgHtml .= '<source srcset="' . implode(', ', $webpsrcset) . '" type="image/webp">';
                }
            }

            if (!empty($srcset)) {
                if ($loadAsync) {
                    $imgHtml .= '<source srcset="' . $placeholder . '" data-srcset="' . implode(', ', $srcset) . '" type="image/jpeg">';
                } else {
                    $imgHtml .= '<source srcset="' . implode(', ', $srcset) . '" type="image/jpeg">';
                }
            }

            // Load a bigger image size asynchronously?
            if ($loadAsync) {
                $classes[]                        = 'lazyload';
                $classes[]                        = 'lazy-image';

                $attributes['data-src']                    = $src;
                $attributes['src']                         = $placeholder;

                $imgHtml .= '<img class="' . implode(' ', $classes) . '" ' . $this->makeAttributeString($attributes) . ' ' . $this->makeStyleString($styles) . ' />';
            } else {
                $attributes['src'] = $src;
                $imgHtml .= '<img class="' . implode(' ', $classes) . '" />';
            }

            // Wrap everything in a <picture> element:
            $imgHtml = '<picture class="' . implode(' ', $pictureclasses) . '">' . $imgHtml;
            $imgHtml .= '</picture>';
        } else {
            // Get image as a <img> element
            $classes[] = 'img-fluid';

            // Load a bigger image size asynchronously?
            if ($loadAsync) {
                $classes[]                        = 'lazyload';
                $classes[]                        = 'lazy-image';

                if (!empty($image->placeholder_svg)) {
                    // A svg-placeholder was found:
                    $attributes['data-src']                    = $src;
                    $attributes['src']                         = 'data:image/svg+xml;utf8,' . $this->svgUrlEncode($image->placeholder_svg);
                } elseif ($image->ext !== 'svg') {
                    // No svg-placeholder, generate low-quality version:
                    $attributes['data-src']                    = $src;
                    $attributes['src']                         = $this->getImageWithOptions($image, array('width' => 100));
                }

                $attributes['data-srcset'] = implode(', ', $srcset);

                if (isset($attributes['sizes'])) {
                    $attributes['data-sizes'] = $attributes['sizes'];
                    unset($attributes['sizes']);
                } else {
                    $attributes['data-sizes'] = 'auto';
                }
            } else {
                $attributes['src']    = $src;
                $attributes['srcset'] = implode(', ', $srcset);
            }

            $imgHtml = '<img class="' . implode(' ', $classes) . '" ' . $this->makeAttributeString($attributes) . ' ' . $this->makeStyleString($styles) . ' />';
        }
        return $imgHtml;
    }

    public function getPictureHtml($args = array()) {
        $output = '';

        $image = $this->getImage($args);
        if (!($image instanceof Pageimage)) {
            return '';
        }

        $attributes        = array(
            'alt' => $this->getFromArgsOrFromImage('alt', $args, $image),
            'data-caption' => $this->getFromArgsOrFromImage('caption', $args, $image)
        );

        $styles            = array();
        if (isset($args['styles'])) {
            $styles = $this->getStylesArray($args['styles']);
        }

        $classes = array();
        if (isset($args['classes'])) {
            $classes = $this->getClassArray($args['classes']);
        }

        $pictureclasses = array();
        if (isset($args['pictureclasses'])) {
            $pictureclasses = $this->getClassArray($args['pictureclasses']);
        }

        $srcset     = $this->getSrcset($image, $args);
        $webpsrcset = array();
        if ($this->isWebpSupported($image)) {
            $webpsrcset = $this->getSrcset($image, $args, true);
        }

        $src = $image->url;
        if ($image->ext !== 'svg') {
            if (!isset($args['default'])) {
                $args['default'] = array('width' => 1000);
            }
            $src = $this->getImageWithOptions($image, $args['default']);
        }

        if ($this->shouldLoadAsync($image, $args)) {
            // Load full sized image async:

            $classes[]                        = 'lazyload';
            $classes[]                        = 'lazy-image';
            $attributes['data-sizes'] = $this->getFromArgsOrFromImage('sizes', $args, $image);

            $loadingimage = $this->getLoadingImage($image, $args);

            if (!empty($webpsrcset) && $image->ext !== 'svg') {
                $output .= '<source srcset="' . $this->getLoadingImage($image, $args, true, 'webp') . ' 1x" data-srcset="' . implode(', ', $webpsrcset) . '" type="image/webp">';
            }
            if (!empty($srcset)  && $image->ext !== 'svg') {
                $output .= '<source srcset="' . $this->getLoadingImage($image, $args, true, 'jpeg') . ' 1x" data-srcset="' . implode(', ', $srcset) . '" type="image/jpeg">';
            }

            $attributes['data-src'] = $src;
            $attributes['src']      = $loadingimage;
            $attributes['class']    = implode(' ', $classes);

            $output .= '<img ' . $this->makeAttributeString($attributes) . ' ' . $this->makeStyleString($styles) . ' />';

            // Wrap everything in a <picture> element:
            $output = '<picture class="' . implode(' ', $pictureclasses) . '">' . $output;
            $output .= '</picture>';

            return $output;
        }

        $attributes['sizes'] = $this->getFromArgsOrFromImage('sizes', $args, $image);

        if (!empty($webpsrcset) && $image->ext !== 'svg') {
            $output .= '<source srcset="' . implode(', ', $webpsrcset) . '" type="image/webp">';
        }

        if (!empty($srcset) && $image->ext !== 'svg') {
            $output .= '<source srcset="' . implode(', ', $srcset) . '" type="image/jpeg">';
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
        if ($image->ext === 'svg') {
            return false;
        }

        if ($image->url === $image->webp->url) {
            return false;
        }

        return true;
    }

    protected function shouldLoadAsync(Pageimage $image, $args = array()) {
        if (isset($args['loadAsync']) && !$args['loadAsync']) {
            return false;
        }

        if ($image->ext === 'svg' && empty($image->placeholder_svg)) {
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

    protected function getFromArgsOrFromImage($key, $args, $image) {
        if (is_array($args) && isset($args[$key])) {
            return $args[$key];
        }
        if ($image instanceof Pageimage && isset($image->{$key}) && !empty($image->{$key})) {
            return $image->{$key};
        }
        return null;
    }

    /**
     * Exctracts class-information from a variable
     */
    protected function getClassArray($classes) {
        if (is_array($classes)) {
            $output = array();
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
        return array();
    }

    /**
     * Exctracts css-style-information from a variable
     */
    protected function getStylesArray($styles) {
        if (is_array($styles)) {
            return $styles;
        }

        if (is_string($styles)) {
            $output     = array();
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

        return array();
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
            if ($value === NULL) {
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
    protected function getLoadingImage($image, $args = array(), $inline = false, $ext = false) {
        if ($image instanceof Pageimage) {
            if ((!$ext || $ext === 'svg') && !empty($image->placeholder_svg)) {
                // A svg-placeholder is defined
                return 'data:image/svg+xml;utf8,' . $this->svgUrlEncode($image->placeholder_svg);
            } elseif (!$inline && is_array($args) && isset($args['default']['width']) && isset($args['default']['height'])) {
                // Width and height are set -> preserve aspect ratio:
                return $this->getImageWithOptions($image, array('width' => 100, 'height' => round($args['default']['width'] / $args['default']['height']) * 100));
            } else if(!$inline){
                return $this->getImageWithOptions($image, array('width' => 100));
            }
        }

        if($ext === 'png'){
            return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVQYV2NgYAAAAAMAAWgmWQ0AAAAASUVORK5CYII=';
        }else if($ext === 'jpg' || $ext === 'jpeg'){
            return 'data:image/jpeg;base64,/9j/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wAALCAABAAEBAREA/8QAFAABAAAAAAAAAAAAAAAAAAAAA//EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AN//Z';
        }else if($ext === 'webp'){
            return 'data:image/webp;base64,UklGRh4AAABXRUJQVlA4TBEAAAAvAAAAAAfQ//73v/+BiOh/AAA=';
        }

        // If no image could be generated: return empty gif:
        return 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACwAAAAAAQABAAACAkQBADs=';
    }

    protected function getSrcset(Pageimage $image, $args = array(), $webp = false) {
        if (!is_array($args) || !isset($args['srcset']) || !is_array($args['srcset'])) {
            return array();
        }

        $output = array();
        foreach ($args['srcset'] as $key => $value) {
            if (empty($value)) {
                continue;
            }
            if (is_string($value)) {
                $output[] = $value . ' ' . $key;
            } elseif ($webp && $image->ext !== 'svg' && is_array($value)) {
                $output[] = $this->getImageWithOptions($image, array_merge($value, ['webp' => true])) . ' ' . $key;
            } elseif ($image->ext !== 'svg' && is_array($value)) {
                $output[] = $this->getImageWithOptions($image, $value) . ' ' . $key;
            }
        }
        return $output;
    }

    /**
     * Returns an image as PageImage.
     * @param  array  $args
     * @return PageImage
     */
    public function getImage($args = array()) {
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
    protected function getImageWithOptions(Pageimage $image, $options = array()) {
        if (!is_array($options)) {
            if ($options === 'original') {
                return $image->url;
            }
            $options = array();
        }

        if (!isset($options['width']) && !isset($options['height'])) {
            $options['width'] = 100;
        }
        if (!isset($options['options'])) {
            $options['options'] = array(
                'cropping'      => true,
                'cleanFilename' => true,
                // 'forceNew' => true,
                // 'upscaling'     => false
            );
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

    public function getPlaceholderImageHtml($args = array()) {
        $args['image'] = $this->getPlaceholderImage();
        return $this->getImgHtml($args);
    }

    public function getPlaceholderPictureHtml($args = array()) {
        $args['image'] = $this->getPlaceholderImage();
        return $this->getPictureHtml($args);
    }
}
