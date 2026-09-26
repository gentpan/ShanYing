<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
function feng_setting( $key, $default = false ) {
 $settings = get_option( 'feng_settings', array() );
 return is_array( $settings ) && array_key_exists( $key, $settings ) ? $settings[$key] : get_theme_mod( $key, $default );
}
function feng_settings_schema() {
 $category_options=array('0'=>'自动选择');
 foreach(get_categories(array('hide_empty'=>false)) as $category)$category_options[(string)$category->term_id]=$category->name;
 return array(
  'appearance' => array( 'title' => '外观与交互', 'fields' => array(
   'visitor_ip_mode'=>array('访客 IP 获取方式','select','direct',array('direct'=>'直连服务器','cdn'=>'使用 CDN（阿里云 / 腾讯云 / Cloudflare）')),
   'admin_appearance'=>array('后台外观主题','select','native',array('native'=>'WordPress 原生','polar'=>'ShanYing · 极夜工作台')),
   'editor_mode'=>array('文章与页面编辑器','select','classic',array('classic'=>'经典编辑器（传统可视化 / 文本）','block'=>'区块编辑器（Gutenberg）')),
   'images_webp'=>array('上传图片自动转换为 WebP','checkbox',true),
   'images_fade'=>array('图片淡入效果','checkbox',true),
   'classic_widgets'=>array('恢复经典小工具','checkbox',true),
   'links_enabled'=>array('启用友情链接管理','checkbox',true),
   'remove_category_base'=>array('去除分类链接中的 category','checkbox',false),
   'disable_emoji'=>array('禁用 Emoji 图片转换','checkbox',false),
   'disable_revisions'=>array('停止保存文章修订版本','checkbox',false),
   'jieqi_enabled'=>array('节气与节假日弹窗','checkbox',true),
   'default_scheme' => array('默认配色','select','system',array('system'=>'跟随系统','light'=>'浅色','dark'=>'深色')),
   'header_logo'=>array('菜单栏 LOGO','image',''),
  )),
  'home' => array('title'=>'首页与内容','fields'=>array(
   'xf_eyebrow'=>array('首页欢迎语','text','你好，欢迎来到我的生活切片'),
   'greeting_enabled'=>array('昵称与当地时间问候','checkbox',true),
   'greeting_pet'=>array('小鸟欢迎联动','checkbox',true),
   'hero_smart_scene'=>array('Hero 智能山水背景','checkbox',true),
   'hero_scene_animation'=>array('Hero 云层与雨雪动画','checkbox',true),
   'hero_scene_preview'=>array('Hero 场景预览（自动时跟随访客）','select','auto',array('auto'=>'自动','dawn'=>'清晨','day'=>'白天','sunset'=>'日落','night'=>'夜晚','cloud'=>'阴天','rain'=>'雨天','snow'=>'雪天')),
   'greeting_weather'=>array('Hero 天气水印','checkbox',true),
   'weather_city'=>array('博主天气城市（建议填写城市英文名）','text',''),
   'weather_visitor'=>array('显示访客所在城市天气','checkbox',true),
   'weather_animation'=>array('天气水印动画','checkbox',true),
   'greeting_weather_service'=>array('天气服务','select','openmeteo',array('openmeteo'=>'Open-Meteo 公共接口（无需密钥，适用于非商业用途）')),
   'home_all_cover'=>array('全部分类区块图片','image',''),
   'home_category_1'=>array('首页第 1 个分类','select','0',$category_options),
   'home_category_2'=>array('首页第 2 个分类','select','0',$category_options),
   'home_category_3'=>array('首页第 3 个分类','select','0',$category_options),
   'home_category_4'=>array('首页第 4 个分类','select','0',$category_options),
   'home_category_5'=>array('首页第 5 个分类','select','0',$category_options),
   'xf_hero_intro'=>array('首页简介','textarea','一些日常，一些远方，一些想要留下的瞬间。'),
   'hero_portrait'=>array('备用 Hero 主图（未设置个人头像时使用）','image',''),
   'hero_photo_1'=>array('生活照片 1','image',''),
   'hero_photo_2'=>array('生活照片 2','image',''),
   'hero_photo_3'=>array('生活照片 3','image',''),
   'hero_photo_4'=>array('生活照片 4','image',''),
   'hero_music'=>array('首页推荐歌曲','select','',feng_hero_music_options()),
   'home_posts_per_page'=>array('最近动态每页数量','number',3),
   'posts_per_page'=>array('分类与搜索每页数量','number',9),
   'comment_placeholder'=>array('评论框提示','text','写下你的想法，让交流在这里发生。'),
   'article_copyright'=>array('文章署名与版权','checkbox',true),
   'article_license'=>array('文章转载规则','select','reserved',array('reserved'=>'保留所有权利（转载需授权）','by'=>'CC BY 4.0','by-nc-sa'=>'CC BY-NC-SA 4.0','custom'=>'自定义说明')),
   'article_license_note'=>array('自定义版权说明','textarea',''),
   'article_stale_days'=>array('文章时效提醒','select','365',array('0'=>'关闭','180'=>'半年未更新','365'=>'一年未更新','730'=>'两年未更新')),
   'comments_ajax'=>array('评论无刷新提交','checkbox',true),
  )),
  'music'=>array('title'=>'音乐播放器','fields'=>array(
   'music_enabled'=>array('启用音乐播放器','checkbox',true),
   'music_tracks'=>array('歌单','tracks',array()),
  )),
  'subscriptions'=>array('title'=>'友链订阅','fields'=>array(
   'feed_hours'=>array('自动同步间隔','select',4,array(4=>'每 4 小时',6=>'每 6 小时')),
  )),
  'pet'=>array('title'=>'养成宠物','fields'=>array(
   'pet_enabled'=>array('启用站点宠物','checkbox',true),
   'pet_kind'=>array('宠物外观','select','bird',array('bird'=>'小鸟 · 啾啾')),
   'pet_name'=>array('宠物名字（留空使用默认）','text',''),
   'pet_side'=>array('显示位置','select','left',array('left'=>'左下角','right'=>'右下角')),
   'pet_mobile'=>array('手机上显示宠物','checkbox',true),
   'pet_ai_enabled'=>array('启用 AI 对话','checkbox',false),
   'pet_ai_daily'=>array('全站每天 AI 回答上限','number',10),
  )),
  'profile'=>array('title'=>'关于与社交','fields'=>array(
   'decade_enabled'=>array('关于页显示博客十年','checkbox',true),
   'decade_start'=>array('博客十年起始日期（留空使用最早公开文章日期）','date',''),
   'profile_name'=>array('展示名称','text',''),
   'profile_avatar'=>array('个人头像（首页 Hero）','image',''),
   'profile_tagline'=>array('个人介绍','textarea','保持好奇，记录生活。'),
   'profile_location'=>array('所在地（可留空）','text',''),
   'social_github'=>array('GitHub','url',''),
   'social_x'=>array('X / Twitter','url',''),
   'social_bilibili'=>array('哔哩哔哩','url',''),
   'social_weibo'=>array('微博','url',''),
   'social_mastodon'=>array('Mastodon','url',''),
   'social_telegram'=>array('Telegram','url',''),
   'social_email'=>array('联系邮箱','email',''),
   'social_rss'=>array('显示 RSS 订阅','checkbox',true),
  )),
  'analytics'=>array('title'=>'网站统计','fields'=>array(
   'analytics_mode'=>array('统计方案','select','off',array('off'=>'关闭','custom'=>'自定义代码')),
   'analytics_code'=>array('自定义统计代码','code',''),
  )),
  'footer'=>array('title'=>'页脚与友链','fields'=>array(
   'footer_background'=>array('Hero 备用背景图片','image',''),
   'footer_seasonal'=>array('Hero 背景随四季切换','checkbox',true),
   'footer_spring'=>array('春季 Hero 图片','image',get_theme_file_uri('/assets/images/seasons/spring.webp')),
   'footer_summer'=>array('夏季 Hero 图片','image',get_theme_file_uri('/assets/images/seasons/summer.webp')),
   'footer_autumn'=>array('秋季 Hero 图片','image',get_theme_file_uri('/assets/images/seasons/autumn.webp')),
   'footer_winter'=>array('冬季 Hero 图片','image',get_theme_file_uri('/assets/images/seasons/winter.webp')),

   'footer_stats'=>array('页脚在线人数与累计访问次数','checkbox',true),
   'site_since'=>array('建站日期','date',''),
   'icp_number'=>array('ICP备案号','text',''),
   'police_number'=>array('公安备案号','text',''),
   'friends_intro'=>array('友链介绍','textarea','在独立的角落，遇见同样认真记录生活的人。'),
   'friends_rules'=>array('友链申请说明','textarea','欢迎通过本页留言交换链接，请留下站点名称、地址和简介。'),
  )),
 );
}
function feng_sanitize_settings( $input ) {
 $input = is_array($input) ? $input : array(); $clean = array();
 foreach ( feng_settings_schema() as $group ) foreach ( $group['fields'] as $key => $field ) {
  $value = $input[$key] ?? ($field[1] === 'checkbox' ? false : $field[2]);
  if ($field[1]==='tracks') { $clean[$key]=feng_music_sanitize_tracks($value); continue; }
  if ( is_array($value) || is_object($value) ) { $value = $field[2]; }
  switch ($field[1]) {
   case 'checkbox': $clean[$key] = (bool)$value; break;
   case 'number': $clean[$key] = max(1,min(60,absint($value))); break;
   case 'video': $id=absint($value); $clean[$key] = $id && strpos((string)get_post_mime_type($id),'video/') === 0 ? $id : 0; break;
   case 'select': $clean[$key] = array_key_exists((string)$value,$field[3]) ? $value : $field[2]; break;
   case 'color': $clean[$key] = sanitize_hex_color($value) ?: $field[2]; break;
   case 'url': case 'image': $clean[$key] = esc_url_raw($value,array('http','https')); break;
   case 'email': $clean[$key] = sanitize_email($value); break;
   case 'code': $clean[$key]=current_user_can('unfiltered_html')?(string)$value:feng_setting($key,''); break;
   case 'textarea': $clean[$key] = sanitize_textarea_field($value); break;
   case 'date': $parts=explode('-', (string)$value); $clean[$key] = count($parts)===3 && checkdate((int)$parts[1],(int)$parts[2],(int)$parts[0]) ? sprintf('%04d-%02d-%02d',...array_map('intval',$parts)) : ''; break;
   default: $clean[$key] = sanitize_text_field($value);
  }
 }
 return $clean;
}
function feng_register_settings() {
 register_setting('feng_settings_group','feng_settings',array('type'=>'array','sanitize_callback'=>'feng_sanitize_settings','default'=>array()));
}
add_action('admin_init','feng_register_settings');
// Drop the previous visitor location when the address source changes.
add_action('update_option_feng_settings',static function($old,$new){
 if(($old['visitor_ip_mode']??'direct')!==($new['visitor_ip_mode']??'direct'))delete_transient('polar_latest_visitor_location');
},10,2);

function feng_admin_menu() { add_theme_page('ShanYing 主题设置','ShanYing 设置','manage_options','feng-settings','feng_settings_screen'); }
add_action('admin_menu','feng_admin_menu');
function feng_admin_assets($hook) {
 if ($hook !== 'appearance_page_feng-settings') return;
 wp_enqueue_media();
 wp_enqueue_style('feng-admin',get_theme_file_uri('/assets/css/admin.css'),array('dashicons'),feng_asset_version('/assets/css/admin.css'));
 wp_enqueue_script('feng-admin',get_theme_file_uri('/assets/js/admin.js'),array(),feng_asset_version('/assets/js/admin.js'),true);
}
add_action('admin_enqueue_scripts','feng_admin_assets');
/** Shared settings shell, using native WordPress controls and save handling. */
function feng_settings_header() {
 $theme=wp_get_theme(get_template());$name=$theme->get('Name');$version=$theme->get('Version');
 echo '<header class="feng-admin-header"><div class="feng-admin-brand"><div class="feng-admin-brandline"><span class="feng-admin-emblem" aria-hidden="true"><img src="'.esc_url(get_theme_file_uri('/assets/images/brand/polar-icon.png')).'" width="38" height="38" alt=""></span><h1>'.esc_html($name).'<span class="feng-admin-title-label">主题设置</span></h1></div><p>记录生活，自有光芒。文章、说说与足迹，都有自己的位置。</p><details class="feng-admin-about"><summary>关于山映 · 技术与功能</summary><p>原生 WordPress / PHP，JavaScript 与 CSS；局部交互采用 React、Motion 和 Lottie，地图支持 Mapbox、Google Maps 与高德。</p><p>分类文章、说说、旅行足迹、友链动态、评论等级、四季页脚、音乐与宠物陪伴；AI 与外部服务按需配置。</p></details></div><div class="feng-admin-header-right"><div class="feng-admin-badges" aria-label="主题信息"><span class="feng-admin-badge feng-admin-badge--theme">'.esc_html($name).'</span><span class="feng-admin-badge feng-admin-badge--version" aria-label="主题版本 '.esc_attr($version).'">v'.esc_html($version).'</span><span class="feng-admin-badge feng-admin-badge--author" aria-label="开发者：西风">西风</span></div><a class="feng-admin-site-link" href="'.esc_url(home_url('/')).'" target="_blank" rel="noopener">查看站点 <span class="dashicons dashicons-external" aria-hidden="true"></span></a></div></header><hr class="wp-header-end">';
}

function feng_settings_description($id) {
 $descriptions=array('appearance'=>'设置默认配色，站点身份与导航沿用 WordPress 原生设置。','home'=>'调整首页欢迎语、内容数量和评论体验。','music'=>'填写歌曲、歌词与封面的外链，每行一首歌。','subscriptions'=>'汇集友情链接的 RSS 更新，设置同步频率并查看运行状态。','pet'=>'选择一位小伙伴，设置它的展示方式与 AI 陪伴。','profile'=>'完善关于页面，以及页脚右侧的社交入口。','footer'=>'维护备案信息、建站日期和友情链接说明。');
 return $descriptions[$id]??'';
}
function feng_settings_field_group($key) {
 $groups=array('xf_eyebrow'=>'首页介绍','home_posts_per_page'=>'内容列表','comment_placeholder'=>'评论体验','pet_enabled'=>'宠物与展示','pet_ai_enabled'=>'智能陪伴','profile_name'=>'个人资料','social_github'=>'社交与订阅','site_since'=>'站点信息','friends_intro'=>'友情链接');
 return $groups[$key]??'';
}
function feng_settings_save_bar($ai=false) {
 echo '<div class="feng-admin-save"><span data-feng-save-state role="status">'.($ai?'文字与封面服务一起保存':'保存当前所有分区的设置').'</span>';
 submit_button('保存设置','primary','submit',false);
 echo '</div>';
}
function feng_settings_screen() {
 if (!current_user_can('manage_options')) return;
 if (isset($_GET['tab']) && $_GET['tab']==='mail') { feng_mail_settings_screen(); return; }
 if (isset($_GET['tab']) && $_GET['tab']==='map') { feng_map_settings_screen(); return; }
 if (isset($_GET['tab']) && $_GET['tab']==='ai') { feng_ai_settings_screen(); return; }
 ?>
 <div class="wrap feng-admin"><?php feng_settings_header(); ?>
 <?php settings_errors(); ?>
 <div class="feng-admin-shell"><?php feng_settings_tabs(); ?><div class="feng-admin-content">
 <form class="feng-settings-form" method="post" action="options.php"><?php settings_fields('feng_settings_group'); ?>
 <?php foreach(feng_settings_schema() as $id=>$group): ?><section class="feng-settings-section" id="feng-<?php echo esc_attr($id); ?>"><header class="feng-section-header"><h2><?php echo esc_html($group['title']); ?></h2><p><?php echo esc_html(feng_settings_description($id)); ?></p></header><?php if($id==='pet') feng_pet_settings_preview(); if($id==='subscriptions') feng_feed_settings_status(); ?><table class="form-table" role="presentation"><colgroup><col class="feng-label-column"><col></colgroup>
 <?php foreach($group['fields'] as $key=>$field): $value=feng_setting($key,$field[2]); $field_group=feng_settings_field_group($key); if($field_group): ?><tr class="feng-field-group"><th colspan="2"><?php echo esc_html($field_group); ?></th></tr><?php endif; ?><tr class="feng-field-row feng-field-<?php echo esc_attr($field[1]); ?>"><th scope="row"><label for="feng-<?php echo esc_attr($key); ?>"><?php echo esc_html($field[0]); ?></label></th><td><div class="feng-field-control">
 <?php $name='feng_settings['.$key.']'; $input_id='feng-'.$key;
 if ($field[1]==='checkbox') { ?><input type="hidden" name="<?php echo esc_attr($name); ?>" value="0"><label><input type="checkbox" id="<?php echo esc_attr($input_id); ?>" name="<?php echo esc_attr($name); ?>" value="1" <?php checked((bool)$value); ?>> 启用</label><?php }
 elseif($field[1]==='tracks') { feng_music_tracks_field($value); }
 elseif($field[1]==='select') { ?><select id="<?php echo esc_attr($input_id); ?>" name="<?php echo esc_attr($name); ?>"><?php foreach($field[3] as $option=>$label) { ?><option value="<?php echo esc_attr($option); ?>" <?php selected($value,$option); ?>><?php echo esc_html($label); ?></option><?php } ?></select><?php }
 elseif(in_array($field[1],array('textarea','code'),true)) { ?><textarea class="large-text" rows="3" id="<?php echo esc_attr($input_id); ?>" name="<?php echo esc_attr($name); ?>"><?php echo esc_textarea($value); ?></textarea><?php }
 else { $media=in_array($field[1],array('image','video'),true); $type=$media?($field[1]==='video'?'number':'url'):$field[1]; ?><input class="regular-text" type="<?php echo esc_attr($type); ?>" id="<?php echo esc_attr($input_id); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" <?php if($field[1]==='number') echo 'min="1" max="60"'; ?>><?php if($media) { ?> <button type="button" class="button feng-media-icon" data-feng-media="<?php echo esc_attr($field[1]); ?>" data-feng-target="<?php echo esc_attr($input_id); ?>" aria-label="选择媒体" title="选择媒体"><span class="dashicons dashicons-format-image" aria-hidden="true"></span></button> <button type="button" class="button feng-media-icon feng-media-icon--clear" data-feng-clear="<?php echo esc_attr($input_id); ?>" aria-label="清除已选媒体" title="清除已选媒体（不删除媒体库文件）"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button><?php } } ?>
 </div><?php if($key==='visitor_ip_mode'): ?><p class="description">默认直连服务器，使用连接 IP。网站使用 CDN 时请选择 CDN，读取回源请求中的访客 IP；用于页脚访客地区、评论地区与访客天气。CDN 模式直接采用转发请求头。</p><?php elseif($key==='analytics_code'): ?><p class="description">粘贴统计平台提供的完整代码，仅具有 unfiltered_html 权限的管理员可修改。启用后输出到页脚，支持带 defer 的脚本。</p><?php elseif($key==='images_webp'): ?><p class="description">新上传的 JPEG 和静态 PNG 转为 WebP（质量 82）；保留透明度，GIF、动画 PNG、SVG 和已有 WebP 保持原格式。失败时保留原图。</p><?php elseif($key==='disable_revisions'): ?><p class="description">只影响后续保存，不删除已有修订。自动保存保留。</p><?php elseif($key==='remove_category_base'): ?><p class="description">保存后自动更新分类路由。与已有页面地址冲突的分类保留原链接。</p><?php elseif($key==='jieqi_enabled'): ?><p class="description">使用节期的邮票风格卡片，按北京时间判断显示时机。访客可以关闭弹出的卡片，站点总开关在此设置。</p><?php elseif($key==='xf_eyebrow'): ?><p class="description">Hero 标题使用<a href="<?php echo esc_url(admin_url('options-general.php')); ?>">站点副标题</a>，这里设置标题上方的欢迎语。</p><?php elseif($key==='header_logo'): ?><p class="description">菜单栏使用此 LOGO；留空时使用 WordPress 站点图标。与首页 Hero 的个人头像分别设置。</p><?php elseif($key==='pet_side'): ?><p class="description">首页显示在最新说说右侧，其余页面使用此位置。</p><?php elseif($key==='profile_avatar'): ?><p class="description">首页 Hero 正面的第一张照片使用这张头像；留空时使用备用 Hero 主图或管理员邮箱头像。关于页仍使用管理员邮箱对应的 Gravatar。</p><?php endif; ?></td></tr><?php endforeach; ?></table>
 <?php if($id==='appearance'): ?><div class="feng-native-links"><h3>站点基础设置</h3><p>直接使用 WordPress 管理站点名称、副标题和导航。</p></div><?php endif; ?>
 </section><?php endforeach; feng_settings_save_bar(); ?></form>
 <?php feng_database_cleanup_form(); ?></div></div></div>
 <?php
}
add_filter('pre_option_link_manager_enabled',function(){return feng_setting('links_enabled',true)?1:0;});

// Use WordPress's built-in classic editing screen; no content conversion is performed.
add_filter('use_block_editor_for_post_type',function($use,$type){
 return in_array($type,array('post','page'),true)&&feng_setting('editor_mode','classic')==='classic'?false:$use;
},100,2);
add_filter('use_block_editor_for_post',function($use,$post){
 return in_array($post->post_type,array('post','page'),true)&&feng_setting('editor_mode','classic')==='classic'?false:$use;
},100,2);

add_action('admin_enqueue_scripts',function(){
 wp_enqueue_style('feng-admin-square',get_theme_file_uri('/assets/css/admin-square.css'),array(),feng_asset_version('/assets/css/admin-square.css'));
},100);

add_filter('admin_body_class',function($classes){return $classes.(feng_setting('admin_appearance','native')==='polar'?' polar-admin-skin':'');});
add_action('admin_enqueue_scripts',function(){
 if(feng_setting('admin_appearance','native')==='polar')wp_enqueue_style('polar-admin-skin',get_theme_file_uri('/assets/css/admin-skin.css'),array('common','forms'),feng_asset_version('/assets/css/admin-skin.css'));
},110);

add_action('customize_controls_enqueue_scripts',function(){
 if(feng_setting('admin_appearance','native')==='polar')wp_enqueue_style('shanying-admin-customizer',get_theme_file_uri('/assets/css/admin-customizer.css'),array('customize-controls'),feng_asset_version('/assets/css/admin-customizer.css'));
},110);

add_filter('post_date_column_status',function($status){
 if(feng_setting('admin_appearance','native')!=='polar'||!$status)return $status;
 return '<span class="polar-date-status">'.$status.'</span>';
});

/* TinyMCE edits inside an iframe, so admin chrome CSS cannot reach its canvas. */
add_filter('tiny_mce_before_init',function($init){
 if(feng_setting('admin_appearance','native')!=='polar')return $init;
 $init['content_style']=($init['content_style']??'').' html{background:#191b1f;color-scheme:dark;}body.mce-content-body{background:#191b1f;color:#eceef1;}body.mce-content-body a{color:#a7d5ff;}body.mce-content-body :is(blockquote,hr,td,th){border-color:#454a53;}';
 return $init;
});
