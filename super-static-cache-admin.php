<?php
/*后台管理界面*/
/*最后更新 2015年8月14日*/

//展示菜单
function display_cache_menu(){
    wp_register_script('jquery', get_template_directory_uri() . '/jquery.js', array('jquery'), '' ); 
    wp_enqueue_script('jquery' );
    add_options_page('Super Static Cache', 'Super Static Cache', 'manage_options',__FILE__, 'show_cache_manage');
}
function show_cache_manage(){
    do_update_actions();
    require_once(dirname(__FILE__).'/options.php');
}
add_action('admin_menu', 'display_cache_menu');

//增加管理链接
function ssc_action_links($links,$pluginfile){
    if($pluginfile == 'super-static-cache/super-static-cache.php'){
        $link=array(
            '<a href="'. get_admin_url(null, 'options-general.php?page=super-static-cache/super-static-cache-admin.php') .'">'.__('Settings','super_static_cache').'</a>'
        );
        $links = array_merge($link, $links);
    }
    return $links;
}
add_filter('plugin_action_links', 'ssc_action_links',10,2);

//增加其它配置连接
function ssc_row_meta($links,$pluginfile){
    if($pluginfile == 'super-static-cache/super-static-cache.php'){
        $link=array(
            '<a href="'. get_admin_url(null, 'options-general.php?page=super-static-cache/super-static-cache-admin.php') .'">'.__('Settings','super_static_cache').'</a>',
            '<a href="https://www.hitoy.org/super-static-cache-for-wordperss.html">'.__('Support','super_static_cache').'</a>',
            '<a href="https://www.hitoy.org/super-static-cache-for-wordperss.html#Donations">'.__('Donate','super_static_cache').'</a>'
        );
        $links = array_merge($links,$link);
    }
    return $links;
}
add_filter('plugin_row_meta','ssc_row_meta',10,2);


//清除文章/单页缓存函数
//参数为id或者标题
function clear_post_cache($args){
    global $wpssc,$wpdb;
    if($args*1 > 0){
        $link=get_permalink($args);
        $wpssc->delete_cache($link);
    }else if(strlen($args) > 2){
        $postres=$wpdb->get_results("SELECT `ID`  FROM `" . $wpdb->posts . "` WHERE post_title like '%".$args."%' LIMIT 0,1 ");
        $link = get_permalink($postres[0]->ID);
        $wpssc->delete_cache($link);
    }
}
//清除分类页面
function clear_category_cache(){
    global $wpssc,$wpdb;
    $sql="SELECT $wpdb->terms.term_id, name FROM $wpdb->terms LEFT JOIN $wpdb->term_taxonomy ON $wpdb->term_taxonomy.term_id = $wpdb->terms.term_id WHERE $wpdb->term_taxonomy.taxonomy = 'category'";
    $cateobj = $wpdb->get_results($sql);
    foreach($cateobj as $cate){
        $link=get_category_link($cate->term_id);
        $wpssc->delete_cache($link);
    }

}
//清除tag页面
function clear_tag_cache(){
    global $wpssc;
    $tagobj=get_tags();
    foreach($tagobj as $tag){
        $link=get_tag_link($tag->term_id);
        $wpssc->delete_cache($link);
    }
}

//清除文章页/单页
function clear_post_page_cache($type){
    global $wpssc,$wpdb;
    $postres=$wpdb->get_results("SELECT ID  FROM  $wpdb->posts WHERE post_type='".$type."' and post_status='publish'");
    foreach($postres as $post){
        $link=get_permalink($post->ID);
        $wpssc->delete_cache($link);
    }
}

//清除首页缓存
function clear_home_cache(){
    global $wpssc;
    $wpssc->delete_cache($wpssc->siteurl."/index.html");
}

//更新配置
function do_update_actions(){
    if($_POST['super_static_cache_mode']){
        $super_static_cache_mode=trim($_POST['super_static_cache_mode']);
        update_option('super_static_cache_mode',$super_static_cache_mode);

        $super_static_cache_excet_arr=$_POST['super_static_cache_excet'];
        $super_static_cache_excet = implode($super_static_cache_excet_arr,',');
        update_option('super_static_cache_excet',$super_static_cache_excet);

        $super_static_cache_strict=($_POST['super_static_cache_strict'] == "true")?true:false;
        update_option('super_static_cache_strict',$super_static_cache_strict);
    }

    if($_POST['update_cache_action']){
        $update_cache_action_arr=$_POST['update_cache_action'];
        $update_cache_action=implode($update_cache_action_arr,',');
        update_option('update_cache_action',$update_cache_action);
    }

    if($_POST['clearcache']){
        $del_type_arr=$_POST['clearcache'];
        if(in_array('home',$del_type_arr)){
            clear_home_cache();
        }
        if(in_array('single',$del_type_arr)){
            clear_post_page_cache("post");
        }
        if(in_array('page',$del_type_arr)){
            clear_post_page_cache("page");
        }
        if(in_array('category',$del_type_arr)){
            clear_category_cache();
        }
        if(in_array('tag',$del_type_arr)){
            clear_tag_cache();
        }
        if(in_array('all',$del_type_arr)){
            global $wpssc;
            if($wpssc->cachemod=="direct"){
                clear_home_cache();
                clear_post_page_cache("post");
                clear_post_page_cache("page");
                clear_category_cache();
                clear_tag_cache();
            }else if($wpssc->cachemod=="phprewrite" || $wpssc->cachemod=="serverrewrite"){
                $wpssc->delete_cache($wpssc->siteurl."/");
            }
        }
    }
    if($_POST['clearpostpagecache']){
        $ids=explode(",",trim($_POST['clearpostpagecache']));
        foreach($ids as $id){
            clear_post_cache($id);
        }
    }
}
