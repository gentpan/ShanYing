<?php
if(!defined('ABSPATH')) exit;

// A single thread per page, including core comment queries and submission links.
// Use 0 (not false) to short-circuit the option without changing saved settings.
add_filter('pre_option_page_comments', static function() { return 0; });

function feng_emoji_toolbar() {
 $emojis=array(
 '😀'=>'开心','😁'=>'笑脸','😂'=>'笑哭','😃'=>'开怀','😄'=>'高兴','😅'=>'汗颜','😆'=>'大笑','😇'=>'天使','😈'=>'调皮','😉'=>'眨眼','😊'=>'微笑','😋'=>'美味','😌'=>'满足','😍'=>'喜欢','😎'=>'酷','😏'=>'得意','😐'=>'平静','😑'=>'无语','😒'=>'不悦','😓'=>'紧张','😔'=>'低落','😕'=>'困惑','😖'=>'纠结','😘'=>'飞吻','😗'=>'亲亲','😙'=>'轻吻','😚'=>'害羞','😛'=>'吐舌','😜'=>'俏皮','😝'=>'鬼脸',
 '😞'=>'失落','😟'=>'担心','😠'=>'生气','😡'=>'愤怒','😢'=>'流泪','😣'=>'坚持','😤'=>'不服气','😥'=>'难过','😮'=>'惊讶','😯'=>'意外','😰'=>'焦虑','😩'=>'疲惫','😪'=>'困倦','😫'=>'抓狂','😬'=>'尴尬','😭'=>'大哭','😲'=>'震惊','😳'=>'脸红','😨'=>'害怕','😱'=>'惊吓','😵'=>'晕眩','😴'=>'睡觉','😶'=>'沉默','😷'=>'口罩',
 '😺'=>'开心猫','😸'=>'笑脸猫','😹'=>'笑哭猫','😻'=>'心动猫','😼'=>'得意猫','😽'=>'亲亲猫','🙀'=>'惊讶猫','😿'=>'哭泣猫','😾'=>'生气猫','🙈'=>'不看','🙉'=>'不听','🙊'=>'不说','🙋'=>'举手','🙌'=>'欢呼','🙏'=>'感谢','👋'=>'挥手','👍'=>'赞','👏'=>'鼓掌','🤝'=>'握手','✌️'=>'胜利','🤔'=>'思考','🥹'=>'感动','🥳'=>'庆祝','❤️'=>'爱心','🎉'=>'礼花','☕'=>'咖啡'
 );
 $html='<div class="feng-emoji-bar wp-exclude-emoji" data-feng-emojis hidden><div class="feng-emoji-options">';
 foreach($emojis as $emoji=>$label) $html.='<button type="button" data-feng-emoji="'.esc_attr($emoji).'" aria-label="插入表情：'.esc_attr($label).'" title="'.esc_attr($label).'">'.esc_html($emoji).'</button>';
 return $html.'</div><button type="button" class="feng-emoji-more" data-feng-emoji-more aria-label="展开更多表情" aria-expanded="false">'.feng_icon('plus').'</button></div>';
}
function feng_comment_client($comment){
 $ua=(string)$comment->comment_agent;$items=array();
 foreach(array('Edg(?:e|A|iOS)?'=>array('edge','Edge'),'OPR|Opera'=>array('opera','Opera'),'Firefox|FxiOS'=>array('firefox','Firefox'),'Chrome|CriOS'=>array('chrome','Chrome'),'Version'=>array('safari','Safari')) as $pattern=>$v)if(preg_match('~(?:'.$pattern.')/([0-9]+(?:\.[0-9]+)*)~',$ua,$m)){$items[]=array($v[0],$v[1].' '.$m[1],'浏览器');break;}
 foreach(array('Android(?: ([0-9.]+))?'=>array('android','Android'),'(?:iPhone|iPad).*?OS ([0-9_]+)'=>array('ios','iOS'),'Windows NT ([0-9.]+)'=>array('windows','Windows'),'Mac OS X ([0-9_]+)'=>array('macos','macOS'),'Ubuntu(?:[/ ]([0-9.]+))?'=>array('ubuntu','Ubuntu'),'Debian(?:[/ ]([0-9.]+))?'=>array('debian','Debian'),'Fedora(?:[/ ]([0-9.]+))?'=>array('fedora','Fedora'),'Linux'=>array('linux','Linux')) as $pattern=>$v)if(preg_match('~'.$pattern.'~i',$ua,$m)){$version=str_replace('_','.', $m[1]??'');if($v[0]==='windows')$version=array('10.0'=>'10 / 11','6.3'=>'8.1','6.2'=>'8','6.1'=>'7')[$version]??$version;$items[]=array($v[0],trim($v[1].' '.$version),'操作系统');break;}
 $platform_version=get_comment_meta($comment->comment_ID,'_feng_macos_version',true);
 foreach($items as &$item){if($item[0]==='macos'){
  if(is_string($platform_version)&&preg_match('/^\d{1,3}(?:\.\d{1,3}){0,2}$/',$platform_version))$item[1]='macOS '.$platform_version;
  elseif(preg_match('/^macOS 10(?:\.15(?:\.|$)|$)/',$item[1]))$item[1]='macOS';
 }}unset($item);
 if(!$items)return '';
 $html='<span class="feng-comment__client feng-comment-device-capsule">';
 foreach($items as $item){
  $html.='<span class="feng-comment-device-icon feng-comment-tip" tabindex="0" aria-label="'.esc_attr($item[1]).'" data-comment-tip="'.esc_attr($item[1]).'">';
  if(in_array($item[0],array('macos','ios'),true)){
   $html.='<img class="feng-device-apple-light" src="'.esc_url(get_theme_file_uri('/assets/images/comment-client/apple-black.svg')).'" width="14" height="14" alt="" loading="lazy"><img class="feng-device-apple-dark" src="'.esc_url(get_theme_file_uri('/assets/images/comment-client/apple.svg')).'" width="14" height="14" alt="" loading="lazy">';
  }else $html.='<img src="'.esc_url(get_theme_file_uri('/assets/images/comment-client/'.$item[0].'.svg')).'" width="14" height="14" alt="" loading="lazy">';
  $html.='</span>';
 }
 $html.='</span>';
 return $html;
}
function feng_comment_level($comment){
 global $wpdb;static $counts=null;
 if($counts===null){$counts=array();foreach($wpdb->get_results("SELECT LOWER(comment_author_email) email, COUNT(*) total FROM {$wpdb->comments} WHERE comment_approved='1' AND comment_type IN ('','comment') AND comment_author_email<>'' GROUP BY LOWER(comment_author_email)") as $row)$counts[$row->email]=(int)$row->total;}
 $total=$counts[strtolower($comment->comment_author_email)]??0;
 $tiers=array(
  array(0,'初声','comment'),array(5,'微语','comment-dots'),
  array(15,'回音','reply'),array(30,'清谈','mug-hot'),
  array(60,'共鸣','heart'),array(100,'畅言','comments'),
  array(180,'妙语','lightbulb'),array(300,'知音','headphones'),
  array(500,'雅集','users'),array(800,'余音','music')
 );
 $index=0;foreach($tiers as $i=>$tier)if($total>=$tier[0])$index=$i;
 $level=$index+1;$tier=$tiers[$index];
 $next=$tiers[$index+1][0]??null;
 $remaining=$next!==null?'距下次升级还差 '.($next-$total).' 条':'已满级';
 $progress=$next!==null?max(0,min(100,round(($total-$tier[0])/($next-$tier[0])*100))):100;
 $tip='共 '.$total.' 条评论，'.$remaining;
 $rules='';foreach($tiers as $i=>$item){$end=isset($tiers[$i+1])?($tiers[$i+1][0]-1).' 条': '条及以上';$range=isset($tiers[$i+1])?$item[0].'–'.$end:$item[0].' '.$end;$rules.='<span class="feng-level-rule"><span>LV-'.($i+1).' · '.esc_html($item[1]).'</span><span>'.esc_html($range).'</span></span>';}
 return '<span class="feng-comment-level feng-comment-tip feng-level-'.(int)$level.'" tabindex="0" aria-label="'.esc_attr('LV-'.$level.' '.$tier[1].'，'.$tip).'">'
 .'<span class="feng-level-number">LV-'.$level.'</span>'
 .'<span class="feng-level-popover"><span class="feng-level-popover-head"><strong>LV-'.$level.' · '.esc_html($tier[1]).'</strong><i class="fa-solid fa-'.esc_attr($tier[2]).'" aria-hidden="true"></i></span>'
 .'<span class="feng-level-progress" role="progressbar" aria-label="本次升级进度" aria-valuemin="0" aria-valuemax="100" aria-valuenow="'.(int)$progress.'"><span style="width:'.(int)$progress.'%"></span></span>'
 .'<span class="feng-level-popover-foot"><small>'.esc_html($remaining).'</small><span class="feng-level-rules"><button type="button" class="feng-level-rules-button" aria-label="查看全部等级规则">等级规则</button><span class="feng-level-rules-list">'.$rules.'</span></span></span></span></span>';


}
/** Match public friend links to the website supplied with a comment. */
function feng_comment_friend_badge($comment,$kind_only=false){
 static $friends=null;
 $normalize=static function($url){
  $parts=wp_parse_url(trim((string)$url));
  if(!$parts||empty($parts['host'])||!in_array(strtolower($parts['scheme']??''),array('http','https'),true))return null;
  return array(preg_replace('/^www\./','',strtolower($parts['host'])),rtrim($parts['path']??'','/'),$parts['port']??null);
 };
 $site=$normalize($comment->comment_author_url);if(!$site)return '';
 if($friends===null){$friends=array();foreach(get_bookmarks(array('hide_invisible'=>true)) as $link){$url=$normalize($link->link_url);if($url){$terms=wp_get_object_terms((int)$link->link_id,'link_category',array('fields'=>'slugs'));$url[3]=!is_wp_error($terms)&&in_array('friend',$terms,true);$friends[]=$url;}}}
 $matched=false;$close=false;
 foreach($friends as $friend){
  if($site[0]!==$friend[0]||$site[2]!==$friend[2])continue;
  if($friend[1]!==''&&$site[1]!==$friend[1]&&!str_starts_with($site[1],$friend[1].'/'))continue;
  $matched=true;$close=$close||$friend[3];
 }
 $kind=$matched?($close?'friend':'link'):'';
 return $kind_only?$kind:($kind?feng_comment_identity_mark($kind):'');
}
function feng_comment_identity_mark($kind){
 $label=array('blogger'=>'博主','friend'=>'博主的朋友','link'=>'友情链接用户')[$kind];
 $icon=$kind==='link'?'user-group':'badge-check';
 return '<span class="feng-comment-identity-mark feng-comment-tip feng-mark-'.esc_attr($kind).'" tabindex="0" aria-label="'.esc_attr($label).'" data-comment-tip="'.esc_attr($label).'"><i class="fa-sharp fa-solid fa-'.esc_attr($icon).'" aria-hidden="true"></i></span>';
}
function feng_comment_floor($comment){
 if((int)$comment->comment_parent!==0)return '';
 global $wp_query;static $floors=array();$post=(int)$comment->comment_post_ID;
 if(!isset($floors[$post])){$items=array_filter($wp_query->comments??array(),static function($c)use($post){return (int)$c->comment_post_ID===$post && $c->comment_approved==='1' && (int)$c->comment_parent===0;});usort($items,static function($a,$b){return strcmp($a->comment_date_gmt,$b->comment_date_gmt)?:((int)$a->comment_ID<=>(int)$b->comment_ID);});$floors[$post]=array_flip(array_map(static function($c){return (int)$c->comment_ID;},$items));}
 if($comment->comment_approved!=='1')return '待审核';
 return isset($floors[$post][$comment->comment_ID])?($floors[$post][$comment->comment_ID]+1).' 楼':'#'.$comment->comment_ID;
}
function feng_comment($comment,$args,$depth) {
 $parent=$comment->comment_parent?get_comment($comment->comment_parent):null;
 $is_admin=$comment->user_id && user_can((int)$comment->user_id,'manage_options');
 $mark_kind=$is_admin?'blogger':feng_comment_friend_badge($comment,true);
 $avatar_mark=in_array($mark_kind,array('blogger','friend'),true);
 ?>
 <li <?php comment_class('feng-comment'.($is_admin?' feng-comment--admin':'')); ?> id="comment-<?php comment_ID(); ?>">
 <article id="div-comment-<?php comment_ID(); ?>" class="feng-comment__body">
 <div class="feng-comment__avatar"><?php $avatar=get_avatar($comment,40,'','',array('loading'=>'lazy')); echo $avatar?:'<span class="feng-avatar-initial" aria-hidden="true">'.esc_html(mb_substr(get_comment_author($comment),0,1)).'</span>'; if($avatar_mark)echo feng_comment_identity_mark($mark_kind); ?></div>
 <div class="feng-comment__main">
 <header class="feng-comment__heading"><div class="feng-comment__identity"><b><?php echo get_comment_author_link($comment); ?></b><?php if($mark_kind==='link')echo feng_comment_identity_mark('link'); ?><?php if(!$is_admin)echo feng_comment_level($comment); ?><?php if($parent): ?><a class="feng-comment-reply-target" href="#comment-<?php echo (int)$parent->comment_ID; ?>" aria-label="<?php echo esc_attr('回复 '.get_comment_author($parent).'，查看原评论'); ?>"><span aria-hidden="true">↳</span> @<?php echo esc_html(get_comment_author($parent)); ?></a><?php endif; ?><time class="feng-comment__date feng-comment-tip" tabindex="0" data-comment-tip="<?php echo esc_attr('评论时间：'.get_comment_date('Y-m-d H:i',$comment)); ?>" datetime="<?php echo esc_attr(get_comment_date(DATE_W3C,$comment)); ?>"><?php echo esc_html(feng_comment_relative_time($comment)); ?></time><?php if(!$is_admin && $comment->user_id && (int)$comment->user_id===(int)get_post_field('post_author',$comment->comment_post_ID)) echo '<span class="feng-badge">作者</span>'; ?><span class="feng-comment__client feng-comment-location" data-comment-geo="<?php echo (int)$comment->comment_ID; ?>"><span data-geo-label>所在地加载中</span></span><?php echo feng_comment_client($comment); ?></div><div class="feng-comment__controls"><?php if(!(int)$comment->comment_parent): ?><a class="feng-comment__number" href="<?php echo esc_url(get_comment_link($comment)); ?>"><?php echo esc_html(feng_comment_floor($comment)); ?></a><?php endif; ?><div class="feng-comment__actions"><?php if(current_user_can('manage_options'))echo '<a href="'.esc_url(get_edit_comment_link($comment->comment_ID)).'" aria-label="编辑评论" title="编辑评论">'.feng_icon('edit').'</a>'; ?><?php comment_reply_link(array_merge($args,array('add_below'=>'div-comment','depth'=>$depth,'max_depth'=>$args['max_depth'],'reply_text'=>feng_icon('comment').'<span class="screen-reader-text">回复</span>','reply_to_text'=>'回复 %s')),$comment); ?></div></div></header>

 <?php if('0'===$comment->comment_approved) echo '<p class="feng-comment__pending">这条留言正在等待审核，暂时仅你可见。</p>'; ?>
 <div class="feng-comment__text"><?php comment_text($comment); ?></div>

 </div></article>
 <?php
}
function feng_comment_form_config($post_id) {
 if(!feng_setting('comments_ajax',true)) return;
 echo '<input type="hidden" name="feng_comment_nonce" value="'.esc_attr(wp_create_nonce('feng_comment_'.$post_id)).'" data-feng-comment-endpoint="'.esc_url(admin_url('admin-ajax.php')).'">';
}
add_action('comment_form','feng_comment_form_config');
function feng_submit_comment() {
 if($_SERVER['REQUEST_METHOD']!=='POST') wp_send_json_error(array('message'=>'请使用表单提交。'),405);
 $post_id=isset($_POST['comment_post_ID'])?absint($_POST['comment_post_ID']):0;
 if(!check_ajax_referer('feng_comment_'.$post_id,'feng_comment_nonce',false)) wp_send_json_error(array('message'=>'页面已过期，请刷新后重新提交；先复制保存你的留言。'),403);
 $comment=wp_handle_comment_submission(wp_unslash($_POST));
 if(is_wp_error($comment)) {
  $code=(int)$comment->get_error_data();
  wp_send_json_error(array('message'=>wp_strip_all_tags($comment->get_error_message())?:'暂时无法提交，请稍后再试。'),$code>=400 && $code<=599?$code:400);
 }
 $consent=isset($_POST['wp-comment-cookies-consent']);
 do_action('set_comment_cookies',$comment,wp_get_current_user(),$consent);
 $url=get_comment_link($comment);
 if(!$consent && 'unapproved'===wp_get_comment_status($comment) && $comment->comment_author_email) $url=add_query_arg(array('unapproved'=>$comment->comment_ID,'moderation-hash'=>wp_hash($comment->comment_date_gmt)),$url);
 $url=wp_validate_redirect(apply_filters('comment_post_redirect',$url,$comment),get_permalink($post_id));
 $edit_token=wp_generate_password(40,false,false);update_comment_meta($comment->comment_ID,'_feng_edit_token',hash('sha256',$edit_token));
 global $wp_query;
 $approved=get_comments(array('post_id'=>$post_id,'status'=>'approve','orderby'=>'comment_date_gmt','order'=>'ASC','number'=>0));
 $wp_query->comments=$approved;
 $visible=$approved;
 if('1'!==$comment->comment_approved)$visible[]=$comment;
 ob_start();
 wp_list_comments(array('style'=>'ol','short_ping'=>true,'avatar_size'=>40,'callback'=>'feng_comment','per_page'=>0,'page'=>1),$visible);
 $comments_html=ob_get_clean();
 wp_send_json_success(array('edit'=>array('id'=>(int)$comment->comment_ID,'token'=>$edit_token,'expires'=>time()+60),'message'=>'1'===$comment->comment_approved?'留言已发布。':'留言已提交，审核通过后会公开显示。','url'=>$url,'commentsHtml'=>$comments_html,'commentCount'=>(int)get_comments_number($post_id)));
}
add_action('wp_ajax_feng_comment','feng_submit_comment');
add_action('wp_ajax_nopriv_feng_comment','feng_submit_comment');

function feng_comment_geo_lookup($ip){
 if(!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE))return array();
 $key='feng_cgeo_'.hash_hmac('sha256',$ip,wp_salt('auth'));$cached=get_transient($key);if($cached!==false)return $cached;
 $lock=$key.'_lock';if(get_transient($lock))return array();set_transient($lock,1,10);
 $response=wp_safe_remote_get('https://api.cnip.io/geoip/'.rawurlencode($ip),array('timeout'=>5,'redirection'=>0,'limit_response_size'=>16000,'headers'=>array('Accept'=>'application/json'),'feng_feed_request'=>true));
 $value=array();if(!is_wp_error($response)&&wp_remote_retrieve_response_code($response)===200){$data=json_decode(wp_remote_retrieve_body($response),true);if(is_array($data)){$parts=array();foreach(array($data['province']??$data['region']??'', $data['city']??'') as $part)if(is_string($part)&&trim($part)!=='')$parts[]=sanitize_text_field($part);$label=implode(' · ',array_unique($parts));$code=strtolower(is_string($data['country_code']??null)?$data['country_code']:'');if($label)$value=array('label'=>$label,'code'=>preg_match('/^[a-z]{2}$/D',$code)?$code:'');}}
 set_transient($key,$value,$value?30*DAY_IN_SECONDS:10*MINUTE_IN_SECONDS);delete_transient($lock);return $value;
}
add_action('rest_api_init',function(){register_rest_route('feng/v1','/comment-location/(?P<id>\d+)',array('methods'=>'GET','permission_callback'=>'__return_true','callback'=>function($request){
 $comment=get_comment((int)$request['id']);$post=$comment?get_post($comment->comment_post_ID):null;
 if(!$comment||$comment->comment_approved!=='1'||!$post||!is_post_publicly_viewable($post)||$post->post_password)return new WP_Error('not_found','评论不可用',array('status'=>404));
 return rest_ensure_response(feng_comment_geo_lookup($comment->comment_author_IP));
}));});

/** Relative labels use UTC elapsed time; tooltip keeps the site's local date. */
function feng_comment_relative_time($comment){
 $date=$comment->comment_date_gmt;
 $timestamp=$date&&$date!=='0000-00-00 00:00:00'?strtotime($date.' UTC'):(new DateTimeImmutable($comment->comment_date,wp_timezone()))->getTimestamp();
 $seconds=max(0,time()-$timestamp);
 if($seconds<60)return max(1,$seconds).' 秒前';
 if($seconds<HOUR_IN_SECONDS)return (int)floor($seconds/MINUTE_IN_SECONDS).' 分钟前';
 if($seconds<DAY_IN_SECONDS)return (int)floor($seconds/HOUR_IN_SECONDS).' 小时前';
 if($seconds<30*DAY_IN_SECONDS)return (int)floor($seconds/DAY_IN_SECONDS).' 天前';
 if($seconds<365*DAY_IN_SECONDS)return (int)floor($seconds/(30*DAY_IN_SECONDS)).' 个月前';
 return (int)floor($seconds/(365*DAY_IN_SECONDS)).' 年前';
}

function feng_comment_remembered(){
 $c=wp_get_current_commenter();return !empty($c['comment_author'])&&!empty($c['comment_author_email']);
}
add_action('wp_enqueue_scripts',function(){wp_enqueue_script('feng-comment-profile',get_theme_file_uri('/assets/js/comment-profile.js'),array('xf-app'),feng_asset_version('/assets/js/comment-profile.js'),true);});

// Store browser-provided macOS hints only with the newly submitted comment.
add_action('comment_post',function($id){
 $v=$_POST['feng_macos_version']??'';
 if(is_string($v)&&preg_match('/^\d{1,3}(?:\.\d{1,3}){0,2}$/',$v)&&strpos($_SERVER['HTTP_USER_AGENT']??'','Mac OS X')!==false)update_comment_meta($id,'_feng_macos_version',$v);
});

function feng_comment_self_edit(){
 nocache_headers();
 $id=absint($_POST['id']??0);$comment=get_comment($id);
 $token=is_string($_POST['token']??null)?$_POST['token']:'';
 if(!$comment||!$token||!hash_equals((string)get_comment_meta($id,'_feng_edit_token',true),hash('sha256',$token))||time()-strtotime($comment->comment_date_gmt.' UTC')>60||!in_array($comment->comment_approved,array('0','1'),true))wp_send_json_error(array('message'=>'编辑时间已结束，或无权修改这条评论。'),403);
 if($comment->user_id&&(int)$comment->user_id!==get_current_user_id())wp_send_json_error(null,403);
 if(!isset($_POST['content']))wp_send_json_success(array('content'=>$comment->comment_content));
 if(!is_string($_POST['content']))wp_send_json_error(null,400);
 $content=trim(wp_unslash($_POST['content']));if($content===''||mb_strlen($content)>10000)wp_send_json_error(array('message'=>'评论不能为空，且不能超过 10000 字。'),400);
 $data=wp_filter_comment(array('comment_post_ID'=>$comment->comment_post_ID,'comment_author'=>$comment->comment_author,'comment_author_email'=>$comment->comment_author_email,'comment_author_url'=>$comment->comment_author_url,'comment_author_IP'=>$comment->comment_author_IP,'comment_content'=>$content,'comment_type'=>'comment','user_id'=>$comment->user_id));
 $approved=wp_allow_comment($data,true);if(is_wp_error($approved))wp_send_json_error(array('message'=>wp_strip_all_tags($approved->get_error_message())),400);
 $result=wp_update_comment(array('comment_ID'=>$id,'comment_content'=>$data['comment_content'],'comment_approved'=>$comment->comment_approved==='0'?'0':$approved),true);
 if(is_wp_error($result)||!$result)wp_send_json_error(array('message'=>'保存失败，请重试。'),400);
 wp_send_json_success(array('message'=>'修改已保存。'));
}
add_action('wp_ajax_feng_comment_self_edit','feng_comment_self_edit');add_action('wp_ajax_nopriv_feng_comment_self_edit','feng_comment_self_edit');
