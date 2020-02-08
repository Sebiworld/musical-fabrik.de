<?php

namespace ProcessWire;

class SectionPagesGrid extends TwackComponent {
    public function __construct($args) {
        parent::__construct($args);

        $this->imageService = $this->getService('ImageService');

        // Determine the ID of the one-page section:
        $this->sectionId = '';
        if ((string) $this->page->section_name) {
            $this->sectionId = (string) $this->page->section_name;
        }

        // The title can be set by $args or by field "title":
        if (isset($args['title'])) {
            $this->title = $args['title'];
        } elseif ($this->page->template->hasField('title') && !empty($this->page->title)) {
            $this->title = $this->page->title;
        }

        if ($this->page->template->hasField('contents')) {
            $this->contents = $this->addComponent('ContentsComponent', ['directory' => '', 'page' => $this->page]);
        }

        // If no special field was passed: Use field name "page_references"
        if (!isset($args['useField'])) {
            $args['useField'] = 'page_references';
        }

        if (!$this->page->template->hasField($args['useField'])) {
            throw new ComponentNotInitializedException('SectionPagesGrid', $this->_('No usable page field was passed.'));
        }

        $this->pages = new PageArray();

        $this->cardClasses = '';
        if ($this->page->template->hasField('card_overlay') && $this->page->card_overlay && $this->page->card_overlay->title) {
            $this->cardClasses = 'overlay ' . $this->page->card_overlay->title;
        }

        $this->imageRatio = '1-1';
        if ($this->page->template->hasField('image_ratio') && $this->page->image_ratio && $this->page->image_ratio->title) {
            $this->imageRatio = $this->page->image_ratio->title;
        }

        $this->imageFactor = 1;
        $ratioParts        = explode('-', $this->imageRatio);
        if (is_array($ratioParts) && count($ratioParts) === 2) {
            $this->imageFactor = floatval($ratioParts[1]) / floatval($ratioParts[0]);
        }

        $this->determineGridClasses();
        $this->importField($this->page->fields->get($args['useField']));
    }

    protected function determineGridClasses() {
        // Determine bootstrap grid string:
        $this->cardSize = '3';
        if ($this->page->template->hasField('card_size') && $this->page->card_size && $this->page->card_size->title) {
            $this->cardSize = $this->page->card_size->title;
        }

        $this->gridClasses = '';
        $cardSizes         = [1, 2, 3, 4, 6, 12];
        $bootstrapSizes    = ['xl', 'lg', 'md', 'sm', 'xs'];

        foreach ($cardSizes as $key => $size) {
            if ($size < $this->cardSize) {
                unset($cardSizes[$key]);
                continue;
            }
            break;
        }

        foreach ($bootstrapSizes as $key => $bootstrapSize) {
            if($bootstrapSize === 'md' && count($cardSizes) <= 1){
                $size = 6;
            }else{
                $size = array_shift($cardSizes);
            }
            
            if ($size === null) {
                break;
            }

            if (!empty($this->gridClasses)) {
                $this->gridClasses = ' ' . $this->gridClasses;
            }

            if($bootstrapSize === 'xs'){
                $this->gridClasses = 'col-' . $size . $this->gridClasses;
            }else{
                $this->gridClasses = 'col-' . $bootstrapSize . '-' . $size . $this->gridClasses;
            }
            

            unset($bootstrapSizes[$key]);
        }
    }

    protected function importField(Field $feld) {
        if ($feld->type instanceof FieldtypeMulti) {
            $values = $this->page->get($feld->name);
            if ($values instanceof PageArray) {
                $this->addPages($values);
            } elseif ($values instanceof Page) {
                $array = new PageArray();
                $array->add($values);
                $this->addPages($array);
            } else {
                Twack::devEcho('SectionPagesGrid->importField() could not read output pages.');
            }
        }
    }

    /**
     * Adds an array of pages
     * @param  PageArray $pages
     */
    protected function addPages(PageArray $pages) {
        foreach ($pages as $page) {
            $this->addPage($page);
        }
    }

    /**
     * Adds a single page
     * @param  Page   $page
     */
    protected function addPage(Page $page) {
        // Twack::devEcho($page->name, $page->viewable());
        if (!$page->viewable()) {
            return false;
        }

        if ($page->hasField('logo_square')) {
            $page->gridImage = $page->logo_square;
        } else {
            $page->gridImage = $page->main_image;
        }

        if ($page->hasField('short_description')) {
            $page->desctext = $page->short_description;
        } elseif ($page->hasField('freetext')) {
            $page->desctext = $page->freetext;
        }

        $this->pages->add($page);
        return $page;
    }
}
