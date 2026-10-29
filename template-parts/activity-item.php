<?php
/** A small, consistent reading row for an article or a short note. */
if(!defined('ABSPATH'))exit;
$entry=get_post($args['post']??null);if(!$entry)return;
$talk=feng_is_talk_post($entry);$locked=post_password_required($entry);
$url=$talk?feng_page_url('talks').'#talk-'.$entry->ID:get_permalink($entry);
$image='';$tags=array();
if($talk) {
 $summary=$locked?'这条说说受密码保护。':mb_substr(trim(wp_strip_all_tags($entry->post_content)),0,180);
 if(!$locked) {
  $tags=wp_get_object_terms($entry->ID,'feng_talk_tag');
  foreach((array)get_post_meta($entry->ID,'_feng_talk_images',true) as $image_id) {
   if(wp_attachment_is_image($image_id)) {$image=wp_get_attachment_image($image_id,'medium',false,array('alt'=>'','loading'=>'lazy','decoding'=>'async'));break;}
  }
 }
} else {
 $title=get_the_title($entry)?:'无标题文章';
 $summary=$locked?'这篇文章受密码保护。':mb_substr(trim(wp_strip_all_tags(get_the_excerpt($entry))),0,140);
 $image=feng_post_image($entry,'medium',array('alt'=>'','loading'=>'lazy','decoding'=>'async'));
}
?>
<article class="feng-activity-item<?php if($image)echo ' has-image'; ?>" data-activity-id="<?php echo (int)$entry->ID; ?>" data-activity-kind="<?php echo $talk?'talk':'post'; ?>">
 <span class="feng-activity-mark" aria-hidden="true"><?php echo feng_icon($talk?'comment':'words'); ?></span>
 <div class="feng-activity-copy"><div class="feng-activity-meta"><span><?php echo $talk?'说说':'文章'; ?></span><time datetime="<?php echo esc_attr(get_the_date(DATE_W3C,$entry)); ?>" title="<?php echo esc_attr(get_the_date('Y年n月j日 H:i',$entry)); ?>"><?php echo esc_html(human_time_diff(get_post_time('U',true,$entry),time()).'前'); ?></time><?php if($talk && !is_wp_error($tags))foreach(array_slice($tags,0,2) as $tag)echo '<span class="feng-activity-tag"># '.esc_html($tag->name).'</span>'; ?></div>
 <?php if($talk): ?>
 <a class="feng-activity-talk" href="<?php echo esc_url($url); ?>"><?php if($summary): ?><p><?php echo esc_html($summary); ?></p><?php else: ?><span class="screen-reader-text">查看这条说说</span><?php endif; ?></a>
 <?php else: ?><h3><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($title); ?></a></h3>
 <?php if($summary): ?><p><?php echo esc_html($summary); ?></p><?php endif; ?><?php endif; ?></div>
 <?php if($image): ?><a class="feng-activity-image" href="<?php echo esc_url($url); ?>" tabindex="-1" aria-hidden="true"><?php echo $image; ?></a><?php endif; ?>
</article>
