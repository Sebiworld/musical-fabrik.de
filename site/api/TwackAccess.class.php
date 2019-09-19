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

    public static function pageIDFileRequest($data) {
        $data = RestApiHelper::checkAndSanitizeRequiredParameters($data, ['id|int']);
        $page = wire('pages')->get('id=' . $data->id);
        return self::pageRequest($page);
    }

    public static function dashboardFileRequest($data) {
        $page = wire('pages')->get('/');
        return self::pageRequest($page);
    }

    public static function pagePathFileRequest($data) {
        $data = RestApiHelper::checkAndSanitizeRequiredParameters($data, ['path|pagePathName']);
        $page = wire('pages')->get('/' . $data->path);
        return self::pageRequest($page);
    }

    protected static function fileRequest(Page $page) {
        if (!$page->viewable()) {
            throw new ForbiddenException();
        }

        $filename = $this->wire('input')->get('file', 'filename');
		if (!$filename || !is_string($filename)) {
			throw new BadRequestException("No valid filename.");
		}
		$file = $page->filesManager->getFile($filename);
		if (!$file || empty($file)) {
			throw new NotFoundException('File not found: ' . $filename);
        }
        
        // Modify size:
        $width = $this->wire('input')->get('width', 'intUnsigned', 0);
        $height = $this->wire('input')->get('height', 'intUnsigned', 0);
        $maxWidth = $this->wire('input')->get('maxwidth', 'intUnsigned', 0);
        $maxHeight = $this->wire('input')->get('maxheight', 'intUnsigned', 0);
        $cropX = $this->wire('input')->get('cropx', 'intUnsigned', 0);
        $cropY = $this->wire('input')->get('cropy', 'intUnsigned', 0);

        // if($cropX > 0 && $cropY > 0){
        //     $file = $file->crop($cropX,)
        // }


        // $ajaxOutput   = $page->render();
        $results      = json_decode($ajaxOutput, true);
        return $results;
    }
}
