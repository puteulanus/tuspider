<?php

function img_list_check($img_list){
    $img_info_list = array();
    
    $ch_tumblr = curl_init();
    curl_setopt($ch_tumblr, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch_tumblr, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch_tumblr,CURLOPT_FORBID_REUSE,0);
    curl_setopt($ch_tumblr, CURLOPT_HTTPHEADER, array(
        'Connection: Keep-Alive',
        'Keep-Alive: 300'
    ));
    curl_setopt($ch_tumblr,CURLOPT_ENCODING , "gzip");
    
    $sign = '';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: multipart/form-data",
        ]
    );
    $body = [ "filecontent" => 'None' , ];
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    for ($i = 0; $i < 10; $i++){
        $sign = json_decode(http_get('http://image.qcloud.com/api.php?Action=DetectDemo.GetSign'),true)['sign'];
        curl_setopt($ch,CURLOPT_URL,"http://web.image.myqcloud.com/photos/v2/10000037/detect/0?sign=${sign}");
        $t_result = json_decode(curl_exec($ch),true);
        if($t_result['code'] != -70){break;}
        print_log('Waiting for qcloud sign...');
        sleep(1);
    }
    if (!$sign){
        error_log('Can\'t get sign from qcloud !' . PHP_EOL);
        return false;
    }
    
    foreach($img_list as $id => $pic_url){
        print_log("Checking " . $id . "/" . count($img_list) . " image" );
        curl_setopt($ch_tumblr,CURLOPT_URL,$pic_url);
        $pic = curl_exec($ch_tumblr);
        //echo curl_getinfo($ch_tumblr,CURLINFO_LOCAL_PORT) . PHP_EOL;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: multipart/form-data",
            ]
        );
        curl_setopt($ch,CURLOPT_URL,"http://web.image.myqcloud.com/photos/v2/10000037/detect/0?sign=${sign}");
        $body = [ "filecontent" => $pic , ];
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $result = json_decode(curl_exec($ch),true);
        if($result['message'] != 'SUCCESS'){
            error_log('Can\'t upload image to qcloud!' . PHP_EOL . json_encode($result) . PHP_EOL);
            continue;
        }
        curl_close($ch);
        
        $s_result = json_decode(
            http_get("http://image.qcloud.com/api.php?Action=DetectDemo.Porn&fileId=" . $result['data']['fileid']),
            true); 
        if($s_result['code'] != 0){
            error_log('Can\'t get result from qcloud!' . PHP_EOL . json_encode($s_result) . PHP_EOL);
            continue;
        }
        $img_info_list[$id] = $s_result['data'];
    }
    return $img_info_list;
}

function get_img_list($posts){
    $img_list = array();
    foreach($posts as $post){
        if($post['type'] == 'photo'){
            $url_list_tmp = array();
            foreach($post['photos'] as $photo){
                if(substr($photo['original_size']['url'],-4) != '.gif'){
                    $url_list_tmp[] = $photo['original_size']['url'];
                }else{
                    break;
                }
            }
            $img_list = array_merge($img_list,$url_list_tmp);
        }
    }
    return $img_list;
}

function get_reblog_list($posts){
    $reblog_from_list = array();
    $reblog_root_list = array();
    foreach($posts as $post){
        if(array_key_exists('reblogged_root_name',$post)){
            if($post['type'] == 'photo'){
                foreach($post['photos'] as $photo){
                    if(substr($photo['original_size']['url'],-4) == '.gif'){break;}
                }
                if(array_key_exists('reblogged_from_name',$post)){
                    $reblog_from_list[] = $post['reblogged_from_name'];
                }
                $reblog_root_list[] = $post['reblogged_root_name'];
            }
        }
    }
    return array_values(array_flip(array_flip(array_merge($reblog_root_list,$reblog_from_list))));
}

function posts_check_basic($posts){
    $photo_num = 0;
    $gif_num = 0;
    $date = $posts[count($posts) - 1]['timestamp'];
    
    foreach($posts as $post){
        if($post['type'] == 'photo'){
            $photo_num++;
            foreach($post['photos'] as $photo){
                if(substr($photo['original_size']['url'],-4) == '.gif'){$gif_num++;}
            }
        }
    }
    
    if ($photo_num < count($posts)*0.9){return false;}
    if ($gif_num > count($posts)*0.2){return false;}
    if ($date < @strtotime("-33 days")){return false;}
    return true;
}

function get_posts($account,$num=5,$type='all'){
    global $API_KEY;
    $type = $type == 'all' ? '' : '/' . $type;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch,CURLOPT_FORBID_REUSE,0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Connection: Keep-Alive',
        'Keep-Alive: 300'
    ));
    curl_setopt($ch,CURLOPT_ENCODING , "gzip");
    $posts_list = array();
    for ($i = 0; $i < $num; $i++) {
        print_log("Geting posts of tumblr user $account " . ($i+1) . "/$num");
        $url = 'https://api.tumblr.com/v2/blog';
        $url .= "/${account}.tumblr.com";
        $url .= '/posts';
        $url .= $type;
        $url .= "?api_key=${API_KEY}";
        $url .= '&notes_info=true&reblog_info=true';
        $url .= '&offset=' . 20 * $i ;
        curl_setopt($ch,CURLOPT_URL,$url);
        $json = curl_exec($ch);
        //echo curl_getinfo($ch,CURLINFO_LOCAL_PORT) . PHP_EOL; // For Keep-alive test
        if ( ! $p_list = json_decode($json,true)){break;}
        if ($p_list['meta']['status'] != 200 || count($p_list['response']['posts']) == 0){break;}
        $posts_list = array_merge($posts_list,$p_list['response']['posts']) ;
    }
    return $posts_list;
}

function http_get($url,$timeout = 5){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    return curl_exec($ch);
}

function print_log($log){
    if(substr(PHP_SAPI_NAME(),0,3) == 'cli'){
        error_log($log);
    }
}