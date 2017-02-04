

# phpamo [![Build Status](https://travis-ci.org/willwashburn/phpamo.svg)](https://travis-ci.org/willwashburn/phpamo)
A PHP library to create urls for Camo - the SSL image proxy :lock:

> **Note:** It's pronounced Fa-fah-mo. I'll be honest, I've picked better names. Its like the "[Name Game](https://en.wikipedia.org/wiki/The_Name_Game)" for camo. Kind of. Ok whatever it doesn't make sense but the library still works!

For more infomration about Camo, please see the [atmos/camo] (https://github.com/atmos/camo) repository.

## Installation
```composer require willwashburn/phpamo```

Alternatively, add ```"willwashburn/phpamo": "1.0.0"``` to your composer.json

## Usage
If you're just looking to get going with the defaults:
```PHP
    $phpamo = new \WillWashburn\Phpamo\Phpamo(
       'YOUR_CAMO_KEY',
       'YOUR_CAMO_DOMAIN'
    );
    
    $phpamo->camo($url); // returns a url guaranteed to be https
```  

Perhaps you only want to camoflauge urls that are http?
```PHP
    $phpamo->camoHttpOnly($url); // returns a https url only when http url is used, otherwise returns the url

```

If you'd like to use query string urls instead of the default hex urls, just 
pass in the query string formatter when creating your object

```PHP
    $phpamo = new \WillWashburn\Phpamo\Phpamo(
       'YOUR_CAMO_KEY',
       'YOUR_CAMO_DOMAIN',
       new QueryStringFormatter(new QueryStringEncoder)
    );
    
    $phpamo->camo($url); // returns a https url in the query string format 

```
  
## Credit

Thanks to [Corey Donohoe](https://github.com/atmos) for creating Camo.

Thanks to [Andrew Kane](https://github.com/ankane/camo/) for creating the ruby client on which this was based.

## Contributing

Everyone is encouraged to help improve this project. Here are a few ways you can help:

- [Report bugs](https://github.com/willwashburn/phpamo/issues)
- Fix bugs and [submit pull requests](https://github.com/willwashburn/phpamo/pulls)
- Write, clarify, or fix documentation
- Suggest or add new features


