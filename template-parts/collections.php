<?php
$available_groups=get_categories(array('hide_empty'=>false,'orderby'=>'name','order'=>'ASC'));
$xf_groups=array();$selected_ids=array();
for($slot=1;$slot<=5;$slot++){$id=absint(feng_setting('home_category_'.$slot,0));if($id&&!in_array($id,$selected_ids,true)){$category=get_term($id,'category');if($category&&!is_wp_error($category)){$xf_groups[]=$category;$selected_ids[]=$id;}}}
foreach($available_groups as $category){if(count($xf_groups)>=5)break;if(!in_array($category->term_id,$selected_ids,true)){$xf_groups[]=$category;$selected_ids[]=$category->term_id;}}

?>
<section id="xf-collections" class="xf-collections feng-article-hub" aria-label="文章分类与列表">
 <div hidden><img data-hub-watermark alt="" hidden><p data-hub-description></p></div>
 <?php get_template_part('template-parts/home-comments'); ?>
 <nav class="feng-article-hub__tabs" aria-label="文章分类">
 <button type="button" data-collection-tab="all" data-watermark="<?php echo esc_url(feng_setting('home_all_cover','')); ?>" data-description="记录生活，也分享实践。" aria-pressed="true" aria-controls="collection-panel-all">全部</button>
 <?php $first=false;foreach($xf_groups as $group): ?><button type="button" data-collection-tab="<?php echo (int)$group->term_id; ?>" data-description="<?php echo esc_attr(wp_strip_all_tags($group->description)?:'关于'.$group->name.'，一些实践与记录。'); ?>" aria-label="<?php echo esc_attr($group->name); ?>" data-hide-label="<?php echo get_term_meta($group->term_id,'feng_category_hide_text',true)==='1'?'true':'false'; ?>" data-watermark="<?php echo esc_url(get_term_meta($group->term_id,'feng_category_cover',true)); ?>" aria-pressed="<?php echo $first?'true':'false'; ?>" aria-controls="collection-panel-<?php echo (int)$group->term_id; ?>"><?php echo esc_html($group->name); ?></button><?php $first=false;endforeach; ?>
 </nav>
 <div id="collection-panel-all" data-collection-panel="all" data-hub-endpoint="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
 <?php $latest=feng_hub_query();$all=(object)array('term_id'=>0,'name'=>'最新文章','slug'=>'all');if($latest->posts)get_template_part('template-parts/collection-list',null,array('term'=>$all,'query'=>$latest,'all'=>true));else echo '<p>还没有发布文章。</p>'; ?>
 </div>
 <?php foreach($xf_groups as $group): ?>
 <div id="collection-panel-<?php echo (int)$group->term_id; ?>" data-collection-panel="<?php echo (int)$group->term_id; ?>" data-hub-endpoint="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" data-hub-category="<?php echo (int)$group->term_id; ?>" data-hub-lazy="1" hidden>
  <p class="feng-hub-placeholder">正在载入文章…</p>
 </div>
 <?php endforeach; ?>
</section>
