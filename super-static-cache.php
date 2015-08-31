<?php
/*
Plugin Name: Super Static Cache
Plugin URI: https://www.hitoy.org/super-static-cache-for-wordperss.html
Description: Super Static Cache is an efficient WordPress caching engine which provides three cache mode. It can reduce the pressure of the database significantly that makes your website faster than ever.
Version: 3.2.3
Author: Hito
Author URI: https://www.hitoy.org/
Text Domain: super_static_cache
Domain Path: /languages/
License: GPL2
*/
/*  Copyright 2015 hitoy  (email : vip@hitoy.org)

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
    }else if(is_single()){
        return 'single';
    }else if(is_tag()){
        return 'tag';
    }else if(is_category()){
        return 'category';
    }else if(is_page()){
        return 'page';
    }else if(is_home()){
        return 'home';
    }else if(is_archive()){
        return 'archive';
    }
    return 'notfound';
}

//递归删除文件
function delete_uri($uri){
    if(!file_exists($uri)) return '';
    if(is_file($uri)){return unlink($uri);}
    $fh = opendir($uri);  
    while(($row = readdir($fh)) !== false){  
        if($row == '.' || $row == '..' || $row == 'rewrite_ok.txt'){  
            continue;  
        }  
        if(!is_dir($uri.'/'.$row)){  
            unlink($uri.'/'.$row);  
        }  
        delete_uri($uri.'/'.$row);  
    }  
    closedir($fh);  
    //删除文件之后再删除自身  
    @rmdir($uri); 
}

//访问远程url的函数
//用来自动建立缓存
function curl($url){
    if(function_exists("curl_init")){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_REFERER,$url);
        curl_setopt($ch, CURLOPT_TIMEOUT,10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT,'SSCS/3 (Super Static Cache Spider/3; +https://www.hitoy.org/super-static-cache-for-wordperss.html#Spider)');
        curl_exec($ch); 
        curl_close($ch); 
    }else{
        file_get_contents($url);
    }
}


//根据post_id获取所有与文章相关的页面
//用来在文章更新时，更新这些页面
function get_related_page($post_id){
    $urls=array();
    //category
    $cates = get_the_category($post_id);
    foreach($cates as $c){
        array_push($urls,get_category_link($c->term_id));
    }
    //tag
    $tags = get_the_tags($post_id);
    if($tags){
        foreach($tags as $t){
            array_push($urls,get_tag_link($t->term_id));
        }
    }
    return $urls;
}


//缓存类
class WPStaticCache{
    public $wppath;            //WP安装路径，服务器的绝对路径
    public $docroot;           //网站的DOCUMENT_ROOT
    public $cachemod;          //缓存方式，关闭，直接缓存，服务器重写，PHP重写
    private $wpuri;             //用户访问的页面在服务器上存放的地址,相对于wp安装目录
    private $cachetag;
    private $htmlcontent;
    //不缓存的页面，默认
    private $nocachepage = array('admin','404','search','preview','trackback','feed');

    //是否是严格模式缓存，默认开启
    //开启严格模式将不缓存既没有后缀，又没有以"/"结尾的uri
    private $isstrict;          

    //siteurl
    public $siteurl;

    public function __construct(){
        $this->docroot = str_replace("//","/",str_replace("\\","/",realpath($_SERVER["DOCUMENT_ROOT"]))."//");
        $this->wppath = str_replace("\\","/",ABSPATH);
        $this->cachemod = get_option("super_static_cache_mode");
        $this->cachetag="\n<!-- This is the static html file created at ".current_time("Y-m-d H:i:s")." by super static cache -->";
        $this->isstrict = (bool) get_option('super_static_cache_strict');

        //获取用户指定的不缓存的页面,并和系统自定义的合并到一块
        $usetnocache=trim(get_option("super_static_cache_excet"));
        $usernocachearr=empty($usetnocache)?array():explode(',',$usetnocache);
        $usernocachearr=array_map('trim',$usernocachearr);
        $this->nocachepage=array_merge($this->nocachepage,$usernocachearr);

        //获取wpuri,相对与WP安装目录
        $fullrequesturi=$this->docroot.urldecode($_SERVER["REQUEST_URI"]);
        $this->wpuri=str_replace("//","/",$fullrequesturi);
        $this->wpuri=substr($fullrequesturi,strlen($this->wppath));

        $this->siteurl=get_option('siteurl');
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

    //获取当前页面类型是否支持缓存
    private function is_pagetype_support_cache(){
        if (in_array(getpagetype(),$this->nocachepage)){
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
            echo file_get_contents($this->get_cache_fname());
            exit();
        }
        //只对GET请求作出缓存
        if($_SERVER['REQUEST_METHOD'] == "GET"){
            ob_start(array($this,"get_request_html"));
            register_shutdown_function(array($this,"save_cache_content"));
        }
    }

    //获取当前访问页面的HTML内容
    public function get_request_html($html){
        $this->htmlcontent=trim($html).$this->cachetag;
        return trim($html);
    }

    //获取要缓存到硬盘上的缓存文件文件名
    //1, 如果缓存模式关闭，也直接返回空
    //2, 当前页面类型如果不支持缓存，那么直接返回空
    //3, 当uri含有.或者以/结尾时，都可缓存 (http://www.example.com/a.html或http://www.example.com/a/,排除的情况http://www.example.com/a)
    //4, 缓存模式为phprewrite或者serverrewrite时，缓存3以外的情况
    //5, 非严格模式，缓存模式为direct时，缓存3以外的情况
    //6, 其它均不给与缓存
    public function get_cache_fname(){
        //1,
        if($this->cachemod == 'close') return false;

        //2,
        if(!$this->is_pagetype_support_cache()) return false;

        preg_match("/^([^?]+)?/i",$this->wpuri,$match);
        $realname=urldecode($match[1]);
        //去掉目录之后的文件名
        $fname=substr($realname,strripos($realname,"/")+1);

        if($this->cachemod == 'serverrewrite' || $this->cachemod == 'phprewrite'){
            $cachedir='super-static-cache';
        }else {
            $cachedir='';
        }

        if($fname == ""){
            //以'/'结尾的请求
            $cachename = $this->wppath.$cachedir.$realname."index.html";
        }else if(strstr($fname,".")){
            //含有后置的请求
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
        return $cachename;
    }

    //写入并保存缓存，最终动作
    //满足三种情况
    //1, url能缓存 filename存在
    //2, 第一次缓存
    //3, 缓存的内容不为空
    public function save_cache_content(){
        $filename = $this->get_cache_fname();
        if($filename && !file_exists($filename) && strlen($this->htmlcontent) > 0){

            if(!file_exists(dirname($filename))){
                //上级目录不存在的时候，创建递归目录
                //注意这里的PHP版本必须在5.0.0以上
                @mkdir(dirname($filename),0777,true);
            }
            file_put_contents($filename,$this->htmlcontent,LOCK_EX);
        }
    }

    //删除缓存
    //传入的参数页面的绝对地址
    //如http://localhost/hello-wrold/
    //为了支持utf-8缓存格式，对url进行urldecode处理
    public function delete_cache($url){
        if(strlen($url) == 0) return false;
        $url=urldecode($url);
        $uri=substr($url,strlen($this->siteurl));
        if($this->cachemod == 'serverrewrite' || $this->cachemod == 'phprewrite'){
            $uri=$this->wppath.'super-static-cache'.$uri;
        }else if($this->cachemod == 'direct'){
            $uri=$this->wppath.$uri;
        }
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
        //更新首页
        $this->delete_cache($this->siteurl.'/index.html');
        curl($this->siteurl);

        //更新文章页
        $url=get_permalink($id);
        $this->delete_cache($url);
        curl($url);

        //更新和文章页有关联的其它页面
        $list=get_related_page($id);
        foreach($list as $u){
            $this->delete_cache($u);
            curl($u);
        }
    }

    //安装函数
    public function install(){
        add_option("super_static_cache_mode","close");
        add_option("super_static_cache_strict",false);
        add_option("super_static_cache_excet","author,date,attachment");
        add_option("update_cache_action","publish_post,post_updated,trashed_post,publish_page");

        //创建rewrite缓存目录
        if(!file_exists($this->wppath.'super-static-cache')){
            @mkdir($this->wppath.'super-static-cache',0777,true);
        }
        file_put_contents($this->wppath."super-static-cache/rewrite_ok.txt","This is a test file from rewrite rules,please do not to remove it.\n");
    }
    //卸载函数
    public function unistall(){
        delete_option("super_static_cache_mode");
        delete_option("super_static_cache_excet");
        delete_option("super_static_cache_strict");
        delete_option("update_cache_action");
        //删除
        unlink($this->wppath."super-static-cache/rewrite_ok.txt");
        delete_uri($this->wppath.'super-static-cache');
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
