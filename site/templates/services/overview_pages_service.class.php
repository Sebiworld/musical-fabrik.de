<?php

namespace ProcessWire;

/**
 * Provides methods for formatting and filtering the output of overview pages.
 */
class OverviewPagesService extends TwackComponent {
    public function __construct($args) {
        parent::__construct($args);
    }

    public function format(PageArray $pages, $args = array()) {
        foreach ($pages as &$page) {
            // Check whether the post is visible to the user:
            if (!$page->viewable()) {
                $pages->remove($page);
            }

            $projectPage = $page->closest('template.name^=project, template.name!=project_role, template.name!=project_roles_container, template.name!=projects_container');
            if ($projectPage instanceof Page && $projectPage->id) {
                $page->projectPage = $projectPage;
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
                    $authors[] = $author->first_name . ' ' . $author->last_name;
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

        $projectPage = $page->closest('template.name^=project, template.name!=project_role, template.name!=project_roles_container, template.name!=projects_container');
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
                $authors[] = $author->first_name . ' ' . $author->last_name;
            }
            $page->authors_readable = implode(' & ', $authors);
        }

        return $page;
    }
}
