<?php
/** ShanYing: shared helpers live here; larger features remain in inc/. */
if (!defined('ABSPATH')) exit;

/** Resolve forwarded addresses only from explicitly trusted origin proxies. */
function feng_ip_in_cidr($ip,$cidr){
 $parts=explode('/',trim((string)$cidr),2);
 $address=@inet_pton($ip);$network=@inet_pton($parts[0]);
 if($address===false||$network===false||strlen($address)!==strlen($network))return false;
 $bits=isset($parts[1])&&ctype_digit($parts[1])?(int)$parts[1]:(isset($parts[1])?-1:strlen($address)*8);
 if($bits<0||$bits>strlen($address)*8)return false;
 $bytes=intdiv($bits,8);$remainder=$bits%8;
 return substr($address,0,$bytes)===substr($network,0,$bytes)&&(!$remainder||((ord($address[$bytes])^ord($network[$bytes]))&(255<<(8-$remainder)))===0);
}
function feng_trusted_proxy($ip){
 // Populate from the CDN console's origin IP list, never from visitor headers.
 $ranges=apply_filters('feng_trusted_proxy_cidrs',get_option('feng_trusted_proxy_cidrs',array()));
 if(!is_array($ranges))return false;
 foreach($ranges as $range)if(is_string($range)&&feng_ip_in_cidr($ip,$range))return true;
 return false;
}
function feng_real_ip(){
 $peer=trim((string)($_SERVER['REMOTE_ADDR']??''));
 if(!filter_var($peer,FILTER_VALIDATE_IP))return '';
 if(!feng_trusted_proxy($peer))return $peer;
 // ESA and CDN use different dedicated real-client headers.
 foreach(array('HTTP_ALI_REAL_CLIENT_IP','HTTP_ALI_CDN_REAL_IP') as $header){
  $ip=trim((string)($_SERVER[$header]??''));
  if(filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE))return $ip;
 }
 // Walk from the nearest hop; a visitor cannot override this with an XFF prefix.
 $chain=explode(',',(string)($_SERVER['HTTP_X_FORWARDED_FOR']??''));
 if(count($chain)>32)return $peer;
 foreach(array_reverse($chain) as $hop){
  $ip=trim($hop);
  if(!filter_var($ip,FILTER_VALIDATE_IP))return $peer;
  if(feng_trusted_proxy($ip))continue;
  return filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE)?$ip:$peer;
 }
 return $peer;
}
// Covers AJAX, core comment forms and REST comment submissions equally.
add_filter('pre_comment_user_ip',static function($ip){
 return isset($_SERVER['REMOTE_ADDR'])?feng_real_ip():$ip;
});

require_once get_template_directory() . '/inc/inc-settings.php';

require_once get_template_directory() . '/inc/inc-ai.php';

/* ===== 主题基础与资源 ===== */
/** setup */
/** Native WordPress capabilities shared by all future designs. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function feng_setup() {
	load_theme_textdomain( 'feng', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_editor_style( array( 'assets/css/admin.css' ) );
	add_theme_support( 'custom-logo', array( 'height' => 80, 'width' => 240, 'flex-height' => true, 'flex-width' => true ) );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	register_nav_menus(
		array(
			'primary' => __( '主导航', 'feng' ),
			'collections' => __( '首页分类', 'feng' ),
		)
	);
}
add_action( 'after_setup_theme', 'feng_setup' );

function feng_content_width() {
	$GLOBALS['content_width'] = (int) apply_filters( 'feng_content_width', 760 );
}
add_action( 'after_setup_theme', 'feng_content_width', 0 );
?>
<?php
/** assets */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function feng_asset_version( $path ) {
 $file = get_theme_file_path( $path );
 return is_file( $file ) ? (string) filemtime( $file ) : wp_get_theme()->get( 'Version' );
}
function feng_enqueue_assets() {
 wp_enqueue_style('feng-code-font',get_theme_file_uri('/assets/css/code-font.css'),array('feng-article'),feng_asset_version('/assets/css/code-font.css'));
 wp_enqueue_style( 'feng-brand-font', 'https://static.bluecdn.com/fonts/alimama-fangyuanti.css', array(), null );
 if ( is_home() || is_front_page() ) {
  wp_enqueue_style( 'feng-hero-font', 'https://static.bluecdn.com/fonts/zzqxmxht.css', array(), null );
 }
 if ( is_singular( 'post' ) ) {
  wp_enqueue_style( 'feng-title-font', 'https://static.bluecdn.com/fonts/kuaikanshijieti.css', array(), null );
 }
 wp_enqueue_style( 'feng', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );
 wp_enqueue_style( 'xf-tokens', get_theme_file_uri( '/assets/css/tokens.css' ), array( 'feng' ), feng_asset_version( '/assets/css/tokens.css' ) );
 wp_enqueue_style( 'xf-base', get_theme_file_uri( '/assets/css/base.css' ), array( 'xf-tokens' ), feng_asset_version( '/assets/css/base.css' ) );
 wp_enqueue_style( 'xf-layout', get_theme_file_uri( '/assets/css/layout.css' ), array( 'xf-base' ), feng_asset_version( '/assets/css/layout.css' ) );
 wp_enqueue_style('feng-pages',get_theme_file_uri('/assets/css/pages.css'),array('xf-layout'),feng_asset_version('/assets/css/pages.css'));
 wp_enqueue_style( 'feng-typography', get_theme_file_uri('/assets/css/typography.css'), array('feng-pages','feng-brand-font'), feng_asset_version('/assets/css/typography.css') );
 wp_enqueue_style('feng-cards',get_theme_file_uri('/assets/css/cards.css'),array('feng-typography'),feng_asset_version('/assets/css/cards.css'));
 wp_enqueue_style('feng-article',get_theme_file_uri('/assets/css/article.css'),array('feng-typography'),feng_asset_version('/assets/css/article.css'));
 if ( is_page_template( 'pages/archives.php' ) ) {
  wp_enqueue_style( 'feng-archives', get_theme_file_uri('/assets/css/archives.css'), array('feng-article'), feng_asset_version('/assets/css/archives.css') );
 }
 wp_enqueue_style('feng-dashboard',get_theme_file_uri('/assets/css/dashboard.css'),array('feng-article'),feng_asset_version('/assets/css/dashboard.css'));
 wp_enqueue_style('feng-navigation',get_theme_file_uri('/assets/css/navigation.css'),array('feng-pages'),feng_asset_version('/assets/css/navigation.css'));
 wp_enqueue_script('feng-navigation',get_theme_file_uri('/assets/js/navigation.js'),array('xf-app'),feng_asset_version('/assets/js/navigation.js'),array('strategy'=>'defer','in_footer'=>true));
 wp_enqueue_script('feng-dashboard',get_theme_file_uri('/assets/js/dashboard.js'),array('xf-app'),feng_asset_version('/assets/js/dashboard.js'),array('strategy'=>'defer','in_footer'=>true));
 wp_enqueue_script('feng-toast',get_theme_file_uri('/assets/js/toast.js'),array(),feng_asset_version('/assets/js/toast.js'),true);
 wp_enqueue_script( 'xf-app', get_theme_file_uri( '/assets/js/app.js' ), array(), feng_asset_version( '/assets/js/app.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
 if ( get_option( 'thread_comments' ) && is_singular() && comments_open() ) { wp_enqueue_script( 'comment-reply' ); }
}
add_action( 'wp_enqueue_scripts', 'feng_enqueue_assets' );
function feng_runtime_marker() {
 printf('<meta name="feng-scheme" content="%s"><meta name="feng-reveal" content="on">',esc_attr(feng_setting('default_scheme','system')));
 printf( '<meta name="xf-navigation" content="%s">', ! is_customize_preview() ? 'on' : 'off' );
}
add_action( 'wp_head', 'feng_runtime_marker', 1 );

/** Non-executable front-end configuration; no named inline JavaScript sources. */
function feng_frontend_config() {
 $icons=array();
 foreach(array('check','chevron-down','play','pause') as $name) $icons[$name]=feng_icon($name);
 $config=array('icons'=>$icons,'endpoint'=>admin_url('admin-ajax.php'),'commentLocationEndpoint'=>rest_url('feng/v1/comment-location/'));
 $config['greeting']=array('enabled'=>(bool)feng_setting('greeting_enabled',true),'pet'=>(bool)feng_setting('greeting_pet',true),'weather'=>(bool)feng_setting('greeting_weather',false),'service'=>feng_setting('greeting_weather_service','openmeteo'),'site'=>get_bloginfo('name'));
 if(feng_setting('pet_enabled',true)) $config['pet']=array('url'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('feng_pet'));
 echo '<script type="application/json" id="feng-config">'.wp_json_encode($config,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).'</script>';
}
add_action('wp_head','feng_frontend_config',5);

/** Resolve existing feature registrations into the consolidated front-end assets. */
add_action('wp_enqueue_scripts',function(){
 $manifest=json_decode(file_get_contents(get_theme_file_path('/inc/asset-bundles.json')),true);
 if(!$manifest)return;
 $styles=wp_styles();$scripts=wp_scripts();$enabled=array();
 foreach($manifest['styles'] as $handle=>$source){
  if(isset($styles->registered[$handle]))$styles->registered[$handle]->src=false;
 }
 foreach($manifest['scripts'] as $handle=>$source){
  if(isset($scripts->registered[$handle])){
   if(wp_script_is($handle,'enqueued'))$enabled[]=$handle;
   $scripts->registered[$handle]->src=false;
  }
 }
 wp_enqueue_style('shanying-theme',get_theme_file_uri('/assets/css/main.css'),array(),feng_asset_version('/assets/css/main.css'));
 $dependencies=array_values(array_filter($scripts->queue,fn($handle)=>$handle!=='shanying-theme'));
 wp_enqueue_script('shanying-theme',get_theme_file_uri('/assets/js/main.js'),$dependencies,feng_asset_version('/assets/js/main.js'),true);
 $GLOBALS['shanying_script_modules']['shanying-theme']=$enabled;
},PHP_INT_MAX);

/** Attach per-request module selection as data, read by the bundle itself. */
add_filter('script_loader_tag',function($tag,$handle){
 if(!isset($GLOBALS['shanying_script_modules'][$handle]))return $tag;
 return preg_replace('/<script\b/','<script data-shanying-modules="'.esc_attr(wp_json_encode($GLOBALS['shanying_script_modules'][$handle])).'"',$tag,1);
},10,2);

/** The dashboard keeps its own small bundle and only runs enqueued modules. */
add_action('admin_enqueue_scripts',function(){
 $scripts=wp_scripts();$enabled=array();$deps=array();
 foreach(array('feng-admin','feng-ai-admin','feng-category-badge','feng-feed-admin','feng-music-admin','feng-travel-editor') as $handle){
  if(isset($scripts->registered[$handle])){
   if(wp_script_is($handle,'enqueued')){$enabled[]=$handle;$deps[]=$handle;}
   $scripts->registered[$handle]->src=false;
  }
 }
 if($enabled){wp_enqueue_script('shanying-admin',get_theme_file_uri('/assets/js/admin.js'),$deps,feng_asset_version('/assets/js/admin.js'),true);$GLOBALS['shanying_script_modules']['shanying-admin']=$enabled;}
 $styles=wp_styles();
 if(isset($styles->registered['feng-admin-square'])){$styles->registered['feng-admin-square']->src=false;if(!wp_style_is('feng-admin','enqueued'))wp_enqueue_style('feng-admin',get_theme_file_uri('/assets/css/admin.css'),array(),feng_asset_version('/assets/css/admin.css'));}
},PHP_INT_MAX);
?>
<?php
/** customizer */
if (!defined('ABSPATH')) exit;
function feng_customize($manager) {
 $manager->add_section('feng_appearance',array('title'=>'ShanYing · 快速外观','priority'=>30));
 foreach(array('xf_eyebrow','xf_hero_intro') as $key) {
  foreach(feng_settings_schema() as $group) if(isset($group['fields'][$key])) $field=$group['fields'][$key];
  $id='feng_settings['.$key.']';
  $callback=$field[1]==='textarea'?'sanitize_textarea_field':'sanitize_text_field';
  $manager->add_setting($id,array('type'=>'option','default'=>$field[2],'sanitize_callback'=>$callback));
  $manager->add_control($id,array('section'=>'feng_appearance','label'=>$field[0],'type'=>$field[1],'choices'=>$field[3]??array()));
 }
}
add_action('customize_register','feng_customize');
?>
<?php
/** category-badge */
/** Category badges live in native term metadata, independent of theme options. */
if(!defined('ABSPATH'))exit;
function feng_badge_svg($xml){
 if(strlen($xml)>524288 || preg_match('/<!DOCTYPE|<!ENTITY/i',$xml))return false;
 $previous=libxml_use_internal_errors(true);$doc=new DOMDocument();$ok=$doc->loadXML($xml,LIBXML_NONET);libxml_clear_errors();libxml_use_internal_errors($previous);
 if(!$ok||!$doc->documentElement||$doc->documentElement->localName!=='svg')return false;
 $elements=array('svg','g','path','rect','circle','ellipse','line','polyline','polygon','defs','linearGradient','radialGradient','stop','clipPath','mask','title','desc','use');
 $attributes=explode(' ','xmlns xmlns:xlink viewBox width height x y x1 x2 y1 y2 cx cy r rx ry d points fill fill-rule fill-opacity stroke stroke-width stroke-linecap stroke-linejoin stroke-miterlimit stroke-dasharray stroke-dashoffset stroke-opacity opacity transform id offset stop-color stop-opacity gradientUnits gradientTransform spreadMethod fx fy fr clip-path clip-rule mask maskUnits maskContentUnits preserveAspectRatio href xlink:href');
 foreach($doc->getElementsByTagName('*') as $node){
  if(!in_array($node->localName,$elements,true))return false;
  foreach($node->attributes as $attr){
   if(!in_array($attr->nodeName,$attributes,true))return false;
   $v=$attr->nodeValue;
   if(in_array($attr->nodeName,array('href','xlink:href'),true)&&!preg_match('/^#[a-zA-Z_][\w:.-]*$/D',$v))return false;
   if(preg_match('/javascript:|data:|https?:|\/\//i',$v)&&!in_array($attr->nodeName,array('xmlns','xmlns:xlink'),true))return false;
   if(stripos($v,'url(')!==false&&!preg_match('/^url\(#[a-zA-Z_][\w:.-]*\)$/D',$v))return false;
  }
 }
 return $doc->saveXML($doc->documentElement);
}
add_filter('upload_mimes',function($mimes){if(current_user_can('manage_categories')&&current_user_can('upload_files'))$mimes['svg']='image/svg+xml';return $mimes;});
add_filter('wp_handle_upload_prefilter',function($file){
 if(strtolower(pathinfo($file['name'],PATHINFO_EXTENSION))!=='svg')return $file;
 if(!current_user_can('manage_categories')||!current_user_can('upload_files')){$file['error']='没有上传 SVG 徽章的权限。';return $file;}
 $svg=feng_badge_svg(file_get_contents($file['tmp_name']));
 if($svg===false)$file['error']='SVG 包含不支持的元素、样式或外部引用。请导出为纯路径 SVG，或使用 PNG / WebP。';
 elseif(file_put_contents($file['tmp_name'],$svg)===false)$file['error']='SVG 保存失败，请重试。';
 return $file;
});
add_filter('wp_check_filetype_and_ext',function($data,$file,$name){
 if(strtolower(pathinfo($name,PATHINFO_EXTENSION))==='svg'&&current_user_can('manage_categories')&&is_readable($file)&&feng_badge_svg(file_get_contents($file))!==false)return array('ext'=>'svg','type'=>'image/svg+xml','proper_filename'=>false);
 return $data;
},10,3);
function feng_badge_icon_code($value){
 if(!is_string($value)||strlen($value)>20000)return '';
 $value=trim($value);if($value==='')return '';
 if(str_starts_with($value,'<svg'))return feng_badge_svg($value)?:'';
 if(str_contains($value,'<')){if(!preg_match('/^<i\s+[^>]*class\s*=\s*([\"\'])(.*?)\1[^>]*>\s*<\/i>$/is',$value,$match))return '';$value=$match[2];}
 $classes=preg_split('/\s+/',$value);if(count($classes)>12)return '';
 foreach($classes as $class)if(!preg_match('/^(?:fa[srlbtdk]?|fa-[a-z0-9-]+)$/D',$class))return '';
 return implode(' ',array_unique($classes));
}
function feng_badge_icon_markup($code){
 $code=feng_badge_icon_code($code);if(!$code)return '';
 if(str_starts_with($code,'<svg'))return '<span class="feng-category-badge feng-category-badge--svg" aria-hidden="true">'.$code.'</span>';
 return '<i class="feng-category-badge '.esc_attr($code).'" aria-hidden="true"></i>';
}
function feng_category_fontawesome(){
 wp_enqueue_style('feng-fontawesome-pro','https://static.bluecdn.com/libs/fontawesome-pro-plus/7.3.1/css/all.min.css',array(),'7.3.1');
}
add_action('wp_enqueue_scripts','feng_category_fontawesome');
add_filter('style_loader_tag',static function($html,$handle){
 if($handle!=='feng-fontawesome-pro')return $html;
 $html=preg_replace('/\smedia=([\'"])all\1/',' media="print" onload="this.media=\'all\'"',$html,1);
 return $html.'<noscript>'.preg_replace('/\smedia="print" onload="this\.media=\'all\'"/',' media="all"',$html).'</noscript>';
},10,2);
function feng_category_badge($id){
 $term=get_term($id,'category');
 if($term&&!is_wp_error($term)){
  $icons=array('代码'=>'code','旅行'=>'travel','外贸'=>'trade');
  $kind=$icons[$term->name]??'';
  if($kind){$fallback=array('code'=>'fa-code','travel'=>'fa-plane-departure','trade'=>'fa-globe');return feng_badge_icon_markup('fa-solid '.$fallback[$kind]);}
 }

 $code=get_term_meta($id,'feng_category_icon',true);if($code)return feng_badge_icon_markup($code);

 $attachment=(int)get_term_meta($id,'feng_category_badge',true);$url=$attachment?wp_get_attachment_url($attachment):false;
 return $url?'<img class="feng-category-badge" src="'.esc_url($url).'" width="28" height="28" alt="" decoding="async">':'';
}
function feng_category_badge_field($term=null){
 $id=$term instanceof WP_Term?(int)get_term_meta($term->term_id,'feng_category_badge',true):0;
 $code=$term instanceof WP_Term?get_term_meta($term->term_id,'feng_category_icon',true):'';
 wp_nonce_field('feng_category_badge','feng_category_badge_nonce');
 $cover=$term instanceof WP_Term?get_term_meta($term->term_id,'feng_category_cover',true):'';
 echo '<div class="feng-category-cover-field"><p><label for="feng-category-cover">分类固定封面（首页与控制面板）</label></p><div style="display:flex;gap:6px"><input type="url" class="large-text" id="feng-category-cover" name="feng_category_cover" value="'.esc_attr($cover).'" placeholder="图片 URL"><button type="button" class="button" data-category-cover-choose>选择或上传图片</button><button type="button" class="button" data-category-cover-clear>清除</button></div><p class="description">建议 800 × 400px，图片以居中裁切铺满首页分类区块。重要图案与文字请放在中央。</p></div>';

 $show_text=!($term instanceof WP_Term)||get_term_meta($term->term_id,'feng_category_hide_text',true)!=='1';
 echo '<p><label><input type="checkbox" name="feng_category_show_text" value="1" '.checked($show_text,true,false).'> 首页分类区块显示图标与名称</label></p><p class="description">图片已经包含分类名称时可关闭；没有图片时仍显示名称。</p>';

 echo '<p><label for="feng-category-icon">Font Awesome 类名 / SVG 代码</label></p><textarea id="feng-category-icon" name="feng_category_icon" rows="3" class="large-text code" placeholder="fa-sharp fa-solid fa-house">'.esc_textarea($code).'</textarea><div data-icon-preview style="font-size:28px;min-height:40px;margin:8px 0">'.feng_badge_icon_markup($code).'</div><p class="description">支持 Pro 原始类名、完整 &lt;i&gt; 标签或纯路径 SVG 代码。填写代码时优先显示代码图标；清空后使用下方图片。</p>';

 echo '<div data-category-badge><input type="hidden" name="feng_category_badge" value="'.esc_attr($id).'"><div data-badge-preview style="margin:8px 0">'.($id?'<img src="'.esc_url(wp_get_attachment_url($id)).'" width="48" height="48" style="object-fit:contain" alt="分类徽章预览">':'').'</div><button type="button" class="button" data-badge-choose>选择或上传徽章</button> <button type="button" class="button" data-badge-remove>移除</button><p class="description">支持 PNG、WebP、JPG、GIF 和纯路径 SVG。建议使用透明背景的正方形图片；留空时只显示分类名称。</p></div>';
}
add_action('category_add_form_fields',function(){echo '<div class="form-field"><label>分类徽章</label>';feng_category_badge_field();echo '</div>';});
add_action('category_edit_form_fields',function($term){echo '<tr class="form-field"><th scope="row">分类徽章</th><td>';feng_category_badge_field($term);echo '</td></tr>';});
function feng_save_category_badge($id){
 if(!current_user_can('manage_categories')||empty($_POST['feng_category_badge_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['feng_category_badge_nonce'])),'feng_category_badge'))return;
 update_term_meta($id,'feng_category_hide_text',isset($_POST['feng_category_show_text'])?'0':'1');
 if(isset($_POST['feng_category_cover'])&&is_string($_POST['feng_category_cover']))update_term_meta($id,'feng_category_cover',esc_url_raw(wp_unslash($_POST['feng_category_cover']),array('http','https')));
 if(isset($_POST['feng_category_icon'])&&is_string($_POST['feng_category_icon'])){$code=feng_badge_icon_code(wp_unslash($_POST['feng_category_icon']));if($code)update_term_meta($id,'feng_category_icon',$code);else delete_term_meta($id,'feng_category_icon');}
 $attachment=isset($_POST['feng_category_badge'])?absint($_POST['feng_category_badge']):0;
 if(!$attachment){delete_term_meta($id,'feng_category_badge');return;}
 if(get_post_type($attachment)!=='attachment'||!in_array(get_post_mime_type($attachment),array('image/png','image/jpeg','image/webp','image/gif','image/svg+xml'),true))return;
 if(get_post_mime_type($attachment)==='image/svg+xml'){$file=get_attached_file($attachment);if(!$file||!is_readable($file)||feng_badge_svg(file_get_contents($file))===false)return;}
 update_term_meta($id,'feng_category_badge',$attachment);
}
add_action('created_category','feng_save_category_badge');add_action('edited_category','feng_save_category_badge');
add_filter('manage_edit-category_columns',function($cols){$cols['feng_badge']='徽章';return $cols;});
add_filter('manage_category_custom_column',function($content,$column,$id){return $column==='feng_badge'?(feng_category_badge($id)?:'—'):$content;},10,3);
add_action('admin_enqueue_scripts',function(){
 $screen=get_current_screen();if(!$screen||$screen->taxonomy!=='category')return;
 feng_category_fontawesome();wp_enqueue_media();wp_enqueue_script('feng-category-badge',get_theme_file_uri('/assets/js/category-badge.js'),array('jquery','media-views'),feng_asset_version('/assets/js/category-badge.js'),true);
});
add_filter('the_category',function($html){
 foreach(get_the_category()?:array() as $term){$badge=feng_category_badge($term->term_id);if($badge)$html=str_replace('>'.esc_html($term->name).'</a>','>'.$badge.esc_html($term->name).'</a>',$html);}
 return $html;
});
add_filter('get_the_archive_title',function($title){return is_category()?feng_category_badge(get_queried_object_id()).$title:$title;});

/** Per-category homepage article layout. */
function feng_collection_layout($id){
 $layout=get_term_meta($id,'feng_collection_layout',true);
 return in_array($layout,array('1','3','4','tiles','list'),true)?$layout:'list';
}
function feng_collection_layout_field($term=null){
 $layout=$term instanceof WP_Term?feng_collection_layout($term->term_id):'list';
 wp_nonce_field('feng_collection_layout','feng_collection_layout_nonce');
 echo '<select id="feng-collection-layout" name="feng_collection_layout">';
 foreach(array('list'=>'文章列表＋右侧封面（默认）','1'=>'单张宽幅卡片','3'=>'三张并排','4'=>'四张并排','tiles'=>'四宫格（两行两列）') as $value=>$label)echo '<option value="'.esc_attr($value).'" '.selected($layout,(string)$value,false).'>'.esc_html($label).'</option>';
 echo '</select>';
 $id=$term instanceof WP_Term?$term->term_id:0;
 echo '<p><label>列表文章数量 <select name="feng_collection_count">';
 foreach(array(3,5) as $n)echo '<option value="'.$n.'" '.selected((int)(get_term_meta($id,'feng_collection_count',true)?:5),$n,false).'>'.$n.' 篇</option>';
 echo '</select></label></p><p><label>卡片底色 <input type="color" name="feng_collection_tint" value="'.esc_attr(get_term_meta($id,'feng_collection_tint',true)?:'#f5f7fa').'"></label></p><p><label><input type="checkbox" name="feng_collection_preview" value="1" '.checked(get_term_meta($id,'feng_collection_preview',true)!=='0',true,false).'> 悬浮文章切换封面</label></p>';
 echo '<p class="description">用于首页此分类的文章展示，每次翻页显示对应数量。请先在“外观 → 菜单”中将此分类加入“首页分类”菜单。手机端自动改为单列。</p>';
}
add_action('category_add_form_fields',function(){echo '<div class="form-field"><label for="feng-collection-layout">首页展示样式</label>';feng_collection_layout_field();echo '</div>';});
add_action('category_edit_form_fields',function($term){echo '<tr class="form-field"><th><label for="feng-collection-layout">首页展示样式</label></th><td>';feng_collection_layout_field($term);echo '</td></tr>';});
function feng_save_collection_layout($id){
 if(!current_user_can('manage_categories')||empty($_POST['feng_collection_layout_nonce'])||!is_string($_POST['feng_collection_layout_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['feng_collection_layout_nonce'])),'feng_collection_layout'))return;
 $layout=isset($_POST['feng_collection_layout'])&&is_string($_POST['feng_collection_layout'])?wp_unslash($_POST['feng_collection_layout']):'3';
 update_term_meta($id,'feng_collection_count',isset($_POST['feng_collection_count'])&&$_POST['feng_collection_count']==='3'?3:5);
 update_term_meta($id,'feng_collection_preview',isset($_POST['feng_collection_preview'])?'1':'0');
 if(isset($_POST['feng_collection_tint'])&&is_string($_POST['feng_collection_tint'])){$tint=sanitize_hex_color(wp_unslash($_POST['feng_collection_tint']));if($tint)update_term_meta($id,'feng_collection_tint',$tint);}
 if(in_array($layout,array('1','3','4','tiles','list'),true))update_term_meta($id,'feng_collection_layout',$layout);
}
add_action('created_category','feng_save_collection_layout');
add_action('edited_category','feng_save_collection_layout');


require_once get_template_directory() . '/inc/inc-media.php';

/* ===== 页面与分页辅助 ===== */
if (!defined('ABSPATH')) exit;

/**
 * Move saved page-template assignments when upgrading the development layout.
 * Only known old paths are changed; page content, slugs and IDs are preserved.
 */
function feng_migrate_page_templates() {
	$version = 'pages-v1';
	if ( get_option( 'feng_template_layout_version' ) === $version ) {
		return;
	}

	$complete = true;
	foreach ( array( 'archives', 'friends', 'about', 'guestbook' ) as $name ) {
		$old = 'page-templates/' . $name . '.php';
		$new = 'pages/' . $name . '.php';
		$page_ids = get_posts( array(
			'post_type'      => 'page',
			'post_status'    => array_keys( get_post_stati() ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_wp_page_template',
			'meta_value'     => $old,
		) );
		foreach ( $page_ids as $page_id ) {
			update_post_meta( $page_id, '_wp_page_template', $new, $old );
			if ( get_post_meta( $page_id, '_wp_page_template', true ) === $old ) {
				$complete = false;
			}
		}
	}
	if ( $complete ) {
		update_option( 'feng_template_layout_version', $version, false );
	}
}
add_action( 'init', 'feng_migrate_page_templates', 20 );

function feng_page_url($name) {
 $pages=get_posts(array('post_type'=>'page','post_status'=>'publish','meta_key'=>'_wp_page_template','meta_value'=>'pages/'.$name.'.php','numberposts'=>1));
 return $pages ? get_permalink($pages[0]) : '';
}
function feng_default_menu() {
 $categories=get_categories(array('hide_empty'=>true,'number'=>6));
 echo '<ul class="xf-menu"><li'.($categories?' class="menu-item-has-children"':'').'><a href="'.esc_url(home_url('/')).'"'.(is_front_page()?' aria-current="page"':'').'>首页</a>';
 if($categories) {
  echo '<ul class="sub-menu">';
  foreach($categories as $category) echo '<li><a href="'.esc_url(get_category_link($category->term_id)).'"'.(is_category($category->term_id)?' aria-current="page"':'').'>'.feng_category_badge($category->term_id).esc_html($category->name).'</a></li>';
  echo '</ul>';
 }
 echo '</li>';
 foreach(array('talks'=>'说说','friends'=>'友链','subscriptions'=>'订阅','about'=>'关于','guestbook'=>'留言') as $slug=>$label) {
  $url=feng_page_url($slug);if($url) echo '<li><a href="'.esc_url($url).'"'.(is_page_template('pages/'.$slug.'.php')?' aria-current="page"':'').'>'.esc_html($label).($slug==='subscriptions'?feng_feed_badge():'').'</a></li>';
 }
 echo '</ul>';
}
function feng_query_size($query) {
 if(is_admin() || !$query->is_main_query() || $query->is_singular()) return;
 if($query->is_home()) $query->set('posts_per_page',max(1,min(60,(int)feng_setting('home_posts_per_page',3))));
 elseif($query->is_archive() || $query->is_search()) $query->set('posts_per_page',max(1,min(60,(int)feng_setting('posts_per_page',9))));
}
add_action('pre_get_posts','feng_query_size');
function feng_archive_years() {
 global $wpdb;
 return $wpdb->get_results("SELECT YEAR(post_date) AS year, COUNT(ID) AS total FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' GROUP BY YEAR(post_date) ORDER BY year DESC");
}
function feng_pagination($total=null,$current=null) {
 global $wp_query;
 $total=$total===null?$wp_query->max_num_pages:$total;
 $current=$current===null?max(1,get_query_var('paged'),get_query_var('page')):$current;
 if($total<2) return;
 $links=paginate_links(array('total'=>$total,'current'=>$current,'mid_size'=>1,'end_size'=>1,'type'=>'array','prev_text'=>feng_icon('pageprev').' 上一页','next_text'=>'下一页 '.feng_icon('pagenext')));
 if($links) echo '<nav class="feng-pagination" aria-label="文章分页"><span class="feng-pagination__count">'.esc_html($current.' / '.$total).'</span><div>'.implode('',$links).'</div></nav>';
}
function feng_page_title_icon() {
 $icons=array('pages/about.php'=>'user','pages/archives.php'=>'box-archive','pages/friends.php'=>'link','pages/guestbook.php'=>'comments','pages/subscriptions.php'=>'rss','pages/talks.php'=>'comment-dots');
 $icon=$icons[get_page_template_slug()]??'file-lines';
 return '<i class="fa-solid fa-'.esc_attr($icon).' feng-page-title-icon" aria-hidden="true"></i>';
}
function feng_page_heading($eyebrow,$intro='') {
 echo '<header class="feng-page-heading feng-unified-heading"><h1>'.feng_page_title_icon().esc_html(get_the_title()).'</h1></header>';
 if($intro) echo '<p class="feng-page-introduction">'.nl2br(esc_html($intro)).'</p>';
}

/** Archive filters use native WordPress rewrite rules, not query-string links. */
function feng_archive_filter_url( $filter = '', $page_id = 0 ) {
 $page_id = $page_id ?: get_queried_object_id();
 $base = get_permalink( $page_id );
 if ( ! is_scalar($filter) || ! preg_match('/^(?:[1-9][0-9]{3}|all)$/', (string)$filter) ) { return $base; }
 return user_trailingslashit( untrailingslashit( $base ) . '/' . $filter );
}
function feng_register_archive_routes() {
 $pages = get_posts( array(
  'post_type' => 'page', 'post_status' => 'publish', 'posts_per_page' => -1,
  'meta_key' => '_wp_page_template', 'meta_value' => 'pages/archives.php',
 ) );
 $routes = array();
 foreach ( $pages as $page ) {
  $path = get_page_uri( $page );
  if ( ! $path ) { continue; }
  $routes[$page->ID] = $path;
  add_rewrite_rule( '^' . preg_quote( $path, '#' ) . '/([1-9][0-9]{3}|all)/?$', 'index.php?page_id=' . $page->ID . '&feng_archive_year=$matches[1]', 'top' );
 }
 // Flush only when the route version, archive page path or permalink setting changes.
 $signature = md5( wp_json_encode( array( 'v1', $routes, get_option('permalink_structure') ) ) );
 if ( get_option( 'feng_archive_route_signature' ) !== $signature ) {
  update_option( 'feng_archive_route_signature', $signature, false );
  flush_rewrite_rules( false );
 }
}
add_action( 'init', 'feng_register_archive_routes', 30 );
add_filter( 'query_vars', static function( $vars ) { $vars[] = 'feng_archive_year'; return $vars; } );

function feng_archive_route_canonical() {
 if ( ! is_page_template( 'pages/archives.php' ) || is_preview() ) { return; }
 $filter = get_query_var( 'feng_archive_year', '' );
 $legacy = isset( $_GET['feng_year'] );
 if ( $legacy && '' === $filter ) { $filter = is_string($_GET['feng_year']) ? wp_unslash($_GET['feng_year']) : ''; }
 if ( ! is_string($filter) || ! preg_match('/^(?:[1-9][0-9]{3}|all)$/', $filter) ) { $filter = ''; }
 if ( ! $legacy && '' === $filter ) { return; }
 $url = feng_archive_filter_url( $filter );
 $actual = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
 if ( $legacy || wp_parse_url($actual, PHP_URL_PATH) !== wp_parse_url($url, PHP_URL_PATH) || isset($_GET['feng_archive_year']) ) {
  wp_safe_redirect( $url, 301 );
  exit;
 }
}
add_action( 'template_redirect', 'feng_archive_route_canonical', 9 );
// Core's singular-page canonical must not strip the archive filter segment.
add_filter( 'redirect_canonical', static function( $redirect ) {
 return is_page_template('pages/archives.php') && get_query_var('feng_archive_year') ? false : $redirect;
} );
add_filter( 'get_canonical_url', static function( $url, $post ) {
 $filter = get_query_var('feng_archive_year');
 return is_page_template('pages/archives.php') && $filter && (int)$post->ID === get_queried_object_id() ? feng_archive_filter_url($filter,$post->ID) : $url;
}, 10, 2 );


/** Public category carousel: category-specific page size, newest first. */
function feng_collection_query( $category, $page = 1 ) {
 $layout=feng_collection_layout($category);
 $size=$layout==='list'?((int)get_term_meta($category,'feng_collection_count',true)===3?3:5):($layout==='tiles'?4:(int)$layout);
 return new WP_Query(array('post_type'=>'post','post_status'=>'publish','cat'=>absint($category),'posts_per_page'=>$size,'paged'=>max(1,absint($page)),'orderby'=>array('date'=>'DESC','ID'=>'DESC'),'ignore_sticky_posts'=>true));
}
function feng_collection_ajax() {
 $category=isset($_GET['category']) && is_scalar($_GET['category']) ? absint($_GET['category']) : 0;
 $page=isset($_GET['page']) && is_scalar($_GET['page']) ? absint($_GET['page']) : 1;
 if(!$category || !term_exists($category,'category') || $page<1 || $page>100000) wp_send_json_error(array('message'=>'分类或页码无效。'),400);
 $query=feng_collection_query($category,$page);
 if(!$query->posts) wp_send_json_error(array('message'=>'没有更多文章了。'),404);
 ob_start();
 foreach($query->posts as $entry) get_template_part('template-parts/post-card',null,array('post'=>$entry,'collection'=>true));
 wp_send_json_success(array('html'=>ob_get_clean(),'pages'=>(int)$query->max_num_pages));
}
add_action('wp_ajax_feng_collection','feng_collection_ajax');
add_action('wp_ajax_nopriv_feng_collection','feng_collection_ajax');

function feng_random_article_id($exclude=0) {
 global $wpdb;
 $where="post_type='post' AND post_status='publish' AND post_password=''";
 $exclude=absint($exclude);
 $count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM {$wpdb->posts} WHERE $where AND ID <> %d",$exclude));
 if(!$count && $exclude)return feng_random_article_id();
 if(!$count)return 0;
 return (int)$wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE $where AND ID <> %d ORDER BY ID LIMIT 1 OFFSET %d",$exclude,wp_rand(0,$count-1)));
}
function feng_random_article_ajax(){
 nocache_headers();
 $exclude=isset($_GET['exclude'])&&is_scalar($_GET['exclude'])?absint($_GET['exclude']):0;
 $id=feng_random_article_id($exclude);
 if(!$id)wp_send_json_error(array('message'=>'还没有可以随机阅读的文章。'),404);
 wp_send_json_success(array('url'=>get_permalink($id)));
}
add_action('wp_ajax_feng_random_article','feng_random_article_ajax');
add_action('wp_ajax_nopriv_feng_random_article','feng_random_article_ajax');
function feng_random_article_redirect(){
 nocache_headers();$id=feng_random_article_id();
 wp_safe_redirect($id?get_permalink($id):home_url('/'),302);exit;
}
add_action('admin_post_feng_random_article','feng_random_article_redirect');
add_action('admin_post_nopriv_feng_random_article','feng_random_article_redirect');

/** Public article hub: only published posts, five per page. */
function feng_hub_query($type='latest',$page=1,$category=0){
 $args=array('posts_per_page'=>5,'paged'=>max(1,min(10000,(int)$page)),'post_status'=>'publish','ignore_sticky_posts'=>true,'orderby'=>array('date'=>'DESC','ID'=>'DESC'));
 if($category)$args['cat']=$category;
 if($type==='comments')$args['orderby']=array('comment_count'=>'DESC','date'=>'DESC','ID'=>'DESC');
 if($type==='views'){
  $args['meta_query']=array('relation'=>'OR','view_count'=>array('key'=>'_feng_views','compare'=>'EXISTS','type'=>'NUMERIC'),'no_views'=>array('key'=>'_feng_views','compare'=>'NOT EXISTS'));
  $args['orderby']=array('view_count'=>'DESC','date'=>'DESC','ID'=>'DESC');
 }
 return new WP_Query($args);
}
function feng_hub_ajax(){
 $type=isset($_GET['type'])&&is_string($_GET['type'])?sanitize_key($_GET['type']):'latest';
 if(!in_array($type,array('latest','comments','views'),true))$type='latest';
 $page=max(1,absint($_GET['page']??1));$category=absint($_GET['category']??0);$query=feng_hub_query($type,$page,$category);
 $term=$category?get_term($category,'category'):(object)array('term_id'=>0,'name'=>'最新文章','slug'=>'all');if(!$term||is_wp_error($term))wp_send_json_error(null,404);
 ob_start();get_template_part('template-parts/collection-list',null,array('term'=>$term,'query'=>$query,'all'=>!$category,'type'=>$type,'page'=>$page));$html=ob_get_clean();
 wp_send_json_success(array('html'=>$html));
}
add_action('wp_ajax_feng_hub','feng_hub_ajax');
add_action('wp_ajax_nopriv_feng_hub','feng_hub_ajax');

/** Most-used public article tags within the selected category, including children. */
function feng_hub_keywords($category=0){
 global $wpdb;
 $category_sql='';
 if($category){
  $children=get_term_children($category,'category');
  $ids=array_merge(array((int)$category),is_wp_error($children)?array():array_map('intval',$children));
  $category_sql=" AND EXISTS (SELECT 1 FROM {$wpdb->term_relationships} cr JOIN {$wpdb->term_taxonomy} ct ON ct.term_taxonomy_id=cr.term_taxonomy_id WHERE cr.object_id=p.ID AND ct.taxonomy='category' AND ct.term_id IN (".implode(',',$ids)."))";
 }
 return $wpdb->get_results("SELECT t.term_id,t.name,COUNT(DISTINCT p.ID) AS articles FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tt ON tt.term_id=t.term_id JOIN {$wpdb->term_relationships} tr ON tr.term_taxonomy_id=tt.term_taxonomy_id JOIN {$wpdb->posts} p ON p.ID=tr.object_id WHERE tt.taxonomy='post_tag' AND p.post_type='post' AND p.post_status='publish' AND p.post_password='' $category_sql GROUP BY t.term_id,t.name ORDER BY articles DESC,t.name ASC LIMIT 8");
}


require_once get_template_directory() . '/inc/inc-feed.php';

/* ===== 首页与音乐 ===== */
/** music */
/** Native playlist settings and a persistent HTML audio player. */
if (!defined('ABSPATH')) exit;
function feng_music_sanitize_tracks($rows) {
 if(is_string($rows)){
  $old=feng_setting('music_tracks',array());$old=is_array($old)?$old:array();$parsed=array();$invalid=false;
  foreach(preg_split('/\r?\n/',$rows) as $line){if(trim($line)==='')continue;$parts=array_map('trim',preg_split('/[|｜]/u',$line));
   if(count($parts)<3||count($parts)>5){$invalid=true;break;}$parts=array_pad($parts,5,'');
   foreach(array(2,3,4) as $i)if(($i===2||$parts[$i]!=='')&&(!filter_var($parts[$i],FILTER_VALIDATE_URL)||!in_array(strtolower(wp_parse_url($parts[$i],PHP_URL_SCHEME)??''),array('http','https'),true)))$invalid=true;
   if($parts[0]==='')$invalid=true;
   $row=array('title'=>$parts[0],'artist'=>$parts[1],'url'=>$parts[2],'lrc_url'=>$parts[3],'cover'=>$parts[4]);
   foreach($old as $previous)if(is_array($previous)&&($previous['url']??'')===$row['url']){$row['lrc']=$previous['lrc']??'';break;}$parsed[]=$row;
  }
  if($invalid||count($parsed)>30){if(function_exists('add_settings_error'))add_settings_error('feng_settings','music_rows','歌单未保存：每行填写「歌名 | 作者 | 音乐地址 | 歌词地址 | 封面地址」，地址须为 HTTP(S)，最多 30 首。原歌单已保留。','error');$rows=$old;}else $rows=$parsed;
 }
 $tracks=array();
 foreach(array_slice(is_array($rows)?$rows:array(),0,30) as $row) {
  if(!is_array($row)) continue;
  $row=array_map(static function($v){return is_scalar($v)?(string)$v:'';},$row);
  $url=esc_url_raw($row['url']??'',array('http','https'));
  if(!$url) continue;
  $tracks[]=array('title'=>mb_substr(sanitize_text_field($row['title']??''),0,120)?:'未命名歌曲','artist'=>mb_substr(sanitize_text_field($row['artist']??''),0,120),'url'=>$url,'lrc_url'=>esc_url_raw($row['lrc_url']??'',array('http','https')),'cover'=>esc_url_raw($row['cover']??'',array('http','https')),'lrc'=>mb_substr(sanitize_textarea_field($row['lrc']??''),0,30000));
 }
 return $tracks;
}
function feng_music_tracks_field($value) {
 $lines=array();foreach(feng_music_sanitize_tracks($value) as $track)$lines[]=implode(' | ',array_map(static function($v){return str_replace('|','%7C',$v);},array($track['title'],$track['artist'],$track['url'],$track['lrc_url'],$track['cover'])));
 echo '<div class="feng-music-links"><p class="description">每行一首，最多 30 首，按行顺序播放。歌名和音乐地址必填；作者、歌词和封面可留空，保留分隔符。</p><p><code>歌名 | 作者 | 音乐地址 | 歌词地址 | 封面地址</code></p><textarea id="feng-music_tracks" class="large-text code" name="feng_settings[music_tracks]" rows="10" spellcheck="false" placeholder="歌名 | 作者 | https://example.com/song.mp3 | https://example.com/song.lrc | https://example.com/cover.webp">'.esc_textarea(implode("\n",$lines)).'</textarea><p class="description">直接填写你自行上传后的文件直链，不是音乐网站的播放页面。歌词支持 UTF-8 LRC 或纯文本，外部歌词服务器需允许跨域读取（CORS）。地址里的竖线请写成 %7C。已有粘贴歌词会保留作备用。</p></div>';
}
add_action('wp_enqueue_scripts',function(){
 wp_enqueue_style('feng-music',get_theme_file_uri('/assets/css/music.css'),array('feng-cards'),feng_asset_version('/assets/css/music.css'));
 if(feng_setting('music_enabled',true)) wp_enqueue_script('feng-music',get_theme_file_uri('/assets/js/music.js'),array('xf-app'),feng_asset_version('/assets/js/music.js'),array('strategy'=>'defer','in_footer'=>true));
});
add_action('wp_footer',function(){
 if(feng_setting('music_enabled',true)) get_template_part('template-parts/music-player');
},5);
?>
<?php
/** activity */
if(!defined('ABSPATH'))exit;
function feng_activity_query($page=1){
 return new WP_Query(array('post_type'=>array('post','feng_talk'),'post_status'=>'publish','has_password'=>false,'posts_per_page'=>max(1,min(30,(int)feng_setting('home_posts_per_page',3))),'paged'=>max(1,(int)$page),'orderby'=>array('date'=>'DESC','ID'=>'DESC'),'ignore_sticky_posts'=>true,'date_query'=>array(array('column'=>'post_date_gmt','after'=>gmdate('Y-m-d H:i:s',time()-7*DAY_IN_SECONDS),'inclusive'=>true))));
}
function feng_activity_rows($query){
 ob_start();foreach($query->posts as $entry)get_template_part('template-parts/activity-item',null,array('post'=>$entry));return ob_get_clean();
}
add_action('wp_enqueue_scripts',function(){
 wp_enqueue_style('feng-activity',get_theme_file_uri('/assets/css/activity.css'),array('feng-pages'),feng_asset_version('/assets/css/activity.css'));
 wp_enqueue_script('feng-activity',get_theme_file_uri('/assets/js/activity.js'),array('xf-app'),feng_asset_version('/assets/js/activity.js'),true);
});
function feng_activity_ajax(){
 $page=isset($_GET['page'])&&is_scalar($_GET['page'])?max(1,absint($_GET['page'])):1;
 if($page>10000)wp_send_json_error(array('message'=>'页码超出范围。'),400);
 $query=feng_activity_query($page);
 wp_send_json_success(array('html'=>feng_activity_rows($query),'page'=>$page,'pages'=>(int)$query->max_num_pages));
}
add_action('wp_ajax_feng_activity','feng_activity_ajax');
add_action('wp_ajax_nopriv_feng_activity','feng_activity_ajax');

/** Public activity over a rolling seven-day window; pending comments stay private. */
function feng_weekly_blog_stats($after=null){
 $after=$after?:gmdate('Y-m-d H:i:s',time()-7*DAY_IN_SECONDS);
 $stats=array('posts'=>0,'talks'=>0,'comments'=>0,'people'=>0,'replies'=>0);
 foreach(array('post'=>'posts','feng_talk'=>'talks') as $type=>$key){
  $query=new WP_Query(array('post_type'=>$type,'post_status'=>'publish','has_password'=>false,'posts_per_page'=>1,'fields'=>'ids','date_query'=>array(array('column'=>'post_date_gmt','after'=>$after,'inclusive'=>true))));
  $stats[$key]=(int)$query->found_posts;
 }
 $people=array();$owners=array();
 foreach(get_comments(array('status'=>'approve','type'=>'comment','number'=>0,'post_status'=>'publish','post_password'=>'','date_query'=>array(array('column'=>'comment_date_gmt','after'=>$after,'inclusive'=>true)))) as $comment){
  $email=strtolower(trim($comment->comment_author_email));
  $identity=$comment->user_id?'user:'.$comment->user_id:'email:'.$email;
  if(!array_key_exists($identity,$owners)){
   $user=$comment->user_id?get_userdata((int)$comment->user_id):($email?get_user_by('email',$email):false);
   $owners[$identity]=$user&&user_can($user,'manage_options');
  }
  if($owners[$identity]){if((int)$comment->comment_parent>0)$stats['replies']++;continue;}
  $stats['comments']++;
  $people[$email?:$identity.':'.$comment->comment_author]=true;
 }
 $stats['people']=count($people);
 return $stats;
}

/** Cache public events, then apply the rolling window at render time. */
function feng_weekly_github_stats($fetch=true){
 $cache=get_transient('polar_github_gentpan_events_v1');
 if(false===$cache){
  if(!$fetch)return null;
  $cache=array('events'=>array(),'complete'=>false,'error'=>false);
  for($page=1;$page<=3;$page++){
   $response=wp_remote_get('https://api.github.com/users/gentpan/events/public?per_page=100&page='.$page,array('timeout'=>8,'headers'=>array('Accept'=>'application/vnd.github+json','User-Agent'=>'ShanYing-xifeng.net')));
   if(is_wp_error($response)||200!==wp_remote_retrieve_response_code($response)){$cache['error']=true;break;}
   $events=json_decode(wp_remote_retrieve_body($response),true);
   if(!is_array($events)){$cache['error']=true;break;}
   $cache['events']=array_merge($cache['events'],$events);
   $last=end($events);
   if(count($events)<100||($last&&strtotime($last['created_at'])<time()-7*DAY_IN_SECONDS)){$cache['complete']=true;break;}
  }
  set_transient('polar_github_gentpan_events_v1',$cache,$cache['error']?5*MINUTE_IN_SECONDS:15*MINUTE_IN_SECONDS);
 }
 if($cache['error'])return null;
 $pushes=0;$repos=array();$seen=array();$daily=array();
 for($i=7;$i>=0;$i--)$daily[wp_date('Y-m-d',time()-$i*DAY_IN_SECONDS)]=0;
 foreach($cache['events'] as $event){
  if(strtotime($event['created_at'])<time()-7*DAY_IN_SECONDS||$event['type']!=='PushEvent'||isset($seen[$event['id']]))continue;
  $day=wp_date('Y-m-d',strtotime($event['created_at']));if(isset($daily[$day]))$daily[$day]++;
  $seen[$event['id']]=true;$pushes++;$repos[$event['repo']['name']]=true;
 }
 return array('daily'=>$daily,'pushes'=>$pushes,'projects'=>count($repos),'complete'=>$cache['complete']);
}

function feng_github_activity_markup($stats){
 if(!$stats)return '<small>GitHub 暂未同步</small>';
 $daily=array_slice($stats['daily'],-7,null,true);$peak=max(1,max($daily));$today_pushes=(int)end($daily);
 ob_start(); ?>
 <span class="feng-mini-caption"><?php echo $today_pushes?'今日 '.$today_pushes.' 次推送':'今日暂无推送'; ?><em> · 近 7 天 <?php echo $stats['complete']?'':'至少 '; ?><?php echo (int)array_sum($daily); ?> 次</em></span><div class="feng-mini-bars" aria-label="GitHub 最近七个日期的公开推送，今天尚未结束<?php echo $stats['complete']?'':'，数据不完整'; ?>"><?php foreach($daily as $date=>$count): ?><span data-count="<?php echo (int)$count; ?>" aria-label="<?php echo esc_attr($date.' · '.$count.' 次推送'); ?>"><i style="--bar-height:<?php echo $count?max(8,round($count/$peak*100)):0; ?>%"></i></span><?php endforeach; ?></div>
 <?php return trim(ob_get_clean());
}
function feng_github_stats_ajax(){
 nocache_headers();
 $stats=feng_weekly_github_stats(true);
 if(!$stats)wp_send_json_error(null,503);
 wp_send_json_success(array('html'=>feng_github_activity_markup($stats)));
}
add_action('wp_ajax_feng_github_stats','feng_github_stats_ajax');
add_action('wp_ajax_nopriv_feng_github_stats','feng_github_stats_ajax');

function feng_heatmap_counts($start,$end){
 global $wpdb;
 $rows=$wpdb->get_results($wpdb->prepare("SELECT DATE(post_date) AS day, COUNT(*) AS total FROM {$wpdb->posts} WHERE post_type IN ('post','feng_talk') AND post_status='publish' AND post_password='' AND post_date >= %s AND post_date <= %s GROUP BY DATE(post_date)",$start->format('Y-m-d').' 00:00:00',$end->format('Y-m-d').' 23:59:59'),OBJECT_K);
 $counts=array();
 if($rows)foreach($rows as $day=>$row)$counts[$day]=(int)$row->total;
 return $counts;
}
function feng_heatmap_day_markup($date){
 if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date))return '';
 $start=DateTimeImmutable::createFromFormat('!Y-m-d',$date,wp_timezone());
 if(!$start||$start->format('Y-m-d')!==$date)return '';
 $today=new DateTimeImmutable('today',wp_timezone());
 if($start>$today||$start<$today->modify('-89 days'))return '';
 $cache_key='feng_heatmap_day_v1_'.$date;
 $cached=get_transient($cache_key);
 if($cached!==false)return is_string($cached)?$cached:'';
 $items=get_posts(array('post_type'=>array('post','feng_talk'),'post_status'=>'publish','has_password'=>false,'posts_per_page'=>30,'no_found_rows'=>true,'orderby'=>array('date'=>'DESC','ID'=>'DESC'),'date_query'=>array(array('after'=>$date.' 00:00:00','before'=>$date.' 23:59:59','inclusive'=>true,'column'=>'post_date'))));
 if(!$items){$html='';set_transient($cache_key,$html,6*HOUR_IN_SECONDS);return $html;}
 ob_start();echo '<ul>';
 foreach($items as $item)echo '<li><a href="'.esc_url($item->post_type==='feng_talk'?feng_page_url('talks').'#talk-'.$item->ID:get_permalink($item)).'"><small>'.($item->post_type==='feng_talk'?'说说 · ':'文章 · ').'</small>'.esc_html($item->post_type==='feng_talk'?wp_trim_words(wp_strip_all_tags($item->post_content),25,'…'):get_the_title($item)).'</a></li>';
 echo '</ul>';$html=ob_get_clean();
 set_transient($cache_key,$html,6*HOUR_IN_SECONDS);
 return $html;
}
function feng_heatmap_day_ajax(){
 $date=isset($_GET['date'])&&is_string($_GET['date'])?sanitize_text_field(wp_unslash($_GET['date'])):'';
 $html=feng_heatmap_day_markup($date);
 wp_send_json_success(array('html'=>$html));
}
add_action('wp_ajax_feng_heatmap_day','feng_heatmap_day_ajax');
add_action('wp_ajax_nopriv_feng_heatmap_day','feng_heatmap_day_ajax');

function feng_hero_visitors(){
 global $wpdb;
 $rows=$wpdb->get_results("SELECT c.comment_ID,c.comment_author,c.comment_author_email,c.comment_author_url,c.user_id FROM {$wpdb->comments} c INNER JOIN {$wpdb->posts} p ON p.ID=c.comment_post_ID WHERE c.comment_approved='1' AND c.comment_type IN ('comment','') AND p.post_status='publish' AND p.post_password='' ORDER BY c.comment_date_gmt DESC,c.comment_ID DESC LIMIT 120");
 $recent_visitors=array();$visitor_counts=array();$visitor_users=array();
 foreach($rows as $comment){
  $email=strtolower(trim($comment->comment_author_email));
  if(!$email && !$comment->user_id)continue;
  $lookup=$comment->user_id?'user:'.$comment->user_id:'email:'.$email;
  if(!array_key_exists($lookup,$visitor_users))$visitor_users[$lookup]=$comment->user_id?get_userdata((int)$comment->user_id):($email?get_user_by('email',$email):false);
  $user=$visitor_users[$lookup];
  if($user && user_can($user,'manage_options'))continue;
  $key=$user?'user:'.$user->ID:'email:'.$email;
  if(!isset($recent_visitors[$key]))$recent_visitors[$key]=$comment;
  $visitor_counts[$key]=($visitor_counts[$key]??0)+1;
 }
 arsort($visitor_counts,SORT_NUMERIC);
 $visitors=array();
 foreach(array_slice($visitor_counts,0,5,true) as $key=>$count)$visitors[$key]=$recent_visitors[$key];
 foreach($recent_visitors as $key=>$comment){
  if(count($visitors)>=10)break;
  if(!isset($visitors[$key]))$visitors[$key]=$comment;
 }
 return $visitors;
}
function feng_hero_visitors_markup(){
 ob_start();
 foreach(feng_hero_visitors() as $visitor){
  $visitor_url=esc_url($visitor->comment_author_url,array('http','https'));
  if($visitor_url)echo '<a class="feng-visitor-avatar" href="'.$visitor_url.'" target="_blank" rel="ugc nofollow noopener noreferrer" title="'.esc_attr($visitor->comment_author.'的网站').'">';
  else echo '<span class="feng-visitor-avatar" title="'.esc_attr($visitor->comment_author).'">';
  echo get_avatar($visitor,44,'',$visitor->comment_author,array('loading'=>'lazy','decoding'=>'async'));
  echo $visitor_url?'</a>':'</span>';
 }
 return ob_get_clean();
}
function feng_weekly_badges_markup(){
 global $wpdb;
 $totals=array(
  'posts'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='post' AND post_status='publish' AND post_password=''"),
  'talks'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='feng_talk' AND post_status='publish'"),
  'comments'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved='1'"),
 );
 ob_start();
 foreach($totals as $key=>$count){$label=array('posts'=>'文章','talks'=>'说说','comments'=>'评论')[$key];echo '<span title="'.esc_attr('全部 · '.$label).'">'.esc_html($label).' <b>'.esc_html((string)$count).'</b></span>';}
 return trim(ob_get_clean());
}
function feng_hero_activity_payload(){
 $key='feng_hero_activity_v1';
 $today=wp_date('Y-m-d');
 $cached=get_transient($key);
 if(is_array($cached)&&($cached['day']??'')===$today)return $cached;
 $now=new DateTimeImmutable('today',wp_timezone());
 $payload=array('day'=>$today,'counts'=>feng_heatmap_counts($now->modify('-89 days'),$now),'badges'=>feng_weekly_badges_markup(),'visitors'=>feng_hero_visitors_markup());
 set_transient($key,$payload,HOUR_IN_SECONDS);
 return $payload;
}
function feng_hero_activity_ajax(){
 wp_send_json_success(feng_hero_activity_payload());
}
add_action('wp_ajax_feng_hero_activity','feng_hero_activity_ajax');
add_action('wp_ajax_nopriv_feng_hero_activity','feng_hero_activity_ajax');
function feng_flush_hero_activity($post_id=0){
 delete_transient('feng_hero_activity_v1');
 $post=$post_id?get_post($post_id):null;
 if($post&&in_array($post->post_type,array('post','feng_talk'),true))delete_transient('feng_heatmap_day_v1_'.substr($post->post_date,0,10));
}
add_action('save_post',static function($id){if(wp_is_post_revision($id)||wp_is_post_autosave($id))return;feng_flush_hero_activity($id);},20);
add_action('before_delete_post','feng_flush_hero_activity');
add_action('trashed_post','feng_flush_hero_activity');
add_action('comment_post',static function(){delete_transient('feng_hero_activity_v1');});
add_action('deleted_comment',static function(){delete_transient('feng_hero_activity_v1');});
add_action('transition_comment_status',static function(){delete_transient('feng_hero_activity_v1');});


require_once get_template_directory() . '/inc/inc-talk.php';

require_once get_template_directory() . '/inc/inc-comment.php';

/* ===== 控制面板辅助 ===== */
/** Public site dashboard: published content only. @package ShanYing */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function feng_dashboard_calendar( $month = '' ) {
 $now = current_datetime();
 $date = preg_match( '/^\d{4}-(0[1-9]|1[0-2])$/', $month ) ? DateTimeImmutable::createFromFormat( '!Y-m', $month, wp_timezone() ) : $now->modify( 'first day of this month' );
 if ( ! $date || (int) $date->format('Y') < 1970 || (int) $date->format('Y') > 2100 ) { $date = $now->modify('first day of this month'); }
 $posts = get_posts(array('post_type'=>'post','post_status'=>'publish','has_password'=>false,'numberposts'=>-1,'year'=>(int)$date->format('Y'),'monthnum'=>(int)$date->format('m'),'fields'=>'ids'));
 $days = array();
 foreach ( $posts as $id ) { $day = (int) get_the_date('j', $id); $days[$day] = isset($days[$day]) ? $days[$day] + 1 : 1; }
 $start = (int) get_option('start_of_week',1);
 $offset = ((int)$date->format('w') - $start + 7) % 7;
 $week = array('日','一','二','三','四','五','六');
 ?>
 <div class="feng-calendar-head"><button type="button" data-feng-month="<?php echo esc_attr($date->modify('-1 month')->format('Y-m')); ?>" aria-label="上个月"><?php echo feng_icon('pageprev'); ?></button><h3><?php echo esc_html($date->format('Y 年 n 月')); ?></h3><button type="button" data-feng-month="<?php echo esc_attr($date->modify('+1 month')->format('Y-m')); ?>" aria-label="下个月"><?php echo feng_icon('pagenext'); ?></button></div>
 <table class="feng-calendar"><caption class="screen-reader-text"><?php echo esc_html($date->format('Y年n月')); ?>文章日历</caption><thead><tr><?php for($i=0;$i<7;$i++) echo '<th scope="col">'.esc_html($week[($start+$i)%7]).'</th>'; ?></tr></thead><tbody><tr>
 <?php
 for($i=0;$i<$offset;$i++) echo '<td></td>';
 $length = (int)$date->format('t');
 for($day=1;$day<=$length;$day++) {
  $today = $date->format('Y-m') === $now->format('Y-m') && $day === (int)$now->format('j');
  echo '<td'.($today?' aria-current="date"':'').'>';
  if(isset($days[$day])) echo '<a href="'.esc_url(get_day_link((int)$date->format('Y'),(int)$date->format('m'),$day)).'" aria-label="'.esc_attr($day.'日，'.$days[$day].'篇文章').'">'.esc_html($day).'</a>';
  else echo '<span>'.esc_html($day).'</span>';
  echo '</td>';
  if(($offset+$day)%7===0 && $day<$length) echo '</tr><tr>';
 }
 for($i=($offset+$length)%7;$i>0 && $i<7;$i++) echo '<td></td>';
 ?></tr></tbody></table><p class="feng-dashboard-note">带圆点的日期有文章，点击即可阅读。</p>
 <?php
}

function feng_dashboard_posts( $posts, $empty ) {
 if(!$posts) { echo '<p class="feng-dashboard-empty">'.esc_html($empty).'</p>'; return; }
 echo '<ul class="feng-dashboard-posts">';
 foreach($posts as $post) echo '<li><a href="'.esc_url(get_permalink($post)).'"><time datetime="'.esc_attr(get_the_date('Y-m-d',$post)).'">'.esc_html(get_the_date('m-d',$post)).'</time><span>'.esc_html(get_the_title($post) ?: __('无标题','feng')).'</span></a></li>';
 echo '</ul>';
}

function feng_dashboard_data() {
 global $wpdb;
 $base = array('post_type'=>'post','post_status'=>'publish','has_password'=>false,'numberposts'=>5,'ignore_sticky_posts'=>true);
 $recent = get_posts($base);
 $sticky_ids = array_map('absint',(array)get_option('sticky_posts',array()));
 $sticky = $sticky_ids ? get_posts(array_merge($base,array('post__in'=>$sticky_ids))) : array();
 // Join explicitly to exclude private, draft, password-protected and custom content.
 $comment_ids = $wpdb->get_col("SELECT c.comment_ID FROM {$wpdb->comments} c INNER JOIN {$wpdb->posts} p ON p.ID=c.comment_post_ID WHERE c.comment_approved='1' AND c.comment_type IN ('comment','') AND p.post_status='publish' AND p.post_password='' AND p.post_type IN ('post','page') ORDER BY c.comment_date_gmt DESC LIMIT 100");
 $comment_ids=array_values(array_filter($comment_ids,function($id){$comment=get_comment($id);$user=$comment->user_id?get_userdata((int)$comment->user_id):get_user_by('email',$comment->comment_author_email);return !$user||!user_can($user,'manage_options');}));
 $comment_ids=array_slice($comment_ids,0,6);
 $tags = get_terms(array('taxonomy'=>'post_tag','hide_empty'=>true,'orderby'=>'count','order'=>'DESC','number'=>16));
 $stats = $wpdb->get_row("SELECT COUNT(*) AS posts, MIN(post_date) AS first_date FROM {$wpdb->posts} WHERE post_status='publish' AND post_type='post' AND post_password=''");
 $comment_total = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->comments} c INNER JOIN {$wpdb->posts} p ON p.ID=c.comment_post_ID WHERE c.comment_approved='1' AND c.comment_type IN ('comment','') AND p.post_status='publish' AND p.post_password='' AND p.post_type IN ('post','page')");
 $days = $stats->first_date ? max(1,(int)(current_datetime()->diff(new DateTimeImmutable($stats->first_date,wp_timezone()))->days)+1) : 0;
 $talks=get_posts(array('post_type'=>'feng_talk','post_status'=>'publish','has_password'=>false,'numberposts'=>3));
 $talk_total=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status='publish' AND post_type='feng_talk' AND post_password=''");
 $recommended=get_posts(array_merge($base,array('orderby'=>array('comment_count'=>'DESC','date'=>'DESC'))));
 $categories=get_terms(array('taxonomy'=>'category','hide_empty'=>false,'orderby'=>'name','order'=>'ASC'));
 if(is_wp_error($categories))$categories=array();
 $friends=get_bookmarks(array('hide_invisible'=>true,'limit'=>10,'orderby'=>'rand'));
 $totals=feng_footer_content_stats();
 $since=feng_setting('site_since','');$started=DateTimeImmutable::createFromFormat('!Y-m-d',$since,wp_timezone());
 $age=$started&&$started->format('Y-m-d')===$since&&$started<=current_datetime()?(int)$started->diff(current_datetime())->days:null;
 $summary=array('文章'=>(int)$stats->posts,'说说'=>$talk_total,'评论'=>$comment_total,'分类'=>count($categories),'浏览量'=>(int)$wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name='feng_total_pageviews'"),'总字数'=>(int)$totals['words'],'建站天数'=>$age);
 return compact('summary','friends','recent','sticky','recommended','talks','talk_total','categories','comment_ids','tags','stats','comment_total','days');
}

function feng_dashboard_ajax() {
 $part = isset($_GET['part']) && is_string($_GET['part']) ? sanitize_key($_GET['part']) : '';
 ob_start();
 if($part==='calendar') {
  $month = isset($_GET['month']) && is_string($_GET['month']) ? sanitize_text_field(wp_unslash($_GET['month'])) : '';
  feng_dashboard_calendar($month);
 } else {
  get_template_part('template-parts/dashboard',null,feng_dashboard_data());
 }
 wp_send_json_success(array('html'=>ob_get_clean()));
}
add_action('wp_ajax_feng_dashboard','feng_dashboard_ajax');
add_action('wp_ajax_nopriv_feng_dashboard','feng_dashboard_ajax');


require_once get_template_directory() . '/inc/inc-pet.php';

/* ===== 阅读与统计辅助 ===== */
/** article */
/** Article metadata. Counts Chinese characters and other words, excluding markup. */
if (!defined('ABSPATH')) exit;
function feng_article_stats($post) {
 $text=html_entity_decode(wp_strip_all_tags(strip_shortcodes($post->post_content)),ENT_QUOTES,get_bloginfo('charset'));
 $han=preg_match_all('/\p{Han}/u',$text);
 $other=preg_replace('/\p{Han}/u',' ',$text);
 $words=preg_match_all("/[\p{L}\p{N}]+(?:[’'’-][\p{L}\p{N}]+)*/u",$other);
 return array('count'=>$han+$words,'minutes'=>max(1,(int)ceil($han/350+$words/200)));
}
/** Count every published article page request, including repeat and admin visits.
 * Full-page caches must bypass this hook or supply their own analytics counter.
 */
function feng_record_article_visit() {
 if (!is_singular('post') || is_preview() || is_feed() || ($_SERVER['REQUEST_METHOD']??'')!=='GET') return;
 $post=get_queried_object();
 if (!$post || $post->post_status!=='publish') return;
 add_post_meta($post->ID,'_feng_views',0,true);
 global $wpdb;
 $wpdb->query($wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_value=CAST(meta_value AS UNSIGNED)+1 WHERE post_id=%d AND meta_key=%s",$post->ID,'_feng_views'));
 wp_cache_delete($post->ID,'post_meta');
}
add_action('template_redirect','feng_record_article_visit');
?>
<?php
/** reading */
/** Progressive reading tools and author-approved article summaries. */
if (!defined('ABSPATH')) exit;
add_action('init',static function() {
 add_post_type_support('post','custom-fields');
 register_post_meta('post','_feng_ai_summary',array('type'=>'string','single'=>true,'default'=>'','show_in_rest'=>true,'sanitize_callback'=>'feng_sanitize_reading_summary','auth_callback'=>static function($allowed,$key,$post_id){return current_user_can('edit_post',$post_id);}));
 foreach(array('feng-note'=>'说明','feng-tip'=>'小提示','feng-warning'=>'注意事项') as $name=>$label) {
  foreach(array('core/group','core/paragraph') as $block) register_block_style($block,array('name'=>$name,'label'=>$label));
 }
 register_block_pattern_category('feng-reading',array('label'=>'ShanYing · 阅读组件'));
 register_block_pattern('feng/reading-note',array('title'=>'阅读提示卡','categories'=>array('feng-reading'),'content'=>'<!-- wp:group {"className":"is-style-feng-note","layout":{"type":"constrained"}} --><div class="wp-block-group is-style-feng-note"><!-- wp:paragraph --><p><strong>写在前面</strong></p><!-- /wp:paragraph --><!-- wp:paragraph --><p>在这里补充背景、适用范围或阅读建议。</p><!-- /wp:paragraph --></div><!-- /wp:group -->'));
 register_block_pattern('feng/reading-questions',array('title'=>'折叠问答','categories'=>array('feng-reading'),'content'=>'<!-- wp:details --><details class="wp-block-details"><summary>这里写一个读者可能关心的问题</summary><!-- wp:paragraph --><p>在这里给出解释，读者点击问题就能展开。</p><!-- /wp:paragraph --></details><!-- /wp:details -->'));
});
function feng_sanitize_reading_summary($text) { return is_scalar($text)?mb_substr(sanitize_textarea_field((string)$text),0,1800):''; }
add_filter('rest_prepare_post',static function($response,$post) {
 // Provenance is editor data; never expose it through public REST, including
 // on password-protected posts where core redacts only the normal excerpt.
 if(!current_user_can('edit_post',$post->ID)) {
  $data=$response->get_data();unset($data['meta']['_feng_ai_summary']);$response->set_data($data);
 }
 return $response;
},10,2);
function feng_reading_summary($post) {
 $post=get_post($post);
 if(!$post || $post->post_type!=='post' || post_password_required($post)) return array('text'=>'','ai'=>false);
 $text=trim(wp_strip_all_tags($post->post_excerpt));
 $ai=trim((string)get_post_meta($post->ID,'_feng_ai_summary',true));
 return array('text'=>$text,'ai'=>$text!=='' && $ai!=='' && $text===$ai);
}
add_action('save_post_post',static function($id) {
 if(wp_is_post_revision($id) || wp_is_post_autosave($id) || !current_user_can('edit_post',$id))return;
 if(!isset($_POST['feng_summary_nonce']) || !is_string($_POST['feng_summary_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['feng_summary_nonce'])),'feng_summary'))return;
 if(isset($_POST['feng_ai_summary_saved']) && is_string($_POST['feng_ai_summary_saved'])) update_post_meta($id,'_feng_ai_summary',feng_sanitize_reading_summary(wp_unslash($_POST['feng_ai_summary_saved'])));
});
add_action('wp_enqueue_scripts',static function() {
 // Global dependencies keep PJAX transitions into articles compatible.
 wp_enqueue_style('feng-reading',get_theme_file_uri('/assets/css/reading.css'),array('feng-article'),feng_asset_version('/assets/css/reading.css'));
 wp_enqueue_script('feng-reading',get_theme_file_uri('/assets/js/reading.js'),array('xf-app'),feng_asset_version('/assets/js/reading.js'),array('strategy'=>'defer','in_footer'=>true));
});

function feng_reading_related($post_id,$mode='category') {
 $query=array('post_type'=>'post','post_status'=>'publish','has_password'=>false,'post__not_in'=>array($post_id),'posts_per_page'=>30,'no_found_rows'=>true,'ignore_sticky_posts'=>true,'orderby'=>array('date'=>'DESC','ID'=>'DESC'));
 if($mode==='related'){$tags=wp_get_post_tags($post_id,array('fields'=>'ids'));if(!$tags)return new WP_Query(array('post__in'=>array(0)));$query['tag__in']=$tags;}
 elseif($mode==='category'){$cats=wp_get_post_categories($post_id);if(!$cats)return new WP_Query(array('post__in'=>array(0)));$query['category__in']=$cats;$query['orderby']='rand';}
 else $query['orderby']='rand';
 return new WP_Query($query);
}

add_action('wp_enqueue_scripts',function(){wp_enqueue_script('feng-related',get_theme_file_uri('/assets/js/related.js'),array('xf-app'),feng_asset_version('/assets/js/related.js'),true);});

function feng_article_freshness($post,$now=null){
 $post=get_post($post);$threshold=(int)feng_setting('article_stale_days','365');
 if(!$post||!$threshold||post_password_required($post))return null;
 $published=get_post_datetime($post,'date');$modified=get_post_datetime($post,'modified');
 if(!$published)return null;$updated=$modified&&$modified>$published?$modified:$published;
 $now=$now?:current_datetime();$days=max(0,(int)$updated->diff($now)->format('%r%a'));
 if($days<$threshold)return null;
 $age=$updated->diff($now);$label=$age->y?$age->y.'年'.($age->m?'零'.$age->m.'个月':''):($age->m?$age->m.'个月':$days.'天');
 return array('days'=>$days,'label'=>$label,'updated'=>$updated);
}

// Legacy editor examples may contain literal HTML inside pre/code. Keep those
// examples as text before wpautop and the browser can interpret resource tags.
function feng_reading_escape_code_examples($content){
 return preg_replace_callback('~(<pre\b[^>]*>\s*<code\b[^>]*>)(.*?)(</code>\s*</pre>)~is',static function($match){
  if(!preg_match('~<(?:script|link|style|iframe|object|embed)\b~i',$match[2]))return $match[0];
  return $match[1].esc_html($match[2]).$match[3];
 },$content);
}
add_filter('the_content','feng_reading_escape_code_examples',8);


/* ===== 交互辅助 ===== */
/** home-profile */
if(!defined('ABSPATH'))exit;
function feng_hero_music_options(){
 $options=array(''=>'不显示');
 foreach(feng_music_sanitize_tracks(feng_setting('music_tracks',array())) as $track)$options[$track['url']]=$track['title'];
 return $options;
}
add_action('wp_enqueue_scripts',function(){
 wp_enqueue_style('feng-category-font',get_theme_file_uri('/assets/fonts/category/font.css'),array(),feng_asset_version('/assets/fonts/category/font.css'));
 wp_enqueue_style('feng-home-profile',get_theme_file_uri('/assets/css/home-profile.css'),array('feng-pages'),feng_asset_version('/assets/css/home-profile.css'));
 wp_enqueue_script('feng-home-profile',get_theme_file_uri('/assets/js/home-profile.js'),array('xf-app'),feng_asset_version('/assets/js/home-profile.js'),true);
});
?>
<?php
/** context-menu */
if(!defined('ABSPATH'))exit;
add_action('wp_enqueue_scripts',function(){
 wp_enqueue_style('feng-context',get_theme_file_uri('/assets/css/context-menu.css'),array('feng-pages'),feng_asset_version('/assets/css/context-menu.css'));
 wp_enqueue_script('feng-context',get_theme_file_uri('/assets/js/context-menu.js'),array('xf-app'),feng_asset_version('/assets/js/context-menu.js'),true);
});
add_action('wp_footer',function(){ ?>
<div class="feng-page-menu" data-page-menu role="menu" aria-label="页面快捷操作" hidden>
<div class="feng-page-menu-nav"><?php foreach(array('back'=>array('arrow-left','后退'),'forward'=>array('arrow-right','前进'),'reload'=>array('refresh','重新加载'),'top'=>array('arrow-up','回到顶部')) as $key=>$v): ?><button role="menuitem" type="button" data-page-tool="<?php echo esc_attr($key); ?>" aria-label="<?php echo esc_attr($v[1]); ?>"><?php echo feng_icon($v[0]); ?></button><?php endforeach; ?></div>
<?php foreach(array('link-tab'=>array('arrow-up-right','在新标签页打开'),'link-window'=>array('arrow-up-right','在新窗口打开'),'link-copy'=>array('link','复制链接地址'),'previous'=>array('previous','上一篇'),'next'=>array('next','下一篇'),'comment'=>array('comment','留言'),'random'=>array('dice','随机阅读一篇文章'),'copy'=>array('copy','复制页面地址'),'theme'=>array('moon','切换深浅配色')) as $key=>$v): ?><button role="menuitem" type="button" data-page-tool="<?php echo esc_attr($key); ?>"><?php echo feng_icon($v[0]); ?><span><?php echo esc_html($v[1]); ?></span></button><?php endforeach; ?>
</div><span class="screen-reader-text" data-page-menu-status role="status"></span>
<?php });


require_once get_template_directory() . '/inc/inc-travel.php';

/* ===== 站点辅助服务 ===== */
/** jieqi */
if(!defined('ABSPATH'))exit;
add_action('wp_enqueue_scripts',function(){
 if(!feng_setting('jieqi_enabled',true))return;
 wp_enqueue_script('feng-jieqi',get_theme_file_uri('/assets/js/jieqi.js'),array(),feng_asset_version('/assets/js/jieqi.js'),true);
 wp_enqueue_style('feng-jieqi',get_theme_file_uri('/assets/css/jieqi.css'),array(),feng_asset_version('/assets/css/jieqi.css'));
});
function feng_jieqi_toggle(){
 if(!feng_setting('jieqi_enabled',true))return;
 echo '<button type="button" class="feng-jieqi-toggle" data-jieqi-toggle aria-pressed="true" title="关闭节气与节假日提醒">节气提醒<span data-jieqi-state>已开启</span></button>';
}
?>
<?php
/** mail */
if(!defined('ABSPATH'))exit;
function feng_mail_config(){return wp_parse_args(get_option('feng_mail_settings',array()),array('enabled'=>false,'host'=>'','port'=>587,'encryption'=>'tls','auth'=>true,'username'=>'','password'=>'','from_email'=>'','from_name'=>''));}
function feng_mail_secret($text,$decrypt=false){
 $key=hash('sha256',wp_salt('auth'),true);
 if($decrypt){$raw=base64_decode($text,true);if(!$raw||strlen($raw)<28)return '';return openssl_decrypt(substr($raw,28),'aes-256-gcm',$key,OPENSSL_RAW_DATA,substr($raw,0,12),substr($raw,12,16))?:'';}
 $iv=random_bytes(12);$tag='';$cipher=openssl_encrypt($text,'aes-256-gcm',$key,OPENSSL_RAW_DATA,$iv,$tag);if($cipher===false)throw new RuntimeException('邮件密码加密失败');return base64_encode($iv.$tag.$cipher);
}
add_action('phpmailer_init',function($mail){
 $c=feng_mail_config();if(!$c['enabled'])return;
 $password=$c['password']?feng_mail_secret($c['password'],true):'';
 if(!$c['host']||!is_email($c['from_email'])||($c['auth']&&(!$c['username']||!$password)))throw new \PHPMailer\PHPMailer\Exception('SMTP 配置不完整，请检查主题邮件设置。');
 $mail->isSMTP();$mail->Host=$c['host'];$mail->Port=(int)$c['port'];$mail->SMTPSecure=$c['encryption'];$mail->SMTPAutoTLS=$c['encryption']!=='';$mail->SMTPAuth=(bool)$c['auth'];$mail->Username=$c['username'];$mail->Password=$password;$mail->Timeout=15;$mail->SMTPDebug=0;$mail->setFrom($c['from_email'],$c['from_name'],false);
});
function feng_mail_settings_screen(){
 if(!current_user_can('manage_options'))return;$notice='';$error=false;
 if($_SERVER['REQUEST_METHOD']==='POST'&&check_admin_referer('feng_mail_settings')){
  if(isset($_POST['mail_test'])){
   $recipient=sanitize_email(wp_unslash($_POST['test_email']??''));
   if(!feng_mail_config()['enabled']||!is_email($recipient)){$notice='请先保存并启用 SMTP，再填写有效的测试收件邮箱。';$error=true;}
   else{$ok=wp_mail($recipient,'SMTP 测试邮件 · '.get_bloginfo('name'),'这是一封由站点管理员主动发送的测试邮件。收到此邮件说明当前邮件配置能够送达。');$notice=$ok?'邮件服务器已接受测试邮件，请检查收件箱和垃圾邮件。':'测试发送失败，请检查服务器地址、端口、加密方式、账号和授权码。';$error=!$ok;}
  }else{
   $old=feng_mail_config();$v=isset($_POST['feng_mail'])&&is_array($_POST['feng_mail'])?wp_unslash($_POST['feng_mail']):array();foreach($v as $k=>$value)if(!is_scalar($value))$v[$k]='';
   $next=array('enabled'=>!empty($v['enabled']),'host'=>strtolower(trim(sanitize_text_field($v['host']??''))),'port'=>absint($v['port']??587),'encryption'=>in_array($v['encryption']??'',array('tls','ssl',''),true)?$v['encryption']:'tls','auth'=>!empty($v['auth']),'username'=>sanitize_text_field($v['username']??''),'password'=>$old['password'],'from_email'=>sanitize_email($v['from_email']??''),'from_name'=>sanitize_text_field($v['from_name']??''));
   if($next['host']!==$old['host']||$next['username']!==$old['username']||!empty($v['clear_password']))$next['password']='';
   if(($v['password']??'')!=='')$next['password']=feng_mail_secret($v['password']);
   if(($next['host']!==''&&!preg_match('/^[a-z0-9.-]+$/D',$next['host']))||$next['port']<1||$next['port']>65535){$notice='服务器只填写主机名或 IPv4 地址，端口范围为 1–65535。';$error=true;}
   elseif($next['enabled']&&(!$next['host']||!$next['from_email']||($next['auth']&&(!$next['username']||!$next['password'])))){$notice='启用前请填写服务器、发件邮箱及认证信息；修改服务器或账号时请重新填写密码。';$error=true;}
   else{update_option('feng_mail_settings',$next,false);$notice='邮件设置已保存。';}
  }
 }
 $c=feng_mail_config();echo '<div class="wrap feng-admin">';feng_settings_header();if($notice)echo '<div class="notice notice-'.($error?'error':'success').'"><p>'.esc_html($notice).'</p></div>';echo '<div class="feng-admin-shell">';feng_settings_tabs('mail');echo '<div class="feng-admin-content"><section class="feng-settings-section"><header class="feng-section-header"><h2>邮件发送 · SMTP</h2><p>用于 WordPress 的通知、找回密码和其他 wp_mail 邮件。请填写邮件服务商提供的配置。</p></header><form method="post" class="feng-settings-form">';wp_nonce_field('feng_mail_settings');
 echo '<table class="form-table"><tr><th>启用 SMTP</th><td><label><input type="checkbox" name="feng_mail[enabled]" value="1" '.checked($c['enabled'],true,false).'>启用</label></td></tr>';
 foreach(array('host'=>'SMTP 服务器','port'=>'端口','username'=>'账号','from_email'=>'发件邮箱','from_name'=>'发件人名称') as $key=>$label){echo '<tr><th><label for="mail-'.$key.'">'.$label.'</label></th><td><input class="regular-text" id="mail-'.$key.'" name="feng_mail['.$key.']" type="'.($key==='port'?'number':($key==='from_email'?'email':'text')).'" value="'.esc_attr($c[$key]).'" '.($key==='port'?'min="1" max="65535"':'').'></td></tr>';}
 echo '<tr><th>连接加密</th><td><select name="feng_mail[encryption]">';foreach(array('tls'=>'STARTTLS（通常 587）','ssl'=>'SSL / TLS（通常 465）',''=>'无加密（仅用于可信内网）') as $key=>$label)echo '<option value="'.esc_attr($key).'" '.selected($c['encryption'],$key,false).'>'.$label.'</option>';echo '</select></td></tr><tr><th>SMTP 认证</th><td><label><input type="checkbox" name="feng_mail[auth]" value="1" '.checked($c['auth'],true,false).'>使用账号密码认证</label></td></tr><tr><th><label for="mail-password">密码 / 授权码</label></th><td><input id="mail-password" class="regular-text" type="password" autocomplete="new-password" name="feng_mail[password]" value="" placeholder="'.($c['password']?'已保存，留空保留':'尚未配置').'"><p><label><input type="checkbox" name="feng_mail[clear_password]" value="1">清除已保存密码</label></p><p class="description">密码加密保存，不在页面回显。邮箱要求授权码时请勿填写登录密码。更换服务器或账号后需重新填写。</p></td></tr></table>';submit_button('保存邮件设置');echo '</form></section><section class="feng-settings-section"><h2>发送测试邮件</h2><p>使用已保存的配置，仅在点击下方按钮后发送。</p><form method="post">';wp_nonce_field('feng_mail_settings');echo '<label for="mail-test-email">收件邮箱</label> <input id="mail-test-email" class="regular-text" name="test_email" type="email" required>';submit_button('发送测试邮件','secondary','mail_test');echo '</form></section></div></div></div>';
}
?>
<?php
/** optimization */
if(!defined('ABSPATH'))exit;
add_filter('use_widgets_block_editor',function($use){return feng_setting('classic_widgets',true)?false:$use;});
add_filter('wp_revisions_to_keep',function($num){return feng_setting('disable_revisions',false)?0:$num;});
add_action('init',function(){if(!feng_setting('disable_emoji',false))return;remove_action('wp_head','print_emoji_detection_script',7);remove_action('wp_enqueue_scripts','wp_enqueue_emoji_styles');remove_action('wp_print_styles','print_emoji_styles');remove_action('admin_print_scripts','print_emoji_detection_script');remove_action('admin_print_styles','print_emoji_styles');remove_filter('the_content_feed','wp_staticize_emoji');remove_filter('comment_text_rss','wp_staticize_emoji');remove_filter('wp_mail','wp_staticize_emoji_for_email');add_filter('emoji_svg_url','__return_false');add_filter('tiny_mce_plugins',function($plugins){return array_diff($plugins,array('wpemoji'));});});
function feng_category_short_paths(){
 if(!feng_setting('remove_category_base',false)||!get_option('permalink_structure'))return array();
 $terms=get_terms(array('taxonomy'=>'category','hide_empty'=>false));if(is_wp_error($terms))return array();$paths=array();
 foreach($terms as $term){$path=trim(get_category_parents($term->term_id,false,'/',true),'/');if(!$path||get_page_by_path($path))continue;$paths[$term->term_id]=$path;}return $paths;
}
add_filter('category_link',function($url,$id){$paths=feng_category_short_paths();return isset($paths[$id])?home_url(user_trailingslashit($paths[$id],'category')):$url;},10,2);
add_filter('rewrite_rules_array',function($rules){$new=array();foreach(feng_category_short_paths() as $id=>$path){$pattern=preg_quote($path,'#');$new[$pattern.'/?$']='index.php?cat='.$id;$new[$pattern.'/page/([0-9]{1,})/?$']='index.php?cat='.$id.'&paged=$matches[1]';$new[$pattern.'/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$']='index.php?cat='.$id.'&feed=$matches[1]';}return $new+$rules;});
function feng_schedule_category_flush(){add_action('shutdown',function(){flush_rewrite_rules(false);});}
add_action('update_option_feng_settings',function($old,$new){if(!empty($old['remove_category_base'])!==!empty($new['remove_category_base']))feng_schedule_category_flush();},10,2);
foreach(array('created_category','edited_category','delete_category') as $hook)add_action($hook,'feng_schedule_category_flush');
add_action('template_redirect',function(){if(!is_category()||!feng_setting('remove_category_base',false))return;$url=get_category_link(get_queried_object_id());$path=wp_parse_url($url,PHP_URL_PATH);$requested=wp_parse_url(wp_unslash($_SERVER['REQUEST_URI']??''),PHP_URL_PATH);if(!get_query_var('paged')&&!is_feed()&&$requested&&untrailingslashit($path)!==untrailingslashit($requested)){wp_safe_redirect($url,301);exit;}});
function feng_database_cleanup_form(){
 if(!current_user_can('manage_options'))return;
 echo '<details class="feng-settings-section"><summary>数据库维护</summary><p>仅清理已过期的 WordPress 临时缓存，不删除文章、评论、友情链接或修订版本。</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';wp_nonce_field('feng_expired_cache');echo '<input type="hidden" name="action" value="feng_expired_cache">';submit_button('清理过期缓存','secondary');echo '</form></details>';
}
add_action('admin_post_feng_expired_cache',function(){if(!current_user_can('manage_options'))wp_die('没有权限');check_admin_referer('feng_expired_cache');delete_expired_transients(true);wp_safe_redirect(admin_url('themes.php?page=feng-settings&cache_cleaned=1'));exit;});
add_action('admin_notices',function(){if(current_user_can('manage_options')&&($_GET['page']??'')==='feng-settings'&&isset($_GET['cache_cleaned']))echo '<div class="notice notice-success"><p>过期缓存已清理。</p></div>';});
?>
<?php
/** site-extras */
if(!defined('ABSPATH'))exit;
add_action('wp_footer',function(){if(feng_setting('analytics_mode','off')==='custom')echo "\n".feng_setting('analytics_code','')."\n";},20);
function feng_blog_decade_data($now=null){
 $value=feng_setting('decade_start','');
 if(!$value){$first=get_posts(array('post_type'=>'post','post_status'=>'publish','has_password'=>false,'posts_per_page'=>1,'orderby'=>array('date'=>'ASC','ID'=>'ASC'),'ignore_sticky_posts'=>true));if(!$first)return null;$value=substr($first[0]->post_date,0,10);}
 $start=DateTimeImmutable::createFromFormat('!Y-m-d',$value,wp_timezone());if(!$start||$start->format('Y-m-d')!==$value)return null;
 $year=(int)$start->format('Y')+10;$month=(int)$start->format('m');$day=(int)$start->format('d');while(!checkdate($month,$day,$year))$day--;
 $end=$start->setDate($year,$month,$day);$now=$now?:current_datetime();$total=(int)$start->diff($end)->days;$elapsed=max(0,min($total,(int)$start->diff($now)->format('%r%a')));
 return array('start'=>$start,'end'=>$end,'days'=>$elapsed,'total'=>$total,'percent'=>round($elapsed/$total*100,1),'future'=>$now<$start);
}
function feng_blog_decade(){
 if(!feng_setting('decade_enabled',true))return;$d=feng_blog_decade_data();if(!$d)return;
 echo '<section class="feng-decade" aria-labelledby="feng-decade-title"><div class="feng-decade-heading"><div><p class="xf-section-kicker">TEN YEARS OF BLOGGING</p><h2 id="feng-decade-title">博客十年</h2><p>'.($d['future']?'计划尚未开始':($d['days']===$d['total']?'十年记录已完成，故事继续。':'已经记录 '.$d['days'].' 天，继续写下生活。')).'</p></div><strong>'.esc_html($d['percent']).'<small>%</small></strong></div><progress value="'.esc_attr($d['days']).'" max="'.esc_attr($d['total']).'" aria-label="博客十年进度">'.esc_html($d['percent']).'%</progress><div class="feng-decade-dates"><time datetime="'.$d['start']->format('Y-m-d').'">'.$d['start']->format('Y.m.d').'</time><span>十年之约</span><time datetime="'.$d['end']->format('Y-m-d').'">'.$d['end']->format('Y.m.d').'</time></div></section>';
}
function feng_upload_is_apng($file){
 $handle=fopen($file,'rb');if(!$handle)return true;fread($handle,8);
 while(!feof($handle)){$header=fread($handle,8);if(strlen($header)<8)break;$length=unpack('N',substr($header,0,4))[1];$type=substr($header,4,4);if($type==='acTL'){fclose($handle);return true;}if($type==='IDAT'||$type==='IEND')break;if(fseek($handle,$length+4,SEEK_CUR)!==0)break;}
 fclose($handle);return false;
}
function feng_upload_webp($upload){
 if(!feng_setting('images_webp',true)||!empty($upload['error'])||empty($upload['file']))return $upload;
 $file=$upload['file'];$mime=wp_get_image_mime($file);if(!in_array($mime,array('image/jpeg','image/png'),true))return $upload;
 $size=wp_getimagesize($file);if(!$size||$size[0]*$size[1]>40000000||($mime==='image/png'&&feng_upload_is_apng($file)))return $upload;
 $editor=wp_get_image_editor($file);if(is_wp_error($editor)||!$editor->supports_mime_type('image/webp'))return $upload;
 if(is_wp_error($editor->maybe_exif_rotate())||is_wp_error($editor->set_quality(82)))return $upload;
 $dir=dirname($file);$name=wp_unique_filename($dir,pathinfo($file,PATHINFO_FILENAME).'.webp');$result=$editor->save($dir.'/'.$name,'image/webp');if(is_wp_error($result))return $upload;
 $upload['file']=$result['path'];$upload['url']=trailingslashit(dirname($upload['url'])).rawurlencode(basename($result['path']));$upload['type']='image/webp';wp_delete_file($file);return $upload;
}
add_filter('wp_handle_upload','feng_upload_webp');add_filter('wp_handle_sideload','feng_upload_webp');
add_action('wp_enqueue_scripts',function(){wp_enqueue_style('feng-site-extras',get_theme_file_uri('/assets/css/site-extras.css'),array(),feng_asset_version('/assets/css/site-extras.css'));if(feng_setting('images_fade',true))wp_enqueue_script('feng-image-fade',get_theme_file_uri('/assets/js/image-fade.js'),array('xf-app'),feng_asset_version('/assets/js/image-fade.js'),true);});

/** Public article totals. Chinese characters count individually, Latin words as words. */
function feng_footer_content_stats(){
 $stats=get_transient('polar_footer_content_totals_v2');
 if(false!==$stats)return $stats;
 global $wpdb;
 $rows=$wpdb->get_results("SELECT post_content,post_date FROM {$wpdb->posts} WHERE post_type='post' AND post_status='publish' AND post_password='' ORDER BY post_date ASC");
 $stats=array('articles'=>count($rows),'words'=>0,'first'=>$rows?substr($rows[0]->post_date,0,10):'');
 foreach($rows as $row){
  $text=html_entity_decode(wp_strip_all_tags(strip_shortcodes($row->post_content)),ENT_QUOTES,'UTF-8');
  $stats['words']+=preg_match_all('/[\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}]/u',$text);
  $text=preg_replace('/[\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}]/u',' ',$text);
  $stats['words']+=preg_match_all('/[\p{L}\p{N}]+(?:[\x{2019}\x{0027}-][\p{L}\p{N}]+)*/u',$text);
 }
 set_transient('polar_footer_content_totals_v2',$stats,DAY_IN_SECONDS);
 return $stats;
}
add_action('save_post_post',function(){delete_transient('polar_footer_content_totals_v2');});
add_action('deleted_post',function(){delete_transient('polar_footer_content_totals_v2');});
?>
<?php
/** greeting */
if(!defined('ABSPATH'))exit;
add_action('wp_enqueue_scripts',function(){
 wp_enqueue_script('feng-greeting',get_theme_file_uri('/assets/js/greeting.js'),array('xf-app'),feng_asset_version('/assets/js/greeting.js'),true);
});
function feng_greeting_identity(){
 nocache_headers();
 $user=wp_get_current_user();$commenter=wp_get_current_commenter();
 $name=$user->exists()?$user->display_name:($commenter['comment_author']??'');
 wp_send_json_success(array('name'=>mb_substr(sanitize_text_field($name),0,30)));
}
add_action('wp_ajax_feng_greeting_identity','feng_greeting_identity');
add_action('wp_ajax_nopriv_feng_greeting_identity','feng_greeting_identity');
?>
<?php
/** visitor-stats */
if(!defined('ABSPATH'))exit;
function feng_visitor_stats_install(){
 if(get_option('feng_visitor_stats_version')==='1')return;
 global $wpdb;require_once ABSPATH.'wp-admin/includes/upgrade.php';$collate=$wpdb->get_charset_collate();
 dbDelta("CREATE TABLE {$wpdb->prefix}feng_visitors (
 visitor char(64) NOT NULL,
 seen bigint unsigned NOT NULL,
 event char(36) NOT NULL DEFAULT '',
 PRIMARY KEY (visitor),
 KEY seen (seen)
 ) ENGINE=InnoDB $collate;");
 add_option('feng_total_pageviews',0,'',false);
 update_option('feng_visitor_stats_version','1',false);
}
add_action('init','feng_visitor_stats_install');
function feng_visitor_stats_ping(){
 if(!feng_setting('footer_stats',true))wp_send_json_error(null,404);
 nocache_headers();
 $visitor=is_string($_POST['visitor']??null)?$_POST['visitor']:'';$event=is_string($_POST['event']??null)?$_POST['event']:'';
 if(!preg_match('/^[a-f0-9-]{36}$/D',$visitor)||($event!==''&&!preg_match('/^[a-f0-9-]{36}$/D',$event)))wp_send_json_error(null,400);
 $origin=$_SERVER['HTTP_ORIGIN']??'';if($origin&&wp_parse_url($origin,PHP_URL_HOST)!==wp_parse_url(home_url(),PHP_URL_HOST))wp_send_json_error(null,403);
 global $wpdb;$table=$wpdb->prefix.'feng_visitors';$now=time();$hash=hash_hmac('sha256',$visitor,wp_salt('auth'));
 $wpdb->query('START TRANSACTION');
 $ok=$wpdb->query($wpdb->prepare("INSERT IGNORE INTO $table (visitor,seen,event) VALUES (%s,%d,'')",$hash,$now));
 $old=$wpdb->get_var($wpdb->prepare("SELECT event FROM $table WHERE visitor=%s FOR UPDATE",$hash));
 if($event!==''&&$old!==$event){
  $ok=$ok!==false&&$wpdb->query("UPDATE {$wpdb->options} SET option_value=CAST(option_value AS UNSIGNED)+1 WHERE option_name='feng_total_pageviews'")!==false;
 }
 $ok=$ok!==false&&$wpdb->query($wpdb->prepare("UPDATE $table SET seen=%d,event=%s WHERE visitor=%s",$now,$event?:$old,$hash))!==false;
 $wpdb->query($ok?'COMMIT':'ROLLBACK');if(!$ok)wp_send_json_error(null,503);
 $wpdb->query($wpdb->prepare("DELETE FROM $table WHERE seen<%d",$now-DAY_IN_SECONDS));
 $online=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE seen>=%d",$now-300));
 $total=(int)$wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name='feng_total_pageviews'");
 $location=get_transient('polar_latest_visitor_location');
 if($event!==''&&$old!==$event){
  $ip=feng_real_ip();
  $geo=feng_comment_geo_lookup($ip);
  if(!$geo&&($preview=feng_local_preview_geo()))$geo=array('label'=>sanitize_text_field($preview['city']??$preview['region']??''),'code'=>strtolower(sanitize_text_field($preview['country_code']??'')));
  if($geo){$location=$geo;set_transient('polar_latest_visitor_location',$geo,DAY_IN_SECONDS);}
 }
 wp_send_json_success(array('online'=>$online,'views'=>$total,'location'=>$location?:null));
}
add_action('wp_ajax_feng_visitor_stats','feng_visitor_stats_ping');add_action('wp_ajax_nopriv_feng_visitor_stats','feng_visitor_stats_ping');
add_action('wp_enqueue_scripts',function(){if(feng_setting('footer_stats',true))wp_enqueue_script('feng-visitor-stats',get_theme_file_uri('/assets/js/visitor-stats.js'),array('xf-app'),feng_asset_version('/assets/js/visitor-stats.js'),true);});
?>
<?php
/** activity-calendar */
if(!defined('ABSPATH'))exit;
/** Calendar uses site-local dates; only public, unprotected content is counted. */
function feng_activity_calendar($type='post',$year=0,$options=array()){
 global $wpdb;
 $talk=$type==='feng_talk';$column=$talk?'post_date':'post_modified';
 $today=new DateTimeImmutable('today',wp_timezone());
 $start=$year?new DateTimeImmutable($year.'-01-01',wp_timezone()):$today->modify('-'.(max(1,min(366,(int)($options['days']??365)))-1).' days');
 $end=$year?$start->modify('+1 year -1 day'):$today;
 $rows=$wpdb->get_results($wpdb->prepare("SELECT DATE($column) day,COUNT(*) total FROM {$wpdb->posts} WHERE post_type=%s AND post_status='publish' AND post_password='' AND $column >= %s AND $column < %s GROUP BY DATE($column)",$talk?'feng_talk':'post',$start->format('Y-m-d'),$end->modify('+1 day')->format('Y-m-d')));
 $counts=array();$total=0;foreach($rows as $row){$counts[$row->day]=(int)$row->total;$total+=(int)$row->total;}
 $grid_rows=max(1,min(7,(int)($options['rows']??7)));
 $grid_start=$grid_rows===7?$start->modify('-'.((int)$start->format('N')-1).' days'):$start;
 $weeks=(int)ceil(((int)$grid_start->diff($end)->days+1)/$grid_rows);
 ?>
 <section class="feng-calendar-activity" aria-label="<?php echo $talk?'说说发布热力图':'文章更新热力图'; ?>">
 <?php if(empty($options['hide_heading'])): ?><header><strong><?php echo $talk?'说说的足迹':'文章更新记录'; ?></strong><span><?php echo $year?(int)$year.' 年':'近一年'; ?> · <?php echo (int)$total; ?> <?php echo $talk?'条说说':'篇文章'; ?></span></header><?php endif; ?>
 <div class="feng-activity-scroll"><div class="feng-activity-grid" style="--activity-weeks:<?php echo (int)$weeks; ?>">
 <?php for($i=0;$i<$weeks*$grid_rows;$i++):$date=$grid_start->modify('+'.$i.' days');$day=$date->format('Y-m-d');$outside=$date<$start||$date>$end;$n=$counts[$day]??0;$level=$n?min(4,(int)ceil(log($n+1,2))):0;$label=$day.' · '.$n.($talk?' 条说说':' 篇文章最后更新'); ?>
 <span class="feng-activity-day" data-level="<?php echo $level; ?>" <?php if($outside): ?>aria-hidden="true" style="visibility:hidden"<?php else: ?>tabindex="0" aria-label="<?php echo esc_attr($label); ?>" data-tip="<?php echo esc_attr($label); ?>"<?php endif; ?>></span>
 <?php endfor; ?>
 </div></div>
 <?php if(empty($options['hide_footer'])): ?><footer><small><?php echo esc_html($start->format('Y.m.d').' — '.$end->format('Y.m.d')); ?><?php if(!$talk)echo ' · 每篇文章按最后更新时间计一次'; ?></small><span class="feng-activity-legend">少 <?php for($i=0;$i<5;$i++)echo '<i data-level="'.$i.'"></i>'; ?> 多</span></footer><?php endif; ?>
 </section>
 <?php
}
?>
<?php
/** weather */
if(!defined('ABSPATH'))exit;
function feng_weather_json($url,$ttl=1200){
 $key='feng_weather_'.md5($url);$cached=get_transient($key);if($cached!==false)return $cached?:null;
 // Brief negative cache also prevents repeated calls during outages.
 set_transient($key,array(),60);
 $response=wp_remote_get($url,array('timeout'=>6,'redirection'=>0));
 if(is_wp_error($response)||wp_remote_retrieve_response_code($response)!==200)return null;
 $data=json_decode(wp_remote_retrieve_body($response),true);if(!is_array($data))return null;
 set_transient($key,$data,$ttl);return $data;
}
function feng_weather_city($name){
 if(!$name)return null;
 $data=feng_weather_json(add_query_arg(array('name'=>$name,'count'=>1,'language'=>'zh','format'=>'json'),'https://geocoding-api.open-meteo.com/v1/search'),DAY_IN_SECONDS);
 return $data['results'][0]??null;
}
function feng_weather_current($city){
 if(!$city||!is_numeric($city['latitude']??null)||!is_numeric($city['longitude']??null))return null;
 $lat=(float)$city['latitude'];$lon=(float)$city['longitude'];if(abs($lat)>90||abs($lon)>180)return null;
 $data=feng_weather_json(add_query_arg(array('latitude'=>round($lat,2),'longitude'=>round($lon,2),'current'=>'temperature_2m,weather_code,is_day','daily'=>'sunrise,sunset','forecast_days'=>2,'timeformat'=>'unixtime','timezone'=>'auto'),'https://api.open-meteo.com/v1/forecast'));
 $c=$data['current']??null;if(!is_numeric($c['temperature_2m']??null)||!isset($c['weather_code']))return null;
 return array('latitude'=>round($lat,2),'longitude'=>round($lon,2),'city'=>sanitize_text_field($city['name']??''),'temperature'=>round($c['temperature_2m']),'code'=>(int)$c['weather_code'],'day'=>(bool)($c['is_day']??true),'time'=>sanitize_text_field((string)($c['time']??'')),'timezone'=>sanitize_text_field($data['timezone']??'UTC'),'utc_offset'=>(int)($data['utc_offset_seconds']??0),'sunrise'=>array_map('intval',$data['daily']['sunrise']??array()),'sunset'=>array_map('intval',$data['daily']['sunset']??array()));
}
/** Local preview uses this machine's public network location, never a made-up visitor. */
function feng_local_preview_geo(){
 $host=strtolower((string)wp_parse_url(home_url(),PHP_URL_HOST));
 $local=wp_get_environment_type()==='local'||$host==='localhost'||str_ends_with($host,'.local')||in_array($host,array('127.0.0.1','[::1]'),true);
 $ip=feng_real_ip();
 if(!$local||filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE))return null;
 $geo=feng_weather_json('https://ipwho.is/?fields=success,city,region,country_code,latitude,longitude',HOUR_IN_SECONDS);
 return !empty($geo['success'])?$geo:null;
}
function feng_weather_ajax(){
 nocache_headers();if(!feng_setting('greeting_weather',true)&&!feng_setting('hero_smart_scene',true))wp_send_json_error(null,404);
 $kind=is_string($_GET['kind']??null)?$_GET['kind']:'host';$city=null;
 if($kind==='visitor'){
  if(!feng_setting('weather_visitor',true))wp_send_json_success(null);
  // Trust the server address, not arbitrary client-supplied forwarding headers.
  $ip=feng_real_ip();
  if(filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE)){
   $geo=feng_weather_json('https://ipwho.is/'.rawurlencode($ip).'?fields=success,city,latitude,longitude',HOUR_IN_SECONDS);
   if(!empty($geo['success']))$city=array('name'=>$geo['city']??'','latitude'=>$geo['latitude']??null,'longitude'=>$geo['longitude']??null);
  }elseif($geo=feng_local_preview_geo()){
   $city=array('name'=>$geo['city']??'','latitude'=>$geo['latitude']??null,'longitude'=>$geo['longitude']??null);
  }
 }else $city=feng_weather_city(feng_setting('weather_city',''));
 wp_send_json_success(feng_weather_current($city));
}
add_action('wp_ajax_feng_weather','feng_weather_ajax');add_action('wp_ajax_nopriv_feng_weather','feng_weather_ajax');


/** Shared search form, used by WordPress get_search_form(). */
function feng_search_form_markup($form, $args = array()) {
    ob_start();
?>
<?php $feng_search_id = wp_unique_id( ! empty( $args['xf_dialog'] ) ? 'xf-dialog-search-' : 'xf-page-search-' ); ?>
<form role="search" method="get" class="xf-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $feng_search_id ); ?>"><?php esc_html_e( '搜索文章', 'feng' ); ?></label>
	<input id="<?php echo esc_attr( $feng_search_id ); ?>" type="search" name="s" value="<?php echo esc_attr( get_search_query( false ) ); ?>" placeholder="<?php esc_attr_e( '搜索文章…', 'feng' ); ?>">
	<button type="submit"><?php esc_html_e( '搜索', 'feng' ); ?></button>
</form>
<?php
    return ob_get_clean();
}
add_filter('get_search_form', 'feng_search_form_markup', 10, 2);

// === Custom Permalink: /post/{display_id}.html ===
add_action('init', 'feng_custom_permalink_init');
function feng_custom_permalink_init() {
    add_rewrite_tag('%display_id%', '([0-9]+)', 'display_id=');
}
add_filter('query_vars', function($vars) {
    $vars[] = 'display_id';
    return $vars;
});
add_filter('post_link', 'feng_display_id_permalink', 10, 3);
function feng_display_id_permalink($permalink, $post, $leavename) {
    if ($post->post_type !== 'post') return $permalink;
    $display_id = get_post_meta($post->ID, '_feng_display_id', true);
    if (!$display_id) $display_id = $post->ID;
    return str_replace('%display_id%', $display_id, $permalink);
}
add_action('parse_request', 'feng_display_id_parse_request');
function feng_display_id_parse_request($wp) {
    if (empty($wp->query_vars['display_id'])) return;
    $display_id = absint($wp->query_vars['display_id']);
    $post_id = feng_get_post_id_by_display_id($display_id);
    if ($post_id) {
        $wp->query_vars['p'] = $post_id;
        unset($wp->query_vars['display_id']);
    }
}
function feng_get_post_id_by_display_id($display_id) {
    global $wpdb;
    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_feng_display_id' AND meta_value=%s LIMIT 1",
        (string) $display_id
    ));
}
