<?php

use Codeception\Util\HttpCode;

class SourcesCest {
    public function _before(ApiTester $I) {
    }

    public function _after(ApiTester $I) {
    }

    // tests
    public function sourcesUnauthenticated(ApiTester $I) {
        $I->wantTo('Ensure unauthenticated user cannot see the list of sources.');
        $I->sendGET('/sources/list');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function sourcesAuthenticated(ApiTester $I) {
        $I->wantTo('Ensure authenticated user can see the list of sources.');
        $I->sendPOST('/login', ['username' => 'admin', 'password' => 'heslo']);
        $I->sendGET('/sources/list');
        $I->seeSchemaMatches('get', '/sources/list', HttpCode::OK);
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    public function sourcesUnauthenticatedCreate(ApiTester $I) {
        $I->wantTo('Ensure unauthenticated user cannot add sources.');
        $I->sendPOST('/source', [
            'title' => 'Test',
            'spout' => 'spouts\\rss\\feed',
            'tags' => 'IOT,test',
            'url' => 'https://github.com/SSilence/selfoss/commits/master.atom',
        ]);
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function sourcesAuthenticatedCreate(ApiTester $I) {
        $I->wantTo('Ensure authenticated user can add sources.');
        $I->sendPOST('/login', ['username' => 'admin', 'password' => 'heslo']);
        $I->sendPOST('/source', [
            'title' => 'Test',
            'spout' => 'spouts\\rss\\feed',
            'tags' => 'IOT,test',
            'url' => 'https://github.com/SSilence/selfoss/commits/master.atom',
        ]);
        $I->seeSchemaMatches('post', '/source', HttpCode::OK);
        $I->seeResponseCodeIs(HttpCode::OK);
    }
}
