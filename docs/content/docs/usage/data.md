+++
title = "Importing & exporting data"
weight = 30
+++

## Importing feeds from a different RSS reader {#import}

selfoss supports importing [OPML](https://en.wikipedia.org/wiki/OPML) files. Find the OPML export in the old application, it is usually located somewhere in settings. Then go to the *Settings* page in your selfoss instance, click the “import an OPML file” link at the top, and upload the file there.


## Exporting feeds {#export}

Similarly, you can get a list of feeds in OPML format by clicking “Export sources” at the top of the *Settings* page. selfoss also adds extra attributes to the outline elements describing the spout parameters for each source to preserve the data when importing into another sefoss instance. Some spouts, especially those for social networks, might not have an RSS URL so apps other than selfoss will not be able to import such sources.
