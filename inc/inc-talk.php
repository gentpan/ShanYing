<?php
/** Front-end short notes. WordPress owns content, authors, terms and attachments. */
if (!defined('ABSPATH')) exit;

add_action('init', function () {
 register_post_type('talk', array(
  'labels'=>array('name'=>'说说','singular_name'=>'说说'),
  'public'=>false, 'publicly_queryable'=>false, 'show_ui'=>false,
  'show_in_rest'=>false, 'exclude_from_search'=>true, 'rewrite'=>false,
  'supports'=>array('title','editor','author','revisions'), 'can_export'=>true,
  'delete_with_user'=>false,
  // Writing goes through the front-end endpoint, with explicit author checks.
  'capabilities'=>array('create_posts'=>'do_not_allow'), 'map_meta_cap'=>true,
  'capability_type'=>'post',
 ));
 register_taxonomy('feng_talk_tag','talk',array(
  'label'=>'说说关键词','public'=>false,'show_ui'=>false,'show_in_rest'=>false,
  'hierarchical'=>false,'rewrite'=>false,
 ));
});

function feng_is_talk_post($post) { return $post && $post->post_type==='talk'; }
function feng_talk_can_publish() { return is_user_logged_in() && current_user_can('read'); }
function feng_talk_upload_limit() { return min(5*MB_IN_BYTES,wp_max_upload_size()); }
function feng_talk_total_limit() { $limit=wp_convert_hr_to_bytes(ini_get('post_max_size'));return max(0,min(20*MB_IN_BYTES,($limit?:21*MB_IN_BYTES)-65536)); }
function feng_talk_can_edit($post) {
 return $post && $post->post_type==='talk' && feng_talk_can_publish()
  && ((int)$post->post_author===get_current_user_id() || current_user_can('manage_options'));
}
function feng_talk_page_setup() {
 if(!current_user_can('manage_options') || feng_page_url('talks')) return;
 // Never replace an existing page's content or template.
 if(get_page_by_path('talks')) return;
 $id=wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_title'=>'说说','post_name'=>'talks','comment_status'=>'closed'),true);
 if(!is_wp_error($id)) update_post_meta($id,'_wp_page_template','pages/talks.php');
}
add_action('admin_init','feng_talk_page_setup');
add_action('init','feng_talk_page_setup',30);
add_action('after_switch_theme','feng_talk_page_setup');


function feng_talk_color($tag) {
 $colors=array('#a06c46','#4c8197','#897097','#558780','#687d99','#ad6963','#748359');
 return $colors[hexdec(substr(hash('sha256',$tag),0,6))%count($colors)];
}
function feng_talk_version($post) {
 return hash('sha256',wp_json_encode(array($post->post_content,$post->post_status,$post->post_modified_gmt,get_post_meta($post->ID,'_feng_talk_images',true),get_post_meta($post->ID,'_feng_talk_location',true),wp_get_object_terms($post->ID,'feng_talk_tag',array('fields'=>'names')))));
}
function feng_talk_data($post) {
 $tags=wp_get_object_terms($post->ID,'feng_talk_tag');$images=array();
 foreach((array)get_post_meta($post->ID,'_feng_talk_images',true) as $id) {
  if(!wp_attachment_is_image($id))continue;
  $url=wp_get_attachment_image_url($id,'large');if(!$url)continue;
  $images[]=array('id'=>(int)$id,'url'=>$url,'full'=>wp_get_attachment_url($id),'alt'=>get_post_meta($id,'_wp_attachment_image_alt',true));
 }
 return array('id'=>$post->ID,'content'=>$post->post_content,'date'=>get_post_time('c',false,$post),
  'date_label'=>get_the_date('Y年n月j日 H:i',$post),'relative'=>human_time_diff(get_post_time('U',true,$post),time()).'前',
  'author'=>get_the_author_meta('display_name',$post->post_author),'location'=>(string)get_post_meta($post->ID,'_feng_talk_location',true),
  'tags'=>is_wp_error($tags)?array():array_map(function($t){return array('id'=>$t->term_id,'name'=>$t->name,'color'=>feng_talk_color($t->name));},$tags),
  'images'=>$images,'can_edit'=>feng_talk_can_edit($post),'version'=>feng_talk_version($post));
}
function feng_talk_card($post) {
 $item=feng_talk_data($post);$angles=array(-1.4,.9,-.6,1.2,-.8,.5);$angle=$angles[$post->ID%6];
 ob_start(); ?>
 <article class="feng-talk-card" data-talk-id="<?php echo (int)$post->ID; ?>" style="--talk-angle:<?php echo esc_attr($angle); ?>deg;--talk-shift:<?php echo (int)(($post->ID%3)*8); ?>px">
 <div class="feng-talk-card-tags"><?php foreach(array_slice($item['tags'],0,2) as $tag): ?><button type="button" data-talk-tag="<?php echo (int)$tag['id']; ?>" style="--tag-color:<?php echo esc_attr($tag['color']); ?>" aria-label="筛选关键词：<?php echo esc_attr($tag['name']); ?>"><?php echo feng_icon('plus'); ?><span><?php echo esc_html($tag['name']); ?></span></button><?php endforeach; ?><?php if(count($item['tags'])>2): ?><span class="feng-talk-tags-more">+<?php echo count($item['tags'])-2; ?></span><?php endif; ?></div>
 <a class="feng-talk-card-open" href="#talk-<?php echo (int)$post->ID; ?>" data-talk-open="<?php echo (int)$post->ID; ?>" aria-label="展开说说：<?php echo esc_attr(mb_substr($item['content']?:'图片说说',0,40)); ?>">
 <div class="feng-talk-card-copy"><time datetime="<?php echo esc_attr($item['date']); ?>" title="<?php echo esc_attr($item['date_label']); ?>"><?php echo esc_html($item['relative']); ?></time><p><?php echo esc_html($item['content']); ?></p><div class="feng-talk-card-meta"><span><?php if($item['location']){echo feng_icon('pin').' '.esc_html($item['location']);} ?></span><span>via 网页</span></div></div>
 <?php if($item['images']): ?><div class="feng-talk-card-images" data-count="<?php echo count($item['images']); ?>"><?php foreach(array_slice($item['images'],0,2) as $image): ?><img loading="lazy" decoding="async" src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>"><?php endforeach; ?><?php if(count($item['images'])>2): ?><span>+<?php echo count($item['images'])-2; ?></span><?php endif; ?></div><?php endif; ?>
 </a></article>
 <?php return ob_get_clean();
}
function feng_talk_list($input=array()) {
 $page=max(1,min(10000,absint($input['page']??1)));$tag=absint($input['tag']??0);
 $args=array('post_type'=>'talk','post_status'=>'publish','posts_per_page'=>18,'paged'=>$page,'orderby'=>array('date'=>'DESC','ID'=>'DESC'),'ignore_sticky_posts'=>true);
 if($tag)$args['tax_query']=array(array('taxonomy'=>'feng_talk_tag','terms'=>array($tag)));
 $q=is_string($input['q']??null)?mb_substr(sanitize_text_field($input['q']),0,100):'';
 if($q!=='')$args['s']=$q;
 $query=new WP_Query($args);$html='';foreach($query->posts as $post)$html.=feng_talk_card($post);
 return array('html'=>$html,'total'=>(int)$query->found_posts,'page'=>$page,'more'=>$page<(int)$query->max_num_pages);
}
function feng_talk_terms() {
 $terms=get_terms(array('taxonomy'=>'feng_talk_tag','hide_empty'=>true,'orderby'=>'count','order'=>'DESC','number'=>80));
 return is_wp_error($terms)?array():array_map(function($t){return array('id'=>$t->term_id,'name'=>$t->name,'count'=>$t->count,'color'=>feng_talk_color($t->name));},$terms);
}
function feng_talk_read_ajax() {
 $op=isset($_GET['op'])&&is_string($_GET['op'])?sanitize_key($_GET['op']):'list';
 if($op==='session')wp_send_json_success(array('can_manage'=>current_user_can('manage_options'),'can_publish'=>feng_talk_can_publish(),'nonce'=>feng_talk_can_publish()?wp_create_nonce('feng_talk_write'):'','tags'=>feng_talk_terms()));
 if($op==='detail') {
  $post=get_post(absint($_GET['id']??0));
  if(!feng_is_talk_post($post) || $post->post_status!=='publish')wp_send_json_error(array('message'=>'这条说说已经不在这里了。'),404);
  wp_send_json_success(feng_talk_data($post));
 }
 wp_send_json_success(feng_talk_list(wp_unslash($_GET)));
}
add_action('wp_ajax_feng_talk_read','feng_talk_read_ajax');
add_action('wp_ajax_nopriv_feng_talk_read','feng_talk_read_ajax');

function feng_talk_input($input) {
 $content=isset($input['content'])&&is_string($input['content'])?trim(sanitize_textarea_field($input['content'])):'';
 $location=isset($input['location'])&&is_string($input['location'])?trim(sanitize_text_field($input['location'])):'';
 $raw=isset($input['tags'])&&is_string($input['tags'])?$input['tags']:'';
 $tags=array_values(array_unique(array_filter(array_map(function($t){return trim(sanitize_text_field(ltrim(trim($t),'#')));},preg_split('/[,，\n]+/u',$raw)))));
 if(mb_strlen($content)>4000 || mb_strlen($location)>60 || count($tags)>4)return new WP_Error('talk_input','正文最多 4000 字、地点最多 60 字，关键词最多 4 个。');
 foreach($tags as $tag)if(mb_strlen($tag)>20)return new WP_Error('talk_input','每个关键词最多 20 个字。');
 $images=isset($input['keep_images'])&&is_string($input['keep_images'])?json_decode($input['keep_images'],true):array();
 if(!is_array($images) || count($images)>4 || array_filter($images,function($id){return !is_int($id)||$id<=0;}))return new WP_Error('talk_images','图片记录无效，请重新选择。');
 return array('content'=>$content,'location'=>$location,'tags'=>$tags,'images'=>array_values(array_unique($images)));
}
function feng_talk_upload($file,$post_id) {
 if(in_array($file['error']??0,array(UPLOAD_ERR_INI_SIZE,UPLOAD_ERR_FORM_SIZE),true)||($file['size']??0)>feng_talk_upload_limit())return new WP_Error('talk_upload','每张图片最多 '.size_format(feng_talk_upload_limit()).'，请检查后重试。');
 if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)return new WP_Error('talk_upload','图片没有完整上传，请重新选择后重试。');
 $size=@getimagesize($file['tmp_name']);
 if(!$size || $size[0]>4096 || $size[1]>4096 || !in_array($size['mime'],array('image/jpeg','image/png','image/webp','image/gif'),true))return new WP_Error('talk_upload','请选择 JPEG、PNG、WebP 或 GIF 图片，长边不超过 4096 像素。');
 require_once ABSPATH.'wp-admin/includes/file.php';require_once ABSPATH.'wp-admin/includes/media.php';require_once ABSPATH.'wp-admin/includes/image.php';
 $upload=wp_handle_upload($file,array('test_form'=>false,'mimes'=>array('jpg|jpeg|jpe'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','gif'=>'image/gif')));
 if(isset($upload['error']))return new WP_Error('talk_upload','图片上传失败，请检查格式和服务器权限。');
 $id=wp_insert_attachment(array('post_mime_type'=>$upload['type'],'post_title'=>sanitize_file_name(pathinfo($file['name'],PATHINFO_FILENAME)),'post_status'=>'inherit','post_author'=>get_current_user_id()),$upload['file'],$post_id,true);
 if(is_wp_error($id)){wp_delete_file($upload['file']);return $id;}
 wp_update_attachment_metadata($id,wp_generate_attachment_metadata($id,$upload['file']));return $id;
}
/** Also called by local integration checks. All authorization lives here, not only in the UI. */
function feng_talk_write($input,$files=array()) {
 if(!feng_talk_can_publish())return new WP_Error('talk_auth','登录后才可以发布说说。',403);
 $op=isset($input['op'])&&is_string($input['op'])?$input['op']:'save';$id=absint($input['id']??0);$post=$id?get_post($id):null;
 if($id && !feng_talk_can_edit($post))return new WP_Error('talk_owner','只能管理自己发布的说说。',403);
 if(in_array($op,array('delete','restore'),true)) {
  if($op==='delete' && !EMPTY_TRASH_DAYS)return new WP_Error('talk_trash','站点未启用回收站，本次没有执行不可撤销的删除。',409);
  if(!$post || ($op==='delete'&&$post->post_status!=='publish') || ($op==='restore'&&$post->post_status!=='trash'))return new WP_Error('talk_missing','这条说说的状态已改变，请刷新后重试。',409);
  $result=$op==='delete'?wp_trash_post($id):wp_untrash_post($id);
  if($result && $op==='restore')$result=wp_update_post(array('ID'=>$id,'post_status'=>'publish'),true);
  return !$result||is_wp_error($result)?new WP_Error('talk_write','未能保存修改，请稍后重试。'):array('id'=>$id);
 }
 if($op!=='save')return new WP_Error('talk_op','不支持这个操作。',400);
 $data=feng_talk_input($input);if(is_wp_error($data))return $data;
 if($post && ($post->post_status!=='publish'||!isset($input['version'])||!is_string($input['version'])||!hash_equals(feng_talk_version($post),$input['version'])))return new WP_Error('talk_conflict','这条说说已在其他页面修改，请重新打开后编辑。',409);
 $old_images=$post?(array)get_post_meta($id,'_feng_talk_images',true):array();
 foreach($data['images'] as $image)if(!in_array($image,$old_images,true))return new WP_Error('talk_images','不能使用其他说说的图片记录。',403);
 if(count($files)+count($data['images'])>4)return new WP_Error('talk_images','每条说说最多 4 张图片。');
 if(array_sum(array_column($files,'size'))>feng_talk_total_limit())return new WP_Error('talk_images','本次图片总大小超过了服务器限制，请减少图片后重试。',413);
 if($data['content']===''&&!$data['images']&&!$files)return new WP_Error('talk_empty','写点什么，或者添加一张图片吧。');
 if(!$post) {
  $recent=get_posts(array('post_type'=>'talk','post_status'=>array('publish','draft','trash'),'author'=>get_current_user_id(),'date_query'=>array(array('after'=>'1 day ago')),'fields'=>'ids','numberposts'=>50));
  if(count($recent)>=50)return new WP_Error('talk_limit','今天已经记录了很多片刻，明天再继续吧。',429);
 }
 $created=!$post;$new_images=array();
 if($created){$id=wp_insert_post(array('post_type'=>'talk','post_status'=>'draft','post_author'=>get_current_user_id(),'post_title'=>'说说','comment_status'=>'closed','ping_status'=>'closed'),true);if(is_wp_error($id))return $id;}
 foreach($files as $file) {
  $image=feng_talk_upload($file,$id);
  if(is_wp_error($image)){foreach($new_images as $remove)wp_delete_attachment($remove,true);if($created)wp_delete_post($id,true);return $image;}
  $new_images[]=$image;
 }
 $result=wp_update_post(wp_slash(array('ID'=>$id,'post_content'=>$data['content'],'post_title'=>mb_substr(preg_replace('/\s+/u',' ',$data['content']),0,50)?:'图片说说','post_status'=>'publish')),true);
 if(is_wp_error($result)){foreach($new_images as $remove)wp_delete_attachment($remove,true);if($created)wp_delete_post($id,true);return $result;}
 update_post_meta($id,'_feng_talk_location',wp_slash($data['location']));
 update_post_meta($id,'_feng_talk_images',array_merge($data['images'],$new_images));
 wp_set_object_terms($id,$data['tags'],'feng_talk_tag');
 return array('item'=>feng_talk_data(get_post($id)));
}
function feng_talk_write_ajax() {
 if(!feng_talk_can_publish())wp_send_json_error(array('message'=>'登录后才可以发布说说。'),403);
 if((int)($_SERVER['CONTENT_LENGTH']??0)>feng_talk_total_limit()+65536)wp_send_json_error(array('message'=>'本次上传超过了服务器限制，请减少图片后重试。'),413);
 $origin=$_SERVER['HTTP_ORIGIN']??'';$home=wp_parse_url(home_url('/'));$expected=$home['scheme'].'://'.$home['host'].(isset($home['port'])?':'.$home['port']:'');
 if($origin && $origin!==$expected)wp_send_json_error(array('message'=>'请从本站发布。'),403);
 check_ajax_referer('feng_talk_write','nonce');
 $files=array();$upload=$_FILES['images']??array();
 if(isset($upload['name'])&&is_array($upload['name']))foreach($upload['name'] as $i=>$name){if(($upload['error'][$i]??4)===UPLOAD_ERR_NO_FILE)continue;$files[]=array('name'=>$name,'type'=>$upload['type'][$i]??'','tmp_name'=>$upload['tmp_name'][$i]??'','error'=>$upload['error'][$i]??4,'size'=>$upload['size'][$i]??0);}
 // Serialize each author's mutations, including request retries and image uploads.
 global $wpdb;$target=absint($_POST['id']??0);$lock='feng_talk_'.substr(hash('sha256',$wpdb->prefix.':'.($target?'post:'.$target:'author:'.get_current_user_id())),0,40);
 if((int)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 0)',$lock))!==1)wp_send_json_error(array('message'=>'上一条修改还在保存，请稍后重试。'),409);
 try {
  $input=wp_unslash($_POST);$request=isset($input['request_id'])&&is_string($input['request_id'])&&preg_match('/^[a-zA-Z0-9-]{12,64}$/',$input['request_id'])?$input['request_id']:'';
  $key='_feng_talk_request_'.hash('sha256',$request);$previous=$request?get_user_meta(get_current_user_id(),$key,true):null;
  if($previous)$result=$previous;
  else {$result=feng_talk_write($input,$files);if(!is_wp_error($result)&&$request){update_user_meta(get_current_user_id(),$key,$result);$keys=(array)get_user_meta(get_current_user_id(),'_feng_talk_requests',true);$keys[]=$key;while(count($keys)>12)delete_user_meta(get_current_user_id(),array_shift($keys));update_user_meta(get_current_user_id(),'_feng_talk_requests',$keys);}}
 }catch(Throwable $error){$result=new WP_Error('talk_write','保存遇到了问题，请稍后重试。',500);}
 finally{$wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)',$lock));}
 if(is_wp_error($result))wp_send_json_error(array('message'=>$result->get_error_message()),is_int($result->get_error_data())?$result->get_error_data():400);
 wp_send_json_success($result);
}
add_action('wp_ajax_feng_talk_write','feng_talk_write_ajax');
add_action('wp_ajax_nopriv_feng_talk_write','feng_talk_write_ajax');
