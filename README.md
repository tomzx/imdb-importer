# IMDb ratings importer
The IMDb ratings importer is pretty straightforward. It allows you to import your rating from an array onto the website itself.

This importer was written so that I could import my ratings from other movie tracking websites (such as Rotten Tomatoes) onto IMDb.

## Requirements
* PHP >= 7.3

## Getting started
`php composer.phar require tomzx/imdb-importer`

## How to use
In order to use the exporter, you will need to do a couple of things:

1. You need to figure out 2 values that you will need to pass to the constructor of IMDbImporter. The first value is the *id* key/value cookie that you receive when you log onto IMDb. You can get the value by logging into your IMDb account and then finding the *id* key. The second value you need to pass is the rating base of the array you are importing from. For instance, if all the ratings you have in your array are on 5 (such as 3.5 out of 5, 5 out of 5, etc.), then the RATING_BASE would be 5. This way, the script takes care of converting the source rating base into IMDb rating base (which is 10).

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
$id = 'ID_FROM_COOKIE';
// Change RATING_BASE
$rating_base = RATING_BASE;

$importer = new Importer($id, $rating_base);
$importer->submit($input);
```

## Example
```php
<?php
require_once 'vendor/autoload.php';

use ImdbImporter\Importer;

$input = require_once 'my_ratings.php';
$id = 'BCYj3D0pzQplRCdWOk999sALuG13hRj53tUCHy5SPlDT7GjcRHw0K-CWnzGsJPg8VC5jEw64mlaSucVtkCjKhvKZYO2SQ0CSTbspanBkgCdqHwRAlx_3h64JcwJLcU3Mmz2OTPr6BC7zrHzozJZ0BcsTNeEXLcsggl7-RsEIFYEnqdE';
$rating_base = 5;

$importer = new Importer($id, $rating_base);
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
