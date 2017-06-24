<?php
/*
Plugin Name: Super Static Cache
Plugin URI: https://www.hitoy.org/super-static-cache-for-wordperss.html
Description: Super Static Cache is an efficient WordPress caching engine which provides three cache mode. It can reduce the pressure of the database significantly that makes your website faster than ever.
Version: 3.3.5
Author: Hito
Author URI: https://www.hitoy.org/
Text Domain: super_static_cache
Domain Path: /languages/
License: GPL2
 */
/*  Copyright 2017 hitoy  (email : vip@hitoy.org)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

//获取当前页面类型
function getpagetype(){
    if(is_trackback()){
        //文章的trackback也属于single, 所以is_trackback要放在前面
        return 'trackback';
    }else if(is_attachment()){
        //文档的attachment也属于single, 所以is_attachment要放在前面
        return 'attachment';
    }else if(is_feed()){
        return 'feed';
    }else if(is_admin()){
        return 'admin';
    }else if(is_preview()){
        return 'preview';
    }else if(is_404()){
        return '404';
    }else if(is_search()){
        return 'search';
    }else if(is_home()){
        return 'home';
    }else if(is_single()){
        return 'single';
    }else if(is_page()){
        return 'page';
    }else if(is_author()){
        return 'author';
    }else if(is_tag()){
        return 'tag';
    }else if(is_category()){
        return 'category';
    }else if(is_paged()){
        return 'paged';
    }else if(is_date()){
        return 'date';
    }
    return 'notfound';
}

//递归删除文件
//危险操作，请注意!!!
function delete_uri($uri){
    if(!is_string($uri)) return false;
    if(empty($uri)) return false;

    //不能清除网站目录之外的文件和网站目录本身
    $abspath=str_replace("//","/",str_replace("\\","/",realpath(ABSPATH))."/");
    if(substr($uri,0,strlen($abspath)) != $abspath) return false;

    /////Direct:首页缓存处理
    if($uri == $abspath){
        unlink($uri."/index.html");
        unlink($uri."/index.html.gz");
        return;
    }

    //文件目录不存在
    if(!file_exists($uri)) return false;

    //删除文件
    if(is_file($uri)){return unlink($uri);}

    $fh = opendir($uri);
    while(($row = readdir($fh)) !== false){
        $nodelete_uri=array(".","..","rewrite_ok.txt","rewrite_ok.html.gz","wp-admin","wp-content","wp-includes",".htaccess","index.php","license.txt","readme.html","wp-activate.php","wp-blog-header.php","wp-comments-post.php","wp-config-sample.php","wp-config.php","wp-cron.php","wp-links-opml.php","wp-load.php","wp-login.php","wp-mail.php","wp-settings.php","wp-signup.php","wp-trackback.php","xmlrpc.php");
        if(in_array($row,$nodelete_uri)) continue;
        if(!is_dir($uri.'/'.$row)){
            unlink($uri.'/'.$row);
        }
        delete_uri($uri.'/'.$row);
    }
    closedir($fh);
    //删除文件之后再删除自身
    @rmdir($uri);
}

//递归创建目录
function mkdirs($path){
    if(is_dir($path)){
        return true;
    }
    if(!mkdirs(dirname($path))){
        return false;
    }
    if(!mkdir($path)){
        return false;
    }
    return true;
}
//给目录赋予权限
function chmods($path,$dirmod=0777,$filemod=0666,$rec=true){
    if($rec==false || is_file($path)){
        return @chmod($path,$filemod);
    }
    if(is_dir($path)){
        @chmod($path,$dirmod);
        $dir=opendir($path);
        while(($file = readdir($dir)) !== false){
            if($file == '.' || $file == '..') continue;
            $ffile=$path.'/'.$file;
            if(is_file($ffile))  @chmod($ffile,$filemod);
            if(is_dir($ffile)) {
                @chmod($ffile,$dirmod);
                if($rec==true) chmods($ffile,$dirmod,$filemod,$rec);
            }
        }
        closedir($dir);
    }
}

//访问远程url的函数
//用来自动建立缓存
function ssc_curl($url){
    if(function_exists("curl_init")){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER,'https://www.hitoy.org/');
        curl_setopt($ch, CURLOPT_TIMEOUT,5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT,'SSCS/3 (Super Static Cache Spider/3; +https://www.hitoy.org/super-static-cache-for-wordperss.html#Spider)');
        curl_exec($ch);
        curl_close($ch);
    }else{
        @ini_set('allow_url_fopen','on');
        @file_get_contents($url);
    }
}


//根据post_id获取所有与文章相关的页面
//用来在文章更新时，更新这些页面
function get_related_page($post_id,$include=array("home","next","prev","category","tag")){
    $urls=array();
    //Home Page and Paged
    if(in_array("home",$include)){
        $home = get_option('siteurl');
        array_push($urls,$home."/index.html");
        array_push($urls,$home."/page/");
    }

    //next and pre post
    if(in_array("next",$include)){
        $netpost = get_next_post();
        array_push($urls,get_permalink($netpost->ID));
    }
    if(in_array("prev",$include)){
        $prepost = get_previous_post();
        array_push($urls,get_permalink($prepost->ID));
    }

    //category
    if(in_array("category",$include)){
        $cates = get_the_category($post_id);
        foreach($cates as $c){
            array_push($urls,get_category_link($c->term_id));
        }
    }

    //tag
    if(in_array("tag",$include)){
        $tags = get_the_tags($post_id);
        if($tags){
            foreach($tags as $t){
                array_push($urls,get_tag_link($t->term_id));
            }
        }
    }
    return $urls;
}


//缓存类
class WPStaticCache{
    public $wppath;
    public $docroot;
    public $cachemod;
    private $wpinspath;
    private $wpuri;
    private $cachetag;
    private $htmlcontent;
    //不缓存的页面类型，默认
    private $nocachepage = array('admin','404','search','preview','trackback','feed');
    //不缓存的单页面，存放全部网址
    private $nocachesinglepage = array();

    //是否是严格模式缓存，默认开启
    //开启严格模式将不缓存既没有后缀，又没有以"/"结尾的uri
    private $isstrict;

    //siteurl
    public $siteurl;

    /*初始化获取wodpress和super static 的相关配置信息
     *docroot代表是网站的document_root, 注意wordpress可以安装在二级目录，所以这个配置有必要
     *wppath unix格式的wordpress在服务器上的时间存放路径，注意ABSPATH是wordperss常量，最后已经添加"/"
     *wpinspath wordpress相对于document root的安装路径，适用于非document root下安装wordperss的情况
     *wpuri 去除安装目录之后的请求的REQUEST_URI
     */
    public function __construct(){
        //系统信息
        $this->docroot = str_replace("//","/",str_replace("\\","/",realpath($_SERVER["DOCUMENT_ROOT"]))."/");
        $this->wppath = str_replace("//","/",str_replace("\\","/",realpath(ABSPATH))."/");
        $this->wpinspath = substr($this->wppath,strlen($this->docroot))."/";
        $this->wpuri = substr($_SERVER["REQUEST_URI"],strlen($this->wpinspath)-1);

        //super static cache配置信息
        $this->siteurl = get_option('siteurl');
        $this->cachemod = get_option("super_static_cache_mode");
        $this->isstrict = (bool) get_option('super_static_cache_strict');
        $this->iscompress = (bool) get_option('super_static_cache_compress');
        $this->cachetag = "\n<!-- This is the static html file created at ".current_time("Y-m-d H:i:s")." by super static cache -->";
        //获取用户指定的不缓存的页面,并和系统自定义的合并到一块
        $usetnocache=trim(get_option("super_static_cache_excet"));
        $usernocachearr = empty($usetnocache)?array():explode(',',$usetnocache);
        $usernocachearr = array_map('trim',$usernocachearr);
        $this->nocachepage = array_merge($this->nocachepage,$usernocachearr);
        //获取不缓存的单页
        $nocachesinglepage = trim(get_option('super_static_cache_nocachesinglepage'));
        $this->nocachesinglepage = empty($nocachesinglepage)?array():explode(',',$nocachesinglepage);
        $this->nocachesinglepage = array_map('trim',$this->nocachesinglepage);
    }


    /*获取当前配置是否支持当前缓存模式
     * 不支持缓存的情况:
     * 1,缓存功能没有开启
     * 2,固定链接没有设置
     * 3,缓存模式为重写，但是重写规则没有更新
     * 4,开启严格缓存模式，且固定链接不以"/"且没有有后缀的文件名结束
     * 5,设置的为常规模式, 但是固定连接中含有目录设置, 可能导致某些页面出现访问文件(返回403或者目录文件列表)
     */
    public function is_permalink_support_cache(){
        $permalink_structure=get_option("permalink_structure");
        //对固定链接进行分析
        //反斜杠出现的的次数
        $dircount=substr_count($permalink_structure,'/');
        //去掉目录之后的文件名
        $fname=substr($permalink_structure,strripos($permalink_structure,"/")+1);

        if($this->cachemod == 'close'){
            return array(false,__('Cache feature is turned off'));
        }else if(empty($permalink_structure)){
            return array(false,__('You Must update Permalink to enable Super Static Cache','super_static_cache'));
        }else if($this->cachemod == 'serverrewrite' && !@fopen($this->siteurl."/rewrite_ok.txt","r")){
            return array(false,__('Rewrite Rules Not Update!','super_static_cache'));
        }else if($this->isstrict && $fname != "" && !strstr($fname,".")){
            return array(false,__('Strict Cache Mode not Support current Permalink!','super_static_cache'));
        }else if($this->cachemod == 'direct' && $dircount > 2){
            return array(false,__('Cache is enabled, But Some Pages May return 403 status or a index page cause your Permalink Settings','super_static_cache'));
        }
        return array(true,__('OK','super_static_cache'));
    }

    //获取当前页面是否设置为缓存
    private function is_page_support_cache(){
        //robots.txt不缓存
        if($this->wpuri == '/robots.txt'){
            return false;
        }
        //当前页面类型不缓存
        if(in_array(getpagetype(),$this->nocachepage)){
            return false;
        }
        //用户设置当前页面不缓存
        if(in_array($this->siteurl.$this->wpuri,$this->nocachesinglepage)){
            return false;
        }
        //登陆用户不缓存
        if(is_user_logged_in()){
            return false;
        }
        return true;
    }


    //主函数，开始进行缓存，注册到template_redirect上
    //只支持GET和POST两种请求方式
    public function init(){
        if($this->cachemod == 'phprewrite' && file_exists($this->get_cache_fname())){
            //PHP缓存模式时，这里进行匹配并获取缓存内容
            if($this->iscompress == true){
                header("Content-Encoding:gzip");
                header("Content-Type:text/html");
            }
            echo file_get_contents($this->get_cache_fname());
            exit();
        }
        //只对GET请求作出缓存
        if($_SERVER['REQUEST_METHOD'] == 'GET' && $this->cachemod != 'close'){
            ob_start(array($this,'get_request_html'));
            register_shutdown_function(array($this,'save_cache_content'));
        }
    }

    //获取当前访问页面的HTML内容
    public function get_request_html($html){
        if($this->iscompress == true && function_exists('gzencode')){
            $this->htmlcontent=gzencode(trim($html).$this->cachetag,9);
        }else{
            $this->htmlcontent=trim($html).$this->cachetag;
        }
        return trim($html);
    }

    //获取要缓存到硬盘上的缓存文件文件名
    //1, 如果缓存模式关闭，也直接返回空
    //2, 当前页面类型如果不支持缓存，那么直接返回空
    //3, 当url含有.或者以/结尾时，都可缓存 (http://www.example.com/a.html或http://www.example.com/a/,排除的情况http://www.example.com/a)
    //4, 缓存模式为phprewrite或者serverrewrite时，缓存3以外的情况
    //5, 非严格模式，缓存模式为direct时，缓存3以外的情况
    //6, 其它均不给与缓存
    public function get_cache_fname(){
        //1,
        if($this->cachemod == 'close') return false;

        //2,
        if(!$this->is_page_support_cache()) return false;
        //对含有查询的情况进行过滤
        preg_match("/^\/([^?]+)?/i",$this->wpuri,$match);
        $realname=!empty($match[1])?urldecode($match[1]):"";

        //去掉目录之后的文件名
        $fname=substr($realname,strripos($realname,"/")+1);

        if($this->cachemod == 'serverrewrite' || $this->cachemod == 'phprewrite'){
            $cachedir='super-static-cache/';
        }else if($this->cachemod == 'direct'){
            $cachedir='';
        }
        if($fname == ""){
            //以'/'结尾的请求
            $cachename = $this->wppath.$cachedir.$realname."index.html";
        }else if(strstr($fname,".")){
            //含有后缀的请求
            $cachename = $this->wppath.$cachedir.$realname;
        }else if($this->cachemod != 'direct'){
            //不管是否严格模式，只要缓存模式不为direct时，都给于缓存
            $cachename = $this->wppath.$cachedir.$realname."/index.html";
        }else if(!$this->isstrict && $this->cachemod == 'direct'){
            //非严格模式，但是缓存模式为direct时,给于缓存
            $cachename = $this->wppath.$cachedir.$realname."/index.html";
        }else {
            $cachename = false;
        }
        if($this->iscompress==true) $cachename.=".gz";
        return $cachename;
    }

    //写入缓存，并赋予相应的文件权限便于其它工具进行处理
    //满足三种情况
    //1, url能缓存 filename存在
    //2, 缓存的内容不为空
    //3, 文件名不存在(保护原有的文件不被改写)
    public function save_cache_content(){
        $filename = $this->get_cache_fname();
        if($filename && strlen($this->htmlcontent) > 0 && !file_exists($filename)){
            //创建存放缓存的目录
            mkdirs(dirname($filename));
            //加锁写入缓存
            file_put_contents($filename,$this->htmlcontent,LOCK_EX);

            //对缓存文件的权限进行更改
            $relauri=substr($filename,strlen($this->wppath)-1);
            preg_match("/^\/(super-static-cache\/)?(.*)$/i",$relauri,$match);
            $realname=!empty($match[2])?$match[2]:"";
            $relapath=substr($realname,0,strpos($realname,'/'));
            if($relapath==""){
                chmods($filename,0777,0666,false);
            }else if($this->cachemod == "direct"){
                chmods($this->wppath.$relapath,0777,0666,true);
            }else if($this->cachemod == "serverrewrite" || $this->cachemod == "phprewrite"){
                chmods($this->wppath."super-static-cache/".$relapath,0777,0666,true);
            }
        }
    }

    //删除缓存
    //传入的参数页面的绝对地址
    //如http://localhost/hello-wrold/
    //为了支持utf-8缓存格式，对url进行urldecode处理
    public function delete_cache($url){
        //如果传入的不是字符串，则返回
        if(!is_string($url)) return false;
        //如果传入URL为空，则返回
        if(strlen($url) == 0 || empty($url)) return false;
        //如果传入的URL不是本域名，则也返回
        if(stripos($url,$this->siteurl) !== 0) return false;

        //对使用目录安装的情况进行注册
        $url=urldecode($url);
        $uri=substr($url,strlen($this->siteurl));

        if($this->cachemod == 'serverrewrite' || $this->cachemod == 'phprewrite'){
            $uri=str_replace("//","/",$this->wppath.'super-static-cache'.$uri);
        }else if($this->cachemod == 'direct'){
            $uri=str_replace("//","/",$this->wppath.$uri);
        }
       //如果系统开启压缩功能，并且URI不是目录，则URI为压缩缓存文件
       if($this->iscompress && !is_dir($uri)) $uri .=".gz"; 
        delete_uri($uri);
        if(file_exists($uri)){
            return false;
        }else{
            return true;
        }
    }

    //当内容被修改时建立文章缓存
    //参数，文章ID，或者评论对象
    public function build_post_cache($obj){
        if(is_object($obj) && $obj->comment_post_ID){
            $id= (int) $obj->comment_post_ID;
        }else if(is_int($obj)){
            $id = $obj;
        }else{
            return;
        }
        //更新文章页
        $url=get_permalink($id);
        $this->delete_cache($url);
        ssc_curl($url);

        //更新和文章页有关联的其它页面
        $list=get_related_page($id);
        foreach($list as $u){
            $this->delete_cache($u);
            ssc_curl($u);
        }
    }

    //安装函数
    public function install(){
        add_option("super_static_cache_mode","close");
        add_option("super_static_cache_strict",true);
        add_option("super_static_cache_compress",false);
        add_option("super_static_cache_excet","author,date,attachment");
        add_option("super_static_cache_nocachesinglepage","");
        add_option("update_cache_action","publish_post,post_updated,trashed_post,publish_page");

        //创建rewrite缓存目录
        if(!file_exists($this->wppath.'super-static-cache')){
            mkdir($this->wppath.'super-static-cache',0777);
        }
        file_put_contents($this->wppath."super-static-cache/rewrite_ok.txt","This is a test file from rewrite rules,please do not to remove it.\n");
        file_put_contents($this->wppath."super-static-cache/rewrite_ok.html.gz",gzencode("This is a test file from rewrite rules,please do not to remove it.\n"));
        chmods($this->wppath.'super-static-cache',0777,0444,true);
    }
    //卸载函数
    public function unistall(){
        delete_option("super_static_cache_mode");
        delete_option("super_static_cache_excet");
        delete_option("super_static_cache_strict");
        delete_option("super_static_cache_compress");
        delete_option("super_static_cache_nocachesinglepage");
        delete_option("update_cache_action");
        //删除一些必要的缓存
        delete_uri($this->wppath."super-static-cache/rewrite_ok.txt");
        delete_uri($this->wppath."super-static-cache/rewrite_ok.html.gz");
        delete_uri($this->wppath.'super-static-cache');
        if($this->cachemod=="direct" && is_file($this->wppath."index.html")){
            delete_uri($this->wppath."index.html");
        }
        if($this->cachemod=="direct" && is_file($this->wppath."index.html.gz")){
            delete_uri($this->wppath."index.html.gz");
        }
    }
}

$wpssc = new WPStaticCache();
add_action("template_redirect",array($wpssc,"init"));

//更新缓存的动作
$update_action_list=explode(",",get_option("update_cache_action"));

//已经通过审核的用户直接发布评论，重新建立缓存
if(in_array('comment_post',$update_action_list)){
    function comment_post_hook($id){
        global $wpssc;
        $comment=get_comment($id);
        if($comment->comment_approved=='1'){
            $wpssc->build_post_cache($comment);
        }
    }
    //发布评论的钩子
    add_action('comment_post','comment_post_hook');
}

//后台界面展示
if(is_admin()){
    //安装和卸载
    register_activation_hook(__FILE__,array($wpssc,'install'));
    register_deactivation_hook(__FILE__,array($wpssc,'unistall'));

    //当文章发布，更新，评论状态更改时，更新缓存的动作
    foreach($update_action_list as $action){
        add_action($action,array($wpssc,'build_post_cache'));
    }

    //后台管理界面
    require_once("super-static-cache-admin.php");

    //加载语言
    load_plugin_textdomain('super_static_cache', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
