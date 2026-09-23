<?php /** Independent document shell. */ ?>
<!doctype html>
<html <?php language_attributes(); ?><?php $feng_scheme=feng_setting('default_scheme','system'); if(!in_array($feng_scheme,array('system','light','dark'),true))$feng_scheme='system'; if(in_array($feng_scheme,array('light','dark'),true)) echo ' data-xf-theme="'.esc_attr($feng_scheme).'"'; ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="xf-theme" content="0.5.2">
<?php if ( is_file( ABSPATH . 'site.webmanifest' ) ) : ?>
<link rel="manifest" href="<?php echo esc_url( home_url( '/site.webmanifest' ) ); ?>">
<?php endif; ?>
<script>
/* Resolve the visitor's theme before styles and body can paint. */
(function(){var root=document.documentElement,saved,manual=false;try{saved=localStorage.getItem('xf-theme');manual=localStorage.getItem('xf-theme-manual')==='1'&&(saved==='light'||saved==='dark');}catch(e){}var configured=<?php echo wp_json_encode($feng_scheme); ?>;var setting=configured!=='system'?configured:(saved==='light'||saved==='dark'||saved==='system'?saved:'system');var dark=setting==='dark'||(setting!=='light'&&window.matchMedia('(prefers-color-scheme: dark)').matches);var hour=new Date().getHours(),night=hour<6||hour>=18;root.setAttribute('data-xf-theme',(night&&!manual)||dark?'dark':'light');})();
</script>
<style>
html{background:#fafafa;color-scheme:light}
html[data-xf-theme="dark"]{background:#0a0a0a;color-scheme:dark}
html[data-xf-theme="dark"] body{background:#0a0a0a}
</style>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="xf-skip-link screen-reader-text" href="#xf-content"><?php esc_html_e( '跳转到正文', 'feng' ); ?></a>
<div class="xf-global-loading" data-xf-progress aria-hidden="true"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="currentColor" focusable="false"><rect x="1.5" y="1.5" rx="1" width="9" height="9"><animate id="polar_loading_first" begin="0;polar_loading_last.end+0.15s" attributeName="x" dur="0.6s" values="1.5;0.5;1.5" keyTimes="0;.2;1"/><animate begin="0;polar_loading_last.end+0.15s" attributeName="y" dur="0.6s" values="1.5;0.5;1.5" keyTimes="0;.2;1"/><animate begin="0;polar_loading_last.end+0.15s" attributeName="width" dur="0.6s" values="9;11;9" keyTimes="0;.2;1"/><animate begin="0;polar_loading_last.end+0.15s" attributeName="height" dur="0.6s" values="9;11;9" keyTimes="0;.2;1"/></rect><rect x="13.5" y="1.5" rx="1" width="9" height="9"><animate begin="polar_loading_first.begin+0.15s" attributeName="x" dur="0.6s" values="13.5;12.5;13.5" keyTimes="0;.2;1"/><animate begin="polar_loading_first.begin+0.15s" attributeName="y" dur="0.6s" values="1.5;0.5;1.5" keyTimes="0;.2;1"/><animate begin="polar_loading_first.begin+0.15s" attributeName="width" dur="0.6s" values="9;11;9" keyTimes="0;.2;1"/><animate begin="polar_loading_first.begin+0.15s" attributeName="height" dur="0.6s" values="9;11;9" keyTimes="0;.2;1"/></rect><rect x="13.5" y="13.5" rx="1" width="9" height="9"><animate begin="polar_loading_first.begin+0.3s" attributeName="x" dur="0.6s" values="13.5;12.5;13.5" keyTimes="0;.2;1"/><animate begin="polar_loading_first.begin+0.3s" attributeName="y" dur="0.6s" values="13.5;12.5;13.5" keyTimes="0;.2;1"/><animate begin="polar_loading_first.begin+0.3s" attributeName="width" dur="0.6s" values="9;11;9" keyTimes="0;.2;1"/><animate begin="polar_loading_first.begin+0.3s" attributeName="height" dur="0.6s" values="9;11;9" keyTimes="0;.2;1"/></rect><rect x="1.5" y="13.5" rx="1" width="9" height="9"><animate id="polar_loading_last" begin="polar_loading_first.begin+0.44999999999999996s" attributeName="x" dur="0.6s" values="1.5;0.5;1.5" keyTimes="0;.2;1"/><animate begin="polar_loading_first.begin+0.44999999999999996s" attributeName="y" dur="0.6s" values="13.5;12.5;13.5" keyTimes="0;.2;1"/><animate begin="polar_loading_first.begin+0.44999999999999996s" attributeName="width" dur="0.6s" values="9;11;9" keyTimes="0;.2;1"/><animate begin="polar_loading_first.begin+0.44999999999999996s" attributeName="height" dur="0.6s" values="9;11;9" keyTimes="0;.2;1"/></rect></svg></div>
<p class="screen-reader-text" data-xf-status role="status" aria-live="polite"></p>
<div id="xf-view" class="xf-site<?php echo is_page()?' feng-integrated-page':(is_singular('post')?' feng-article-page':''); ?>">
<?php
$feng_header_logo = trim( (string) feng_setting( 'header_logo', '' ) );
if ( ! $feng_header_logo ) {
 $feng_custom_logo = (int) get_theme_mod( 'custom_logo', 0 );
 if ( $feng_custom_logo ) $feng_header_logo = wp_get_attachment_image_url( $feng_custom_logo, 'full' ) ?: '';
}
if ( ! $feng_header_logo ) $feng_header_logo = get_site_icon_url( 192 );
if ( ! $feng_header_logo ) $feng_header_logo = get_theme_file_uri( '/assets/images/brand/polar-icon.png' );
?>
<header class="xf-header xf-container">
 <a class="xf-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
  <span class="xf-brand__avatar" aria-hidden="true"><img src="<?php echo esc_url( $feng_header_logo ); ?>" width="32" height="32" alt="" loading="eager" decoding="async"></span>
  <span class="xf-brand__title"><?php bloginfo( 'name' ); ?><small><?php bloginfo( 'description' ); ?></small></span>
 </a>
 <nav class="xf-nav" aria-label="<?php esc_attr_e( '主导航', 'feng' ); ?>">
 <?php
 if ( has_nav_menu( 'primary' ) ) { wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_class' => 'xf-menu', 'fallback_cb' => false ) ); } else { feng_default_menu(); }
 ?>

 </nav>
 <div class="xf-tools"><div class="feng-header-search"><button class="xf-icon-button" type="button" data-xf-search-open aria-expanded="false" aria-controls="feng-header-search" aria-label="<?php esc_attr_e( '打开搜索', 'feng' ); ?>"><i class="feng-icon feng-fa fa-solid fa-magnifying-glass" aria-hidden="true"></i></button><form id="feng-header-search" class="xf-search feng-header-search__form" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" inert aria-hidden="true"><label class="screen-reader-text" for="feng-header-query">搜索文章</label><input id="feng-header-query" type="search" name="s" placeholder="搜索文章…" autocomplete="off"><button type="submit" aria-label="提交搜索"><i class="feng-icon feng-fa fa-solid fa-magnifying-glass" aria-hidden="true"></i></button></form></div><a class="xf-icon-button feng-random-article" href="<?php echo esc_url(admin_url('admin-post.php?action=feng_random_article')); ?>" data-feng-random data-endpoint="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" data-exclude="<?php echo is_singular('post')?(int)get_queried_object_id():0; ?>" aria-label="随机阅读一篇文章" title="随机文章"><?php echo str_replace('fa-classic fa-regular','fa-solid',feng_icon('dice')); ?></a><?php if($feng_scheme==='system'): ?><button class="xf-icon-button" type="button" data-xf-theme-toggle data-mode="system" aria-label="<?php esc_attr_e( '切换深浅配色', 'feng' ); ?>" aria-pressed="false"><i class="fa-solid fa-desktop feng-scheme-system" aria-hidden="true"></i><?php echo str_replace('fa-classic fa-regular','fa-solid',feng_icon('moon','feng-scheme-moon')); ?><?php echo str_replace('fa-classic fa-regular','fa-solid',feng_icon('sun','feng-scheme-sun')); ?></button><?php endif; ?><?php if(feng_setting('social_rss',true)): ?><a class="xf-icon-button" href="<?php echo esc_url(get_feed_link()); ?>" data-feng-copy-rss aria-label="RSS 订阅" title="复制 RSS 订阅链接"><?php echo str_replace('fa-classic fa-regular','fa-solid',feng_icon('rss')); ?></a><?php endif; ?><button class="xf-icon-button feng-dashboard-toggle" type="button" data-feng-dashboard-toggle aria-label="打开控制面板" aria-expanded="false" aria-controls="feng-dashboard" title="控制面板"><span class="t-icon-swap" data-state="a" aria-hidden="true"><span class="t-icon" data-icon="a"><i class="feng-icon feng-fa fa-solid fa-grid-2" aria-hidden="true"></i></span><span class="t-icon" data-icon="b"><?php echo str_replace('fa-classic fa-regular','fa-solid',feng_icon('close')); ?></span></span></button></div>
</header>
<section id="feng-dashboard" class="feng-dashboard" hidden aria-labelledby="feng-dashboard-title" data-endpoint="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
 <div class="feng-dashboard-head"><div><p class="feng-dashboard-kicker">POLAR / EXPLORE</p><h1 id="feng-dashboard-title" tabindex="-1">站点控制面板</h1><p>文章、说说、分类与近况，都在这里。</p></div><div data-dashboard-summary-slot></div></div>
 <div data-feng-dashboard-content aria-live="polite"></div>
</section>
<main id="xf-content" class="xf-main" tabindex="-1">
