<?php
if(!defined('ABSPATH'))exit;
$portrait=feng_setting('profile_avatar','')?:feng_setting('hero_portrait','');
if(!$portrait)$portrait=get_avatar_url(get_option('admin_email'),array('size'=>320));
$photos=array();for($i=1;$i<=4;$i++){ $url=feng_setting('hero_photo_'.$i,'');if($url)$photos[]=$url; }
$name=feng_setting('profile_name','')?:get_bloginfo('name');
$now=new DateTimeImmutable('today',wp_timezone());$start=$now->modify('-89 days');
$notes=get_posts(array('post_type'=>'feng_talk','post_status'=>'publish','has_password'=>false,'posts_per_page'=>1,'no_found_rows'=>true));
?>
<noscript><style>.feng-photo-stack[data-stack-loading] .feng-stack-front{opacity:1}</style></noscript>
<?php
$footer_background=feng_setting('footer_background','');
if(feng_setting('footer_seasonal',true)){
 $month=(int)wp_date('n');
 $season=$month>=3&&$month<=5?'spring':($month>=6&&$month<=8?'summer':($month>=9&&$month<=11?'autumn':'winter'));
 $footer_background=feng_setting('footer_'.$season,'')?:($footer_background?:get_theme_file_uri('/assets/images/seasons/'.$season.'.webp'));
}
$smart_scene=feng_setting('hero_smart_scene',true);
$scene_images=array();
if($smart_scene){
 foreach(array('dawn','day','sunset','night','cloud','rain','snow') as $scene)$scene_images[$scene]=get_theme_file_uri('/assets/images/hero-fuji/'.$scene.'.webp');
 $footer_background=$scene_images['day'];
}
?>
<section class="xf-stage feng-profile-hero" aria-labelledby="xf-stage-title" data-xf-stage<?php if($smart_scene): ?> data-smart-scene data-scene-images="<?php echo esc_attr(wp_json_encode($scene_images)); ?>" data-scene-preview="<?php echo esc_attr(feng_setting('hero_scene_preview','auto')); ?>" data-scene-animation="<?php echo feng_setting('hero_scene_animation',true)?'true':'false'; ?>"<?php endif; ?>>
 <?php if($footer_background): ?><img class="polar-hero-landscape" src="<?php echo esc_url($footer_background); ?>" alt="" decoding="async" fetchpriority="high" aria-hidden="true"><?php endif; ?>
 <?php if(feng_setting('greeting_weather',true)||$smart_scene): ?>
 <details class="feng-hero-weather" data-hero-weather data-watermark-enabled="<?php echo feng_setting('greeting_weather',true)?'true':'false'; ?>" data-animated="<?php echo feng_setting('weather_animation',true)?'true':'false'; ?>" data-visitor="<?php echo feng_setting('weather_visitor',true)?'true':'false'; ?>" data-endpoint="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" hidden>
 <summary aria-label="查看两地天气"><svg viewBox="0 0 80 80" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><g class="weather-sun"><circle cx="40" cy="32" r="13"/><path d="M40 10v-5m0 49v5M18 32h-5m49 0h5M24 16l-4-4m36 36 4 4M24 48l-4 4m36-36 4-4"/></g><path class="weather-cloud" d="M21 48a11 11 0 1 1 2-22 17 17 0 0 1 33 2 10 10 0 1 1 3 20Z"/><g class="weather-rain"><path d="m27 56-3 8m16-8-3 8m16-8-3 8"/></g><g class="weather-snow"><circle cx="25" cy="59" r="2"/><circle cx="40" cy="63" r="2"/><circle cx="55" cy="58" r="2"/></g><path class="weather-moon" d="M53 45A21 21 0 0 1 32 16a22 22 0 1 0 21 29Z"/></svg></summary>
 <div class="feng-weather-card"><p class="polar-weather-date" data-weather-date></p><p data-weather-host hidden></p><p data-weather-visitor hidden></p></div>
 </details>
 <?php endif; ?>
 
 <div class="feng-profile-top">
 <div class="xf-stage__identity"><div class="feng-hero-welcome-row"><p class="xf-welcome" data-feng-greeting><?php echo esc_html(feng_setting('xf_eyebrow','你好，欢迎来到我的生活切片')); ?></p><?php $hero_github=feng_weekly_github_stats(false); ?>
 <div class="feng-mini-activity" data-github-stats data-endpoint="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"<?php echo $hero_github?' data-ready="1"':''; ?>><a href="https://x.com/gentpan" target="_blank" rel="noopener noreferrer" aria-label="X / Twitter" title="X / Twitter"><i class="feng-icon fa-brands fa-x-twitter" aria-hidden="true"></i></a><a href="https://github.com/gentpan" target="_blank" rel="noopener noreferrer" aria-label="GitHub" title="GitHub"><i class="feng-icon fa-brands fa-github" aria-hidden="true"></i></a>
 <span data-github-slot><?php echo $hero_github?feng_github_activity_markup($hero_github):'<small>GitHub 同步中</small>'; ?></span>
 </div></div><h1 id="xf-stage-title"><?php echo esc_html(get_bloginfo('description')); ?></h1><div class="feng-hero-latest"><?php if($notes):$note=$notes[0]; ?><a class="feng-hero-note" href="<?php echo esc_url(feng_page_url('talks')); ?>"><small><?php echo feng_icon('comment'); ?><span>最新说说 · <?php echo esc_html(human_time_diff(get_post_time('U',true,$note),time()).'前'); ?></span></small><span><?php echo esc_html(wp_trim_words(wp_strip_all_tags($note->post_content),55,'…')); ?></span></a><?php endif; ?></div>



 <?php if(feng_setting('music_enabled',true)): foreach(feng_music_sanitize_tracks(feng_setting('music_tracks',array())) as $track): if($track['url']!==feng_setting('hero_music',''))continue; ?><button class="feng-hero-music" type="button" data-hero-music="<?php echo esc_attr($track['url']); ?>"><?php if($track['cover']): ?><img src="<?php echo esc_url($track['cover']); ?>" alt=""><?php endif; ?><span><small>最近分享的音乐</small><strong><?php echo esc_html($track['title']); ?></strong><small><?php echo esc_html($track['artist']); ?></small></span><span data-hero-music-state>播放</span></button><?php endforeach;endif; ?>
 </div>
 <div class="feng-profile-visual"><button class="feng-photo-stack" data-stack-loading type="button" data-profile-open aria-haspopup="dialog" aria-label="展开<?php echo esc_attr($name); ?>的个人相册"><img class="feng-stack-front" fetchpriority="high" loading="eager" src="<?php echo esc_url($portrait); ?>" alt="<?php echo esc_attr($name); ?>"><?php foreach($photos as $i=>$photo): ?><span class="feng-stack-piece" data-corner="<?php echo (int)$i; ?>"><img class="feng-stack-back" data-stack-src="<?php echo esc_url($photo); ?>" alt=""></span><?php endforeach; ?></button><?php if(feng_setting('pet_enabled',true)): ?><div class="feng-home-perch" data-feng-pet-slot></div><?php endif; ?></div>
 </div>
 <div class="feng-profile-bottom" data-hero-activity data-endpoint="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"><div class="feng-profile-calendar"><div class="feng-profile-calendar-heading"><strong><span class="feng-activity-icon" data-lordicon-content="siteactivity" aria-hidden="true"><i class="fa-solid fa-chart-column"></i></span>站点动态</strong><div class="feng-heatmap-legend" aria-label="每日发布数量：从少到多，依次为 0、1、2、3、4 条及以上"><span>少</span><?php for($level=0;$level<=4;$level++): ?><i data-level="<?php echo $level; ?>" title="<?php echo $level===4?'4 条及以上':$level.' 条'; ?>" aria-hidden="true"></i><?php endfor; ?><span>多</span></div><div class="feng-weekly-badges" data-hero-badges aria-label="站点统计"><?php foreach(array('文章','说说','评论') as $label): ?><span title="<?php echo esc_attr('全部 · '.$label); ?>"><?php echo esc_html($label); ?> <b>—</b></span><?php endforeach; ?></div></div><div class="feng-heatmap" aria-label="本站发布记录热力图" aria-busy="true">
 <?php for($i=0;$i<90;$i++):$date=$start->modify('+'.$i.' days')->format('Y-m-d'); ?><button type="button" data-profile-day="<?php echo esc_attr($date); ?>" data-level="0" disabled aria-label="<?php echo esc_attr($date); ?>"></button><?php endfor; ?></div></div>
 <div class="feng-profile-updates"> <div class="feng-profile-visitors"><div class="feng-visitors-heading"><small>最近来聊天的朋友</small><nav class="feng-discover-links" aria-label="发现更多博客"><span class="feng-discover-label">发现更多博客</span><a class="xf-icon-button" href="https://www.travellings.cn/go.html" target="_blank" rel="noopener noreferrer" aria-label="开往，发现更多博客" title="开往 · 发现更多博客"><i class="feng-icon feng-fa fa-solid fa-train" aria-hidden="true"></i></a><a class="xf-icon-button" href="https://www.foreverblog.cn/go.html" target="_blank" rel="noopener noreferrer" aria-label="十年之约，探索更多文章" title="十年之约 · 探索更多文章"><i class="feng-icon feng-fa fa-solid fa-blog" aria-hidden="true"></i></a></nav></div><div data-hero-visitors></div></div></div></div>

 
 <dialog class="feng-profile-dialog" data-profile-dialog aria-label="个人相册"><button type="button" data-profile-close aria-label="关闭">×</button><header><img src="<?php echo esc_url($portrait); ?>" alt=""><div><h2><?php echo esc_html($name); ?></h2><p><?php echo esc_html(feng_setting('profile_tagline','')); ?></p></div></header><div class="feng-profile-photos"><?php foreach($photos as $i=>$photo): ?><button type="button" data-profile-photo="<?php echo esc_url($photo); ?>" aria-label="放大生活照片 <?php echo $i+1; ?>"><img src="<?php echo esc_url($photo); ?>" alt="生活照片 <?php echo $i+1; ?>" loading="lazy"></button><?php endforeach; ?></div><img data-profile-large hidden alt="放大的生活照片"></dialog>
 <dialog class="feng-profile-dialog" data-profile-calendar-dialog aria-label="当天记录"><button type="button" data-profile-close aria-label="关闭">×</button><h2 data-profile-date></h2><div data-profile-records hidden></div><p data-profile-empty hidden>这一天没有发布记录。</p></dialog>
</section>
