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

    /**
     * Returns the image as HTML tag
     * @param  array  $args
     * @return string
     */
    public function getImgHtml($args = array()) {
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

        $useProgressively = true;
        if (isset($args['useProgressively']) && !$args['useProgressively']) {
            $useProgressively = false;
        }

        // Use Progressively?
        if ($useProgressively) {
            if (!isset($args['xs'])) {
                $args['xs'] = array();
            }

            $attribute['data-progressive'] = $originalImageUrl;
            $originalImageUrl                 = $this->getImageWithOptions($image, $args['xs']);
            $classes[]                     = 'progressive--not-loaded';

            if (isset($args['sm'])) {
                if (!isset($args['sm']['width']) && !isset($args['sm']['height'])) {
                    $args['sm']['width'] = 600;
                }
                $attribute['data-progressive-sm'] = $this->getImageWithOptions($image, $args['sm']);
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
            if ($useProgressively) {
                $classes[] = 'progressive__bg';
            }
            $imgHtml = '<div class="' . implode(' ', $classes) . '" ' . $this->makeAttributeString($attribute) . ' ' . $this->makeStyleString($styles) . ' ></div>';
        } else {
            $attribute['src'] = $originalImageUrl;
            // $attribute['width'] = $image->width;
            // $attribute['height'] = $image->height;

            $classes[] = 'img-fluid';
            if ($useProgressively) {
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
        if (!isset($args['feld'])) {
            return '';
        }

        $fieldValue = $args['page']->get($args['feld']);
        $image     = $fieldValue;
        if ($fieldValue instanceof Pageimages) {
            // Several pictures were delivered
            $image = $image->getRandom();
            if (isset($args['selectBy'])) {
                if ($args['selectBy'] == 'first') {
                    $image = $fieldValue->first();
                } elseif ($args['selectBy'] == 'last') {
                    $image = $fieldValue->last();
                }elseif (is_numeric($args['selectBy'])) {
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
                'cropping'      => '',
                'cleanFilename' => true,
                'upscaling'     => false
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
        $configPage = $this->configurationService->getConfigurationPage();
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
}
