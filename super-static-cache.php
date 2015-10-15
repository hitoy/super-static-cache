<?php
/*
Plugin Name: Super Static Cache
Plugin URI: https://www.hitoy.org/super-static-cache-for-wordperss.html
Description: Super Static Cache is an efficient WordPress caching engine which provides three cache mode. It can reduce the pressure of the database significantly that makes your website faster than ever.
Version: 3.2.8
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

//��ȡ��ǰҳ������
function getpagetype(){
		if(is_trackback()){
				//���µ�trackbackҲ����single, ����is_trackbackҪ����ǰ��
				return 'trackback';
		}else if(is_attachment()){
				//�ĵ���attachmentҲ����single, ����is_attachmentҪ����ǰ��
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

//�ݹ�ɾ���ļ�
//Σ�ղ�������ע��!!!
function delete_uri($uri){
		if(!is_string($uri)) return false;
		if(empty($uri)) return false;

		//���������վĿ¼֮����ļ�����վĿ¼����
		$abspath=str_replace("//","/",str_replace("\\","/",realpath(ABSPATH))."/");
		if(substr($uri,0,strlen($abspath) !== $abspath)) return false;
		if($uri == $abspath) $uri=$uri."/index.html";

		//�ļ�Ŀ¼������
		if(!file_exists($uri)) return false;

		//ɾ���ļ�
		if(is_file($uri)){return unlink($uri);}

		$fh = opendir($uri);
		while(($row = readdir($fh)) !== false){
				$nodelete_uri=array(".","..","rewrite_ok.txt","wp-admin","wp-content","wp-includes",".htaccess","index.php","license.txt","readme.html","wp-activate.php","wp-blog-header.php","wp-comments-post.php","wp-config-sample.php","wp-config.php","wp-cron.php","wp-links-opml.php","wp-load.php","wp-login.php","wp-mail.php","wp-settings.php","wp-signup.php","wp-trackback.php","xmlrpc.php");
				if(in_array($row,$nodelete_uri)) continue;
				if(!is_dir($uri.'/'.$row)){
						unlink($uri.'/'.$row);
				}
				delete_uri($uri.'/'.$row);
		}
		closedir($fh);
		//ɾ���ļ�֮����ɾ������
		@rmdir($uri);
}

//�ݹ鴴��Ŀ¼
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
//��Ŀ¼����Ȩ��
function chmods($path,$mod=0777,$rec=true){
		if($rec==false){
				return chmod($path,$mod);
		}
		if(is_dir($path)){
				chmod($path,$mod);
				$dir=opendir($path);
				while(($file = readdir($dir)) !== false){
						if($file == '.' || $file == '..') continue;
						$ffile=$path.'/'.$file;
						if(is_file($ffile))  chmod($ffile,$mod);
						if(is_dir($ffile)) {
								chmod($ffile,$mod);
								if($rec==true) chmods($ffile,$mod,$rec);
						}
				}
				closedir($dir);
		}
}

//����Զ��url�ĺ���
//�����Զ���������
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
				ini_set('allow_url_fopen','on');
				file_get_contents($url);
		}
}


//����post_id��ȡ������������ص�ҳ��
//���������¸���ʱ��������Щҳ��
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


//������
class WPStaticCache{
		public $wppath;
		public $docroot;
		public $cachemod;
		private $wpinspath;
		private $wpuri;
		private $cachetag;
		private $htmlcontent;
		//�������ҳ�棬Ĭ��
		private $nocachepage = array('admin','404','search','preview','trackback','feed');

		//�Ƿ����ϸ�ģʽ���棬Ĭ�Ͽ���
		//�����ϸ�ģʽ���������û�к�׺����û����"/"��β��uri
		private $isstrict;

		//siteurl
		public $siteurl;

		/*��ʼ����ȡwodpress��super static �����������Ϣ
		 *docroot��������վ��document_root, ע��wordpress���԰�װ�ڶ���Ŀ¼��������������б�Ҫ
		 *wppath unix��ʽ��wordpress�ڷ������ϵ�ʱ����·����ע��ABSPATH��wordperss����������Ѿ����"/"
		 *wpinspath wordpress�����document root�İ�װ·���������ڷ�document root�°�װwordperss�����
		 *wpuri ȥ����װĿ¼֮��������REQUEST_URI
		 */
		public function __construct(){
				//ϵͳ��Ϣ
				$this->docroot = str_replace("//","/",str_replace("\\","/",realpath($_SERVER["DOCUMENT_ROOT"]))."/");
				$this->wppath = str_replace("//","/",str_replace("\\","/",realpath(ABSPATH))."/");
				$this->wpinspath = substr($this->wppath,strlen($this->docroot))."/";
				$this->wpuri = substr($_SERVER["REQUEST_URI"],strlen($this->wpinspath)-1);

				//super static cache������Ϣ
				$this->siteurl = get_option('siteurl');
				$this->cachemod = get_option("super_static_cache_mode");
				$this->isstrict = (bool) get_option('super_static_cache_strict');
				$this->cachetag = "\n<!-- This is the static html file created at ".current_time("Y-m-d H:i:s")." by super static cache -->";
				//��ȡ�û�ָ���Ĳ������ҳ��,����ϵͳ�Զ���ĺϲ���һ��
				$usetnocache=trim(get_option("super_static_cache_excet"));
				$usernocachearr = empty($usetnocache)?array():explode(',',$usetnocache);
				$usernocachearr = array_map('trim',$usernocachearr);
				$this->nocachepage = array_merge($this->nocachepage,$usernocachearr);
		}


		/*��ȡ��ǰ�����Ƿ�֧�ֵ�ǰ����ģʽ
		 * ��֧�ֻ�������:
		 * 1,���湦��û�п���
		 * 2,�̶�����û������
		 * 3,����ģʽΪ��д��������д����û�и���
		 * 4,�����ϸ񻺴�ģʽ���ҹ̶����Ӳ���"/"��û���к�׺���ļ�������
		 * 5,���õ�Ϊ����ģʽ, ���ǹ̶������к���Ŀ¼����, ���ܵ���ĳЩҳ����ַ����ļ�(����403����Ŀ¼�ļ��б�)
		 */
		public function is_permalink_support_cache(){
				$permalink_structure=get_option("permalink_structure");
				//�Թ̶����ӽ��з���
				//��б�ܳ��ֵĵĴ���
				$dircount=substr_count($permalink_structure,'/');
				//ȥ��Ŀ¼֮����ļ���
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

		//��ȡ��ǰҳ�������Ƿ�֧�ֻ���
		private function is_pagetype_support_cache(){
				if (in_array(getpagetype(),$this->nocachepage)){
						return false;
				}
				//��½�û�������
				if(is_user_logged_in()){
						return false;
				}
				return true;
		}


		//����������ʼ���л��棬ע�ᵽtemplate_redirect��
		//ֻ֧��GET��POST��������ʽ
		public function init(){
				if($this->cachemod == 'phprewrite' && file_exists($this->get_cache_fname())){
						//PHP����ģʽʱ���������ƥ�䲢��ȡ��������
						echo file_get_contents($this->get_cache_fname());
						exit();
				}
				//ֻ��GET������������
				if($_SERVER['REQUEST_METHOD'] == 'GET' && $this->cachemod != 'close'){
						ob_start(array($this,'get_request_html'));
						register_shutdown_function(array($this,'save_cache_content'));
				}
		}

		//��ȡ��ǰ����ҳ���HTML����
		public function get_request_html($html){
				$this->htmlcontent=trim($html).$this->cachetag;
				return trim($html);
		}

		//��ȡҪ���浽Ӳ���ϵĻ����ļ��ļ���
		//1, �������ģʽ�رգ�Ҳֱ�ӷ��ؿ�
		//2, ��ǰҳ�����������֧�ֻ��棬��ôֱ�ӷ��ؿ�
		//3, ��url����.������/��βʱ�����ɻ��� (http://www.example.com/a.html��http://www.example.com/a/,�ų������http://www.example.com/a)
		//4, ����ģʽΪphprewrite����serverrewriteʱ������3��������
		//5, ���ϸ�ģʽ������ģʽΪdirectʱ������3��������
		//6, �����������뻺��
		public function get_cache_fname(){
				//1,
				if($this->cachemod == 'close') return false;

				//2,
				if(!$this->is_pagetype_support_cache()) return false;

				//�Ժ��в�ѯ��������й���
				preg_match("/^([^?]+)?/i",$this->wpuri,$match);
				$realname=urldecode($match[1]);
				//ȥ��Ŀ¼֮����ļ���
				$fname=substr($realname,strripos($realname,"/")+1);

				if($this->cachemod == 'serverrewrite' || $this->cachemod == 'phprewrite'){
						$cachedir='super-static-cache';
				}else {
						$cachedir='';
				}

				if($fname == ""){
						//��'/'��β������
						$cachename = $this->wppath.$cachedir.$realname."index.html";
				}else if(strstr($fname,".")){
						//���к�׺������
						$cachename = $this->wppath.$cachedir.$realname;
				}else if($this->cachemod != 'direct'){
						//�����Ƿ��ϸ�ģʽ��ֻҪ����ģʽ��Ϊdirectʱ�������ڻ���
						$cachename = $this->wppath.$cachedir.$realname."/index.html";
				}else if(!$this->isstrict && $this->cachemod == 'direct'){
						//���ϸ�ģʽ�����ǻ���ģʽΪdirectʱ,���ڻ���
						$cachename = $this->wppath.$cachedir.$realname."/index.html";
				}else {
						$cachename = false;
				}
				return $cachename;
		}

		//д�벢���滺�棬���ն���
		//�����������
		//1, url�ܻ��� filename����
		//2, �ļ���������(����ԭ�е��ļ�������д)
		//3, ��������ݲ�Ϊ��
		public function save_cache_content(){
				$filename = $this->get_cache_fname();
				if($filename && !file_exists($filename) && strlen($this->htmlcontent) > 0){
						//������Ż����Ŀ¼
						mkdirs(dirname($filename));
						//����д�뻺��
						file_put_contents($filename,$this->htmlcontent,LOCK_EX);

						//���ڻ���Ĳ���Ȩ��
						if($this->cachemod == 'serverrewrite' || $this->cachemod == 'phprewrite'){
								$cachedir='super-static-cache';
						}else {
								$cachedir='';
						}
						$modir=substr($filename,0,strlen($this->wppath.$cachedir));
						chmods($modir,0777);
				}
		}

		//ɾ������
		//����Ĳ���ҳ��ľ��Ե�ַ
		//��http://localhost/hello-wrold/
		//Ϊ��֧��utf-8�����ʽ����url����urldecode����
		public function delete_cache($url){
				//�������Ĳ����ַ������򷵻�
				if(!is_string($url)) return false;
				//�������URLΪ�գ��򷵻�
				if(strlen($url) == 0 || empty($url)) return false;
				//��������URL���Ǳ���������Ҳ����
				if(stripos($url,$this->siteurl) !== 0) return false;

				//��ʹ��Ŀ¼��װ���������ע��
				$url=urldecode($url);
				$uri=substr($url,strlen($this->siteurl));

				if($this->cachemod == 'serverrewrite' || $this->cachemod == 'phprewrite'){
						$uri=str_replace("//","/",$this->wppath.'super-static-cache'.$uri);
				}else if($this->cachemod == 'direct'){
						$uri=str_replace("//","/",$this->wppath.$uri);
				}
				delete_uri($uri);
				if(file_exists($uri)){
						return false;
				}else{
						return true;
				}
		}

		//�����ݱ��޸�ʱ�������»���
		//����������ID���������۶���
		public function build_post_cache($obj){
				if(is_object($obj) && $obj->comment_post_ID){
						$id= (int) $obj->comment_post_ID;
				}else if(is_int($obj)){
						$id = $obj;
				}else{
						return;
				}
				//������ҳ
				$this->delete_cache($this->siteurl.'/index.html');
				curl($this->siteurl);

				//��������ҳ
				$url=get_permalink($id);
				$this->delete_cache($url);
				curl($url);

				//���º�����ҳ�й���������ҳ��
				$list=get_related_page($id);
				foreach($list as $u){
						$this->delete_cache($u);
						curl($u);
				}
		}

		//��װ����
		public function install(){
				add_option("super_static_cache_mode","close");
				add_option("super_static_cache_strict",false);
				add_option("super_static_cache_excet","author,date,attachment");
				add_option("update_cache_action","publish_post,post_updated,trashed_post,publish_page");

				//����rewrite����Ŀ¼
				if(!file_exists($this->wppath.'super-static-cache')){
						mkdir($this->wppath.'super-static-cache',0777);
				}
				file_put_contents($this->wppath."super-static-cache/rewrite_ok.txt","This is a test file from rewrite rules,please do not to remove it.\n");
				chmods($this->wppath.'super-static-cache',0777);
		}
		//ж�غ���
		public function unistall(){
				delete_option("super_static_cache_mode");
				delete_option("super_static_cache_excet");
				delete_option("super_static_cache_strict");
				delete_option("update_cache_action");
				//ɾ��
				delete_uri($this->wppath."super-static-cache/rewrite_ok.txt");
				delete_uri($this->wppath.'super-static-cache');
				if($this->cachemod=='direct'){
						delete_uri($this->wppath.'index.html');
				}
		}

}

$wpssc = new WPStaticCache();
add_action("template_redirect",array($wpssc,"init"));

//���»���Ķ���
$update_action_list=explode(",",get_option("update_cache_action"));

//�Ѿ�ͨ����˵��û�ֱ�ӷ������ۣ����½�������
if(in_array('comment_post',$update_action_list)){
		function comment_post_hook($id){
				global $wpssc;
				$comment=get_comment($id);
				if($comment->comment_approved=='1'){
						$wpssc->build_post_cache($comment);
				}
		}
		//�������۵Ĺ���
		add_action('comment_post','comment_post_hook');
}

//��̨����չʾ
if(is_admin()){
		//��װ��ж��
		register_activation_hook(__FILE__,array($wpssc,'install'));
		register_deactivation_hook(__FILE__,array($wpssc,'unistall'));

		//�����·��������£�����״̬����ʱ�����»���Ķ���
		foreach($update_action_list as $action){
				add_action($action,array($wpssc,'build_post_cache'));
		}

		//��̨�������
		require_once("super-static-cache-admin.php");

		//��������
		load_plugin_textdomain('super_static_cache', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
