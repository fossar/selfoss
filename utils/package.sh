#!/bin/sh
if [ -z "$TRAVIS_TAG" ]; then
    SHORT_COMMIT=$(git rev-parse --short HEAD)
    NEW_VERSION=$(jq -r '.ver' package.json | sed "s/SNAPSHOT/$SHORT_COMMIT/")
    grunt replace --newversion=$NEW_VERSION
fi

grunt
