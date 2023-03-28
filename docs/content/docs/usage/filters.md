+++
title = "Filtering sources"
weight = 40
+++

You can limit which items of sources will be stored in the database by specifying a filter expression in the “Filter” field of a source on the Settings page.

The filter expression can take one of the following forms:

1. An *atomic filter*: a Perl-compatible regular expression, as [accepted by PHP](https://www.php.net/manual/en/reference.pcre.pattern.syntax.php), between forward slashes (`/`). For example, `/reg(ular expression|ex)/` will keep only items whose title or content matches the regular expression between the slashes (i.e. contain the phrase “regular expression” or “regex”). Learn more about regular expressions on <https://www.regular-expressions.info/>.
