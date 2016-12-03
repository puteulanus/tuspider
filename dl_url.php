<?php

include_once 'config.inc.php';
include_once 'function.inc.php';

$whitelist = json_decode( file_get_contents('data/whitelist.txt'), true );

$url_list = array();
foreach($whitelist as $name){
    $url_list = array_merge($url_list, get_img_list(get_posts($name)));
}

$url_list = array_values(array_flip(array_flip($url_list)));

echo count($url_list) . PHP_EOL;

$contents = '';
foreach($url_list as $url){
    $contents .= $url . PHP_EOL;
    $contents .= '  dir=image_dl/' . PHP_EOL;
    $contents .= '  out=' . substr(md5($url),16) . '.' . array_reverse(explode('.',array_reverse(explode('/', parse_url($url)['path'] ) )[0]))[0] .PHP_EOL ;
}

file_put_contents('url.txt', $contents);