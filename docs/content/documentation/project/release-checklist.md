+++
title = "Release checklist"
weight = 40
+++

1. Update `NEWS.md` with highlights of changes that are not yet there. Set a release date.
2. Prepare a [draft of release](https://github.com/SSilence/selfoss/releases/new) on GitHub with the most recent part of the changelog as the body. Set the tag name to the expected version of the release.
3. Update the version strings throughout the code base to the new release using `npm run bump-version 2.19` and commit the changes.
4. Create a tag using `git tag 2.19`.
5. Push the tag to GitHub `git push origin master --tags`, draft will be automatically published and release tarball will be built.
6. After the tarball is available, run `zola build` in the `docs/` directory and upload the generated contents of `docs/public` to the website.
7. Change the versions to new snapshot `npm run bump-version 2.20-SNAPSHOT` and commit the changes.
