<?php

namespace ProcessWire;

class PagesService extends TwackComponent {

    public function __construct($args) {
        parent::__construct($args);
        $this->projectPage = $this->getService('ProjectService')->getProjectPageWithFallback();
    }

    /**
     * Returns all pages that can be output on this page.
     * @return PageArray
     */
    public function getResults($args = array(), $selector = array()) {
        $output   = new \StdClass();
        $results  = new PageArray();

        if ($this->projectPage instanceof Page && $this->projectPage->id && $this->getService('ProjectService')->isProjectPage($this->projectPage)) {
            // If project page: Include only sub-pages of the project
            $selector[] = ['has_parent', $this->projectPage->id];
        }

        if (isset($args['sort'])) {
            $selector[] = ['sort', $args['sort']];
        } else {
            $selector[] = ['sort', '-datetime_from'];
        }

        // Filtering by keywords:
        if (isset($args['tags'])) {
            if (is_string($args['tags'])) {
                $args['tags'] = explode(',', $args['tags']);
            }

            if (is_array($args['tags'])) {
                $selector[] = ['tags', $args['tags']];
            }
        }

        // Filtering by free text:
        if (isset($args['query'])) {
            if (is_string($args['query'])) {
                $selector[] = ['title|name|intro|contents.text', '%=', $args['query'], 'text'];
            }
        }

        $results = $this->wire('pages')->find($selector);

        // Store original number of articles without limit:
        $output->totalNumber = $results->count;

        // The index of the last element:
        $output->lastElementIndex = 0;

        if (isset($args['start'])) {
            $selector[]                  = ['start', '=', $args['start'], 'int'];
            $output->lastElementIndex    = intval($args['start']);
        } elseif (isset($args['offset'])) {
            $selector[]                  = ['start', '=', $args['offset'], 'int'];
            $output->lastElementIndex    = intval($args['offset']);
        } else {
            $selector[] = ['start', 0];
        }

        if (isset($args['limit']) && $args['limit'] >= 0) {
            $selector[]                  = ['limit', '=', $args['limit'], 'int'];
            $output->lastElementIndex    = $output->lastElementIndex + intval($args['limit']);
        } elseif (!isset($args['limit'])) {
            $selector[]                  = ['limit', 12];
            $output->lastElementIndex    = $output->lastElementIndex + 12;
        }

        $results = $this->wire('pages')->find($selector);

        // Are there any more posts that can be downloaded?
        $output->moreAvailable = $output->lastElementIndex + 1 < $output->totalNumber;

        // Prepare args for the overview pages service:
        if (isset($args['charLimit'])) {
            $args['limit'] = $args['charLimit'];
        } else {
            unset($args['limit']);
        }
        $results = $this->format($results, $args);

        $output->items = $results;

        return $output;
    }

    public function getAjax($selector = array()) {
        $ajaxOutput = array();

        $args = wire('input')->post('args');
        if (!is_array($args)) {
            $args = array();
        }

        // Is a tag filter set?
        if (wire('input')->get('tags')) {
            $args['tags'] = wire('input')->get('tags');
        }

        // Is something entered in the free text search?
        if (wire('input')->get('q')) {
            $args['query'] = wire('input')->get('q');
        }

        if (wire('input')->get('limit')) {
            $args['limit'] = wire('input')->get('limit');
        }

        if (wire('input')->get('start')) {
            $args['start'] = wire('input')->get('start');
        } elseif (wire('input')->get('offset')) {
            $args['start'] = wire('input')->get('offset');
        }

        $args['charLimit']                       = 150;
        $result                                  = $this->getResults($args, $selector);
        $ajaxOutput['totalNumber']               = $result->totalNumber;
        $ajaxOutput['moreAvailable']             = $result->moreAvailable;
        $ajaxOutput['lastElementIndex']          = $result->lastElementIndex;

        // Deliver HTML card for each post:
        $ajaxOutput['items'] = array();
        foreach ($result->items as $item) {
            $component = $this->addComponent('ArticleCard', ['directory' => 'partials', 'page' => $item]);
            if ($component instanceof TwackNullComponent) {
                continue;
            }

            $ajaxOutput['items'][] = $component->getAjax();
        }

        return $ajaxOutput;
    }

    public function format(PageArray $pages, $args = array()) {
        foreach ($pages as &$page) {
            // Check whether the post is visible to the user:
            if (!$page->viewable()) {
                $pages->remove($page);
            }

            $projectPage = $this->getService('ProjectService')->getProjectPage($page);
            if ($projectPage instanceof Page && $projectPage->id) {
                $page->projectPage = $projectPage;

                if ($page->projectPage->color) {
                    $page->color = $page->projectPage->color;
                }
            }

            if (isset($args['limit']) && $page->template->hasField('intro')) {
                $limit  = $args['limit'];
                $endstr = '&nbsp;…';
                if (isset($args['endstr'])) {
                    $endstr = $args['endstr'];
                }
                $page->intro = Twack::wordLimiter($page->intro, $limit, $endstr);
            }

            if ($page->template->hasField('authors') && $page->authors instanceof PageArray) {
                $authors = array();
                foreach ($page->authors as $author) {
                    $authors[] = $author->first_name . ' ' . $author->surname;
                }
                $page->authors_readable = implode(' & ', $authors);
            }
        }
        return $pages;
    }

    public function formatPage(Page $page, $args = array()) {
        // Check whether the post is visible to the user:
        if (!$page->viewable()) {
            return false;
        }

        $projectPage = $this->getService('ProjectService')->getProjectPage($page);
        if ($projectPage instanceof Page && $projectPage->id) {
            $page->projectPage = $projectPage;

            if ($projectPage->color) {
                $page->color = $projectPage->color;
            }
        }

        if (isset($args['limit']) && $page->template->hasField('intro')) {
            $limit  = $args['limit'];
            $endstr = '&nbsp;…';
            if (isset($args['endstr'])) {
                $endstr = $args['endstr'];
            }
            $page->intro = Twack::wordLimiter($page->intro, $limit, $endstr);
        }

        if ($page->template->hasField('authors') && $page->authors instanceof PageArray) {
            $authors = array();
            foreach ($page->authors as $author) {
                $authors[] = $author->first_name . ' ' . $author->surname;
            }
            $page->authors_readable = implode(' & ', $authors);
        }

        return $page;
    }
}
