#!/bin/sh
export SELFOSS_ZIPBALL=$(echo selfoss-*.zip)
export SELFOSS_VERSION=$(jq -r '.ver' package.json)
sed -i "s/SELFOSS_VERSION/$SELFOSS_VERSION/g;s/SELFOSS_ZIPBALL/$SELFOSS_ZIPBALL/g" utils/bintray.json
