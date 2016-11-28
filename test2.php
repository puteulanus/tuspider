<?php

include_once 'config.inc.php';
include_once 'function.inc.php';

//echo get_qcloud_sign();
//exit;

var_dump(
    img_list_process(
        get_img_list(get_posts('coreha',1)),
        array(
            img_list_func_maker( 
                function ($pic,$sign) {return img_pron_check($pic,$sign);},
                function () {return get_qcloud_sign(); } )
        )
    )
);