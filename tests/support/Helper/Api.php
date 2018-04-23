<?php

namespace Helper;

class Api extends \Codeception\Module {
    /** @var ?object $apiDescription */
    private $apiDescription = null;

    private function createApiDescription() {
        if ($this->apiDescription === null) {
            $this->apiDescription = json_decode(file_get_contents(__DIR__ . '/../_generated/api-description.json'));
        }

        return $this->apiDescription;
    }

    public function getSchema($method, $path, $responseStatus) {
        return $this->createApiDescription()->paths->{$path}->{$method}->responses->{$responseStatus}->content->{'application/json'};
    }
}
