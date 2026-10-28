<?php
/** friend-avatar */
/** Match approved commenters to friend domains; retain hashes, never copy email addresses. */
if(!defined('ABSPATH'))exit;
function feng_friend_domain($url){
 $url=trim((string)$url);if(!$url)return '';if(!preg_match('~^https?://~i',$url))$url='https://'.$url;
 $p=wp_parse_url($url);if(!$p||isset($p['user'])||isset($p['pass'])||empty($p['host']))return '';
 $host=strtolower(rtrim($p['host'],'.'));if(function_exists('idn_to_ascii'))$host=idn_to_ascii($host,0,INTL_IDNA_VARIANT_UTS46)?:$host;
 return preg_replace('/^www\./','',$host);
}
function feng_friend_avatar_rebuild(){
 global $wpdb;$old=get_option('feng_friend_avatars',array());$map=array();$domains=array();
 foreach(get_bookmarks(array('hide_invisible'=>false)) as $link){$domain=feng_friend_domain($link->link_url);if(!$domain)continue;$id=(int)$link->link_id;$prior=$old[$id]??array();$map[$id]=array('domain'=>$domain,'manual'=>($prior['domain']??'')===$domain?($prior['manual']??''):'','hash'=>'','comment'=>0);$domains[$domain][]=$id;}
 if($domains){
  $rows=$wpdb->get_results("SELECT comment_ID,comment_author_email,comment_author_url FROM {$wpdb->comments} WHERE comment_approved='1' AND comment_type IN ('','comment') AND comment_author_email<>'' AND comment_author_url<>'' ORDER BY comment_date_gmt DESC,comment_ID DESC");
  foreach($rows as $c){$domain=feng_friend_domain($c->comment_author_url);if(empty($domains[$domain])||!is_email($c->comment_author_email))continue;
   foreach($domains[$domain] as $id)if(!$map[$id]['hash']){$map[$id]['hash']=md5(strtolower(trim($c->comment_author_email)));$map[$id]['comment']=(int)$c->comment_ID;}
  }
 }
 update_option('feng_friend_avatars',$map,false);update_option('feng_friend_avatar_version','1',false);
}
add_action('init',function(){if(get_option('feng_friend_avatar_version')!=='1')feng_friend_avatar_rebuild();},45);
foreach(array('comment_post','transition_comment_status','edit_comment','deleted_comment','add_link','edit_link','deleted_link') as $hook)add_action($hook,'feng_friend_avatar_rebuild',50,0);
function feng_friend_avatar($friend,$size=48,$icon_fallback=false){
 if(is_numeric($friend))$friend=get_bookmark((int)$friend);if(!$friend)return '';
 $domain=feng_friend_domain($friend->link_url);$all=get_option('feng_friend_avatars',array());$v=$all[(int)$friend->link_id]??array();
 $hash=($v['domain']??'')===$domain?(($v['manual']??'')?:($v['hash']??'')):'';
 $fallback='https://favicon.la/'.rawurlencode($domain);
 $url=!empty($friend->link_image)?$friend->link_image:($hash?'https://gravatar.bluecdn.com/avatar/'.$hash.'?s='.($size*2).'&d=404':$fallback);
 return '<img class="feng-friend-avatar-img" src="'.esc_url($url).'" data-friend-fallback="'.esc_url($fallback).'" width="'.absint($size).'" height="'.absint($size).'" alt="" loading="lazy" decoding="async"><span class="feng-friend-avatar-initial" hidden aria-hidden="true">'.($icon_fallback?'<i class="fa-solid fa-globe" aria-hidden="true"></i>':esc_html(mb_substr(wp_specialchars_decode($friend->link_name),0,1))).'</span>';
}
?>
<?php
/** feed */
/** Friend subscriptions: bounded local snapshots, RSS/Atom ingestion and WP-Cron. */
if (!defined('ABSPATH')) exit;

function feng_feed_sources() {
 return array_values(array_filter(get_bookmarks(array('hide_invisible'=>true,'orderby'=>'name')), static function($link) { return trim((string)$link->link_rss) !== ''; }));
}
function feng_feed_hours() { return (int)feng_setting('feed_hours',4) === 6 ? 6 : 4; }
add_filter('cron_schedules', static function($schedules) {
 foreach(array(4,6) as $h) $schedules['feng_feed_'.$h]=array('interval'=>$h*HOUR_IN_SECONDS,'display'=>'每 '.$h.' 小时同步友链');
 return $schedules;
});
function feng_feed_schedule() {
 $name='feng_feed_'.feng_feed_hours(); $event=wp_get_scheduled_event('feng_feed_refresh');
 if (!$event || $event->schedule!==$name) {
  wp_clear_scheduled_hook('feng_feed_refresh');
  wp_schedule_event(time()+60,$name,'feng_feed_refresh');
 }
}
add_action('init','feng_feed_schedule',40);
add_action('update_option_feng_settings','feng_feed_schedule');
add_action('switch_theme',static function() { foreach(array('feng_feed_refresh','feng_feed_step','feng_feed_changed') as $hook) wp_clear_scheduled_hook($hook); });
function feng_feed_link_changed($id) {
 $link=get_bookmark($id); $saved=get_option('feng_feed_source_'.absint($id),array());
 if (!$link || $link->link_visible!=='Y' || ($saved['hash']??'')!==hash('sha256',trim((string)$link->link_rss))) delete_option('feng_feed_source_'.absint($id));
 if (!wp_next_scheduled('feng_feed_changed')) wp_schedule_single_event(time()+10,'feng_feed_changed');
}
add_action('add_link','feng_feed_link_changed');
add_action('edit_link','feng_feed_link_changed');
add_action('deleted_link',static function($id) { delete_option('feng_feed_source_'.absint($id)); });

/** Do not mistake Atom updated time for original publication time. */
function feng_feed_published($item) {
 foreach(array(array('http://www.w3.org/2005/Atom','published'),array('http://purl.org/atom/ns#','issued'),array('','pubDate'),array('http://purl.org/rss/1.0/','pubDate'),array('http://purl.org/dc/elements/1.1/','date')) as $tag) {
  $values=$item->get_item_tags($tag[0],$tag[1]);
  $raw=trim((string)($values[0]['data']??''));
  // A calendar date is required; relative words such as "today" are not dates.
  if ($raw && preg_match('/\b\d{4}\b/',$raw)) {
   try { return max(0,(new DateTimeImmutable($raw,new DateTimeZone('UTC')))->getTimestamp()); } catch(Exception $e) { return 0; }
  }
 }
 return 0;
}
function feng_feed_text($text,$limit) { return mb_substr(trim(preg_replace('/\s+/u',' ',wp_strip_all_tags(html_entity_decode((string)$text,ENT_QUOTES|ENT_HTML5,'UTF-8')))),0,$limit); }
function feng_feed_parse($xml) {
 if(is_string($xml)&&preg_match('/^\s*(?:<!doctype\s+html|<html\b)/i',$xml))return new WP_Error('feed_html','订阅地址返回了普通网页，可能是站点暂停、地址失效或访问拦截。');
 if (!$xml || strlen($xml)>1048576 || preg_match('/<!\s*(DOCTYPE|ENTITY)/i',$xml) || strpos($xml,"\0")!==false) return new WP_Error('feed_xml','订阅内容为空、过大或包含不支持的 XML 声明。');
 require_once ABSPATH.WPINC.'/class-simplepie.php';
 $feed=new SimplePie\SimplePie();
 $feed->enable_cache(false); $feed->set_raw_data($xml); $feed->set_autodiscovery_level(0);
 if (!$feed->init() || $feed->error() || !$feed->get_type()) return new WP_Error('feed_parse','无法解析 RSS / Atom，请检查 RSS 地址。');
 $entries=array(); $urls=array();
 $raw_items=$feed->get_items();
 usort($raw_items,static function($a,$b) { return feng_feed_published($b)<=>feng_feed_published($a); });
 foreach($raw_items as $item) {
  $url=esc_url_raw((string)$item->get_permalink(),array('http','https'));
  $parts=wp_parse_url($url);
  if (!$url || empty($parts['host']) || !in_array($parts['scheme']??'',array('http','https'),true) || isset($parts['user']) || isset($parts['pass'])) continue;
  $id=hash('sha256',(string)($item->get_id()?:$url));
  if (isset($entries[$id]) || isset($urls[$url])) continue;
  $urls[$url]=true;
  $entries[$id]=array('id'=>$id,'url'=>$url,'title'=>feng_feed_text($item->get_title(),200)?:'无标题文章','summary'=>feng_feed_text($item->get_description(),150),'published'=>feng_feed_published($item));
 }
 usort($entries,static function($a,$b) { return $b['published']<=>$a['published']; });
 return array_slice($entries,0,200);
}
/** Validate each redirect ourselves as well as through the WP safe HTTP transport. */
function feng_feed_safe_url($url) {
 $override=apply_filters('feng_feed_url_validation',null,$url);if(is_bool($override))return $override;
 $parts=wp_parse_url($url);
 if (!$parts || !in_array($parts['scheme']??'',array('http','https'),true) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || !wp_http_validate_url($url)) return false;
 $host=strtolower(trim($parts['host'],'[].'));
 if ($host==='localhost' || str_ends_with($host,'.local')) return false;
 $addresses=filter_var($host,FILTER_VALIDATE_IP)?array($host):gethostbynamel($host);
 if (!$addresses) return false;
 $flags=defined('FILTER_FLAG_GLOBAL_RANGE')?FILTER_FLAG_GLOBAL_RANGE:FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE;
 foreach($addresses as $ip) if (!filter_var($ip,FILTER_VALIDATE_IP,$flags)) return false;
 return true;
}
function feng_feed_fetch($source) {
 $url=trim((string)$source->link_rss); $deadline=microtime(true)+12;
 for($hop=0;$hop<4;$hop++) {
  if (!feng_feed_safe_url($url)) return new WP_Error('feed_url','RSS 地址必须是公开的 HTTP / HTTPS 地址，不能指向本机或私有网络。');
  if (microtime(true)>=$deadline) return new WP_Error('feed_timeout','订阅源响应超时，请稍后重试。');
  $response=wp_safe_remote_get($url,array('feng_feed_request'=>true,'timeout'=>max(1,$deadline-microtime(true)),'redirection'=>0,'limit_response_size'=>1048577,'headers'=>array('Accept'=>'application/rss+xml, application/atom+xml, application/xml, text/xml')));
  if (is_wp_error($response)) {
   $reason=$response->get_error_message();
   if(preg_match('/timed? out|timeout|cURL error 28/i',$reason))return new WP_Error('feed_timeout','订阅源响应超时，请稍后重试。');
   if(preg_match('/SSL|certificate|cURL error 60/i',$reason))return new WP_Error('feed_tls','订阅源 HTTPS 证书验证失败。');
   if(preg_match('/resolve host|DNS/i',$reason))return new WP_Error('feed_dns','订阅源域名暂时无法解析。');
   return new WP_Error('feed_network','暂时无法连接订阅源，请检查地址或稍后重试。');
  }
  $code=wp_remote_retrieve_response_code($response);
  if (in_array($code,array(301,302,303,307,308),true)) {
   $location=wp_remote_retrieve_header($response,'location');
   if (!is_string($location) || !$location) break;
   $url=WP_Http::make_absolute_url($location,$url); continue;
  }
  if ($code!==200) return new WP_Error('feed_http','订阅源返回 HTTP '.absint($code).'。');
  return feng_feed_parse(wp_remote_retrieve_body($response));
 }
 return new WP_Error('feed_redirect','订阅源重定向次数过多或缺少目标地址。');
}
function feng_feed_store($source,$result) {
 $key='feng_feed_source_'.(int)$source->link_id; $hash=hash('sha256',trim($source->link_rss));
 $old=get_option($key,array()); if (($old['hash']??'')!==$hash) $old=array();
 $snapshot=$old; $snapshot['hash']=$hash; $snapshot['attempt']=time();
 if (is_wp_error($result)) {
  $snapshot['error']=$result->get_error_message(); update_option($key,$snapshot,false); return 0;
 }
 $merged=array(); $urls=array();
 foreach($old['items']??array() as $entry) { $merged[$entry['id']]=$entry; $urls[$entry['url']]=$entry['id']; }
 $added=0;
 foreach($result as $entry) {
  $prior=$merged[$entry['id']]??(isset($urls[$entry['url']])?$merged[$urls[$entry['url']]]:null);
  if (!$prior) $added++;
  // An edited or temporarily undated entry keeps its original publication date.
  if ($prior && $prior['published']) $entry['published']=$prior['published'];
  if ($prior) unset($merged[$prior['id']]);
  $merged[$entry['id']]=$entry; $urls[$entry['url']]=$entry['id'];
 }
 usort($merged,static function($a,$b) { return $b['published']<=>$a['published']; });
 $snapshot['items']=array_slice($merged,0,200); $snapshot['success']=time(); $snapshot['error']='';
 update_option($key,$snapshot,false); return $added;
}

/** One source per step bounds worker duration. MySQL advisory lock releases on disconnect. */
function feng_feed_run($start=false) {
 global $wpdb;
 $lock='feng_feed_'.md5(DB_NAME.$wpdb->prefix);
 if ((int)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,0)',$lock))!==1) return get_option('feng_feed_cycle',array());
 try {
  $cycle=get_option('feng_feed_cycle',array()); $sources=feng_feed_sources(); $by_id=array();
  foreach($sources as $source) $by_id[(int)$source->link_id]=$source;
  if ($start && empty($cycle['pending'])) $cycle=array('started'=>time(),'pending'=>array_keys($by_id),'total'=>count($sources),'done'=>0,'added'=>0,'errors'=>0);
  if (!empty($cycle['pending'])) {
   $id=array_shift($cycle['pending']);
   if (isset($by_id[$id])) {
    $result=feng_feed_fetch($by_id[$id]);
    // A link may have been edited/deleted while its request was in flight.
    $current=get_bookmark($id);
    if ($current && $current->link_visible==='Y' && $current->link_rss===$by_id[$id]->link_rss) {
     $cycle['added']+=feng_feed_store($current,$result);
     if(is_wp_error($result)) $cycle['errors']++;
    }
   }
   $cycle['done']++;
  }
  if (empty($cycle['pending'])) {
   if (!empty($cycle['started']) && empty($cycle['finished'])) $cycle['finished']=time();
   wp_clear_scheduled_hook('feng_feed_step');
  } elseif (!wp_next_scheduled('feng_feed_step')) wp_schedule_single_event(time()+15,'feng_feed_step');
  update_option('feng_feed_cycle',$cycle,false);
  return $cycle;
 } finally { $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)',$lock)); }
}
add_action('feng_feed_refresh',static function(){ feng_feed_run(true); });
add_action('feng_feed_changed',static function(){ feng_feed_run(true); });
add_action('feng_feed_step',static function(){ feng_feed_run(false); });

/** Cache reads only; the midnight boundary follows Settings > General > Timezone. */
function feng_feed_data($now=null) {
 $now=$now??time(); $start=(new DateTimeImmutable('@'.$now))->setTimezone(wp_timezone())->setTime(0,0);
 $items=array(); $sources=array(); $seen=array(); $today=0;
 foreach(feng_feed_sources() as $source) {
  $snapshot=get_option('feng_feed_source_'.(int)$source->link_id,array());
  if (($snapshot['hash']??'')!==hash('sha256',trim($source->link_rss))) $snapshot=array();
  $sources[]=array('id'=>(int)$source->link_id,'name'=>$source->link_name,'success'=>$snapshot['success']??0,'error'=>$snapshot['error']??'');
  foreach($snapshot['items']??array() as $entry) {
   if ($entry['published']>$now || isset($seen[$entry['url']])) continue;
   $seen[$entry['url']]=true; $entry['source']=(int)$source->link_id; $entry['source_name']=$source->link_name;
   $entry['today']=$entry['published']>=$start->getTimestamp() && $entry['published']>0;
   if ($entry['today']) $today++;
   $items[]=$entry;
  }
 }
 usort($items,static function($a,$b) { return $b['published']<=>$a['published'] ?: strcmp($a['id'],$b['id']); });
 return array('items'=>$items,'sources'=>$sources,'today'=>$today,'day'=>$start->format('Y-m-d'),'midnight'=>$start->modify('+1 day')->getTimestamp(),'cycle'=>get_option('feng_feed_cycle',array()));
}
function feng_feed_badge() {
 $data=feng_feed_data();
 return '<span class="t-badge feng-feed-badge" data-open="'.($data['today']?'true':'false').'" data-feed-count="'.$data['today'].'"><span class="t-badge-dot" aria-hidden="true">'.esc_html($data['today']>99?'99+':$data['today']).'</span><span class="screen-reader-text">'.esc_html('，今日更新 '.$data['today'].' 篇').'</span></span>';
}
add_filter('nav_menu_item_title',static function($title,$item) {
 $url=feng_page_url('subscriptions');
 if ($url && untrailingslashit($item->url)===untrailingslashit($url)) $title.=feng_feed_badge();
 return $title;
},10,2);
add_action('wp_ajax_feng_feed_count','feng_feed_count_ajax');
add_action('wp_ajax_nopriv_feng_feed_count','feng_feed_count_ajax');
function feng_feed_count_ajax() { $data=feng_feed_data(); wp_send_json_success(array('today'=>$data['today'],'day'=>$data['day'],'midnight'=>$data['midnight'])); }
function feng_feed_ensure_page() {
 if (get_option('feng_feed_page_ready')) return;
 $existing=get_posts(array('post_type'=>'page','post_status'=>array('publish','draft','private','pending','trash'),'meta_key'=>'_wp_page_template','meta_value'=>'pages/subscriptions.php','numberposts'=>1));
 if ($existing || get_page_by_path('subscriptions')) { update_option('feng_feed_page_ready',1,false); return; }
 $id=wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_title'=>'订阅','post_name'=>'subscriptions','comment_status'=>'closed','page_template'=>'pages/subscriptions.php'),true);
 if (!is_wp_error($id) && $id) update_option('feng_feed_page_ready',1,false);
}
add_action('init','feng_feed_ensure_page',35);
?>
<?php
/** feed-ui */
/** Subscription page and native WordPress settings UI. */
if (!defined('ABSPATH')) exit;
function feng_feed_rows($items) {
 ob_start();
 foreach($items as $entry) { ?>
 <article class="feng-feed-entry"><span class="feng-feed-entry__mark" aria-hidden="true"><?php echo feng_friend_avatar($entry['source'],40); ?></span><div class="feng-feed-entry__body"><div class="feng-feed-entry__meta"><span><?php echo esc_html($entry['source_name']); ?></span><span>·</span><?php if($entry['published']) { ?><time datetime="<?php echo esc_attr(gmdate('c',$entry['published'])); ?>"><?php echo esc_html(wp_date(wp_date('Y',$entry['published'])===wp_date('Y')?'m 月 d 日 H:i':'Y 年 m 月 d 日',$entry['published'])); ?></time><?php } else { ?><span>发布时间未提供</span><?php } ?><?php if($entry['today']) { ?><span class="feng-feed-today">今日</span><?php } ?></div><h2><a href="<?php echo esc_url($entry['url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($entry['title']); ?></a></h2><?php if($entry['summary']) { ?><p><?php echo esc_html($entry['summary']); ?></p><?php } ?></div></article>
 <?php }
 return ob_get_clean();
}
function feng_feed_list($source=0,$today=false,$page=1) {
 $data=feng_feed_data();
 $items=array_values(array_filter($data['items'],static function($entry) use($source,$today) { return (!$source || $entry['source']===$source) && (!$today || $entry['today']); }));
 $total=count($items); $page=max(1,$page);
 return array('html'=>feng_feed_rows(array_slice($items,($page-1)*30,30)),'total'=>$total,'more'=>$page*30<$total,'page'=>$page);
}
function feng_feed_list_ajax() {
 $source=isset($_GET['source']) && is_scalar($_GET['source'])?absint($_GET['source']):0;
 $page=isset($_GET['page']) && is_scalar($_GET['page'])?min(10000,max(1,absint($_GET['page']))):1;
 wp_send_json_success(feng_feed_list($source,isset($_GET['today']) && $_GET['today']==='1',$page));
}
add_action('wp_ajax_feng_feed_list','feng_feed_list_ajax');
add_action('wp_ajax_nopriv_feng_feed_list','feng_feed_list_ajax');


// Public applications enter the native link manager as invisible bookmarks.
add_action('admin_post_nopriv_feng_friend_apply','feng_friend_apply');
add_action('admin_post_feng_friend_apply','feng_friend_apply');
function feng_friend_apply(){
 if($_SERVER['REQUEST_METHOD']!=='POST') wp_die('请通过申请表提交。', '', array('response'=>405));
 check_admin_referer('feng_friend_apply','friend_nonce');
 $input=wp_unslash($_POST);
 foreach(array('site_name','site_url','site_description','site_avatar','site_rss','contact_website','page_id','site_email','friend_mode') as $field) if(isset($input[$field])&&!is_scalar($input[$field])) wp_die('表单格式不正确。');
 $page=absint($input['page_id']??0);
 if(get_post_status($page)!=='publish'||get_page_template_slug($page)!=='pages/friends.php'||post_password_required($page)) wp_die('此页面暂不接受申请。');
 if(!empty($input['contact_website'])) wp_die('提交失败。');
 $name=sanitize_text_field($input['site_name']??'');$description=sanitize_textarea_field($input['site_description']??'');
 $email=trim($input['site_email']??'');if(!is_email($email))wp_die('请填写有效的常用邮箱。');
 $identity_hash=md5(strtolower($email));$editing=($input['friend_mode']??'apply')==='edit';
 $avatar_input=trim($input['site_avatar']??'');
 $avatar_hash=is_email($avatar_input)?md5(strtolower($avatar_input)):'';
 if($avatar_hash)$input['site_avatar']='';
 elseif($avatar_input&&!preg_match('~^https?://~i',$avatar_input))wp_die('头像请填写有效邮箱或 HTTP / HTTPS 图片地址。');
 $urls=array();foreach(array('site_url','site_avatar','site_rss') as $field){$raw=trim($input[$field]??'');$url=esc_url_raw($raw,array('http','https'));if($raw&&(!$url||!wp_parse_url($url,PHP_URL_HOST))) wp_die('请填写有效的 HTTP 或 HTTPS 地址。');if(strlen($url)>255) wp_die('地址过长。');$urls[$field]=$url;}
 if(!$name||!$description||!$urls['site_url']||mb_strlen($name)>100||mb_strlen($description)>255) wp_die('请填写站点名称、地址和简短介绍。');
 if(!$avatar_input)$avatar_hash=$identity_hash;
 $rate='feng_friend_apply_'.hash_hmac('sha256',$_SERVER['REMOTE_ADDR']??'',wp_salt());
 if(get_transient($rate)) wp_die('刚刚已提交申请，请稍后再试。', '', array('response'=>429));
 global $wpdb;
 if(!$editing&&$wpdb->get_var($wpdb->prepare("SELECT link_id FROM {$wpdb->links} WHERE link_url=%s LIMIT 1",$urls['site_url']))) wp_die('这个站点已在友链或待审核列表中，请勿重复申请。');
 if($editing){
  $identities=get_option('feng_friend_identity_hashes',array());$avatars=get_option('feng_friend_avatars',array());$matches=array();
  foreach(get_bookmarks(array('hide_invisible'=>false)) as $link){$hash=$identities[$link->link_id]??($avatars[$link->link_id]['manual']??'');if($hash&&hash_equals($hash,$identity_hash))$matches[]=$link;}
  if(count($matches)!==1){set_transient($rate,1,MINUTE_IN_SECONDS);wp_die('无法唯一匹配原友链，请核对上次申请邮箱，或联系博主处理。');}
  $target=$matches[0];
  if(!feng_friend_verify_code($input,$identity_hash,(int)$target->link_id))wp_die('验证码无效或已过期，请重新获取后提交。');
  $proposal=array('name'=>$name,'url'=>$urls['site_url'],'description'=>$description,'avatar'=>$urls['site_avatar'],'rss'=>$urls['site_rss'],'avatar_hash'=>$avatar_hash,'submitted'=>time());
  update_option('feng_friend_change_'.(int)$target->link_id,$proposal,false);
  require_once ABSPATH.'wp-admin/includes/bookmark.php';
  wp_update_link(array('link_id'=>$target->link_id,'link_notes'=>'待审核修改申请：'.wp_json_encode($proposal,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)));
  set_transient($rate,1,10*MINUTE_IN_SECONDS);
  wp_safe_redirect(add_query_arg('friend_submitted','1',get_permalink($page)),303);exit;
 }
 require_once ABSPATH.'wp-admin/includes/bookmark.php';
 $id=wp_insert_link(array('link_name'=>$name,'link_url'=>$urls['site_url'],'link_description'=>$description,'link_image'=>$urls['site_avatar'],'link_rss'=>$urls['site_rss'],'link_visible'=>'N','link_owner'=>0,'link_notes'=>'前台友链申请，待审核。审核后将「可见」设为是。','link_category'=>array((int)get_option('default_link_category'))),true);
 if(is_wp_error($id)||!$id) wp_die('申请保存失败，请稍后重试。');
 $identities=get_option('feng_friend_identity_hashes',array());$identities[(int)$id]=$identity_hash;update_option('feng_friend_identity_hashes',$identities,false);
 if($avatar_hash){
  $avatars=get_option('feng_friend_avatars',array());
  $avatars[(int)$id]=array('domain'=>feng_friend_domain($urls['site_url']),'manual'=>$avatar_hash,'hash'=>'','comment'=>0);
  update_option('feng_friend_avatars',$avatars,false);
 }
 set_transient($rate,1,10*MINUTE_IN_SECONDS);
 wp_safe_redirect(add_query_arg('friend_submitted','1',get_permalink($page)).'#friend-application',303);exit;
}

// Link-category icons share the article-category SVG / Font Awesome sanitizer.
function feng_friend_category_icon($id){return feng_badge_icon_markup(get_term_meta($id,'feng_friend_category_icon',true))?:feng_badge_icon_markup('fa-regular fa-folder');}

function feng_friend_send_code(){
 check_ajax_referer('feng_friend_apply','friend_nonce');
 $email=isset($_POST['email'])&&is_string($_POST['email'])?strtolower(trim(wp_unslash($_POST['email']))):'';
 if(!is_email($email))wp_send_json_error(array('message'=>'请填写有效邮箱。'),400);
 $key=hash_hmac('sha256',$email,wp_salt());$ipkey='feng_friend_mail_ip_'.hash_hmac('sha256',$_SERVER['REMOTE_ADDR']??'',wp_salt());
 if(get_transient('feng_friend_mail_wait_'.$key)||(int)get_transient($ipkey)>=10)wp_send_json_error(array('message'=>'发送过于频繁，请稍后再试。'),429);
 set_transient('feng_friend_mail_wait_'.$key,1,60);set_transient($ipkey,(int)get_transient($ipkey)+1,HOUR_IN_SECONDS);
 $hash=md5($email);$ids=get_option('feng_friend_identity_hashes',array());$avatars=get_option('feng_friend_avatars',array());$matches=array();
 foreach(get_bookmarks(array('hide_invisible'=>false)) as $link){$saved=$ids[$link->link_id]??($avatars[$link->link_id]['manual']??'');if($saved&&hash_equals($saved,$hash))$matches[]=$link->link_id;}
 $token=wp_generate_password(40,false,false);
 if(count($matches)===1){
  $code=(string)random_int(100000,999999);
  $record=array('email'=>$hash,'link'=>(int)$matches[0],'hash'=>wp_hash_password($code),'expires'=>time()+600,'tries'=>0);
  set_transient('feng_friend_code_'.$token,$record,600);
  if(!wp_mail($email,'友链修改验证码：'.get_bloginfo('name'),'你的友链修改验证码是：'.$code."\n10 分钟内有效，仅可使用一次。如果不是你本人操作，请忽略本邮件。")){
   delete_transient('feng_friend_code_'.$token);wp_send_json_error(array('message'=>'邮件发送失败，请稍后重试或联系博主。'),503);
  }
 }
 wp_send_json_success(array('token'=>$token,'message'=>'若邮箱与原友链匹配，验证码将发送至该邮箱，10 分钟内有效。'));
}
add_action('wp_ajax_feng_friend_send_code','feng_friend_send_code');
add_action('wp_ajax_nopriv_feng_friend_send_code','feng_friend_send_code');
function feng_friend_verify_code($input,$identity_hash,$link_id){
 $token=$input['email_token']??'';$code=$input['email_code']??'';
 if(!is_string($token)||!preg_match('/^[a-zA-Z0-9]{40}$/D',$token)||!is_string($code)||!preg_match('/^[0-9]{6}$/D',$code))return false;
 $key='feng_friend_code_'.$token;$record=get_transient($key);
 if(!$record||$record['expires']<=time()||$record['tries']>=5)return false;
 $record['tries']++;set_transient($key,$record,max(1,$record['expires']-time()));
 if(!hash_equals($record['email'],$identity_hash)||(int)$record['link']!==$link_id||!wp_check_password($code,$record['hash']))return false;
 delete_transient($key);return true;
}


// Recreational memory-game scores; display names are not verified identities.
add_action('wp_ajax_feng_friend_game','feng_friend_game');
add_action('wp_ajax_nopriv_feng_friend_game','feng_friend_game');
function feng_friend_game(){
 check_ajax_referer('feng_friend_game','nonce');
 $op=isset($_POST['op'])&&is_string($_POST['op'])?$_POST['op']:'';
 if($op==='player')wp_send_json_success(feng_game_player());
 if(strpos($op,'match_')===0){feng_match_game_request($op);return;}
 $size=($_POST['size']??'16')==='64'?64:16;
 $score_key=$size===64?'feng_friend_game_scores_64':'feng_friend_game_scores';
 $board=get_option($score_key,array());
 if($op==='list')wp_send_json_success(array('scores'=>array_values($board)));
 $ip=hash_hmac('sha256',$_SERVER['REMOTE_ADDR']??'',wp_salt());
 if($op==='start'){
  if(get_transient('feng_game_wait_'.$ip))wp_send_json_error(array('message'=>'请稍候再开始。'),429);
  set_transient('feng_game_wait_'.$ip,1,3);
  $deck=array_merge(range(0,$size/2-1),range(0,$size/2-1));shuffle($deck);$token=wp_generate_password(32,false,false);
  set_transient('feng_game_round_'.$token,array('deck'=>$deck,'size'=>$size,'start'=>microtime(true)),HOUR_IN_SECONDS);
  wp_send_json_success(array('deck'=>$deck,'token'=>$token));
 }
 if($op!=='score')wp_send_json_error(null,400);
 $token=$_POST['token']??'';$raw=$_POST['flips']??'';
 if(!is_string($token)||!preg_match('/^[A-Za-z0-9]{32}$/D',$token)||!is_string($raw)||strlen($raw)>10000)wp_send_json_error(null,400);
 $round=get_transient('feng_game_round_'.$token);$flips=json_decode(wp_unslash($raw),true);
 if(!$round||($round['size']??16)!==$size||!is_array($flips)||count($flips)<$size||count($flips)>1000||count($flips)%2)wp_send_json_error(null,400);
 $matched=array();for($i=0;$i<count($flips);$i+=2){$a=$flips[$i];$b=$flips[$i+1];if(!is_int($a)||!is_int($b)||$a<0||$a>=$size||$b<0||$b>=$size||$a===$b||isset($matched[$a])||isset($matched[$b]))wp_send_json_error(null,400);if($round['deck'][$a]===$round['deck'][$b]){$matched[$a]=true;$matched[$b]=true;}}
 $elapsed=(int)round(microtime(true)-$round['start']);if(count($matched)!==$size||$elapsed<1)wp_send_json_error(null,400);
 delete_transient('feng_game_round_'.$token);
 $commenter=wp_get_current_commenter();$user=wp_get_current_user();$name=$user->exists()?$user->display_name:($commenter['comment_author']?:'访客');
 $entry=array_merge(feng_game_player(),array('moves'=>count($flips),'seconds'=>$elapsed));
 $board[]=$entry;usort($board,function($a,$b){return $a['moves']<=>$b['moves']?:$a['seconds']<=>$b['seconds'];});$fast=$board;usort($fast,function($a,$b){return $a['seconds']<=>$b['seconds']?:$a['moves']<=>$b['moves'];});$board=array_values(array_unique(array_merge(array_slice($board,0,10),array_slice($fast,0,10)),SORT_REGULAR));update_option($score_key,$board,false);
 wp_send_json_success(array('scores'=>$board,'score'=>$entry));
}

function feng_game_player(){
 $user=wp_get_current_user();$commenter=wp_get_current_commenter();$email=$user->exists()?$user->user_email:($commenter['comment_author_email']??'');
 return array('name'=>mb_substr(sanitize_text_field($user->exists()?$user->display_name:($commenter['comment_author']?:'访客')),0,30),'avatar'=>get_avatar_url($email?:0,array('size'=>64,'default'=>'mystery')));
}

function feng_match_game_request($op){
 $scores=get_option('feng_match_scores',array());
 if($op==='match_list')wp_send_json_success(array('scores'=>$scores));
 if($op==='match_start'){
  $key='feng_match_wait_'.hash_hmac('sha256',$_SERVER['REMOTE_ADDR']??'',wp_salt());if(get_transient($key))wp_send_json_error(null,429);set_transient($key,1,3);
  $token=wp_generate_password(32,false,false);set_transient('feng_match_round_'.$token,time(),HOUR_IN_SECONDS);wp_send_json_success(array('token'=>$token));
 }
 if($op!=='match_score')wp_send_json_error(null,400);
 $token=$_POST['token']??'';$points=$_POST['points']??'';
 if(!is_string($token)||!preg_match('/^[A-Za-z0-9]{32}$/D',$token)||!is_string($points)||!ctype_digit($points))wp_send_json_error(null,400);
 $start=get_transient('feng_match_round_'.$token);$seconds=time()-(int)$start;$points=(int)$points;
 if(!$start||$seconds<10||$points<0||$points>100000||($_POST['moves']??'')!=='30')wp_send_json_error(null,400);
 delete_transient('feng_match_round_'.$token);$scores[]=array_merge(feng_game_player(),array('points'=>$points,'seconds'=>$seconds));usort($scores,function($a,$b){return $b['points']<=>$a['points']?:$a['seconds']<=>$b['seconds'];});$scores=array_slice($scores,0,10);update_option('feng_match_scores',$scores,false);wp_send_json_success(array('scores'=>$scores));
}
