# Contributing to selfoss

## Reporting an issue

If you discover a bug, please [report it to the issue tracker](https://github.com/fossar/selfoss/issues/new). Try to describe the problem in as much detail as possible.

You can also ask for a new feature but unless I would personally use it, I will probably not find time to implement it.


## Contributing code

We accept [pull requests](https://github.com/fossar/selfoss/compare) with your changes.

For larger changes, please discuss it in an issue first, to avoid potentially wasting effort.

Every patch is expected to adhere to our coding style, which is checked automatically by CI. You can install the checkers locally using `npm run install-dependencies`, and then run the checks using `npm run check` before submitting a pull request. There is also `npm run fix`, that will attempt to fix the formatting.

Please try to make commits small and self-contained. If you need to tweak something you previously committed, squash the new changes into the original commit before the PR is merged. `git commit --fixup` and `git rebase --autosquash` will help you, see https://dev.to/koffeinfrei/the-git-fixup-workflow-386d.


## Translating

You can use [Weblate](https://hosted.weblate.org/projects/selfoss/translations/) to help us translate selfoss into a language you know.
