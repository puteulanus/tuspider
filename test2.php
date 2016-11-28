<?php

include_once 'config.inc.php';
include_once 'function.inc.php';

$list = get_img_list(get_posts('coreha',1));
var_dump(
    img_list_process($list, fam(PORN_FILTER) ) 
);