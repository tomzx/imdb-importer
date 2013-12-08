<?php

namespace ImdbImporter;

class Importer
{
	private $id = null;
	private $rating_base = 10;

	public function __construct($id, $rating_base = 10)
	{
		$this->id = $id;
		$this->rating_base = $rating_base;
		if ($rating_base <= 0) {
			throw new \Exception('Invalid rating base value '.$rating_base.'. Rating base must be positive.');
		}
	}

	public function submit(array $ratings)
	{
		// http://www.imdb.com/ratings/_ajax/title
		// tconst = movie id (tt3123123)
		// rating = your rating on 10
		// auth = auth key to submit
		// tracking_tag = 'title-maindetails'
		foreach ($ratings as $rating)
		{
			echo 'Fetching submission details for "'.$rating['title'].'"'.PHP_EOL;

			$tconst = $this->get_tconst($rating);

			if ($tconst === null) continue;

			$auth = $this->get_auth_token($tconst);

			$this->submit_rating($rating, $tconst, $auth);
		}
	}

	private function get_tconst($rating)
	{
		$imdb_title = urlencode($rating['title']);

		$content = file_get_contents('http://www.imdb.com/xml/find?json=1&nr=1&tt=on&q='.$imdb_title);

		if ($content === false)
		{
			throw new \Exception('Error while fetching tconst for '.$rating['title'].'.');
		}

		$json = json_decode($content, true);

		if ($json === null)
		{
			throw new \Exception('Could not decode json result for '.$rating['title'].'.');
		}

		// title_popular, title_exact, title_approx
		// TODO: If we fail to find the exact title, try the next category until we've gone through them all
		if (array_key_exists('title_popular', $json))
		{
			$type = 'title_popular';
		}
		else if (array_key_exists('title_exact', $json))
		{
			$type = 'title_exact';
		}
		else if (array_key_exists('title_approx', $json))
		{
			$type = 'title_approx';
		}
		else
		{
			return null;
		}

		// Check title matches
		$matched_title_index = -1;
		$i = -1;
		foreach ($json[$type] as $movie)
		{
			++$i;
			if (strcasecmp($movie['title'], $rating['title']) === 0)
			{
				$matched_title_index = $i;
				break;
			}
		}

		if ($matched_title_index === -1)
		{
			echo 'Non matching title ' . $rating['title'].PHP_EOL;
			return null;
		}

		return $json[$type][$matched_title_index]['id'];
	}

	private function get_auth_token($tconst)
	{
		$cookie_details = ['id' => $this->id];

		$context_options = [
			'http' => [
				'method' => 'GET',
				'header' => 'Cookie: '.$this->http_build_cookie($cookie_details)
			]
		];
		$context = stream_context_create($context_options);

		$page_url = 'http://www.imdb.com/title/'.$tconst;
		$page_content = file_get_contents($page_url, false, $context);

		$data_auth_begin = strpos($page_content, 'data-auth');
		$data_auth_end = strpos($page_content, '"', $data_auth_begin + 11);
		$data_auth = substr($page_content, $data_auth_begin + 11, $data_auth_end - ($data_auth_begin + 11));

		return $data_auth;
	}

	private function submit_rating($rating, $tconst, $auth)
	{
		echo 'Submitting rating for '.$rating['title'].PHP_EOL;

		$cookie_details = ['id' => $this->id];

		$imdb_rating = floor($rating['rating'] / $this->rating_base * 10);

		$data = ['tconst' => $tconst, 'rating' => $imdb_rating, 'auth' => $auth, 'tracking_tag' => 'title-maindetails'];
		$data = http_build_query($data);
		$context_options = [
			'http' => [
				'method' => 'POST',
				'header' => 'Cookie: '.$this->http_build_cookie($cookie_details)."\r\n".
				'Content-type: application/x-www-form-urlencoded'."\r\n".
				'Content-Length: '.strlen($data),
				'content' => $data
			]
		];
		$context = stream_context_create($context_options);

		$page_url = 'http://www.imdb.com/ratings/_ajax/title';
		$page_content = file_get_contents($page_url, false, $context);

		echo 'Submitted to http://www.imdb.com/title/'.$tconst.PHP_EOL;
	}

	private function http_build_cookie(array $data)
	{
		$cookie_string = '';
		foreach ($data as $key => $value)
		{
			$cookie_string .= $key.'='.$value.';';
		}
		return $cookie_string;
	}
}