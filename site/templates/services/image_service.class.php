<?php

namespace ProcessWire;

/**
 * Returns image elements
 */
class ImageService extends TwackComponent {
    protected $configurationService;

    public function __construct($args) {
        parent::__construct($args);
        $this->configurationService = $this->getService('ConfigurationService');
    }

    public function getImgHtml($args = array()) {
        $image = $this->getImage($args);
        if (!($image instanceof Pageimage)) {
            return '';
        }

        $attributes = array();
        $styles     = array();
        $classes    = array();

        $attributes['src'] = $image->url;
        $noscriptImageUrl  = $image->url;

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

        if ($image->ext !== 'svg') {
            if (!isset($args['default'])) {
                $args['default'] = array('width' => 1000);
            }
            $attributes['src'] = $this->getImageWithOptions($image, $args['default']);
            $noscriptImageUrl  = $attributes['src'];
        }

        if (isset($args['srcset']) && is_array($args['srcset'])) {
            $srcsetparts = array();
            foreach ($args['srcset'] as $key => $value) {
                if (empty($value)) {
                    continue;
                }
                if (is_string($value)) {
                    $srcsetparts[] = $value . ' ' . $key;
                } elseif ($image->ext !== 'svg' && is_array($value)) {
                    $srcsetparts[] = $this->getImageWithOptions($image, $value) . ' ' . $key;
                }
            }
            $attributes['srcset'] = implode(', ', $srcsetparts);
        }

        if (isset($args['sizes']) && is_array($args['sizes'])) {
            $attributes['sizes'] = $args['sizes'];
        }

        $loadAsync = true;
        if (isset($args['loadAsync']) && !$args['loadAsync']) {
            $loadAsync = false;
        }

        if($loadAsync && $image->ext === 'svg' && empty($image->placeholder_svg)){
            $loadAsync = false;
        }

        // Load a bigger image size asynchronously?
        if ($loadAsync) {
            $classes[]                        = 'lazyload';
            $classes[]                        = 'lazy-image';

            if (!empty($image->placeholder_svg)) {
                // A svg-placeholder was found:
                $attributes['data-src']                    = $attributes['src'];
                $attributes['src']                         = 'data:image/svg+xml;utf8,' . $this->svgUrlEncode($image->placeholder_svg);
            } else if ($image->ext !== 'svg') {
                // No svg-placeholder, generate low-quality version:
                $attributes['data-src']                    = $attributes['src'];
                $attributes['src']                         = $this->getImageWithOptions($image, array('width' => 100));
            }

            if (isset($attributes['srcset'])) {
                if (isset($args['outputType']) && in_array($args['outputType'], ['bg-image', 'background-image'])) {
                    $attributes['data-bgset'] = $attributes['srcset'];
                }else{
                    $attributes['data-srcset'] = $attributes['srcset'];
                }
                
                unset($attributes['srcset']);
            }

            if (isset($attributes['sizes'])) {
                $attributes['data-sizes'] = $attributes['sizes'];
                unset($attributes['sizes']);
            } else {
                $attributes['data-sizes'] = 'auto';
            }
        }

        if (isset($args['outputType']) && in_array($args['outputType'], ['bg-image', 'background-image'])) {
            $styles['background-image'] = 'url("' . $attributes['src'] . '")';
            unset($attributes['src']);
            $imgHtml                    = '<div class="' . implode(' ', $classes) . '" ' . $this->makeAttributeString($attributes) . ' ' . $this->makeStyleString($styles) . ' ></div>';
        } else {
            // $attributes['width'] = $image->width;
            // $attributes['height'] = $image->height;

            $classes[] = 'img-fluid';
            $imgHtml = '<img class="' . implode(' ', $classes) . '" ' . $this->makeAttributeString($attributes) . ' ' . $this->makeStyleString($styles) . ' />';
            
            // if (!isset($args['noscript']) || $args['noscript'] !== false) {
            //     $arContent = array_search('ar-content', $classes) !== false;
            //     if ($arContent) {
            //         unset($classes[array_search('ar-content', $classes)]);
            //     }
            //     if(array_search('lazyload', $classes) !== false){
            //         unset($classes[array_search('lazyload', $classes)]);
            //     }
            //     if(array_search('lazy-image', $classes) !== false){
            //         unset($classes[array_search('lazy-image', $classes)]);
            //     }
            //     $imgHtml = '<div class="lazyload" data-noscript=""><noscript ' . ($arContent ? 'class="ar-content"' : '') . '><img class="' . implode(' ', $classes) . '" src="' . $noscriptImageUrl . '" /></noscript></div>' . $imgHtml;
            // }
        }
        return $imgHtml;
    }

    /**
     * Returns the image as HTML tag
     * @param  array  $args
     * @return string
     */
    public function getImgHtmlOld($args = array()) {
        $image = $this->getImage($args);
        if (!($image instanceof Pageimage)) {
            return '';
        }

        $originalImageUrl = $image->url;

        $attribute = array();
        $styles    = array();
        $classes   = array();

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
                $attribute['alt'] = $args['alt'];
            } else {
                $attribute['alt'] = $image->alt;
            }
        }

        if (isset($args['caption'])) {
            $attribute['data-caption'] = $args['caption'];
        } elseif (isset($image->caption) && !empty($image->caption)) {
            $attribute['data-caption'] = $image->caption;
        }

        if (!isset($args['normal'])) {
            $args['normal'] = array('width' => 1000);
        }
        $originalImageUrl = $this->getImageWithOptions($image, $args['normal']);

        $loadAsync = true;
        if (isset($args['loadAsync']) && !$args['loadAsync']) {
            $loadAsync = false;
        }

        // Use Progressively?
        if ($loadAsync) {
            if (!isset($args['xs'])) {
                $args['xs'] = array();
            }

            if ($image->ext == 'svg' && !empty($image->placeholder_svg)) {
                $attribute['data-progressive'] = $originalImageUrl;
                $originalImageUrl              = 'data:image/svg+xml;utf8,' . $this->svgUrlEncode($image->placeholder_svg);
            } else {
                $attribute['data-progressive']    = $originalImageUrl;
                $originalImageUrl                 = $this->getImageWithOptions($image, $args['xs']);
                if (!empty($image->placeholder_svg)) {
                    $originalImageUrl = 'data:image/svg+xml;utf8,' . $this->svgUrlEncode($image->placeholder_svg);
                }
                $classes[]                     = 'progressive--not-loaded';

                if (isset($args['sm'])) {
                    if (!isset($args['sm']['width']) && !isset($args['sm']['height'])) {
                        $args['sm']['width'] = 600;
                    }
                    $attribute['data-progressive-sm'] = $this->getImageWithOptions($image, $args['sm']);
                }
            }
        }

        // Use Modal?
        if (isset($args['fullsize-modal'])) {
            if (!isset($args['fullsize-modal']['width']) && !isset($args['fullsize-modal']['height'])) {
                $args['fullsize-modal']['width'] = 1400;
            }

            $attribute['data-fullsize-modal'] = $this->getImageWithOptions($image, $args['fullsize-modal']);
            $attribute['data-fullsize']       = $image->url;
        }

        if (isset($args['outputType']) && in_array($args['outputType'], ['bg-image', 'background-image'])) {
            $styles['background-image'] = 'url("' . $originalImageUrl . '")';
            if ($loadAsync) {
                $classes[] = 'progressive__bg';
            }
            $imgHtml = '<div class="' . implode(' ', $classes) . '" ' . $this->makeAttributeString($attribute) . ' ' . $this->makeStyleString($styles) . ' ></div>';
        } else {
            $attribute['src'] = $originalImageUrl;
            // $attribute['width'] = $image->width;
            // $attribute['height'] = $image->height;

            $classes[] = 'img-fluid';
            if ($loadAsync) {
                $classes[] = 'progressive__img';
            }
            $imgHtml = '<img class="' . implode(' ', $classes) . '" ' . $this->makeAttributeString($attribute) . ' ' . $this->makeStyleString($styles) . ' />';
        }
        return $imgHtml;
    }

    /**
     * Returns the image as PageImage
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

    protected function makeAttributeString($attributArray) {
        $attributeString = '';
        foreach ($attributArray as $name => $attribut) {
            if (strlen($attributeString) >= 1) {
                $attributeString .= ' ';
            }
            $attributeString .= $name;
            if (!empty($attribut)) {
                $attributeString .= '="' . $attribut . '"';
            }
        }
        return ($attributeString);
    }

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
            return $image->width($options['width'], $options['options'])->url;
        } elseif (!isset($options['width'])) {
            return $image->height($options['height'], $options['options'])->url;
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

    public function svgUrlEncode($data) {
        $data = \preg_replace('/\v(?:[\v\h]+)/', ' ', $data);
        $data = \str_replace('"', "'", $data);
        $data = \rawurlencode($data);
        // re-decode a few characters understood by browsers to improve compression
        $data = \str_replace('%20', ' ', $data);
        $data = \str_replace('%3D', '=', $data);
        $data = \str_replace('%3A', ':', $data);
        $data = \str_replace('%2F', '/', $data);
        return $data;
    }
}
