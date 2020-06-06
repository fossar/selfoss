+++
title = "Creating spouts"
weight = 20
+++

You can easily add your own data sources. Spouts (aka plug-ins) fetch the content from the different sources. Some spouts are included:

* RSS Feeds
* Images from a RSS Feed
* Images from deviantArt Users
* Images from tumblr
* Your twitter timeline
* Tweets of a twitter user
* heise News with full content
* golem News with full content
* MMOSpy News with full content

If you want to get the newest entries from your own source (e.g. an IMAP e-mail Account, Log Files or any data from your own application), you can include a new spout in your selfoss stream by writing just one PHP class (saved in a single PHP file).

Create a new PHP file under `src/spouts/your_spouts/your_spout.php` (choose a name for `your_spouts` and `your_spout`). The easiest way is to copy the [`src/spouts/rss/feed.php`](https://github.com/SSilence/selfoss/blob/mastersrc/spouts/rss/feed.php) and to modify this file.

### Member Variables
Set the `$name` and `$description` variable with the name and description of your spout. The `$params` contain the definition of the input fields which a user will have to fill to add a new source of your spout (e.g. `username` and `password` for accessing the source data).

A simple example for the member variables of a spout for accessing an e-mail inbox via IMAP:

```php
<?php

namespace spouts\mail;

class imap extends \spouts\spout {
    public $name = 'E-mail';
    public $description = 'Obtain e-mails from IMAP account';
    public $params = [
        'email' => [
            'title'      => 'E-mail',
            'type'       => 'text',
            'default'    => '',
            'required'   => true,
            'validation' => ['email']
        ],
        'password' => [
            'title'      => 'Password',
            'type'       => 'password',
            'default'    => '',
            'required'   => true,
            'validation' => ['notempty']
        ],
        'host' => [
            'title'      => 'URL',
            'type'       => 'text',
            'default'    => '',
            'required'   => true,
            'validation' => ['notempty']
        ]
    ];
}
```

### Methods

Your source will have to implement a few methods. Following UML diagram shows the inheritance structure:

![selfoss source UML diagram](images/uml.png)

The class has to implement three things:

* A `load($params)` function will be executed by selfoss when the content will be updated (the `https://your-selfoss-url.com/update` will be executed). This `load` function has one parameter `$params` which contains the user defined parameters (e.g. username, password or anything which the user has configured (as you can define in the members variable `$params`). This function contains your source code for fetching the data (e.g. loading the emails from an IMAP email account).
* You have to implement the [`Iterator`](https://www.php.net/manual/en/class.iterator.php) interface. selfoss will use it to iterate over all single entries of your source (e.g. the emails which were fetched by the load function). See [php.net manual (OOP5 iterators)](https://secure.php.net/manual/en/language.oop5.iterations.php) for more informations about this iterator functions.
* selfoss iterates over all the entries by using the `Iterator` interface. selfoss will receive all information about the entries by using the functions defined by the abstract class `\spouts\spout` (e.g. it will get the email subject by executing the `getTitle()` method).

### Thumbnails

If you would like to show thumbnails instead of text, you have to implement the optional method `getThumbnail()`. This method have to return the URL of the image. selfoss will load and generate the thumbnail automatically. See [`src/spouts/rss/images.php`](https://github.com/SSilence/selfoss/blob/master/src/spouts/rss/images.php) for an example. This spout searches for an image in an rss feed and returns it.

### Your Spouts

Feel free to send us your own spouts. We are really happy about new sources we can add to further versions of selfoss. You can send them by email to [tobias.zeising@aditu.de](mailto:tobias.zeising@aditu.de) or as a pull request to the [GitHub repository](https://github.com/SSilence/selfoss).
