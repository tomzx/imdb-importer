IMDB ratings importer
=========

The IMDB ratings importer is pretty straightforward. It allows you to import your rating from a json file onto the website itself.

This importer was written so that I could import my ratings from other movie tracking websites (such as Rotten Tomatoes) onto IMDB.

How to use
----------

In order to use the exporter, you will need to do a couple of things:

1. You need to figure out 3 values that you will need to pass to the constructor of ImdbImporter. The first value is the location of your json data file. The second value is the *id* key/value cookie that you receive when you log onto IMDB. You can get the value by logging into your IMDB account and then finding the *id* key. The third value you need to pass is the rating base of the file you are importing from. For instance, if all the ratings you have in your json files are on 5 (such as 3.5 out of 5, 5 out of 5, etc.), then the RATING_BASE would be 5. This way, the script takes care of converting the source rating base into IMDB rating base (which is 10).

2. Write a simple script that will load the importer, set the required values and then finally call submit_rating().

```php
<?php
require_once 'importer.php';

// Change FILE to the location of your input json data file
$input = 'FILE.json';
// Change ID_FROM_COOKIE
$id = 'ID_FROM_COOKIE';
// Change RATING_BASE
$rating_base = RATING_BASE;

$importer = new ImdbImporter($input, $id, $rating_base);
$importer->submit();
```

Example
-------

```php
<?php
require_once 'importer.php';

$input = 'my_ratings.json';
$id = 'BCYj3D0pzQplRCdWOk999sALuG13hRj53tUCHy5SPlDT7GjcRHw0K-CWnzGsJPg8VC5jEw64mlaSucVtkCjKhvKZYO2SQ0CSTbspanBkgCdqHwRAlx_3h64JcwJLcU3Mmz2OTPr6BC7zrHzozJZ0BcsTNeEXLcsggl7-RsEIFYEnqdE';
$rating_base = 5;

$importer = new ImdbImporter($input, $id, $rating_base);
$importer->submit();
```

3. Done! All the movies that are in your JSON input file should be

JSON input format
------------------

The expected JSON input format is pretty simple and minimalist. It is an array of objects, each containing the title of the movie and the rating you gave it.

```javascript
[
  {
    "title": "The Mask",
    "rating": "5.0"
  },
  {
    "title": "The Matrix",
    "rating": "5.0"
  }
]
```