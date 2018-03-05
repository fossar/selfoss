# Release checklist
1. Update `NEWS.md` with highlights of changes. Set a release date.
2. Prepare a [draft of release](https://github.com/SSilence/selfoss/releases/new) on GitHub with the most recent part of the changelog as the body. Set the tag name to the expected version of the release.
3. Update the version strings throughout the code base to the new release using `grunt version --newversion 2.18`.
4. Create a tag using `git tag 2.18`.
5. Push the tag to GitHub `git push origin master --tags`, draft will be automatically published and release tarball will be built.
6. After the tarball is available, update the website.
7. Change the versions to new snapshot `grunt version --newversion 2.19-SNAPSHOT`.
