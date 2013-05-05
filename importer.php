<?php

function http_build_cookie(array $data)
{
	$cookie_string = '';
	foreach ($data as $key => $value)
	{
		$cookie_string .= $key.'='.$value.';';
	}
	return $cookie_string;
}

class ImdbImporter
{
	private $input = null;
	private $id = null;
	private $rating_base = 10;

	public function __construct($input, $id, $rating_base = 10)
	{
		$this->input = $input;
		$this->id = $id;
		$this->rating_base = $rating_base;
	}

	public function submit()
	{
		// http://www.imdb.com/ratings/_ajax/title
		// tconst = movie id (tt3123123)
		// rating = your rating on 10
		// auth = auth key to submit
		// tracking_tag = 'title-maindetails'

		$ratings = json_decode(file_get_contents($this->input), true);

		foreach ($ratings as $rating)
		{
			echo 'Fetching submission details for '.$rating['title'].PHP_EOL;

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

		$json = json_decode($content, true);

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
			continue;
		}

		// Check title matches
		$matched_title_index = -1;
		$i = -1;
		foreach ($json[$type] as $movie)
		{
			++$i;
			if ($movie['title'] === $rating['title'])
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

		$context_options = ['http' =>
								[
								'method' => 'GET',
								'header' => 'Cookie: '.http_build_cookie($cookie_details)
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

		$cookie_details = array('id' => $this->id);

		$imdb_rating = floor($rating['rating'] / $this->rating_base * 10);

		$data = ['tconst' => $tconst, 'rating' => $imdb_rating, 'auth' => $auth, 'tracking_tag' => 'title-maindetails'];
		$data = http_build_query($data);
		$context_options = ['http' =>
								[
								'method' => 'POST',
								'header' => 'Cookie: '.http_build_cookie($cookie_details)."\r\n".
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
}