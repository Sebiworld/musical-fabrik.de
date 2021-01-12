<?php

namespace ProcessWire;

class AppApiTest {
    public static function test($data) {
        return [
          'test' => true,
          'success' => 'YEAH!',
          'responseCode' => 202
        ];
    }
}
