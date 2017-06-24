<?php
/*后台管理界面*/
/*最后更新 2017年5月*/

//展示菜单
function display_cache_menu(){
    add_options_page('Super Static Cache', 'Super Static Cache', 'manage_options','Super-Static-Cache', 'show_cache_manage');
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
            '<a href="'. get_admin_url(null, 'options-general.php?page=Super-Static-Cache') .'">'.__('Settings','super_static_cache').'</a>'
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
            '<a href="'. get_admin_url(null, 'options-general.php?page=Super-Static-Cache') .'">'.__('Settings','super_static_cache').'</a>',
            '<a href="https://www.hitoy.org/super-static-cache-for-wordperss.html">'.__('Support','super_static_cache').'</a>',
            '<a href="https://www.hitoy.org/super-static-cache-for-wordperss.html#Donations">'.__('Donate','super_static_cache').'</a>'
        );
        $links = array_merge($links,$link);
    }
    return $links;
}
add_filter('plugin_row_meta','ssc_row_meta',10,2);


//判断伪静态是否配置好
function is_rewrite_ok(){
    global $wpssc;
    if($wpssc->iscompress){
        if(@fopen($wpssc->siteurl."/rewrite_ok.html","r")){
            return true;
        }
    }else{
        if(@fopen($wpssc->siteurl."/rewrite_ok.txt","r")){
            return true;
        }
    }
    return false;
}
//获取web服务器类型
function getwebserver(){
    $software=strtolower($_SERVER["SERVER_SOFTWARE"]);
    switch ($software){
    case strstr($software,"nginx"):
        return "nginx";
        break;
    case strstr($software,"apache"):
        return "apache";
        break;
    case strstr($software,"iis"):
        return "iis";
        break;
    default:
        return "unknown";
    }
}

//获取WP安装目录
function getwpinstallpath(){
    global $wpssc;
    return "/".substr($wpssc->wppath,strlen($wpssc->docroot));
}

//自动更新apache htaccess规则
function insert_htaccess($rule){
    global $wpssc;
    $raw_rules = file_get_contents($wpssc->wppath.".htaccess");
    $rules = $rule."\n".$raw_rules;
    if(!file_put_contents($wpssc->wppath.".htaccess",$rules,LOCK_EX)) return false;
    return true;
}

//设置伪静态规则
function getrewriterule($escape=true){
    if(is_rewrite_ok()) return false;
    $cachemod=get_option("super_static_cache_mode");
    $compress=get_option("super_static_cache_compress");
    $webscr=getwebserver();

    $rules=false;

    $httpdcompressflushreule="\n<IfModule mod_headers.c>\n<FilesMatch \"\.(html|txt)\.gz$\">\nheader set Content-Encoding gzip\nheader set Content-Type text/html\n</FilesMatch>\n</Ifmodule>\n#End Super Static Cache\n";
    $httpdrewriterule="#BEGIN Super Static Cache\n#Must the First Rewrite Rule\n<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteBase {wp_install_dir}\nRewriteRule ^super-static-cache/ - [L]\n\nRewriteCond %{REQUEST_METHOD} !POST\nRewriteCond %{QUERY_STRING} !.*=.*\nRewriteCond %{DOCUMENT_ROOT}{wp_install_dir}super-static-cache/$1{iscompressed} -f\nRewriteRule ^(.*)$ {wp_install_dir}super-static-cache/$1{iscompressed} [L]\n\nRewriteCond %{REQUEST_METHOD} !POST\nRewriteCond %{QUERY_STRING} !.*=.*\nRewriteCond %{DOCUMENT_ROOT}{wp_install_dir}super-static-cache/$1/index.html{iscompressed} -f\nRewriteRule ^(.*)$ {wp_install_dir}super-static-cache/$1/index.html{iscompressed} [L]\n</IfModule>\n";

    $nginxcompressstatic = "#ngx_http_gzip_static_module and ngx_http_gunzip_module Must Be Added To Nginx\n    gzip_static always;\n    gunzip on;\n";
    $nginxrewriterule='#BEGIN Super Static Cache
location {wp_install_dir} {
    {wp_gzip_static_flush}
    if (-f $request_filename) {
        break;
    }
    if ($uri ~ {wp_install_dir}(.*)$){
        set $wpuri $1;
        set $sscfile $document_root{wp_install_dir}super-static-cache/$1;
    }
    set $ssc Y;
    if ($query_string !~ .*=.*){
        set $ssc "${ssc}Y";
    }
    if ($request_method != "POST"){
        set $ssc "${ssc}Y";
    }

    if (-f $sscfile{iscompressed}){
        set $ssc "${ssc}F";
    }
    if (-f $sscfile/index.html{iscompressed}){
        set $ssc "${ssc}I";
    }
   
    if ($ssc = YYYF){
        rewrite . {wp_install_dir}super-static-cache/$wpuri break;
    }
    if ($ssc = YYYI){
        rewrite . {wp_install_dir}super-static-cache/$wpuri/index.html break;
    }

    if (!-e $request_filename){
        rewrite . {wp_install_dir}index.php last;
    }
}
#End Super Static Cache';

    //Apache规则
    if($cachemod == 'serverrewrite' && $webscr == 'apache' && $compress == true){
        $httpdrewriterule=str_replace('{wp_install_dir}',getwpinstallpath(),$httpdrewriterule);
        $httpdrewriterule=str_replace('{iscompressed}','.gz',$httpdrewriterule);
        if($escape){
            $rules=htmlentities($httpdrewriterule.$httpdcompressflushreule);
        }else{
            $rules=$httpdrewriterule.$httpdcompressflushreule;
        }
    }else if($cachemod == 'serverrewrite' && $webscr == 'apache' && $compress == false){
        $httpdrewriterule=str_replace('{wp_install_dir}',getwpinstallpath(),$httpdrewriterule);
        $httpdrewriterule=str_replace('{iscompressed}','',$httpdrewriterule);
        if($escape){
            $rules=htmlentities($httpdrewriterule.$httpdcompressflushreule);
        }else{
            $rules=$httpdrewriterule.$httpdcompressflushreule;
        }
    }
    //Nginx规则
    
    else if($cachemod == 'serverrewrite' && $webscr == 'nginx' && $compress == true){
        $nginxrewriterule=str_replace('{wp_install_dir}',getwpinstallpath(),$nginxrewriterule);
        $nginxrewriterule=str_replace('{iscompressed}','.gz',$nginxrewriterule);
        $nginxrewriterule=str_replace('{wp_gzip_static_flush}',$nginxcompressstatic,$nginxrewriterule);
        if($escape){
            $rules=htmlentities($nginxrewriterule);
        }else{
            $rules=$nginxrewriterule;
        }
    }else if($cachemod == 'serverrewrite' && $webscr == 'nginx' && $compress == false){
        $nginxrewriterule=str_replace('{wp_install_dir}',getwpinstallpath(),$nginxrewriterule);
        $nginxrewriterule=str_replace('{iscompressed}','',$nginxrewriterule);
        $nginxrewriterule=str_replace('{wp_gzip_static_flush}','',$nginxrewriterule);
        if($escape){
            $rules=htmlentities($nginxrewriterule);
        }else{
            $rules=$nginxrewriterule;
        }
    }else {
        $rules = __('Your server type is not detected, Please visit https://www.hitoy.org/super-static-cache-for-wordperss.html for help.','super-static-cache');
    }
    return $rules;
}

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
    if(empty($_POST)) return;
    if(!empty($_POST['super_static_cache_mode'])){
        $super_static_cache_mode=trim($_POST['super_static_cache_mode']);
        update_option('super_static_cache_mode',$super_static_cache_mode);

        $super_static_cache_excet_arr=isset($_POST['super_static_cache_excet'])?$_POST['super_static_cache_excet']:array();
        $super_static_cache_excet = implode(',',$super_static_cache_excet_arr);
        update_option('super_static_cache_excet',$super_static_cache_excet);

        $super_static_cache_strict=($_POST['super_static_cache_strict'] == "true")?true:false;
        update_option('super_static_cache_strict',$super_static_cache_strict);

        $super_static_cache_nocachesinglepage = $_POST['super_static_cache_nocachesinglepage'];
        $super_static_cache_nocachesinglepage = str_replace("\r\n",",",$super_static_cache_nocachesinglepage);
        update_option('super_static_cache_nocachesinglepage',$super_static_cache_nocachesinglepage);
        //Apache 自动更新规则
        if(getwebserver()=='apache' && !is_rewrite_ok()){
            insert_htaccess(getrewriterule(false));
        }
    }

    if(!empty($_POST['super_static_cache_compress'])){
        $super_static_cache_compress = ($_POST['super_static_cache_compress'] == "true")?true:false;
        update_option('super_static_cache_compress',$super_static_cache_compress);
    }

    if(!empty($_POST['update_cache_action_submit'])){
        $update_cache_action_arr=isset($_POST['update_cache_action'])?$_POST['update_cache_action']:array();
        $update_cache_action=implode(',',$update_cache_action_arr);
        update_option('update_cache_action',$update_cache_action);
    }

    if(!empty($_POST['clearcache'])){
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
                clear_category_cache();
                clear_tag_cache();
                clear_post_page_cache("post");
                clear_post_page_cache("page");
            }else if($wpssc->cachemod=="phprewrite" || $wpssc->cachemod=="serverrewrite"){
                $wpssc->delete_cache($wpssc->siteurl."/");
            }
        }
    }
    if(!empty($_POST['clearpostpagecache'])){
        $ids=explode(",",trim($_POST['clearpostpagecache']));
        foreach($ids as $id){
            clear_post_cache($id);
        }
    }
}
