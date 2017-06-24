<div class="advanced">
    <div class="postbox">
        <h3 class="hndle"><?php _e('Cache build action','super_static_cache');?></h3>
        <div class="inside">
            <form method="post" action="">
                <?php _e('<p>When the following actions occur, the cache will be generate/regenerate.</p>','super_static_cache');?>
                <div><label><?php _e('Publish a Post','super_static_cache');?></label><input type="checkbox" name="update_cache_action[]" value="publish_post" <?php theselected('update_cache_action','publish_post');?>></div>
                <div><label><?php _e('Update a Post','super_static_cache');?></label><input type="checkbox" name="update_cache_action[]" value="post_updated" <?php theselected('update_cache_action','post_updated');?>></div>
                <div><label><?php _e('Trash a Post','super_static_cache');?></label><input type="checkbox" name="update_cache_action[]" value="trashed_post" <?php theselected('update_cache_action','trashed_post');?>></div>
                <div><label><?php _e('Publish a Page','super_static_cache');?></label><input type="checkbox" name="update_cache_action[]" value="publish_page" <?php theselected('update_cache_action','publish_page');?>></div>
                <div><label><?php _e('Approve a Comment','super_static_cache');?></label><input type="checkbox" name="update_cache_action[]" value="comment_post,comment_unapproved_to_approved" <?php theselected('update_cache_action','comment_unapproved_to_approved');?>></div>
                <div><label><?php _e('Trash a Comment','super_static_cache');?></label><input type="checkbox" name="update_cache_action[]" value="comment_approved_to_trash" <?php theselected('update_cache_action','comment_approved_to_trash');?>></div>
                <div><label><?php _e('Mark a Comment as spam','super_static_cache');?></label><input type="checkbox" name="update_cache_action[]" value="comment_approved_to_spam" <?php theselected('update_cache_action','comment_approved_to_spam');?>></div><br/>
                <input type="submit" name="update_cache_action_submit" class="button-primary" value="<?php _e('Save','super_static_cache')?>">
            </div>
        </form>
    </div>
    <div class="postbox">
        <h3 class="hndle"><?php _e('Purge Cache','super_static_cache');?></h3>
        <div class="inside">
            <form method="post" action="">
                <?php _e('<p>Clear cached files to force server to fetch a fresh version. You can purge files selectively or all at once.</p>','super_static_cache');?>
                <div><label><?php _e('Home','super_static_cache');?></label><input type="checkbox" name="clearcache[]" value="home"/></div>
                <div><label><?php _e('Single','super_static_cache');?></label><input type="checkbox" name="clearcache[]" value="single"/></div>
                <div><label><?php _e('Page','super_static_cache');?></label><input type="checkbox" name="clearcache[]" value="page"/></div>
                <div><label><?php _e('Category','super_static_cache');?></label><input type="checkbox" name="clearcache[]" value="category"/></div>
                <div><label><?php _e('Tag','super_static_cache');?></label><input type="checkbox" name="clearcache[]" value="tag"/></div>
                <div><label><?php _e('All','super_static_cache');?></label><input type="checkbox" name="clearcache[]" value="all"/></div>
                <div><label><?php _e('Purge Individual Posts','super_static_cache');?></label><input type="input" name="clearpostpagecache" /><span><?php _e('Please Enter the title or id of a post, separate with commas','super_static_cache');?></span></div><br/>
                <input type="submit" class="button-secondary" value="<?php _e('Purge Caches','super_static_cache')?>">
            </div>
        </form>
    </div>
</div>
