# Contributing to selfoss

## Reporting an issue

If you discover a bug or wish to have a feature added, [report it to the issue tracker](https://github.com/fossar/selfoss/issues/new). Try to describe the problem in as much detail as possible.


## Contributing code

We accept [pull requests](https://github.com/fossar/selfoss/compare) with your changes.

Every patch is expected to adhere to our coding style, which is checked automatically by CI. You can install the checkers locally using <code><a href="https://github.com/casey/just">just</a> install-dependencies</code>, and then run the checks using `just check` before submitting a pull request. There is also `just fix`, that will attempt to fix the formatting.

Please try to make commits small and self-contained. If you need to tweak something you previously committed, squash the new changes into the original commit before the PR is merged. `git commit --fixup` and `git rebase --autosquash` will help you, see https://dev.to/koffeinfrei/the-git-fixup-workflow-386d.


## Translating

You can use [Weblate](https://hosted.weblate.org/projects/selfoss/translations/) to help us translate selfoss into a language you know.
