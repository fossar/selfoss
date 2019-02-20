#!/bin/sh
if [ -z "$TRAVIS_TAG" ]; then
    SHORT_COMMIT=$(git rev-parse --short HEAD)
    NEW_VERSION=$(jq -r '.ver' package.json | sed "s/SNAPSHOT/$SHORT_COMMIT/")
    npm run bump-version $NEW_VERSION
fi

npm run dist
