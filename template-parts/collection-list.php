<?php
if(!defined('ABSPATH'))exit;
$term=$args['term'];$query=$args['query'];
$is_all=!empty($args['all']);
$category_url=$is_all?home_url('/?post_type=post'):get_category_link($term);
$cover=!empty($query->posts)?feng_post_image_url($query->posts[0],'large'):'';
$tint=sanitize_hex_color(get_term_meta($term->term_id,'feng_collection_tint',true))?:($term->slug==='travel'?'#f1f6f0':($term->slug==='code'?'#f0f4fb':'#f5f4f2'));
$preview=true;
?>
<section class="feng-category-list" data-category-list data-xf-reveal style="--category-tint:<?php echo esc_attr($tint); ?>" aria-labelledby="xf-group-<?php echo (int)$term->term_id; ?>">
 <div class="feng-category-list__body">
  <?php $type=$args['type']??'latest';$page=$args['page']??1; ?>
  <header class="feng-hub-toolbar"><nav aria-label="文章排序"><?php if(!$is_all): ?><a class="feng-hub-category-entry" href="<?php echo esc_url($category_url); ?>" aria-label="<?php echo esc_attr('进入'.$term->name.'分类'); ?>">进入分类 <?php echo feng_icon('arrow-up-right'); ?></a><?php else: ?><?php foreach(array('latest'=>'最新文章','comments'=>'热评文章','views'=>'浏览最多') as $key=>$label): ?><button type="button" data-hub-type="<?php echo esc_attr($key); ?>" aria-pressed="<?php echo $key===$type?'true':'false'; ?>"><?php echo esc_html($label); ?></button><?php endforeach;endif; ?></nav><div class="feng-hub-pages"><button type="button" data-hub-page="<?php echo max(1,$page-1); ?>" data-hub-sort="<?php echo esc_attr($type); ?>" aria-label="上一页" <?php disabled($page<=1); ?>><?php echo feng_icon('previous'); ?></button><span><?php echo (int)$page; ?> / <?php echo max(1,(int)$query->max_num_pages); ?></span><button type="button" data-hub-page="<?php echo $page+1; ?>" data-hub-sort="<?php echo esc_attr($type); ?>" aria-label="下一页" <?php disabled($page>=$query->max_num_pages); ?>><?php echo feng_icon('next'); ?></button></div></header>
  <ol>
  <?php foreach($query->posts as $entry):$image=$preview?feng_post_image_url($entry,'large'):''; ?>
   <li><a href="<?php echo esc_url(get_permalink($entry)); ?>" data-category-preview="<?php echo esc_url($image?:$cover); ?>"><span><?php echo esc_html(get_the_title($entry)); ?></span><time datetime="<?php echo esc_attr(get_the_date(DATE_W3C,$entry)); ?>"><?php echo esc_html(get_the_date('Y.m.d',$entry)); ?></time></a></li>
  <?php endforeach; ?>
  <?php for($blank=count($query->posts);$blank<5;$blank++): ?><li class="feng-list-empty" aria-hidden="true"></li><?php endfor; ?>
  </ol>
 </div>
 <div class="feng-category-list__picture" aria-hidden="true">
 <?php if($cover): ?><img class="feng-category-list__base" src="<?php echo esc_url($cover); ?>" alt="" loading="lazy" decoding="async"><?php else: ?><span class="feng-category-list__placeholder"><?php echo esc_html($term->name); ?></span><?php endif; ?>
 <img class="feng-category-list__preview" alt="" hidden><span class="feng-preview-loading"></span>
 </div>
</section>
<?php
$intro_term=$is_all?null:get_term((int)$term->term_id,'category');
$intro_text=$intro_term&&!is_wp_error($intro_term)?trim(wp_strip_all_tags($intro_term->description)):'';
$intro_image=$intro_term&&!is_wp_error($intro_term)?get_term_meta($intro_term->term_id,'feng_category_cover',true):'';
if($intro_text||$intro_image): ?>
<div class="polar-category-intro">
 <?php if($intro_image): ?><img src="<?php echo esc_url($intro_image); ?>" alt="" loading="lazy" decoding="async"><?php endif; ?>
 <?php if($intro_text): ?><p><?php echo esc_html($intro_text); ?></p><?php endif; ?>
</div>
<?php endif; ?>
<?php $keywords=feng_hub_keywords($is_all?0:$term->term_id); ?>
<nav class="feng-hub-keywords" aria-label="<?php echo esc_attr($is_all?'探索文章关键词':$term->name.'分类关键词'); ?>">
<?php if($keywords): ?><span class="feng-hub-keywords__label">继续探索</span><?php foreach($keywords as $keyword):$tag_url=get_tag_link((int)$keyword->term_id);if(is_wp_error($tag_url))continue; ?>
<a href="<?php echo esc_url($tag_url); ?>" aria-label="<?php echo esc_attr($keyword->name.'，'.($is_all?'全站':'当前分类').' '.$keyword->articles.' 篇文章'); ?>"><span class="feng-keyword-hash" aria-hidden="true">#</span><?php echo esc_html($keyword->name); ?><sup aria-hidden="true"><?php echo esc_html(number_format_i18n((int)$keyword->articles)); ?></sup></a>
<?php endforeach;endif; ?>
</nav>
