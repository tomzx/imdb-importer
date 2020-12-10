<?php

namespace ImdbImporter;

use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class Importer implements LoggerAwareInterface
{
    /**
     * @var string|null
     */
    private $id = null;
    /**
     * @var int
     */
    private $rating_base = 10;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var bool
     */
    private $pretend = false;

    /**
     * @param string $id
     * @param int $rating_base
     * @throws Exception
     */
    public function __construct($id, $rating_base = 10)
    {
        $this->id = $id;
        $this->rating_base = $rating_base;
        if ($rating_base <= 0) {
            throw new Exception('Invalid rating base value ' . $rating_base . '. Rating base must be positive.');
        }
    }

    /**
     * Submit ratings to IMDb
     *
     * @param array $ratings
     * e.g.
     * [
     *  [
     *      'title' => 'The Matrix', // Name of the film
     *      'id' => 133093 // If you know it send the imdb ID and that will be used instead of searching
     *      'rating' => 5,
     *  ]
     * ]
     */
    public function submit(array $ratings)
    {
        foreach ($ratings as $rating) {
            if (isset($rating['id'])) {
                $tconst = $this->formatImdbId($rating['id']);
            } else {
                $this->getLogger()->debug("Searching for $rating[title]");
                $tconst = $this->getIdByTitle($rating['title']);
            }

            if ($tconst === null) {
                continue;
            }

            $auth = $this->getAuthToken($tconst);

            $this->submitRating($rating, $tconst, $auth);
        }
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    private function getLogger()
    {
        if ( ! $this->logger) {
            $this->logger = new Logger();
        }

        return $this->logger;
    }

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setPretend($value)
    {
        $this->pretend = $value;

        return $this;
    }

    /**
     * @param string $title
     * @return null|int
     * @throws Exception
     */
    private function getIdByTitle($title)
    {
        // Taken from https://stackoverflow.com/a/10064701/108301
        $normalizeChars = [
            'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A',
            'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',
            'Ï'=>'I', 'Ñ'=>'N', 'Ń'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U',
            'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a',
            'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i',
            'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ń'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u',
            'ú'=>'u', 'û'=>'u', 'ü'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f',
            'ă'=>'a', 'ș'=>'s', 'ț'=>'t', 'Ă'=>'A', 'Ș'=>'S', 'Ț'=>'T',
        ];

        $imdb_title = str_replace(' ', '_', strtolower(strtr($title, $normalizeChars)));

        $first_character = $imdb_title[0];
        $content = $this->httpRequest('https://v2.sg.media-imdb.com/suggests/' . $first_character . '/' . $imdb_title . '.json');

        if ($content === false) {
            throw new Exception('Error while fetching tconst for ' . $title . '.');
        }

        // Decapsulate the jsonp response
        $start = strpos($content, '(');
        $end = strrpos($content, ')');

        if ($start === false || $end === false) {
            throw new Exception('Result is not in JSONP anymore... This script will need to be updated.');
        }

        $content = substr($content, $start + 1, $end - $start - 1);

        $json = json_decode($content, true);

        if ($json === null) {
            throw new Exception('Could not decode json result for ' . $title . '.');
        }

        // Check if a title matches
        foreach ($json['d'] as $suggestion) {
            if (strcasecmp($suggestion['l'], $title) === 0) {
                return $suggestion['id'];
            }
        }

        $this->getLogger()->warning('Could not find title "' . $title . '"');
        return null;
    }

    /**
     * @param string $tconst
     * @return string
     */
    private function getAuthToken($tconst)
    {
        $cookie_details = ['id' => $this->id];

        $context_options = [
            'http' => [
                'method' => 'GET',
                'header' => 'Cookie: ' . $this->httpBuildCookie($cookie_details)
            ]
        ];
        $context = stream_context_create($context_options);

        $page_url = 'http://www.imdb.com/title/' . $tconst;
        $page_content = $this->httpRequest($page_url, $context);

        $data_auth_begin = strpos($page_content, 'data-auth');
        $data_auth_end = strpos($page_content, '"', $data_auth_begin + 11);
        $data_auth = substr($page_content, $data_auth_begin + 11, $data_auth_end - ($data_auth_begin + 11));

        if ( ! $data_auth) {
            throw new Exception('Could not fetch data auth token.');
        }

        return $data_auth;
    }

    /**
     * @param array $rating
     * @param string $tconst
     * @param $auth
     */
    private function submitRating(array $rating, $tconst, $auth)
    {
        $this->getLogger()->debug("Submitting rating for " . json_encode($rating) . " $tconst");

        if ( ! $this->pretend) {
            $cookie_details = ['id' => $this->id];

            $imdb_rating = floor($rating['rating'] / $this->rating_base * 10);

            $data = [
                'tconst'       => $tconst,
                'rating'       => $imdb_rating,
                'auth'         => $auth,
                'tracking_tag' => 'title-maindetails'
            ];
            $data = http_build_query($data);
            $context_options = [
                'http' => [
                    'method'  => 'POST',
                    'header'  => 'Cookie: ' . $this->httpBuildCookie($cookie_details) . "\r\n" .
                        'Content-type: application/x-www-form-urlencoded' . "\r\n" .
                        'Content-Length: ' . strlen($data),
                    'content' => $data
                ]
            ];
            $context = stream_context_create($context_options);

            $page_url = 'http://www.imdb.com/ratings/_ajax/title';
            $page_content = $this->httpRequest($page_url, $context);
            $page_content = json_decode($page_content, true);

            if ($page_content['status'] !== 200) {
                $this->getLogger()->error('Could not submit rating for ' . json_encode($rating) . ' status = '. $page_content['status'] . '.');
                return;
            }
        }

        $this->getLogger()->info('Submitted rating for ' . (isset($rating['title']) ? $rating['title'] : $rating['id']));
    }

    /**
     * @param string $url
     * @param resource|null $context
     * @return string
     */
    protected function httpRequest($url, $context = null)
    {
        return file_get_contents($url, false, $context);
    }

    /**
     * @param array $data
     * @return string
     */
    private function httpBuildCookie(array $data)
    {
        $cookie_string = '';
        foreach ($data as $key => $value) {
            $cookie_string .= $key . '=' . $value . ';';
        }
        return $cookie_string;
    }

    /**
     * Turn a numerical id into a tt******* formatted string if required
     *
     * @param string $id
     * @return string
     * @throws \Exception
     */
    private function formatImdbId($id)
    {
        if (strpos($id, 'tt') === 0) {
            return $id;
        }

        if (is_numeric($id)) {
            return 'tt' . str_pad($id, 7, '0', STR_PAD_LEFT);
        }

        throw new Exception("Failed to validate [$id] as an IMDb ID");
    }
}
