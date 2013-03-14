<?php

function readability($key, $link) {
	$content = file_get_contents("https://readability.com/api/content/v1/parser?token=" . $key . "&url=" . $link);
	$data = json_decode($content);

	return $data->content;
}
