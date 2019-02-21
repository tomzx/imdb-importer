# IMDb ratings importer
The IMDb ratings importer is pretty straightforward. It allows you to import your rating from an array onto the website itself.

This importer was written so that I could import my ratings from other movie tracking websites (such as Rotten Tomatoes) onto IMDb.

## Requirements
* PHP 5.4 <

## Getting started
`php composer.phar require tomzx/imdb-importer`

## How to use
In order to use the exporter, you will need to do a couple of things:

1. You need to figure out 3 values that you will need to pass to the constructor of IMDbImporter. The first two values are the *id* and *sid* cookies that you receive when you log onto IMDb. You can get their values by logging into your IMDb account and finding the cookie values. The third value you need to pass is the rating base of the array you are importing from. For instance, if all the ratings you have in your array are on 5 (such as 3.5 out of 5, 5 out of 5, etc.), then the RATING_BASE would be 5. This way, the script takes care of converting the source rating base into IMDb rating base (which is 10).

2. Write a simple script that will load the importer, set the required values and then finally call submit(). Submit receives 1 argument, which is the array of movie titles/rating you want to import.

```php
<?php
require_once 'vendor/autoload.php';

use ImdbImporter\Importer;

// Change this array to the data you want to import
$input = [
  [
    "title" => "Movie 1",
    "rating" => "5.0"
  ],
];
// Another option
// $input = [['title' => 'The Mask', 'rating' => '5.0']];
// Change ID_FROM_COOKIE
$id = 'ID_COOKIE_VALUE';
$sid = 'SID_COOKIE_VALUE';
// Change RATING_BASE
$rating_base = RATING_BASE;

$importer = new Importer($id, $sid, $rating_base);
$importer->submit($input);
```

## Example
```php
<?php
require_once 'vendor/autoload.php';

use ImdbImporter\Importer;

$input = require_once 'my_ratings.php';
$id = 'BCYpudRRmg35BwA5SWQSttrcAuf5x8hpg9SSlUX_UyCtrTqQu_yzev2mIEg5UtCH4Un9TFXXyEuV%0D%0A57CGQeKtp9aRDRVzR6yKr87wW15uljhEoljwEfYthLlw0SLHXTHq9nzrGlkKdaiLm4Bthm8nQQfC%0D%0AEA%0D%0A';
$sid = 'BCYvtlia4keHaaj15aXIAGMWMPhYe5TE3aC163F_iFbWkM0sPqclSy5aTpWrIblEmTnju44ysSwM%0D%0A5Cb5UIRFZ60XA1yorI4hGHMo21cUE1I3P8Ye8c35PR_KkihCI_IVb15xJ1bD55WnP-yoKfgKKdFA%0D%0Acw%0D%0A';
$rating_base = 5;

$importer = new Importer($id, $sid, $rating_base);
$importer->submit($input);
```

Done! All the movies that are in your input array should be now rated on IMDb.

## Input format
The expected input format is pretty simple and minimalist. It is an array of arrays, each containing the title of the movie and the rating you gave it.

```php
<?php
return [
  [
    "title" => "The Mask",
    "rating" => "5.0"
  ],
  [
    "title" => "The Matrix",
    "rating" => "5.0"
  ]
];
```

## License
The code is licensed under the [MIT license](http://choosealicense.com/licenses/mit/). See [LICENSE](LICENSE).
