<?php
if(!defined('ABSPATH'))exit;

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

add_action('admin_init',function(){
 add_option('feng_ai_settings',array(),'',false);
 register_setting('feng_ai_group','feng_ai_settings',array('type'=>'array','sanitize_callback'=>'feng_ai_sanitize_settings','default'=>array(),'show_in_rest'=>false));
});
function feng_settings_tabs($ai=false) {
 $icons=array('analytics'=>'chart-area','appearance'=>'admin-appearance','home'=>'admin-home','music'=>'format-audio','subscriptions'=>'rss','pet'=>'pets','profile'=>'admin-users','footer'=>'admin-links');
 echo '<nav class="nav-tab-wrapper feng-admin-tabs" aria-label="设置分区"><span class="feng-nav-label">配置与管理</span>';
 foreach(feng_settings_schema() as $id=>$group) {
  $url=($ai?admin_url('themes.php?page=feng-settings'):'').'#feng-'.$id;
  echo '<a class="nav-tab" href="'.esc_url($url).'"><span class="dashicons dashicons-'.esc_attr($icons[$id]??'admin-generic').'" aria-hidden="true"></span>'.esc_html($group['title']).'</a>';
 }
 echo '<a class="nav-tab'.($ai===true?' nav-tab-active':'').'" '.($ai===true?'aria-current="page"':'').' href="'.esc_url(admin_url('themes.php?page=feng-settings&tab=ai')).'"><span class="dashicons dashicons-superhero-alt" aria-hidden="true"></span>AI 助手</a>';
 echo '<a class="nav-tab'.($ai==='map'?' nav-tab-active':'').'" '.($ai==='map'?'aria-current="page"':'').' href="'.esc_url(admin_url('themes.php?page=feng-settings&tab=map')).'"><span class="dashicons dashicons-location-alt" aria-hidden="true"></span>地图服务</a>';
 echo '<a class="nav-tab'.($ai==='mail'?' nav-tab-active':'').'" '.($ai==='mail'?'aria-current="page"':'').' href="'.esc_url(admin_url('themes.php?page=feng-settings&tab=mail')).'"><span class="dashicons dashicons-email-alt" aria-hidden="true"></span>邮件发送</a></nav>';
}
function feng_ai_settings_screen() {
 ?>
 <div class="wrap feng-admin"><?php feng_settings_header(); settings_errors(); ?><div class="feng-admin-shell"><?php feng_settings_tabs(true); ?><div class="feng-admin-content">
 <header class="feng-section-header feng-ai-heading"><h2>AI 助手</h2><p>分别配置文字与封面服务。支持自定义模型和接口地址。</p></header>
 <form class="feng-settings-form" method="post" action="options.php" id="feng-ai-settings-form"><?php settings_fields('feng_ai_group'); ?>
 <?php foreach(array('text'=>'文字生成','image'=>'封面生成') as $kind=>$heading): $config=feng_ai_config($kind); ?>
 <section class="feng-ai-config feng-settings-section" data-kind="<?php echo esc_attr($kind); ?>"><header class="feng-section-header"><h2><?php echo esc_html($heading); ?></h2><p><?php echo $kind==='text'?'用于文章摘要、关键词提取和宠物问答。':'用于生成文章封面，可选择不同于文字服务的供应商。'; ?></p></header><table class="form-table" role="presentation"><colgroup><col class="feng-label-column"><col></colgroup>
 <tr><th><label for="feng-ai-<?php echo esc_attr($kind); ?>-provider">服务预设</label></th><td><select id="feng-ai-<?php echo esc_attr($kind); ?>-provider" name="feng_ai_settings[<?php echo esc_attr($kind); ?>][provider]" data-ai-field="provider">
 <?php foreach(feng_ai_presets()[$kind] as $id=>$preset) { ?><option value="<?php echo esc_attr($id); ?>" <?php selected($config['provider'],$id); ?>><?php echo esc_html($preset['label']); ?></option><?php } ?></select><p class="description">选择预设会填写模型、地址和协议；仍可手动修改。切换服务或地址后需重新填写密钥。</p></td></tr>
 <tr><th><label for="feng-ai-<?php echo esc_attr($kind); ?>-protocol">接口协议</label></th><td><select id="feng-ai-<?php echo esc_attr($kind); ?>-protocol" name="feng_ai_settings[<?php echo esc_attr($kind); ?>][protocol]" data-ai-field="protocol">
 <?php foreach(($kind==='text'?array('chat'=>'OpenAI 兼容 · Chat Completions','responses'=>'OpenAI · Responses'):array('images'=>'OpenAI 兼容 · Images','dashscope'=>'百炼 · 同步图像生成')) as $id=>$label) { ?><option value="<?php echo esc_attr($id); ?>" <?php selected($config['protocol'],$id); ?>><?php echo esc_html($label); ?></option><?php } ?></select></td></tr>
 <?php foreach(array('endpoint'=>'Endpoint / API 地址','model'=>'模型名称','key'=>'API Key') as $field=>$label): $id='feng-ai-'.$kind.'-'.$field; ?>
 <tr><th><label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label></th><td><input class="regular-text" id="<?php echo esc_attr($id); ?>" name="feng_ai_settings[<?php echo esc_attr($kind); ?>][<?php echo esc_attr($field); ?>]" type="<?php echo $field==='key'?'password':($field==='endpoint'?'url':'text'); ?>" data-ai-field="<?php echo esc_attr($field); ?>" value="<?php echo $field==='key'?'':esc_attr($config[$field]); ?>" <?php if($field==='key') { ?>autocomplete="new-password" placeholder="<?php echo $config['key']!==''?'已保存；留空保持不变':'填写服务商 API Key'; ?>"<?php } elseif($field==='model') { ?>list="feng-ai-<?php echo esc_attr($kind); ?>-models"<?php } ?>>
 <?php if($field==='key') { ?><p class="description"><?php echo $config['key']!==''?'密钥已保存，不在页面回显。':'尚未配置密钥。'; ?></p><label><input type="checkbox" name="feng_ai_settings[<?php echo esc_attr($kind); ?>][clear_key]" value="1">删除已保存的密钥</label><?php }
 elseif($field==='endpoint') { ?><p class="description">支持 HTTPS 基础地址或完整接口地址。千问预设为北京地域；也可填写自己的百炼业务空间域名或其他地域地址。</p><?php }
 else { ?><datalist id="feng-ai-<?php echo esc_attr($kind); ?>-models"><?php foreach(feng_ai_presets()[$kind] as $preset) foreach($preset['models'] as $model) echo '<option value="'.esc_attr($model).'"></option>'; ?></datalist><p class="description">可直接输入模型 ID，是否可用取决于当前账号及地域。</p><?php } ?></td></tr>
 <?php endforeach; ?></table>
 <p><button type="button" class="button" data-ai-test="<?php echo esc_attr($kind); ?>"><?php echo $kind==='text'?'测试文字连接':'测试封面生成'; ?></button> <span class="spinner"></span></p>
 <p class="description"><?php echo $kind==='text'?'测试已保存的配置，发送一条简短请求。':'测试已保存的配置，实际生成 1 张图片并保存到媒体库，会产生服务商用量。'; ?></p><div class="feng-ai-status" role="status" aria-live="polite"></div>
 </section><?php endforeach; feng_settings_save_bar(true); ?></form>
 <p>保存后，在「文章 → 编辑文章」的 <strong>山映 AI 助手</strong> 中使用。点击生成时，标题和正文会发送给你配置的服务商；文章不会自动发布。</p>
 <p class="description">模型预设核对于 2026-09-06。官方文档：<a href="https://api-docs.deepseek.com/" target="_blank" rel="noopener noreferrer">DeepSeek</a> · <a href="https://developers.openai.com/api/docs/models" target="_blank" rel="noopener noreferrer">OpenAI</a> · <a href="https://help.aliyun.com/zh/model-studio/getting-started/models" target="_blank" rel="noopener noreferrer">千问</a>。DeepSeek 用于文字生成，封面请另外配置图片服务。</p></div></div></div>
 <?php
}
add_action('add_meta_boxes_post',function($post){
 if(current_user_can('edit_post',$post->ID)) add_meta_box('feng-ai-editor','山映 AI 助手','feng_ai_editor_screen','post','side','default',array('__block_editor_compatible_meta_box'=>true));
});
function feng_ai_editor_screen($post) {
 ?>
 <div id="feng-ai-editor-tools" data-post-id="<?php echo (int)$post->ID; ?>">
 <?php wp_nonce_field('feng_summary','feng_summary_nonce'); ?>
 <input type="hidden" id="feng-ai-summary-saved" name="feng_ai_summary_saved" value="<?php echo esc_attr(get_post_meta($post->ID,'_feng_ai_summary',true)); ?>">
 <p>根据当前标题和正文生成。摘要和关键词生成后自动填入，可继续调整；封面生成后自动设为特色图片。<?php if(current_user_can('manage_options')) { ?><a href="<?php echo esc_url(admin_url('themes.php?page=feng-settings&tab=ai')); ?>" target="_blank" rel="noopener">配置 AI 服务 <span class="dashicons dashicons-external" aria-hidden="true"></span></a><?php } ?></p>
 <div class="feng-ai-actions"><button type="button" class="button" data-ai-generate="summary">一键生成摘要</button> <button type="button" class="button" data-ai-generate="keywords">一键提取关键词</button></div>
 <p class="description">文章摘要会显示在正文开头。AI 生成并应用的摘要会标注来源；手写摘要显示为「内容提要」。</p>
 <div class="feng-ai-result" data-ai-result="summary" hidden><p><label for="feng-ai-summary"><strong>摘要预览</strong></label></p><textarea id="feng-ai-summary" class="widefat" rows="4"></textarea><p><button type="button" class="button button-primary" data-ai-apply="summary">应用到文章摘要</button></p></div>
 <div class="feng-ai-result" data-ai-result="keywords" hidden><p><label for="feng-ai-keywords"><strong>关键词预览</strong></label></p><input id="feng-ai-keywords" class="widefat" type="text"><p class="description">用逗号分隔，可编辑后添加为标签，已有标签保留。</p><p><button type="button" class="button button-primary" data-ai-apply="keywords">添加到文章标签</button></p></div>
 <?php if(current_user_can('upload_files')) { ?><hr><p><label for="feng-ai-style"><strong>封面风格</strong></label> <select id="feng-ai-style"><?php foreach(feng_ai_styles() as $id=>$style) echo '<option value="'.esc_attr($id).'">'.esc_html($style[0]).'</option>'; ?></select></p>
 <p><label for="feng-ai-prompt">补充画面要求（可选）</label></p><textarea id="feng-ai-prompt" class="widefat" rows="2" placeholder="例如：清晨的山谷，偏绿色，留出标题空间"></textarea>
 <p><button type="button" class="button" data-ai-generate="cover">一键生成封面</button></p><p class="description">生成 1 张横版封面，自动压缩为 WebP（最长边 1920px，质量 82），保存到媒体库并设为特色图片。</p>
 <div class="feng-ai-result" data-ai-result="cover" hidden><img alt="生成的封面预览"><p><a data-ai-media-link target="_blank" rel="noopener">在媒体库查看</a></p></div><?php } ?>
 <div class="feng-ai-status" role="status" aria-live="polite"></div><span class="spinner"></span>
 </div>
 <?php
}
add_filter('get_user_option_meta-box-order_post',function($order){
 if(!is_array($order))return $order;
 $ids=array('feng-ai-editor','feng-travel');
 foreach($order as $context=>$list)$order[$context]=implode(',',array_diff(explode(',',(string)$list),$ids));
 $order['side']=implode(',',array_filter(array_merge(explode(',',$order['side']??''),$ids)));
 return $order;
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
 $c=feng_mail_config();echo '<div class="wrap feng-admin">';feng_settings_header();if($notice)echo '<div class="notice notice-'.($error?'error':'success').'"><p>'.esc_html($notice).'</p></div>';echo '<div class="feng-admin-shell">';feng_settings_tabs('mail');echo '<div class="feng-admin-content"><section class="feng-settings-section"><header class="feng-section-header"><h2>邮件发送 · SMTP</h2><p>用于 WordPress 的通知、找回密码和其他 wp_mail 邮件。请填写邮件服务商提供的配置。</p></header><form class="feng-settings-form" method="post">';wp_nonce_field('feng_mail_settings');
 echo '<table class="form-table"><tr><th>启用 SMTP</th><td><label><input type="checkbox" name="feng_mail[enabled]" value="1" '.checked($c['enabled'],true,false).'>启用</label></td></tr>';
 foreach(array('host'=>'SMTP 服务器','port'=>'端口','username'=>'账号','from_email'=>'发件邮箱','from_name'=>'发件人名称') as $key=>$label){echo '<tr><th><label for="mail-'.$key.'">'.$label.'</label></th><td><input class="regular-text" id="mail-'.$key.'" name="feng_mail['.$key.']" type="'.($key==='port'?'number':($key==='from_email'?'email':'text')).'" value="'.esc_attr($c[$key]).'" '.($key==='port'?'min="1" max="65535"':'').'></td></tr>';}
 echo '<tr><th>连接加密</th><td><select name="feng_mail[encryption]">';foreach(array('tls'=>'STARTTLS（通常 587）','ssl'=>'SSL / TLS（通常 465）',''=>'无加密（仅用于可信内网）') as $key=>$label)echo '<option value="'.esc_attr($key).'" '.selected($c['encryption'],$key,false).'>'.$label.'</option>';echo '</select></td></tr><tr><th>SMTP 认证</th><td><label><input type="checkbox" name="feng_mail[auth]" value="1" '.checked($c['auth'],true,false).'>使用账号密码认证</label></td></tr><tr><th><label for="mail-password">密码 / 授权码</label></th><td><input id="mail-password" class="regular-text" type="password" autocomplete="new-password" name="feng_mail[password]" value="" placeholder="'.($c['password']?'已保存，留空保留':'尚未配置').'"><p><label><input type="checkbox" name="feng_mail[clear_password]" value="1">清除已保存密码</label></p><p class="description">密码加密保存，不在页面回显。邮箱要求授权码时请勿填写登录密码。更换服务器或账号后需重新填写。</p></td></tr></table>';submit_button('保存邮件设置');echo '</form></section><section class="feng-settings-section"><h2>发送测试邮件</h2><p>使用已保存的配置，仅在点击下方按钮后发送。</p><form method="post">';wp_nonce_field('feng_mail_settings');echo '<label for="mail-test-email">收件邮箱</label> <input id="mail-test-email" class="regular-text" name="test_email" type="email" required>';submit_button('发送测试邮件','secondary','mail_test');echo '</form></section></div></div></div>';
}

function feng_database_cleanup_form(){
 if(!current_user_can('manage_options'))return;
 echo '<details class="feng-settings-section"><summary>数据库维护</summary><p>仅清理已过期的 WordPress 临时缓存，不删除文章、评论、友情链接或修订版本。</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';wp_nonce_field('feng_expired_cache');echo '<input type="hidden" name="action" value="feng_expired_cache">';submit_button('清理过期缓存','secondary');echo '</form></details>';
}
add_action('admin_post_feng_expired_cache',function(){if(!current_user_can('manage_options'))wp_die('没有权限');check_admin_referer('feng_expired_cache');delete_expired_transients(true);wp_safe_redirect(admin_url('themes.php?page=feng-settings&cache_cleaned=1'));exit;});
add_action('admin_notices',function(){if(current_user_can('manage_options')&&($_GET['page']??'')==='feng-settings'&&isset($_GET['cache_cleaned']))echo '<div class="notice notice-success"><p>过期缓存已清理。</p></div>';});

function feng_pet_settings_preview() {
 $s=feng_pet_snapshot();echo '<div class="feng-pet-admin-gallery" aria-label="宠物外观预览">';
 foreach(feng_pet_catalog() as $kind=>$pet)echo '<figure>'.feng_pet_art($kind).'<figcaption>'.esc_html($pet['label'].' · '.$pet['name']).'</figcaption></figure>';
 echo '</div><p>小鸟保留已有成长档案。修改名字不会清零；关闭功能会保留已有数据。有效阅读 +1、摸摸 +1、投喂 +3、新审核评论 +8。</p><p>当前：<strong>'.esc_html($s['identity']['name'].' · '.$s['stage'].' · '.$s['xp'].' 成长值').'</strong>。已陪伴 '.(int)$s['age'].' 天。</p><p>AI 使用「AI 助手」中保存的文字模型。提问时发送宠物公开统计、最近七天日记和当前访客最近三轮对话；本站不保存对话。未配置或达到额度时，自动使用日常问答。每位访客每天最多 3 次 AI 请求；失败尝试也计入全站额度。昼夜按 WordPress「设置 → 常规」中的站点时区计算。</p>';
}

add_action('add_meta_boxes_post',function(){add_meta_box('feng-travel','旅行足迹','feng_travel_box','post','side','default');});
function feng_travel_box($post){
 wp_nonce_field('feng_travel_save','feng_travel_nonce');
 $flag=get_post_meta($post->ID,'_feng_footprint',true);$v=feng_travel_location($post->ID);
 echo '<p><label><input type="checkbox" name="feng_footprint" value="1" '.checked($flag==='1'||($flag===''&&(has_category('travel',$post)||isset($v['lat'],$v['lng'])||!empty($v['place_id']))),true,false).'> 收录到足迹页面</label></p><p class="description">保存文章后，在「文章 → 足迹」配置地点和旅行日期。</p>';
}
function feng_travel_details_box($post){$v=feng_travel_location($post->ID);wp_nonce_field('feng_travel_save','feng_travel_nonce'); ?>
<div data-travel-editor data-post="<?php echo (int)$post->ID; ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('feng_travel_search')); ?>" data-endpoint="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
<p><label><input type="checkbox" name="feng_footprint" value="1" <?php $flag=get_post_meta($post->ID,'_feng_footprint',true);checked($flag==='1'||($flag===''&&(has_category('travel',$post)||isset($v['lat'],$v['lng'])||!empty($v['place_id'])))); ?>> 收录到足迹页面</label></p><p>任何分类的文章都可以收录。手动坐标请指定坐标系；搜索结果会自动标记来源。切换地图服务后，原服务专属搜索结果需要重新选点。</p>
<p><label for="feng-place-query">搜索城市或地址</label> <input id="feng-place-query" type="search" data-place-query placeholder="例如：曼谷，泰国"> <button class="button" type="button" data-place-search>搜索地点</button></p><p data-place-status role="status"></p><div data-place-results></div>
<?php foreach(array('city'=>'城市 / 地点','country'=>'国家 / 地区名称','code'=>'国家代码（两位，例如 cn、th、mv）','lat'=>'纬度（-90～90）','lng'=>'经度（-180～180）','note'=>'足迹备注') as $key=>$label): ?><p><label for="feng-travel-<?php echo $key; ?>"><?php echo esc_html($label); ?></label><br><input class="widefat" id="feng-travel-<?php echo $key; ?>" name="feng_travel[<?php echo $key; ?>]" value="<?php echo esc_attr($v[$key]??''); ?>" <?php if(in_array($key,array('lat','lng'),true))echo 'type="number" step="any" min="-'.($key==='lat'?90:180).'" max="'.($key==='lat'?90:180).'"';else echo 'type="text"'; ?>></p><?php endforeach; ?>
<p><label for="feng-travel-date-start">旅行开始日期</label><br><input type="date" id="feng-travel-date-start" name="feng_travel[date_start]" value="<?php echo esc_attr($v['date_start']??''); ?>"> <label for="feng-travel-date-end">结束日期（可选）</label> <input type="date" id="feng-travel-date-end" name="feng_travel[date_end]" value="<?php echo esc_attr($v['date_end']??''); ?>"></p><p class="description">填写实际旅行日期，与文章发布日期无关。单日旅行只填开始日期；结束日期不能早于开始日期，留空不显示。</p>
<input type="hidden" name="feng_travel[source]" value="<?php echo esc_attr($v['source']??'manual'); ?>"><input type="hidden" name="feng_travel[place_id]" value="<?php echo esc_attr($v['place_id']??''); ?>"><p><label>坐标系 <select name="feng_travel[crs]"><option value="wgs84" <?php selected($v['crs']??'wgs84','wgs84'); ?>>WGS84 / GPS</option><option value="gcj02" <?php selected($v['crs']??'','gcj02'); ?>>GCJ-02 / 高德</option></select></label></p><p>搜索后点击候选地点填入字段，再保存文章。填写地点名称即可显示国旗地点；地图标记需要有效经纬度，或通过 Google 搜索选择的地点 ID。</p></div>
<?php }
function feng_travel_save_post($id){if(wp_is_post_revision($id)||wp_is_post_autosave($id)||!current_user_can('edit_post',$id)||empty($_POST['feng_travel_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['feng_travel_nonce'])),'feng_travel_save'))return;$v=isset($_POST['feng_travel'])&&is_array($_POST['feng_travel'])?wp_unslash($_POST['feng_travel']):array();foreach($v as $x)if(!is_scalar($x))return;$clean=feng_travel_clean($v);if($clean['source']==='google'&&$clean['place_id']){$clean['lat']=null;$clean['lng']=null;}if(isset($_POST['feng_travel']))update_post_meta($id,'_feng_travel',$clean);update_post_meta($id,'_feng_footprint',isset($_POST['feng_footprint'])?'1':'0');}
add_action('save_post_post','feng_travel_save_post');
add_action('admin_menu',function(){add_submenu_page('edit.php','旅行足迹','足迹','manage_options','feng-travel','feng_travel_admin');});
function feng_map_settings_screen(){
 if(!current_user_can('manage_options'))return;
 if(isset($_POST['feng_travel_config_nonce'])&&check_admin_referer('feng_travel_config','feng_travel_config_nonce')){
  $old=feng_map_config();$input=isset($_POST['feng_map'])&&is_array($_POST['feng_map'])?wp_unslash($_POST['feng_map']):array();$next=$old;
  $next['provider']=in_array($input['provider']??'',array('mapbox','google','amap'),true)?$input['provider']:'mapbox';
  foreach(feng_map_fields() as $key=>$label){$value=is_string($input[$key]??null)?trim(sanitize_text_field($input[$key])):'';if(!empty($_POST['clear_'.$key]))$next[$key]='';elseif($value!=='')$next[$key]=$value;}
  $next['globe_background']=in_array($input['globe_background']??'',array('system','light','dark'),true)?$input['globe_background']:'system';
  $next['amap_proxy']=esc_url_raw(is_string($input['amap_proxy']??null)?$input['amap_proxy']:'',array('https'));
  if($next['mapbox_token']!==''&&!preg_match('/^pk\.[A-Za-z0-9._-]+$/D',$next['mapbox_token']))echo '<div class="notice notice-error"><p>Mapbox 请使用 pk. 开头的公开 Token。</p></div>';
  else {update_option('feng_map_settings',$next,false);echo '<div class="notice notice-success"><p>地图配置已保存。</p></div>';}
 }
 $cfg=feng_map_config();echo '<div class="wrap feng-admin">';feng_settings_header();echo '<div class="feng-admin-shell">';feng_settings_tabs('map');echo '<div class="feng-admin-content"><section class="feng-settings-section"><header class="feng-section-header"><h2>地图服务</h2><p>主题不提供共享 Key。每位站长在服务商申请后填写自己的凭据，只有选中的服务会被加载。</p></header><form class="feng-settings-form" method="post">';wp_nonce_field('feng_travel_config','feng_travel_config_nonce');echo '<table class="form-table"><tr><th>地图服务</th><td><select name="feng_map[provider]">';foreach(array('mapbox'=>'Mapbox（默认，全球地球视图）','google'=>'Google Maps','amap'=>'高德地图（国内）') as $key=>$label)echo '<option value="'.$key.'" '.selected($cfg['provider'],$key,false).'>'.esc_html($label).'</option>';echo '</select></td></tr>';
 echo '<tr><th>地球背景</th><td><select name="feng_map[globe_background]">';foreach(array('system'=>'跟随站点配色','light'=>'浅色','dark'=>'深色星空') as $value=>$label)echo '<option value="'.esc_attr($value).'" '.selected($cfg['globe_background'],$value,false).'>'.esc_html($label).'</option>';echo '</select><p class="description">足迹页地球视图的默认背景，与地图风格独立。</p></td></tr>';
 foreach(feng_map_fields() as $key=>$label)echo '<tr><th><label for="map-'.$key.'">'.esc_html($label).'</label></th><td><input type="password" autocomplete="new-password" class="regular-text" id="map-'.$key.'" name="feng_map['.$key.']" value="" placeholder="'.($cfg[$key]?'已保存，留空保留':'尚未配置').'"><label> <input type="checkbox" name="clear_'.$key.'" value="1">清除</label></td></tr>';
 echo '</table><p>Mapbox：公开 Token 用于地图与 Permanent Geocoding，需开启相应计费权限。</p><p>Google：浏览器 Key 开启 Maps JavaScript API 和 Geocoding API；服务器 Key 开启 Geocoding API。Google 搜索结果只保存 Place ID，坐标在 Google 地图展示时解析，不长期存储。</p><p>高德：分别填写 Web 端 JS Key、安全密钥及 Web 服务 Key。JS 凭据用于浏览器地图，Web 服务 Key 只在服务器搜索时使用。建议按服务商要求限制域名/IP。生产环境也可填写安全代理地址，使用 security.serviceHost 隐藏安全密钥。</p><p><label>高德安全代理地址（可选） <input class="regular-text" type="url" name="feng_map[amap_proxy]" placeholder="https://your-site.example/_AMapService" value="'.esc_attr($cfg['amap_proxy']).'"></label></p>';
 submit_button('保存地图设置');echo '</form>';
 echo '</section></div></div></div>';
}
function feng_travel_admin(){
 if(!current_user_can('manage_options'))return;
 $edit_id=absint($_GET['footprint_post']??0);
 if($edit_id&&get_post_type($edit_id)==='post'&&current_user_can('edit_post',$edit_id)){
  if(isset($_POST['feng_travel_nonce'])){check_admin_referer('feng_travel_save','feng_travel_nonce');feng_travel_save_post($edit_id);echo '<div class="notice notice-success"><p>足迹资料已保存。</p></div>';}
  echo '<div class="wrap"><h1>配置旅行足迹</h1><h2>'.esc_html(get_the_title($edit_id)).'</h2><form method="post">';feng_travel_details_box(get_post($edit_id));submit_button('保存足迹');echo '</form></div>';return;
 }
 $term=get_page_by_path('footprint');echo '<div class="wrap"><h1>旅行足迹</h1>';
 {echo '<p><a class="button" href="'.esc_url($term?get_permalink($term):home_url('/footprint/')).'">查看旅行页面</a> <a class="button" href="'.esc_url(admin_url('edit.php')).'">管理旅行文章</a></p><table class="widefat striped"><thead><tr><th>文章</th><th>地点</th><th>地图坐标</th></tr></thead><tbody>';foreach(feng_footprint_posts(array('publish','draft','pending','private')) as $p){$v=feng_travel_location($p->ID);echo '<tr><td><a href="'.esc_url(admin_url('edit.php?page=feng-travel&footprint_post='.$p->ID)).'">'.esc_html($p->post_title).'</a></td><td>'.esc_html(trim(($v['city']??'').' '.($v['country']??''))).'</td><td>'.esc_html(isset($v['lat'],$v['lng'])?$v['lat'].', '.$v['lng']:'待填写').'</td></tr>';}echo '</tbody></table>'; }echo '</div>';
}
add_action('wp_ajax_feng_travel_search',function(){
 check_ajax_referer('feng_travel_search','nonce');$id=absint($_POST['post']??0);if(!$id||!current_user_can('edit_post',$id))wp_send_json_error(array('message'=>'没有编辑权限。'),403);
 $cfg=feng_map_config();
 $q=sanitize_text_field(wp_unslash($_POST['q']??''));if(mb_strlen($q)<2||mb_strlen($q)>150)wp_send_json_error(array('message'=>'请输入 2～150 字的地点。'),400);
 $key='feng_geo_limit_'.get_current_user_id();if(get_transient($key))wp_send_json_error(array('message'=>'请稍等几秒再搜索。'),429);set_transient($key,1,3);
 $result=feng_map_search($q,$cfg);if(is_wp_error($result))wp_send_json_error(array('message'=>$result->get_error_message()),400);wp_send_json_success($result);
});

add_action('add_meta_boxes_link',function(){add_meta_box('feng-friend-avatar','邮箱头像','feng_friend_avatar_box','link','side');});
function feng_friend_avatar_box($link){
 wp_nonce_field('feng_friend_avatar','feng_friend_avatar_nonce');$all=get_option('feng_friend_avatars',array());$v=$all[(int)($link->link_id??0)]??array();
 echo '<p><label for="feng-friend-email">友链站长邮箱</label></p><input type="email" class="widefat" id="feng-friend-email" name="feng_friend_email" autocomplete="off" value=""><p class="description">填写后仅保存 Gravatar 哈希，不额外保存邮箱原文。留空保留设置；未手动指定时，自动匹配已审核评论的网站域名。</p>';
 if(!empty($v['manual']))echo '<p>已设置手动邮箱头像。</p><label><input type="checkbox" name="feng_friend_email_clear" value="1"> 清除手动设置，恢复自动匹配</label>';
 elseif(!empty($v['hash']))echo '<p>已从审核通过的评论匹配头像。</p>';
 else echo '<p>尚未匹配邮箱，将显示网站徽标。</p>';
}
function feng_friend_avatar_save($id){
 if(!current_user_can('manage_links')||empty($_POST['feng_friend_avatar_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['feng_friend_avatar_nonce'])),'feng_friend_avatar'))return;
 $all=get_option('feng_friend_avatars',array());if(!isset($all[$id]))return;
 if(!empty($_POST['feng_friend_email_clear']))$all[$id]['manual']='';
 $email=isset($_POST['feng_friend_email'])&&is_string($_POST['feng_friend_email'])?trim(wp_unslash($_POST['feng_friend_email'])):'';
 if(is_email($email))$all[$id]['manual']=md5(strtolower($email));update_option('feng_friend_avatars',$all,false);
}
add_action('add_link','feng_friend_avatar_save',60);add_action('edit_link','feng_friend_avatar_save',60);

function feng_feed_settings_status() {
 $data=feng_feed_data(); $cycle=$data['cycle']; $next=wp_next_scheduled('feng_feed_refresh'); ?>
 <p>在<a href="<?php echo esc_url(admin_url('link-manager.php')); ?>">友情链接</a>中编辑站点，展开「高级」，填写原生「RSS 地址」。支持 RSS / Atom，仅同步公开可见的友链。</p>
 <p>菜单角标按 WordPress 站点时区（<?php echo esc_html(wp_timezone_string()); ?>）统计今日零点后发布、已经同步的文章。首次导入的旧文章不计入今日更新。</p>
 <p>已添加 <?php echo count($data['sources']); ?> 个订阅源 · 今日 <?php echo (int)$data['today']; ?> 篇 · 下次计划：<?php echo $next?esc_html(wp_date('m-d H:i',$next)):'尚未安排'; ?></p>
 <p><button type="button" class="button button-secondary" data-feed-refresh data-endpoint="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('feng_feed_refresh')); ?>">立即同步订阅</button> <span data-feed-admin-status role="status"><?php echo !empty($cycle['pending'])?'正在同步，剩余 '.count($cycle['pending']).' 个订阅源。':(!empty($cycle['finished'])?esc_html('上次同步：'.wp_date('m-d H:i',$cycle['finished']).' · 新收录 '.$cycle['added'].' 篇 · 失败 '.$cycle['errors'].' 个源'):'等待首次同步。'); ?></span></p>
 <p class="description">每个友链保留最近 200 篇已获取记录，不下载全文或附件。源站只提供最近几篇时，首次同步也只能获取这些文章。同步失败保留旧记录。定时任务由 WordPress WP-Cron 执行，低访问量站点可能延后；需要准时执行时，可由服务器定时调用 wp-cron.php。</p>
 <?php if(defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) echo '<p class="description">当前环境关闭了 WP-Cron 自动触发。请确认服务器已配置定时调用；手动同步仍可正常使用。</p>'; ?>
 <?php foreach($data['sources'] as $source) if($source['error']) echo '<p class="description">'.esc_html($source['name'].'：'.$source['error']).'</p>'; ?>
 <p><a href="<?php echo esc_url(feng_page_url('subscriptions')); ?>">查看订阅页面</a> · <a href="<?php echo esc_url(admin_url('nav-menus.php')); ?>">把「订阅」添加到自定义菜单</a></p>
 <?php
}
function feng_feed_admin_refresh() {
 if ($_SERVER['REQUEST_METHOD']!=='POST' || !current_user_can('manage_options')) wp_send_json_error(array('message'=>'没有同步权限。'),403);
 check_ajax_referer('feng_feed_refresh','nonce');
 $start=isset($_POST['start']) && $_POST['start']==='1';
 if ($start && get_transient('feng_feed_manual_cooldown')) wp_send_json_error(array('message'=>'刚刚同步过，请一分钟后再试。'),429);
 if ($start) set_transient('feng_feed_manual_cooldown',1,60);
 $cycle=feng_feed_run($start);
 wp_send_json_success(array('pending'=>count($cycle['pending']??array()),'done'=>$cycle['done']??0,'total'=>$cycle['total']??0,'added'=>$cycle['added']??0,'errors'=>$cycle['errors']??0));
}
add_action('wp_ajax_feng_feed_refresh','feng_feed_admin_refresh');

function feng_friend_category_icon_field($term=null){
 $code=$term instanceof WP_Term?get_term_meta($term->term_id,'feng_friend_category_icon',true):'';
 wp_nonce_field('feng_friend_category_icon','feng_friend_category_icon_nonce');
 echo '<label for="feng-friend-category-icon">Font Awesome 类名 / SVG 代码</label><textarea id="feng-friend-category-icon" name="feng_friend_category_icon" class="large-text code" rows="4" placeholder="fa-solid fa-code">'.esc_textarea($code).'</textarea><p class="description">可填写 Font Awesome 类名、完整 i 标签或 SVG 代码。留空使用文件夹图标。</p><div class="feng-admin-icon-preview">'.feng_badge_icon_markup($code).'</div>';
}
add_action('link_category_add_form_fields',function(){echo '<div class="form-field">';feng_friend_category_icon_field();echo '</div>';});
add_action('link_category_edit_form_fields',function($term){echo '<tr class="form-field"><th scope="row">分类图标</th><td>';feng_friend_category_icon_field($term);echo '</td></tr>';});
function feng_friend_category_icon_save($id){
 $taxonomy=get_taxonomy('link_category');
 if(!$taxonomy||!current_user_can($taxonomy->cap->edit_terms))return;
 $nonce=$_POST['feng_friend_category_icon_nonce']??'';
 if(!is_string($nonce)||!wp_verify_nonce(wp_unslash($nonce),'feng_friend_category_icon'))return;
 if(!isset($_POST['feng_friend_category_icon'])||!is_string($_POST['feng_friend_category_icon']))return;
 $code=feng_badge_icon_code(wp_unslash($_POST['feng_friend_category_icon']));
 if($code)update_term_meta($id,'feng_friend_category_icon',$code);else delete_term_meta($id,'feng_friend_category_icon');
}
add_action('created_link_category','feng_friend_category_icon_save');
add_action('edited_link_category','feng_friend_category_icon_save');
add_filter('manage_edit-link_category_columns',function($columns){$columns['feng_friend_icon']='图标';return $columns;});
add_filter('manage_link_category_custom_column',function($content,$column,$id){return $column==='feng_friend_icon'?feng_friend_category_icon($id):$content;},10,3);

add_action('add_meta_boxes_link',function(){add_meta_box('feng-friend-cover','友链封面图',function($link){
 wp_nonce_field('feng_friend_cover','feng_friend_cover_nonce');
 echo '<p><label for="feng-friend-cover-url">独立封面地址（选填）</label></p><input class="widefat" type="url" id="feng-friend-cover-url" name="feng_friend_cover" value="'.esc_attr(get_option('feng_friend_cover_'.(int)($link->link_id??0),'')).'"><p class="description">使用横向图片网址。有封面时展示封面；留空显示完整头像与模糊背景。</p>';
},'link','side');});
function feng_friend_cover_save($id){
 if(!current_user_can('manage_links')||!isset($_POST['feng_friend_cover_nonce'],$_POST['feng_friend_cover'])||!is_string($_POST['feng_friend_cover_nonce'])||!is_string($_POST['feng_friend_cover'])||!wp_verify_nonce(wp_unslash($_POST['feng_friend_cover_nonce']),'feng_friend_cover'))return;
 $url=esc_url_raw(wp_unslash($_POST['feng_friend_cover']),array('http','https'));
 if($url)update_option('feng_friend_cover_'.(int)$id,$url,false);else delete_option('feng_friend_cover_'.(int)$id);
}
add_action('add_link','feng_friend_cover_save');add_action('edit_link','feng_friend_cover_save');
add_action('deleted_link',function($id){delete_option('feng_friend_cover_'.(int)$id);});

function feng_category_badge_field($term=null){
 $id=$term instanceof WP_Term?(int)get_term_meta($term->term_id,'feng_category_badge',true):0;
 $code=$term instanceof WP_Term?get_term_meta($term->term_id,'feng_category_icon',true):'';
 wp_nonce_field('feng_category_badge','feng_category_badge_nonce');
 $cover=$term instanceof WP_Term?get_term_meta($term->term_id,'feng_category_cover',true):'';
 echo '<div class="feng-category-cover-field"><p><label for="feng-category-cover">分类固定封面（首页与控制面板）</label></p><div class="feng-admin-inline-fields"><input type="url" class="large-text" id="feng-category-cover" name="feng_category_cover" value="'.esc_attr($cover).'" placeholder="图片 URL"><button type="button" class="button" data-category-cover-choose>选择或上传图片</button><button type="button" class="button" data-category-cover-clear>清除</button></div><p class="description">建议 800 × 400px，图片以居中裁切铺满首页分类区块。重要图案与文字请放在中央。</p></div>';
 $show_text=!($term instanceof WP_Term)||get_term_meta($term->term_id,'feng_category_hide_text',true)!=='1';
 echo '<p><label><input type="checkbox" name="feng_category_show_text" value="1" '.checked($show_text,true,false).'> 首页分类区块显示图标与名称</label></p><p class="description">图片已经包含分类名称时可关闭；没有图片时仍显示名称。</p>';
 echo '<p><label for="feng-category-icon">Font Awesome 类名 / SVG 代码</label></p><textarea id="feng-category-icon" name="feng_category_icon" rows="3" class="large-text code" placeholder="fa-sharp fa-solid fa-house">'.esc_textarea($code).'</textarea><div data-icon-preview class="feng-admin-icon-preview feng-admin-icon-preview--large">'.feng_badge_icon_markup($code).'</div><p class="description">支持 Pro 原始类名、完整 &lt;i&gt; 标签或纯路径 SVG 代码。填写代码时优先显示代码图标；清空后使用下方图片。</p>';
 echo '<div data-category-badge><input type="hidden" name="feng_category_badge" value="'.esc_attr($id).'"><div data-badge-preview class="feng-admin-badge-preview">'.($id?'<img class="feng-contain-image" src="'.esc_url(wp_get_attachment_url($id)).'" width="48" height="48" alt="分类徽章预览">':'').'</div><button type="button" class="button" data-badge-choose>选择或上传徽章</button> <button type="button" class="button" data-badge-remove>移除</button><p class="description">支持 PNG、WebP、JPG、GIF 和纯路径 SVG。建议使用透明背景的正方形图片；留空时只显示分类名称。</p></div>';
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
