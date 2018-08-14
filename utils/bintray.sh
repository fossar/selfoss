#!/bin/sh
ls -al
export SELFOSS_ZIPBALL=$(echo selfoss-*.zip)
export SELFOSS_VERSION=$(jq -r '.ver' package.json)
echo "------------------" $SELFOSS_VERSION $SELFOSS_ZIPBALL
sed -i "s/SELFOSS_VERSION/$SELFOSS_VERSION/g;s/SELFOSS_ZIPBALL/$SELFOSS_ZIPBALL/g" utils/bintray.json
