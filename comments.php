<?php
if(post_password_required()) return;
$commenter=wp_get_current_commenter();$required=(bool)get_option('require_name_email');$req=$required?' required aria-required="true"':'';
$remembered=!is_user_logged_in()&&feng_comment_remembered();
$identity='';
if(is_user_logged_in()){$u=wp_get_current_user();$identity='<span class="feng-form-identity">'.get_avatar($u->ID,24).'<span>已登录为 '.esc_html($u->display_name).'</span><a class="feng-form-logout" href="'.esc_url(wp_logout_url(get_permalink())).'">退出登录</a></span>';}
elseif($remembered){$identity='<span class="feng-form-identity">'.get_avatar($commenter['comment_author_email'],24).'<span>'.esc_html($commenter['comment_author']).'</span><button type="button" data-feng-switch-profile aria-expanded="false">切换资料</button></span>';}
?>
<section id="comments" class="xf-comments feng-comments" aria-label="评论区" data-xf-reveal>
 <div class="feng-group-title"><h2><?php echo feng_icon('comment'); ?><?php echo esc_html(number_format_i18n(get_comments_number())); ?> 条评论</h2></div>
 <?php if(have_comments()): ?><ol class="xf-comment-list feng-comment-list"><?php wp_list_comments(array('style'=>'ol','short_ping'=>true,'avatar_size'=>40,'callback'=>'feng_comment','per_page'=>0,'page'=>1)); ?></ol>
 <?php else: ?><p class="feng-empty">还没有留言，来写下第一句吧。</p><?php endif; ?>
 <?php if(!comments_open()): ?><p class="feng-empty">这里的评论已关闭，感谢每一次交流。</p><?php else:
 comment_form(array(
  'title_reply'=>'来几句走心的评论吧','title_reply_to'=>'回复 %s','title_reply_after'=>'<span class="feng-comment-title-icon" aria-hidden="true">'.feng_icon('comment').'</span>'.$identity.'</h3>','cancel_reply_link'=>'取消回复','label_submit'=>'发送留言',
  'submit_button'=>'<button name="%1$s" id="%2$s" class="%3$s" type="submit" aria-label="提交评论">'.feng_icon('comment').'<span class="feng-submit-label" aria-hidden="true">提交评论</span></button>',
  'logged_in_as'=>'',
  'must_log_in'=>'<p class="must-log-in">请先<a href="'.esc_url(wp_login_url(get_permalink())).'">登录</a>后留言。</p>',
  'class_form'=>'comment-form feng-comment-form'.($remembered?' is-remembered':''),'class_submit'=>'submit feng-submit',
  'comment_notes_before'=>'',
  'fields'=>array(
   'author'=>'<p class="comment-form-author"><span class="feng-field-icon">'.feng_icon('person').'</span><label class="screen-reader-text" for="author">昵称'.($required?' *':'').'</label><input id="author" placeholder="名称'.($required?' *':'').'" name="author" type="text" autocomplete="name" maxlength="245" value="'.esc_attr($commenter['comment_author']).'"'.$req.'></p>',
   'email'=>'<p class="comment-form-email"><span class="feng-field-icon">'.feng_icon('email').'</span><label class="screen-reader-text" for="email">邮箱'.($required?' *':'').'</label><input id="email" placeholder="邮箱'.($required?' *':'').'" name="email" type="email" autocomplete="email" maxlength="100" value="'.esc_attr($commenter['comment_author_email']).'"'.$req.'></p>',
   'url'=>'<p class="comment-form-url"><span class="feng-field-icon">'.feng_icon('link').'</span><label class="screen-reader-text" for="url">网站（选填）</label><input id="url" name="url" type="url" autocomplete="url" maxlength="200" value="'.esc_attr($commenter['comment_author_url']).'" placeholder="博客链接（选填）"></p>',
   'cookies'=>'<input type="hidden" name="wp-comment-cookies-consent" value="yes">',
  ),
  'comment_field'=>'<div class="comment-form-comment"><label class="screen-reader-text" for="comment">你的留言 *</label><textarea id="comment" name="comment" rows="7" required aria-required="true" placeholder="'.esc_attr(feng_setting('comment_placeholder','一句走心评论，胜过千言万语。')).'"></textarea>'.feng_emoji_toolbar().'</div>',
  'comment_notes_after'=>'<p class="feng-form-status" role="status" aria-live="polite" data-feng-comment-status></p>',
 )); endif; ?>
</section>
