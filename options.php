<?php
global $wpssc;

//设置选择框的状态函数
function theselected($key,$value,$checkbox='checked=checked'){
    $arr_val=explode(",",get_option($key));
    if(in_array($value,$arr_val)){
        echo $checkbox;
        return true;
    }
    return false;
}

/*获取警告信息，主要是对缓存模式选择进行通知
 * 同is_permalink_support_cache
 */
function notice_msg(){
    $permalink_structure=get_option("permalink_structure");
    $cachemod=get_option("super_static_cache_mode");
    $isstrict=get_option("super_static_cache_strict");
    $siteurl=get_option("siteurl");
    //对固定链接进行分析
    //反斜杠出现的的次数
    $dircount=substr_count($permalink_structure,'/');
    //去掉目录之后的文件名
    $fname=substr($permalink_structure,strripos($permalink_structure,"/")+1);

    if($cachemod == 'close'){
        return array(false,__('Cache feature is turned off','super_static_cache'));
    }else if(empty($permalink_structure)){
        return array(false,__('You Must update Permalink to enable Super Static Cache','super_static_cache'));
    }else if($cachemod == 'serverrewrite' && !is_rewrite_ok()){
        return array(false,__('Rewrite Rules Not Update!','super_static_cache'));
    }else if($isstrict && $fname != "" && !strstr($fname,".")){
        return array(false,__('Strict Cache Mode not Support current Permalink!','super_static_cache'));
    }else if($cachemod == 'direct' && $dircount > 2){
        return array(false,__('Cache is enabled, But Some Pages May return 403 status or a index page cause your Permalink Settings','super_static_cache'));
    }
    return array(true,__('OK','super_static_cache'));
}

?>

<div class="wrap">
<style>
.ssc_menu {width:98%;font-size:15px;height:40px;line-height:40px;border-bottom:1px solid #ccc;padding-left:2%;margin-bottom:20px}
.ssc_menu a {display:block;width:100px;float:left;padding:0 10px;border:1px solid #ccc;border-bottom:none;text-align:center;cursor:pointer;margin-left:-1px;margin-bottom:-1px;font-weight:bold;text-decoration:none}
.ssc_menu a:hover {background:white;}
.ssc_menu a.selected {background:white;}
h3 {margin-left:12px;}
div label {display:inline-block;margin-left:5px;margin-right:20px}
div label:first-child {display:inline-block;width:200px}
.updaterewrite {display:none}
.updaterewrite pre {padding:10px;border:1px solid #333;background:#eee;overflow:auto}
.setcachestrict {display:none}
.setcompress {display:none}
textarea {width:80%;height:100px}
</style>
<script>
jQuery(function(){
    jQuery("[name='super_static_cache_mode']").bind("click",function(){
            var index = jQuery(this).index("[name='super_static_cache_mode']");
            if(index === 0){
                jQuery(".setcachestrict").css("display","none");
                jQuery(".setcompress").css("display","none");
            }else if(index === 1){
                jQuery(".setcachestrict").css("display","block");
                jQuery(".setcompress").css("display","none");
            }else if(index === 2 || index == 3){
                jQuery(".setcachestrict").css("display","none");
                jQuery(".setcompress").css("display","block");
            }
    })
});
</script>
<?php
$notice=notice_msg();
if($notice[0] === false){
    echo "<div id=\"message\" class=\"error\"><p>".$notice[1]."</p></div>";
}
?>
    <h2><?php _e('Super Static Cache Settings','super_static_cache');?></h2><br/>
    <div class="ssc_menu">
        <a href="?page=Super-Static-Cache&tab=general" <?php if(empty($_GET['tab']) || $_GET['tab'] === 'general'){echo 'class="selected"';}?> ><?php _e('General','super_static_cache');?></a>
        <a href="?page=Super-Static-Cache&tab=advanced" <?php if(!empty($_GET['tab']) && $_GET['tab'] === 'advanced'){echo 'class="selected"';}?> ><?php _e('Advanced','super_static_cache');?></a>
    </div>
<?php
if(empty($_GET['tab']) || $_GET['tab'] === 'general'){
    require_once(dirname(__FILE__)."/options-general.php");
}else if($_GET['tab'] == 'advanced'){
    require_once(dirname(__FILE__)."/options-advanced.php");
}
?>
    <div class="postbox">
        <h3 class="hndle"><?php _e('About','super_static_cache');?></h3>
            <div class="inside">
<?php
_e('<p>Super Static Cache is developing and maintaining by <a href="https://www.hitoy.org/">Hito</a>.<br/>It is a advanced fully static cache plugin, with easy configuration and high efficiency. When a post cached, It will no longer need the Database. It is a better choice when your posts more than 5000.</p><p>Have any suggestions, please contact vip@hitoy.org.</p><h4>Rating for This Plugin</h4><p>Please <a href="http://wordpress.org/support/view/plugin-reviews/super-static-cache" target="_blank">Rating for this plugin</a> and tell me your needs. This is very useful for my development.</p><h4>Donation</h4><p>You can Donate to this plugin to let this plugin further improve.</p><form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank"><input type="hidden" name="cmd" value="_s-xclick"><input type="hidden" name="hosted_button_id" value="3EL4H6L7LY3YS"><input type="image" src="https://www.hitoy.org/wp-content/uploads/donate_paypal.gif" border="0" name="submit" alt="PayPal"><img border="0" src="https://www.paypal.com/en_GB/i/btn/btn_donateCC_LG.gif" width="1" height="1"></form>','super_static_cache');
?>
            </div>
    </div>
</div>
