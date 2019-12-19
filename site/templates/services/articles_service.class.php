<?php

namespace ProcessWire;

/**
 * Provides methods for reading articles
 */
class ArticlesService extends TwackComponent {
    protected $projectPage          = false;
    protected $overviewPagesService = false;

    public function __construct($args) {
        parent::__construct($args);

        if ($this->startsWith($this->page->template->name, 'project')) {
            $this->projectPage = $this->page;
        } else {
            $this->projectPage = $this->page->closest('template.name^=project, template.name!=project_role, template.name!=project_roles_container, template.name!=projects_container');
        }

        $this->overviewPagesService = $this->getService('OverviewPagesService');
    }

    public function getArticlesPage() {
        $articlesPage = wire('pages')->get('/')->children('template.name=articles_container')->first();
        if ($this->projectPage instanceof Page && $this->projectPage->id) {
            $results = $this->projectPage->find('template.name=articles_container');
            if ($results->count > 0) {
                $articlesPage = $results->first();
            }
        }
        return $articlesPage;
    }

    /**
     * Returns all articles that can be output on this page.
     * @return PageArray
     */
    public function getArticles($args = array()) {
        $output   = new \StdClass();
        $articles = new PageArray();

        if ($this->projectPage instanceof Page && $this->projectPage->id) {
            // If project page: Include all sub-articles of the project
            $articles->add($this->projectPage->find('template=article'));
        } else {
            // Articles from the main page:
            $articles->add(wire('pages')->find('template=article'));
        }

        // Globally released articles:
        $articles->add(wire('pages')->find('template=article, show_global=1'));

        if (isset($args['sort'])) {
            $articles->filter('sort=' . $args['sort']);
        } else {
            $articles->filter('sort=-datetime_from');
        }

        // Filtering by keywords:
        if (isset($args['tags'])) {
            if (is_string($args['tags'])) {
                $args['tags'] = explode(',', $args['tags']);
            }

            if (is_array($args['tags'])) {
                $articles->filter('tags=' . implode('|', $args['tags']));
            }
        }

        // Filtering by free text:
        if (isset($args['query'])) {
            if (is_string($args['query'])) {
                $query = wire('sanitizer')->text($args['query']);
                $articles->filter("title|name|intro|contents.text%={$query}");
            }
        }

        // Store original number of articles without limit:
        $output->totalNumber = $articles->count;

        // The index of the last element:
        $output->lastElementIndex = 0;

        // Include Limit and Offset:
        $limitStrings = array();

        if (isset($args['start'])) {
            $limitStrings[]              = 'start=' . $args['start'];
            $output->lastElementIndex    = intval($args['start']);
        } elseif (isset($args['offset'])) {
            $limitStrings[]              = 'start=' . $args['offset'];
            $output->lastElementIndex    = intval($args['offset']);
        } else {
            $limitStrings[] = 'start=0';
        }

        if (isset($args['limit']) && $args['limit'] >= 0) {
            $limitStrings[]              = 'limit=' . $args['limit'];
            $output->lastElementIndex    = $output->lastElementIndex + intval($args['limit']);
        } elseif (!isset($args['limit'])) {
            $limitStrings[]              = 'limit=12';
            $output->lastElementIndex    = $output->lastElementIndex + 12;
        }

        if (!empty($limitStrings)) {
            $articles->filter(implode(', ', $limitStrings));
        }

        // Are there any more posts that can be downloaded?
        $output->moreAvailable = $output->lastElementIndex + 1 < $output->totalNumber;

        // Prepare args for the overview pages service:
        if (isset($args['charLimit'])) {
            $args['limit'] = $args['charLimit'];
        } else {
            unset($args['limit']);
        }
        $articles = $this->overviewPagesService->format($articles, $args);

        foreach ($articles as &$article) {
            if ($article->projectPage instanceof Page && $article->projectPage->color) {
                $article->color = $article->projectPage->color;
            }
        }

        $output->articles = $articles;

        return $output;
    }

    public function getAjax() {
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
        $aktuelles                               = $this->getArticles($args);
        $ajaxOutput['totalNumber']               = $aktuelles->totalNumber;
        $ajaxOutput['moreAvailable']             = $aktuelles->moreAvailable;
        $ajaxOutput['lastElementIndex']          = $aktuelles->lastElementIndex;

        // Deliver HTML card for each post:
        $ajaxOutput['articles'] = array();
        foreach ($aktuelles->articles as $article) {
            $component = $this->addComponent('ArticleCard', ['directory' => 'partials', 'page' => $article]);
            if ($component instanceof TwackNullComponent) {
                continue;
            }

            $ajaxOutput['articles'][] = $component->getAjax();
        }

        return $ajaxOutput;
    }

    public function getGalleriesPage() {
        $galleriesPage = wire('pages')->get('/')->children('template.name=galleries_container')->first();
        if ($this->projectPage instanceof Page && $this->projectPage->id) {
            $results = $this->projectPage->find('template.name=galleries_container');
            if ($results->count > 0) {
                $galleriesPage = $results->first();
            }
        }
        return $galleriesPage;
    }

    /**
     * Returns all galleries that can be output on this page.
     * @return PageArray
     */
    public function getGalleries($args = array()) {
        $output   = new \StdClass();
        $galleries = new PageArray();

        if ($this->projectPage instanceof Page && $this->projectPage->id) {
            // If project page: Include all sub-galleries of the project
            $galleries->add($this->projectPage->find('template=gallery'));
        } else {
            // Galleries from the main page:
            // $galleries->add(wire('pages')->find('template=gallery'));

            // Globally released galleries:
            $galleries->add(wire('pages')->find('template=gallery, show_global=1'));
        }

        if (isset($args['sort'])) {
            $galleries->filter('sort=' . $args['sort']);
        } else {
            $galleries->filter('sort=-datetime_from');
        }

        // Filtering by keywords:
        if (isset($args['tags'])) {
            if (is_string($args['tags'])) {
                $args['tags'] = explode(',', $args['tags']);
            }

            if (is_array($args['tags'])) {
                $galleries->filter('tags=' . implode('|', $args['tags']));
            }
        }

        // Filtering by free text:
        if (isset($args['query'])) {
            if (is_string($args['query'])) {
                $query = wire('sanitizer')->text($args['query']);
                $galleries->filter("title|name|intro|contents.text%={$query}");
            }
        }

        // Store original number of galleries without limit:
        $output->totalNumber = $galleries->count;

        // The index of the last element:
        $output->lastElementIndex = 0;

        // Include Limit and Offset:
        $limitStrings = array();

        if (isset($args['start'])) {
            $limitStrings[]              = 'start=' . $args['start'];
            $output->lastElementIndex    = intval($args['start']);
        } elseif (isset($args['offset'])) {
            $limitStrings[]              = 'start=' . $args['offset'];
            $output->lastElementIndex    = intval($args['offset']);
        } else {
            $limitStrings[] = 'start=0';
        }

        if (isset($args['limit']) && $args['limit'] >= 0) {
            $limitStrings[]              = 'limit=' . $args['limit'];
            $output->lastElementIndex    = $output->lastElementIndex + intval($args['limit']);
        } elseif (!isset($args['limit'])) {
            $limitStrings[]              = 'limit=12';
            $output->lastElementIndex    = $output->lastElementIndex + 12;
        }

        if (!empty($limitStrings)) {
            $galleries->filter(implode(', ', $limitStrings));
        }

        // Are there any more posts that can be downloaded?
        $output->moreAvailable = $output->lastElementIndex + 1 < $output->totalNumber;

        // Prepare args for the overview pages service:
        if (isset($args['charLimit'])) {
            $args['limit'] = $args['charLimit'];
        } else {
            unset($args['limit']);
        }
        $galleries = $this->overviewPagesService->format($galleries, $args);

        foreach ($galleries as &$gallery) {
            if ($gallery->projectPage instanceof Page && $gallery->projectPage->color) {
                $gallery->color = $gallery->projectPage->color;
            }
        }

        $output->galleries = $galleries;

        return $output;
    }

    protected function startsWith($haystack, $needle) {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }
}
