This directory contains the source for the selfoss website hosted at <https://selfoss.aditu.de>. It is mostly written in [CommonMark](https://commonmark.org/) and built using [Zola](https://www.getzola.org) static site generator.

After [installing Zola](https://www.getzola.org/documentation/getting-started/installation/) (if you use Nix, it will already be available in your [development environment](content/docs/development/setting-up.md)), you can run `zola serve` in this directory to start a server that will allow you to preview the built site, automatically rebuilding on changes.

You can also run `zola build`, which will generate the site inside the `public/` subdirectory.
