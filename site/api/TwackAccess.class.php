<?php

namespace ProcessWire;

class TwackAccess {
    public static function pageIDRequest($data) {
        $data = RestApiHelper::checkAndSanitizeRequiredParameters($data, ['id|int']);
        $page = wire('pages')->get('id=' . $data->id);
        return self::pageRequest($page);
    }

    public static function dashboardRequest() {
        $page = wire('pages')->get('/');
        return self::pageRequest($page);
    }

    public static function pagePathRequest($data) {
        $data = RestApiHelper::checkAndSanitizeRequiredParameters($data, ['path|pagePathName']);
        $page = wire('pages')->get('/' . $data->path);
        return self::pageRequest($page);
    }

    protected static function pageRequest(Page $page) {
        if (!wire('modules')->isInstalled('Twack')) {
            throw new InternalServererrorException('Twack module not found.');
        }
        $twackModule                       = wire('modules')->get('Twack');
        $twackModule->forceAjax            = true;
        $twackModule->forcePlainAjaxOutput = true;

        if (!$page->viewable()) {
            throw new ForbiddenException();
        }

        $ajaxOutput   = $page->render();
        $results      = json_decode($ajaxOutput, true);
        return $results;
    }
}
