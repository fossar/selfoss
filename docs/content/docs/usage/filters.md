+++
title = "Filtering sources"
weight = 40
+++

You can limit which items of sources will be stored in the database by specifying a filter expression in the “Filter” field of a source on the Settings page.

<div class="admonition warning">

## Warning

The *field-specific* or *negated* filters require the upcoming selfoss 2.20. Older versions only support *atomic filters*.

</div>

The filter expression can take one of the following forms:

1. An *atomic filter*: a Perl-compatible regular expression, as [accepted by PHP](https://www.php.net/manual/en/reference.pcre.pattern.syntax.php), between forward slashes (`/`). For example, `/reg(ular expression|ex)/` will keep only items whose title or content matches the regular expression between the slashes (i.e. contain the phrase “regular expression” or “regex”). Learn more about regular expressions on <https://www.regular-expressions.info/>.
2. A *field-specific filter*: an *atomic filter* preceded by one of the field names below and a colon:
    - `title:/regex/` will keep only items whose title matches the regular expression between the slashes.
    - `content:/regex/` will keep only items whose content matches the regular expression between the slashes.
    - `url:/regex/` will keep only items whose URL matches the regular expression between the slashes. For example, `url:/^https:\/\/www\.bbc\.co\.uk\/sport\//` will match all articles in sport section of BBC news.
    - `author:/regex/` will keep only items which have an author that matches the regular expression between the slashes.
    - `category:/regex/` will keep only items which have a category that matches the regular expression between the slashes.
3. A *negated filter* is either an *atomic filter* or a *field-specific filter* preceded by an exclamation mark (`!`). It will only keep items would not be kept by the filter after the exclamation mark.
