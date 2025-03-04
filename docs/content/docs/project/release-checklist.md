+++
title = "Release checklist"
weight = 40
+++

1. Update `NEWS.md` with highlights of changes that are not yet there. Set a release date.
2. Revert <https://github.com/fossar/selfoss/pull/1521>.
2. Update the version strings throughout the code base to the new release using `npm run bump-version 2.19` and commit the changes.
3. Commit the changes.
4. Create a tag using `git tag 2.19`.
5. Push the tag to GitHub `git push origin master --tags`, GitHub actions will automatically build the release tarball and publish the release on GitHub.
6. Netlify will automatically build the website and deploy it.
7. Change the versions to new snapshot `npm run bump-version 2.20-SNAPSHOT`, commit the changes, and push them to the repo to start a new cycle.
