<?php
if(!defined('ABSPATH'))exit;
function feng_footprint_posts($status='publish'){
 $candidates=get_posts(array('post_type'=>'post','post_status'=>$status,'posts_per_page'=>-1,'orderby'=>'date','order'=>'DESC'));
 return array_values(array_filter($candidates,function($post){$flag=get_post_meta($post->ID,'_feng_footprint',true);if($flag!=='')return $flag==='1';$v=feng_travel_location($post->ID);return has_category('travel',$post)||isset($v['lat'],$v['lng'])||!empty($v['place_id']);}));
}
function feng_travel_location($id){$v=get_post_meta($id,'_feng_travel',true);return is_array($v)?$v:array();}
function feng_travel_clean($v){
 $out=array();foreach(array('date_start','date_end') as $key){$date=$v[$key]??'';$parsed=is_string($date)?DateTimeImmutable::createFromFormat('!Y-m-d',$date):false;$out[$key]=$parsed&&$parsed->format('Y-m-d')===$date?$date:'';}
 if(!$out['date_start']||($out['date_end']&&$out['date_end']<$out['date_start']))$out['date_end']='';
 $out['source']=in_array($v['source']??'',array('mapbox','google','amap'),true)?$v['source']:'manual';$out['place_id']=sanitize_text_field($v['place_id']??'');$out['crs']=($v['crs']??'')==='gcj02'?'gcj02':'wgs84';foreach(array('city','country','note') as $key)$out[$key]=sanitize_text_field($v[$key]??'');
 $code=strtolower(trim($v['code']??''));$out['code']=preg_match('/^[a-z]{2}$/D',$code)?$code:'';
 foreach(array('lat'=>90,'lng'=>180) as $key=>$max){$n=$v[$key]??'';$out[$key]=is_numeric($n)&&is_finite((float)$n)&&abs((float)$n)<=$max?(float)$n:null;}
 if($out['lat']===null||$out['lng']===null){$out['lat']=null;$out['lng']=null;}
 return $out;
}
function feng_travel_badge($id){$v=feng_travel_location($id);if(empty($v['city'])&&empty($v['country']))return '';return '<span class="feng-travel-badge">'.(!empty($v['code'])?'<img src="'.esc_url('https://flagcdn.io/flags/4x3/'.$v['code'].'.svg').'" width="24" height="18" alt="'.esc_attr($v['country']??'').'" loading="lazy">':'').'<span>'.esc_html(implode(' · ',array_filter(array($v['city']??'',$v['country']??'')))).'</span></span>';}
function feng_map_fields(){return array('mapbox_token'=>'Mapbox 公开 Token','google_browser'=>'Google 浏览器 Key','google_server'=>'Google 服务器 Key','amap_browser'=>'高德 Web 端 JS Key','amap_security'=>'高德 JS 安全密钥','amap_server'=>'高德 Web 服务 Key');}
function feng_map_config(){return wp_parse_args(get_option('feng_map_settings',array()),array_merge(array_fill_keys(array_keys(feng_map_fields()),''),array('provider'=>'mapbox','mapbox_token'=>get_option('feng_mapbox_token',''),'amap_proxy'=>'','globe_background'=>'system')));}
function feng_map_public(){ $c=feng_map_config();$p=$c['provider'];return array('globe_background'=>$c['globe_background'],'provider'=>$p,'key'=>$c[$p==='mapbox'?'mapbox_token':$p.'_browser'],'security'=>$p==='amap'&&!$c['amap_proxy']?$c['amap_security']:'','proxy'=>$p==='amap'?$c['amap_proxy']:''); }
function feng_map_search($q,$cfg){
 $provider=$cfg['provider'];$key=$cfg[$provider==='mapbox'?'mapbox_token':$provider.'_server'];
 if(!$key)return new WP_Error('map_key','请先在「外观 → ShanYing 设置 → 地图服务」填写所选地图的搜索 Key。');
 if($provider==='mapbox')$url=add_query_arg(array('q'=>$q,'access_token'=>$key,'permanent'=>'true','autocomplete'=>'false','limit'=>5,'language'=>'zh','types'=>'country,region,place,locality,address'),'https://api.mapbox.com/search/geocode/v6/forward');
 elseif($provider==='google')$url=add_query_arg(array('address'=>$q,'key'=>$key,'language'=>'zh-CN'),'https://maps.googleapis.com/maps/api/geocode/json');
 else $url=add_query_arg(array('address'=>$q,'key'=>$key),'https://restapi.amap.com/v3/geocode/geo');
 $r=wp_safe_remote_get($url,array('timeout'=>15,'redirection'=>0,'limit_response_size'=>200000,'feng_feed_request'=>true));
 if(is_wp_error($r)||wp_remote_retrieve_response_code($r)!==200)return new WP_Error('map_network','地图服务连接失败或拒绝请求，请检查 Key、权限、额度和网络。');
 $data=json_decode(wp_remote_retrieve_body($r),true);return feng_map_results($provider,$data);
}
function feng_map_results($provider,$data){
 if(!is_array($data))return new WP_Error('map_response','地图返回内容无法识别。');$items=array();
 if($provider==='mapbox')foreach($data['features']??array() as $f){$p=$f['properties']??array();$country=$p['context']['country']??array();$xy=$f['geometry']['coordinates']??array();$items[]=feng_travel_clean(array('source'=>'mapbox','city'=>$p['name']??'','country'=>$country['name']??(($p['feature_type']??'')==='country'?($p['name']??''):''),'code'=>$country['country_code']??$p['country_code']??'','lat'=>$xy[1]??null,'lng'=>$xy[0]??null))+array('label'=>sanitize_text_field($p['full_address']??$p['name']??''));}
 elseif($provider==='google'){
  if(!in_array($data['status']??'',array('OK','ZERO_RESULTS'),true))return new WP_Error('map_api','Google 搜索失败，请检查 Geocoding API 权限、服务器 Key 和计费。');
  foreach($data['results']??array() as $r){$v=array('source'=>'google','place_id'=>$r['place_id']??'','lat'=>null,'lng'=>null,'city'=>'','country'=>'','code'=>'');foreach($r['address_components']??array() as $part){if(in_array('country',$part['types'],true)){$v['country']=$part['long_name'];$v['code']=$part['short_name'];}if(in_array('locality',$part['types'],true))$v['city']=$part['long_name'];}$items[]=feng_travel_clean($v)+array('label'=>sanitize_text_field($r['formatted_address']??''));}
 }else{
  if(($data['status']??'')!=='1')return new WP_Error('map_api','高德搜索失败，请检查 Web 服务 Key 和权限。');
  foreach($data['geocodes']??array() as $r){$xy=explode(',',$r['location']??'');$items[]=feng_travel_clean(array('source'=>'amap','crs'=>'gcj02','city'=>is_string($r['city']??null)&&$r['city']!==''?$r['city']:($r['province']??''),'country'=>'中国','code'=>'cn','lng'=>$xy[0]??null,'lat'=>$xy[1]??null))+array('label'=>sanitize_text_field($r['formatted_address']??''));}
 }return array_slice($items,0,5);
}
