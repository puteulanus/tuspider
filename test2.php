<?php

include_once 'config.inc.php';
include_once 'function.inc.php';

//$list = get_img_list(get_posts('coreha',1));

//var_dump(
//    img_list_process($list, fam(PORN_FILTER, TYPE_FILTER) ) 
//);

$tmplist_array = json_decode(file_get_contents($tmplist));
$blacklist_array = json_decode(file_get_contents($blacklist));
$whitelist_array = json_decode(file_get_contents($whitelist));

while(count($tmplist_array)){
    
    if(in_array($tmplist_array[0],$whitelist_array) || in_array($tmplist_array[0],$blacklist_array)){
        unset($tmplist_array[0]);
        $tmplist_array = array_values($tmplist_array);
        file_put_contents($tmplist,json_encode($tmplist_array));
        continue;
    }
    
    $posts = get_posts($tmplist_array[0]);
    if(!posts_check_basic($posts)){blacklist_add($tmplist_array[0]);continue;}
    if(count($posts) < 100){blacklist_add($tmplist_array[0]);continue;}
    
    $img_list_tmp = get_img_list($posts);
    $img_list = array();
    for($i = 0; $i < 20; $i++){
        $img_list[] = $img_list_tmp[$i * 5 + 4];
    }
    $info_list = img_list_process($img_list, fam(PORN_FILTER, TYPE_FILTER) );
    $porn_num = 0;
    $type2_num = 0;
    foreach($info_list[0] as $info){
        if ($info['data']['result'] == 1 ||
            ($info['data']['porn_score'] > $info['data']['normal_score'] &&
                $info['data']['porn_score'] > $info['data']['hot_score'])
        ){
            $porn_num++;
        }elseif($info['data']['hot_score'] > $info['data']['normal_score'] &&
                $info['data']['hot_score'] > $info['data']['porn_score']){
            $porn_num++;
        }
    }
    foreach($info_list[1] as $info){
        if($info == 2){
            $type2_num++;
        }
    }
    if($porn_num < 15){blacklist_add($tmplist_array[0]);continue;}
    if($type2_num > 3){blacklist_add($tmplist_array[0]);continue;}
    
    $whitelist_array[] = $tmplist_array[0];
    $whitelist_array = array_values(array_flip(array_flip($whitelist_array)));
    unset($tmplist_array[0]);
    $tmplist_array = array_merge($tmplist_array,get_reblog_list($posts));
    $tmplist_array = array_values(array_flip(array_flip($tmplist_array)));
    
    file_put_contents($whitelist,json_encode($whitelist_array));
    file_put_contents($tmplist,json_encode($tmplist_array));
}

function blacklist_add($name){
    global $blacklist;
    global $blacklist_array;
    global $tmplist;
    global $tmplist_array;
    
    unset($tmplist_array[0]);
    $tmplist_array = array_values($tmplist_array);
    file_put_contents($tmplist,json_encode($tmplist_array));
    
    $blacklist_array[] = $name;
    $blacklist_array = array_values(array_flip(array_flip($blacklist_array)));
    file_put_contents($blacklist,json_encode($blacklist_array));
}