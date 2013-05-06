IMDB ratings importer
=========

The IMDB ratings importer is pretty straightforward. It allows you to import your rating from a json file onto the website itself.

This importer was written so that I could import my ratings from other movie tracking websites (such as Rotten Tomatoes) onto IMDB.

Requirements
------------

* PHP 5.4 <

How to use
----------

In order to use the exporter, you will need to do a couple of things:

1. You need to figure out 2 values that you will need to pass to the constructor of ImdbImporter. The first value is the *id* key/value cookie that you receive when you log onto IMDB. You can get the value by logging into your IMDB account and then finding the *id* key. The second value you need to pass is the rating base of the file you are importing from. For instance, if all the ratings you have in your json files are on 5 (such as 3.5 out of 5, 5 out of 5, etc.), then the RATING_BASE would be 5. This way, the script takes care of converting the source rating base into IMDB rating base (which is 10).

2. Write a simple script that will load the importer, set the required values and then finally call submit(). Submit receives 1 argument, which is the array of movie titles/rating you want to import.

```php
<?php
require_once 'importer.php';

// Change FILE to the location of your input json data file
$input = json_decode(file_get_contents('FILE.json'));
// Another option
// $input = [['title' => 'The Mask', 'rating' => '5.0']];
// Change ID_FROM_COOKIE
$id = 'ID_FROM_COOKIE';
// Change RATING_BASE
$rating_base = RATING_BASE;

$importer = new ImdbImporter($id, $rating_base);
$importer->submit($input);
```

Example
-------

```php
<?php
require_once 'importer.php';

$input = json_decode(file_get_contents('my_ratings.json'));
$id = 'BCYj3D0pzQplRCdWOk999sALuG13hRj53tUCHy5SPlDT7GjcRHw0K-CWnzGsJPg8VC5jEw64mlaSucVtkCjKhvKZYO2SQ0CSTbspanBkgCdqHwRAlx_3h64JcwJLcU3Mmz2OTPr6BC7zrHzozJZ0BcsTNeEXLcsggl7-RsEIFYEnqdE';
$rating_base = 5;

$importer = new ImdbImporter($id, $rating_base);
$importer->submit($input);
```

3. Done! All the movies that are in your JSON input file should be now rated on IMDB.

Input format
------------------

The expected input format is pretty simple and minimalist. It is an array of arrays, each containing the title of the movie and the rating you gave it.

```php
<?php
[
  [
    "title" => "The Mask",
    "rating" => "5.0"
  ],
  [
    "title" => "The Matrix",
    "rating" => "5.0"
  ]
]
```

License
-------

The code is licensed under the MIT license (http://opensource.org/licenses/MIT). See license.txt.