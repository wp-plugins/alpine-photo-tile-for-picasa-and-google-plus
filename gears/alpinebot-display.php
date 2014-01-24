<?php

/** ##############################################################################################################################################
 *    AlpineBot Secondary
 * 
 *    Display functions
 *    Contains ONLY UNIVERSAL functions
 * 
 *  ##########################################################################################
 */

class PhotoTileForGooglePlusBotSecondary extends PhotoTileForGooglePlusPrimary{     
   
/**
 *  Update global (non-widget) options
 *  
 *  @ Since 1.2.4
 *  @ Updated 1.2.5
 */
  function update_global_options(){
    $options = $this->get_all_options();
    $defaults = $this->option_defaults(); 
    foreach( $defaults as $name=>$info ){
      if( empty($info['widget']) && isset($options[$name])){
        // Update non-widget settings only
        $this->set_active_option($name,$options[$name]);
      }
    }
    // Go ahead and reset info also
    $this->set_private('results', array('photos'=>array(),'feed_found'=>false,'success'=>false,'userlink'=>'','hidden'=>'','message'=>'') );
  }
  
//////////////////////////////////////////////////////////////////////////////////////
///////////////////////      Feed Fetch Functions       //////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////

/**
 *  Function for creating cache key
 *  
 *  @ Since 1.2.2
 */
  function key_maker( $array ){
    if( isset($array['name']) && is_array( $array['info'] ) ){
      $return = $array['name'];
      foreach( $array['info'] as $key=>$val ){
        $return = $return."-".(!empty($val)?$val:$key);
      }
      $return = $this->filter_filename( $return );
      return $return;
    }
  }
/**
 *  Filter string and remove specified characters
 *  
 *  @ Since 1.2.2
 */  
  function filter_filename( $name ){
    $name = @ereg_replace('[[:cntrl:]]', '', $name ); // remove ASCII's control characters
    $bad = array_merge(
      array_map('chr', range(0,31)),
      array("<",">",":",'"',"/","\\","|","?","*"," ",",","\'",".")); 
    $return = str_replace($bad, "", $name); // Remove Windows filename prohibited characters
    return $return;
  }
  
//////////////////////////////////////////////////////////////////////////////////////
/////////////////////////      Cache Functions       /////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////

/**
 * Functions for retrieving results from cache
 *  
 * @ Since 1.2.4
 *
 */
  function retrieve_from_cache( $key ){
    if ( !$this->check_active_option('cache_disable') ) {
      if( $this->cacheExists($key) ) {
        $results = $this->getCache($key);
        $results = @unserialize($results);
        if( count($results) ){
          $results['hidden'] .= '<!-- Retrieved from cache -->';
          $this->set_private('results',$results);
          if( $this->check_active_result('photos') ){
            return true;
          }
        }
      }
    }
    return false;
  }
/**
 * Functions for storing results in cache
 *  
 * @ Since 1.2.4
 *
 */
  function store_in_cache( $key ){
    if( $this->check_active_result('success') && !$this->check_active_option('disable_cache') ){     
      $cache_results = $this->get_private('results');
      if(!is_serialized( $cache_results  )) { $cache_results  = @maybe_serialize( $cache_results ); }
      $this->putCache($key, $cache_results);
      $cachetime = $this->get_option( 'cache_time' );
      if( !empty($cachetime) && is_numeric($cachetime) ){
        $this->setExpiryInterval( $cachetime*60*60 );
      }
    }
  }

/**
 * Functions for caching results and clearing cache
 *  
 * @since 1.1.0
 *
 */
  function setCacheDir($val) {  $this->set_private('cacheDir',$val); }  
  function setExpiryInterval($val) {  $this->set_private('expiryInterval',$val); }  
  function getExpiryInterval($val) {  return (int)$this->get_private('expiryInterval'); }
  
  function cacheExists($key) {  
    $filename_cache = $this->get_private('cacheDir') . '/' . $key . '.cache'; //Cache filename  
    $filename_info = $this->get_private('cacheDir') . '/' . $key . '.info'; //Cache info  
  
    if (file_exists($filename_cache) && file_exists($filename_info)) {  
      $cache_time = file_get_contents ($filename_info) + (int)$this->get_private('expiryInterval'); //Last update time of the cache file  
      $time = time(); //Current Time  
      $expiry_time = (int)$time; //Expiry time for the cache  

      if ((int)$cache_time >= (int)$expiry_time) {//Compare last updated and current time  
        return true;  
      }  
    }
    return false;  
  } 

  function getCache($key)  {  
    $filename_cache = $this->get_private('cacheDir') . '/' . $key . '.cache'; //Cache filename  
    $filename_info = $this->get_private('cacheDir') . '/' . $key . '.info'; //Cache info  
  
    if (file_exists($filename_cache) && file_exists($filename_info))  {  
      $cache_time = file_get_contents ($filename_info) + (int)$this->get_private('expiryInterval'); //Last update time of the cache file  
      $time = time(); //Current Time  

      $expiry_time = (int)$time; //Expiry time for the cache  

      if ((int)$cache_time >= (int)$expiry_time){ //Compare last updated and current time 
        return file_get_contents ($filename_cache);   //Get contents from file  
      }  
    }
    return null;  
  }  

  function putCache($key, $content) {  
    $time = time(); //Current Time  
    $dir = $this->get_private('cacheDir');
    if ( !file_exists($dir) ){  
      @mkdir($dir);  
      $cleaning_info = $dir . '/cleaning.info'; //Cache info 
      @file_put_contents ($cleaning_info , $time); // save the time of last cache update  
    }
    
    if ( file_exists($dir) && is_dir($dir) ){
      $filename_cache = $dir . '/' . $key . '.cache'; //Cache filename  
      $filename_info = $dir . '/' . $key . '.info'; //Cache info  
    
      @file_put_contents($filename_cache ,  $content); // save the content  
      @file_put_contents($filename_info , $time); // save the time of last cache update  
    }
  }
  
  function clearAllCache() {
    $dir = $this->get_private('cacheDir') . '/';
    if(is_dir($dir)){
      $opendir = @opendir($dir);
      while(false !== ($file = readdir($opendir))) {
        if($file != "." && $file != "..") {
          if(file_exists($dir.$file)) {
            $file_array = @explode('.',$file);
            $file_type = @array_pop( $file_array );
            // only remove cache or info files
            if( 'cache' == $file_type || 'info' == $file_type){
              @chmod($dir.$file, 0777);
              @unlink($dir.$file);
            }
          }
          /*elseif(is_dir($dir.$file)) {
            @chmod($dir.$file, 0777);
            @chdir('.');
            @destroy($dir.$file.'/');
            @rmdir($dir.$file);
          }*/
        }
      }
      @closedir($opendir);
    }
  }
  
  function cleanCache() {
    $cleaning_info = $this->get_private('cacheDir') . '/cleaning.info'; //Cache info     
    if (file_exists($cleaning_info))  {  
      $cache_time = file_get_contents ($cleaning_info) + (int)$this->cleaningInterval; //Last update time of the cache cleaning  
      $time = time(); //Current Time  
      $expiry_time = (int)$time; //Expiry time for the cache  
      if ((int)$cache_time < (int)$expiry_time){ //Compare last updated and current time     
        // Clean old files
        $dir = $this->get_private('cacheDir') . '/';
        if(is_dir($dir)){
          $opendir = @opendir($dir);
          while(false !== ($file = readdir($opendir))) {                            
            if($file != "." && $file != "..") {
              if(is_dir($dir.$file)) {
                //@chmod($dir.$file, 0777);
                //@chdir('.');
                //@destroy($dir.$file.'/');
                //@rmdir($dir.$file);
              }
              elseif(file_exists($dir.$file)) {
                $file_array = @explode('.',$file);
                $file_type = @array_pop( $file_array );
                $file_key = @implode( $file_array );
                if( $file_type && $file_key && 'info' == $file_type){
                  $filename_cache = $dir . $file_key . '.cache'; //Cache filename  
                  $filename_info = $dir . $file_key . '.info'; //Cache info   
                  if (file_exists($filename_cache) && file_exists($filename_info)) {  
                    $cache_time = file_get_contents ($filename_info) + (int)$this->cleaningInterval; //Last update time of the cache file  
                    $expiry_time = (int)$time; //Expiry time for the cache  
                    if ((int)$cache_time < (int)$expiry_time) {//Compare last updated and current time  
                      @chmod($filename_cache, 0777);
                      @unlink($filename_cache);
                      @chmod($filename_info, 0777);
                      @unlink($filename_info);
                    }  
                  }
                  /*elseif (file_exists($filename_cache) && file_exists($filename_info)) {  
                    $cache_time = file_get_contents ($filename_info) + (int)$this->cleaningInterval; //Last update time of the cache file  
                    $expiry_time = (int)$time; //Expiry time for the cache  
                    if ((int)$cache_time < (int)$expiry_time) {//Compare last updated and current time  
                      @chmod($filename_cache, 0777);
                      @unlink($filename_cache);
                      @chmod($filename_info, 0777);
                      @unlink($filename_info);
                    } 
                  }*/
                }
              }
            }
          }
          @closedir($opendir);
        }
        @file_put_contents ($cleaning_info , $time); // save the time of last cache cleaning        
      }
    }
  } 
  
  /*
  function putCacheImage($image_url){
    $time = time(); //Current Time  
    if ( ! file_exists($this->cacheDir) ){  
      @mkdir($this->cacheDir);  
      $cleaning_info = $this->cacheDir . '/cleaning.info'; //Cache info 
      @file_put_contents ($cleaning_info , $time); // save the time of last cache update  
    }
    
    if ( file_exists($this->cacheDir) && is_dir($this->cacheDir) ){ 
      //replace with your cache directory
      $dir = $this->cacheDir.'/';
      //get the name of the file
      $exploded_image_url = explode("/",$image_url);
      $image_filename = end($exploded_image_url);
      $exploded_image_filename = explode(".",$image_filename);
      $name = current($exploded_image_filename);
      $extension = end($exploded_image_filename);
      //make sure its an image
      if($extension=="gif"||$extension=="jpg"||$extension=="png"){
        //get the remote image
        $image_to_fetch = @file_get_contents($image_url);
        //save it
        $filename_image = $dir . $image_filename;
        $filename_info = $dir . $name . '.info'; //Cache info  
      
        $local_image_file = @fopen($filename_image, 'w+');
        @chmod($dir.$image_filename,0755);
        @fwrite($local_image_file, $image_to_fetch);
        @fclose($local_image_file);
        
        @file_put_contents($filename_info , $time); // save the time of last cache update  
      }
    }
  }
  
  function getImageCache($image_url)  {  
    $dir = $this->cacheDir.'/';
  
    $exploded_image_url = explode("/",$image_url);
    $image_filename = end($exploded_image_url);
    $exploded_image_filename = explode(".",$image_filename);
    $name = current($exploded_image_filename);  
    $filename_image = $dir . $image_filename;
    $filename_info = $dir . $name . '.info'; //Cache info  
  
    if (file_exists($filename_image) && file_exists($filename_info))  {  
      $cache_time = @file_get_contents ($filename_info) + (int)$this->expiryInterval; //Last update time of the cache file  
      $time = time(); //Current Time  

      $expiry_time = (int)$time; //Expiry time for the cache  

      if ((int)$cache_time >= (int)$expiry_time){ //Compare last updated and current time 
        return $this->cacheUrl.'/'.$image_filename;   // Return image URL
      }else{
        $local_image_file = @fopen($filename_image, 'w+');
        @chmod($dir.$image_filename,0755);
        @fwrite($local_image_file, $image_to_fetch);
        @fclose($local_image_file);
        
        @file_put_contents($filename_info , $time); // save the time of last cache update  
      }
    }elseif( $this->cacheAttempts < $this->cacheLimit ){
      $this->putCacheImage($image_url);
      $this->cacheAttempts++;
    }
    return null;  
  }  
  */
}

/** ##############################################################################################################################################
 *  ##############################################################################################################################################
 *  ##############################################################################################################################################
 *  ##############################################################################################################################################
 *  ##############################################################################################################################################
 *  ##############################################################################################################################################
 *  ##############################################################################################################################################
 *  ##############################################################################################################################################
 *   
 *    AlpineBot Tertiary
 * 
 *    Display functions
 *    Contains ONLY UNIQUE functions
 * 
 *  ##########################################################################################
 */
 
class PhotoTileForGooglePlusBotTertiary extends PhotoTileForGooglePlusBotSecondary{ 
 
  // For Reference:
  // http://www.picasa.com/services/api/response.json.html
  // sq = thumbnail 75x75
  // t = 100 on longest side
  // s = 240 on longest side
  // n = 320 on longest side
  // m = 500 on longest side
  // z = 640 on longest side
  // c = 800 on longest side
  // b = 1024 on longest side*
  // o = original image, either a jpg, gif or png, depending on source format**
  // *Before May 25th 2010 large photos only exist for very large original images.
  // **Original photos behave a little differently. They have their own secret (called originalsecret in responses) and a variable file extension (called originalformat in responses). These values are returned via the API only when the caller has permission to view the original size (based on a user preference and various other criteria). The values are returned by the picasa.photos.getInfo method and by any method that returns a list of photos and allows an extras parameter (with a value of original_format), such as picasa.photos.search. The picasa.photos.getSizes method, as always, will return the full original URL where permissions allow.

//////////////////////////////////////////////////////////////////////////////////////
//////////////////        Unique Feed Fetch Functions        /////////////////////////
//////////////////////////////////////////////////////////////////////////////////////    

/**
 * Alpine PhotoTile for GooglePlus: Photo Retrieval Function.
 * The PHP for retrieving content from GooglePlus.
 *
 * @ Since 1.0.0
 * @ Updated 1.2.5
 */  
  function photo_retrieval(){
    $picasa_options = $this->get_private('options');
    $defaults = $this->option_defaults();

    $key_input = array(
      'name' => 'picasa',
      'info' => array(
        'vers' => $this->get_private('vers'),
        'src' => (isset($picasa_options['picasa_source'])?$picasa_options['picasa_source']:''),
        'uid' => (isset($picasa_options['picasa_user_id'])?$picasa_options['picasa_user_id']:''),
        'alb' => (isset($picasa_options['picasa_user_album'])?$picasa_options['picasa_user_album']:''),
        'authkey' => (isset($picasa_options['picasa_auth_key'])?$picasa_options['picasa_auth_key']:''),
        'key' => (isset($picasa_options['picasa_keyword'])?$picasa_options['picasa_keyword']:''),
        'num' => (isset($picasa_options['picasa_photo_number'])?$picasa_options['picasa_photo_number']:''),
        'link' => (isset($picasa_options['picasa_display_link'])?$picasa_options['picasa_display_link']:''),
        'text' => (isset($picasa_options['picasa_display_link_text'])?$picasa_options['picasa_display_link_text']:''),
        'size' => (isset($picasa_options['picasa_photo_size'])?$picasa_options['picasa_photo_size']:'')
        )
      );
    $key = $this->key_maker( $key_input );  // Make Key
    if( $this->retrieve_from_cache( $key ) ){  return; } // Check Cache
    
    if( function_exists('json_decode') ) {
      $this->try_json();
    }

    if( !$this->check_active_result('success') ) {
      $this->try_rss();
    }
    
    if( $this->check_active_result('success') ){
      $src = $this->get_private('src');
      if( $this->check_active_result('userlink') && $this->check_active_option($src.'_display_link') && $this->check_active_option($src.'_display_link_text') && 'community' != $this->get_active_option($src.'_source') ){
        $linkurl = $this->get_active_result('userlink');
        $link = '<div class="AlpinePhotoTiles-display-link" >';
        $link .='<a href="'.$linkurl.'" target="_blank" >';
        $link .= $this->get_active_option($src.'_display_link_text');
        $link .= '</a></div>';
        $this->set_active_result('userlink',$link);
      }else{
        $this->set_active_result('userlink',null);
      }
    }else{
      if( $this->check_active_result('feed_found') ){
        $this->append_active_result('message','- Picasa feed was successfully retrieved, but no photos found.<br> If you are using the "User Recent" option, try a specific album instead.');
      }else{
        $this->append_active_result('message','- Please recheck your ID(s). <br>- If you are showing a private album, check that the "Retrieve Photos From" option is set to "User\'s Private Album" and that the Authorization Key is correct.');
      }
    }
    
    //$this->results = array('continue'=>$this->success,'message'=>$this->message,'hidden'=>$this->hidden,'photos'=>$this->photos,'user_link'=>$this->userlink);

    $this->store_in_cache( $key );  // Store in cache

  }
/**
 *  Function for forming GooglePlus request
 *  
 *  @ Since 1.2.4
 *  @ Updated 1.2.6
 */ 
  function get_picasa_request($format='json'){
    $picasa_options = $this->get_private('options');
    $request = false;
    $picasa_uid = empty($picasa_options['picasa_user_id']) ? 'uid' : $picasa_options['picasa_user_id'];
    $picasa_uid = str_replace(array('/',' '),'',$picasa_uid);
    $picasa_uid = str_replace('http:','',$picasa_uid );
    $picasa_uid = str_replace('.picasa.com','',$picasa_uid);
    $num = $picasa_options['picasa_photo_number']?$picasa_options['picasa_photo_number']:10;
    $size = $picasa_options['picasa_photo_size']?$picasa_options['picasa_photo_size']:400;
    
    switch ($picasa_options['picasa_source']){
      case 'user_recent':
        $request = 'http://picasaweb.google.com/data/feed/api/user/'.$picasa_uid.'?kind=photo&alt='.$format.'&max-results='.$num.'&thumbsize='.$size.'u&imgmax=1024u';
      break;
      case 'user_album':
        $picasa_album = empty($picasa_options['picasa_user_album']) ? '' : $picasa_options['picasa_user_album'];
        $request = 'http://picasaweb.google.com/data/feed/api/user/'.$picasa_uid.'/albumid/'.$picasa_album.'?kind=photo&alt='.$format.'&kind=photo&max-results='.$num.'&thumbsize='.$size.'u&imgmax=1024u';
      break;
      case 'private_user_album':
        $auth_key = empty($picasa_options['picasa_auth_key']) ? '' : $picasa_options['picasa_auth_key'];
        $auth_key = 'Gv1sRg'.( str_replace('Gv1sRg','',$auth_key) ); // If used Google+ to find key, doesn't include Gv1sRg
        $picasa_album = empty($picasa_options['picasa_user_album']) ? '' : $picasa_options['picasa_user_album'];
        $request = 'http://picasaweb.google.com/data/feed/api/user/'.$picasa_uid.'/albumid/'.$picasa_album.'?kind=photo&alt='.$format.'&kind=photo&authkey='.$auth_key.'&max-results='.$num.'&thumbsize='.$size.'u&imgmax=1024u';
      break;      
      case 'global_keyword':
        $picasa_keyword = empty($picasa_options['picasa_keyword']) ? '' : $picasa_options['picasa_keyword'];
        $request = 'http://picasaweb.google.com/data/feed/api/all?kind=photo&alt='.$format.'&q='.$picasa_keyword.'&max-results='.$num.'&thumbsize='.$size.'u&imgmax=1024u';
      break;
    }
    return $request;
 }

/**
 *  Function for fetching picasa feed
 *  
 *  @ Since 1.2.5
 */
  function get_picasa_feed($request){
    $_picasa_json = array();
    $this->append_active_result('hidden','<!-- Request made -->');
    $response = wp_remote_get($request,
      array(
        'method' => 'GET',
        'timeout' => 20,
        'sslverify' => apply_filters('https_local_ssl_verify', false)
      )
    );
    
    if( is_wp_error( $response ) || !isset($response['body']) ) {
      $this->append_active_result('hidden','<!-- Failed using wp_remote_get() and JSON @ '.$request.' -->');
      return false;
    }else{
      return $response['body'];
    }
  }
/**
 *  Function for making Picasa request with json return format
 *  
 *  @ Since 1.2.4
 */
  function try_json(){
    // Retrieve content using wp_remote_get and JSON
    $request = $this->get_picasa_request('json');
    $options = $this->get_private('options');
    
    $_picasa_json = $this->get_picasa_feed($request);
    if( !empty( $_picasa_json ) ){
      $_picasa_json = @json_decode( $_picasa_json );
    }

    if( empty($_picasa_json) || empty($_picasa_json->feed) || empty($_picasa_json->feed) ){ 
      $this->append_active_result('hidden','<!-- Failed using wp_remote_get() and JSON @ '.$request.' -->');
      $this->set_active_result('success',false);
    }else{

      $content = array();
      if( isset($_picasa_json->feed->entry) ){
        $content = $_picasa_json->feed->entry;
      }else{
        // There seems to be a bug where kind=photo returns no photos
        $sec_request = str_replace('kind=photo&','',$request);
        $sec_picasa_json = $this->get_picasa_feed($sec_request);
        if( !empty( $sec_picasa_json ) ){
          $sec_picasa_json = @json_decode( $sec_picasa_json );
          if( isset($sec_picasa_json->feed->entry) ){
            $content = $sec_picasa_json->feed->entry;
            $_picasa_json = $sec_picasa_json;
          }
        }
      }
      $link = $_picasa_json->feed->link[1]->href;
      $this->set_active_result('userlink',$link);
      $s = 0; // simple counter

      if( count($content) ) {
        foreach( $content as $p ) {
          if( $s<$options['picasa_photo_number'] ){   
            $the_photo = array();
            $title = (array) $p->title;
            $the_photo['image_title'] = (string) $title['$t'];
            $the_photo['image_title'] = str_replace(array('.jpg', '.JPG'),'',$the_photo['image_title']);
            $the_photo['image_caption'] = '';

            // list of link urls;
            $the_photo['image_link'] = '';
            $glink = $p->link;
            if( isset($glink[2]->href) && isset($glink[2]->type) && 'text/html' == $glink[2]->type ){
              $the_photo['image_link'] = $glink[2]->href;
            }elseif( isset($glink[1]->href) && isset($glink[1]->type) && 'text/html' == $glink[1]->type ){
              $the_photo['image_link'] = $glink[1]->href;
            }elseif( isset($glink[0]->href) && isset($glink[0]->type) && 'text/html' == $glink[0]->type ){
              $the_photo['image_link'] = $glink[0]->href;
            }
            
            
            $mg = 'media$group';
            $mc = 'media$content';
            $mt = 'media$thumbnail';
            $m_thumb = isset($p->$mg->$mt)?$p->$mg->$mt:$p;
            $m_content = isset($p->$mg->$mc)?$p->$mg->$mc:$p;
            // list of photo urls
            $the_photo['image_original'] = isset($p->content->src)?(string) $p->content->src:(isset($m_content[0]->url)?(string) $m_content[0]->url: '');
            $the_photo['image_source'] = isset($m_thumb[0]->url)?(string) $m_thumb[0]->url:$the_photo['image_original'];

            $this->push_photo( $the_photo );
            
            $s++;
          }else{
            break;
          }
        }
      }
    
      if( $this->check_active_result('photos') ){
        $this->set_active_result('success',true);
        $this->append_active_result('hidden','<!-- Success using wp_remote_get() and JSON -->');
      }else{
        $this->set_active_result('success',false);
        $this->set_active_result('feed_found',true);
        $this->append_active_result('hidden','<!-- No photos found using wp_remote_get() and JSON @ '.$request.' -->');
      }
    }   
  }
  
/**
 *  Function for making picasa request with xml return format ( API v2 )
 *  
 *  @ Since 1.2.4
 */
  function try_rss(){
   if(!function_exists('APTFPICAbyTAP_specialarraysearch')){
      function APTFPICAbyTAP_specialarraysearch($array, $find){
        foreach ($array as $key=>$value){
          if( is_string($key) && $key==$find){
            return $value;
          }
          elseif(is_array($value)){
            $results = APTFPICAbyTAP_specialarraysearch($value, $find);
          }
          elseif(is_object($value)){
            $sub = $array->$key;
            $results = APTFPICAbyTAP_specialarraysearch($sub, $find);
          }
          // If found, return
          if(!empty($results)){return $results;}
        }
        return $results;
      }
    }
    
    include_once(ABSPATH . WPINC . '/feed.php');
    
    if( !function_exists('return_noCache') ){
      function return_noCache( $seconds ){
        // change the default feed cache recreation period to 30 seconds
        return 30;
      }
    }
    
    $request = $this->get_picasa_request('rss');

    add_filter( 'wp_feed_cache_transient_lifetime' , 'return_noCache' );
    $rss = @fetch_feed( $request );
    remove_filter( 'wp_feed_cache_transient_lifetime' , 'return_noCache' );

    if (!is_wp_error( $rss ) && !empty($rss) ){ // Check that the object is created correctly 
      // Bulldoze through the feed to find the items 
      $data = @APTFPICAbyTAP_specialarraysearch($rss,'child');
      $data = @APTFPICAbyTAP_specialarraysearch($data,'child');
      $data = @APTFPICAbyTAP_specialarraysearch($data,'child');
      
      $link = $data[null]['link'][0]['data']; 
      $this->set_active_result('userlink',$link);
      $rss_data = isset($data[null]['item'])?$data[null]['item']:array();

      $s = 0; // simple counter
      if ( !empty($rss_data) ){ // Check again
        foreach ( $rss_data as $item ) {
          //print_r( $item );
          if( $s<$picasa_options['picasa_photo_number'] ){
            $the_photo = array();
            $the_photo['image_title'] = $item['child']['']['title']['0']['data'];     
            $the_photo['image_title'] = str_replace(array('.jpg', '.JPG'),'',$the_photo['image_title']);
            $the_photo['image_caption'] = '';
          
            $thumb = @APTFPICAbyTAP_specialarraysearch($item,'thumbnail');
            
            $the_photo['image_link'] = $item['child']['']['link']['0']['data'];  
            $the_photo['image_original'] =  $item['child']['']['enclosure']['0']['attribs']['']['url'];
            $the_photo['image_source'] = $the_photo['image_original'];
            if( isset($thumb['0']['attribs']['']['url']) ){
              $the_photo['image_source'] = $thumb['0']['attribs']['']['url'];
            }
            $this->push_photo( $the_photo );
            $s++;
          }
          else{
            break;
          }
        }
      }
      
      if( $this->check_active_result('photos') ){
        $this->set_active_result('success',true);
        $this->append_active_result('hidden','<!-- Success using feed_fetch() and RSS -->');
      }else{
        $this->set_active_result('success',false);
        $this->set_active_result('feed_found',true);
        $this->append_active_result('hidden','<!-- No photos found using feed_fetch() and RSS @ '.$request.' -->');
      }
    }
    else{
      $this->set_active_result('success',false);
      $this->append_active_result('hidden','<!-- Failed using feed_fetch() and RSS -->');
    }      
  }
   
  
}
  
/** ##############################################################################################################################################
 *  ##############################################################################################################################################
 *  ##############################################################################################################################################
 *  ##############################################################################################################################################
 *  ##############################################################################################################################################
 *  ##############################################################################################################################################
 *  ##############################################################################################################################################
 *  ##############################################################################################################################################
 *   
 *  AlpineBot Display
 * 
 *  Display functions
 *  Try to keep only UNIVERSAL functions
 * 
 */
 
class PhotoTileForGooglePlusBot extends PhotoTileForGooglePlusBotTertiary{
/**
 *  Function for printing vertical style
 *  
 *  @ Since 0.0.1
 *  @ Updated 1.2.6.5
 */
  function display_vertical(){
    $this->set_private('out',''); // Clear any output;
    $this->update_count(); // Check number of images found
    $this->randomize_display(); 
    $opts = $this->get_private('options');
    $src = $this->get_private('src');
    $wid = $this->get_private('wid');
                      
    $this->add('<div id="'.$wid.'-AlpinePhotoTiles_container" class="AlpinePhotoTiles_container_class">');     
    
      // Align photos
      $css = $this->get_parent_css();
      $this->add('<div id="'.$wid.'-vertical-parent" class="AlpinePhotoTiles_parent_class" style="'.$css.'">');

        for($i = 0;$i<$opts[$src.'_photo_number'];$i++){
          $css = "margin:1px 0 5px 0;padding:0;max-width:100%;";
          $pin = $this->get_option( 'pinterest_pin_it_button' );
          $this->add_image($i,$css,$pin); // Add image
        }
        
        $this->add_credit_link();
      
      $this->add('</div>'); // Close vertical-parent

      $this->add_user_link();

    $this->add('</div>'); // Close container
    $this->add('<div class="AlpinePhotoTiles_breakline"></div>');
    
    // Add Lightbox call (if necessary)
    $this->add_lightbox_call();
    
    $parentID = $wid."-vertical-parent";
    $borderCall = $this->get_borders_call( $parentID );

    if( !empty($opts['style_shadow']) || !empty($opts['style_border']) || !empty($opts['style_highlight'])  ){
      $this->add("
<script>  
  // Check for on() ( jQuery 1.7+ )
  if( jQuery.isFunction( jQuery(window).on ) ){
    jQuery(window).on('load', function(){".$borderCall."}); // Close on()
  }else{
    // Otherwise, use bind()
    jQuery(window).bind('load', function(){".$borderCall."}); // Close bind()
  }
</script>");  
    }
  }  
/**
 *  Function for printing cascade style
 *  
 *  @ Since 0.0.1
 *  @ Updated 1.2.6.5
 */
  function display_cascade(){
    $this->set_private('out',''); // Clear any output;
    $this->update_count(); // Check number of images found
    $this->randomize_display();
    $opts = $this->get_private('options');
    $wid = $this->get_private('wid');
    $src = $this->get_private('src');
    
    $this->add('<div id="'.$wid.'-AlpinePhotoTiles_container" class="AlpinePhotoTiles_container_class">');     
    
      // Align photos
      $css = $this->get_parent_css();
      $this->add('<div id="'.$wid.'-cascade-parent" class="AlpinePhotoTiles_parent_class" style="'.$css.'">');
      
        for($col = 0; $col<$opts['style_column_number'];$col++){
          $this->add('<div class="AlpinePhotoTiles_cascade_column" style="width:'.(100/$opts['style_column_number']).'%;float:left;margin:0;">');
          $this->add('<div class="AlpinePhotoTiles_cascade_column_inner" style="display:block;margin:0 3px;overflow:hidden;">');
          for($i = $col;$i<$opts[$src.'_photo_number'];$i+=$opts['style_column_number']){
            $css = "margin:1px 0 5px 0;padding:0;max-width:100%;";
            $pin = $this->get_option( 'pinterest_pin_it_button' );
            $this->add_image($i,$css,$pin); // Add image
          }
          $this->add('</div></div>');
        }
        $this->add('<div class="AlpinePhotoTiles_breakline"></div>');
          
        $this->add_credit_link();
      
      $this->add('</div>'); // Close cascade-parent

      $this->add('<div class="AlpinePhotoTiles_breakline"></div>');
      
      $this->add_user_link();

    // Close container
    $this->add('</div>');
    $this->add('<div class="AlpinePhotoTiles_breakline"></div>');
    
    // Add Lightbox call (if necessary)
    $this->add_lightbox_call();
    
    $parentID = $wid."-cascade-parent";
    $borderCall = $this->get_borders_call( $parentID );

    if( !empty($opts['style_shadow']) || !empty($opts['style_border']) || !empty($opts['style_highlight'])  ){
      $this->add("
<script>
  // Check for on() ( jQuery 1.7+ )
  if( jQuery.isFunction( jQuery(window).on ) ){
    jQuery(window).on('load', function(){".$borderCall."}); // Close on()
  }else{
    // Otherwise, use bind()
    jQuery(window).bind('load', function(){".$borderCall."}); // Close bind()
  }
</script>");  
    }
  }
/**
 *  Get jQuery borders plugin string
 *  
 *  @ Since 1.2.6.5
 */
  function get_borders_call( $parentID ){
    $highlight = $this->get_option("general_highlight_color");
    $highlight = (!empty($highlight)?$highlight:'#64a2d8');
    
    $return = "
      if( jQuery().AlpineAdjustBordersPlugin ){
        jQuery('#".$parentID."').AlpineAdjustBordersPlugin({
          highlight:'".$highlight."'
        });
      }else{
        var css = '".($this->get_private('url').'/css/'.$this->get_private('wcss').'.css')."';
        var link = jQuery(document.createElement('link')).attr({'rel':'stylesheet','href':css,'type':'text/css','media':'screen'});
        jQuery.getScript('".($this->get_private('url').'/js/'.$this->get_private('wjs').'.js')."', function(){
          if(document.createStyleSheet){
            document.createStyleSheet(css);
          }else{
            jQuery('head').append(link);
          }
          if( jQuery().AlpineAdjustBordersPlugin ){
            jQuery('#".$parentID."').AlpineAdjustBordersPlugin({
              highlight:'".$highlight."'
            });
          }
        }); // Close getScript
      }
    ";
    return $return;
  }
/**
 *  Function for printing and initializing JS styles
 *  
 *  @ Since 0.0.1
 *  @ Updated 1.2.6.5
 */
  function display_hidden(){
    $this->set_private('out',''); // Clear any output;
    $this->update_count(); // Check number of images found
    $this->randomize_display();
    $opts = $this->get_private('options');
    $wid = $this->get_private('wid');
    $src = $this->get_private('src');
    
    $this->add('<div id="'.$wid.'-AlpinePhotoTiles_container" class="AlpinePhotoTiles_container_class">');     
      // Align photos
      $css = $this->get_parent_css();
      $this->add('<div id="'.$wid.'-hidden-parent" class="AlpinePhotoTiles_parent_class" style="'.$css.'">');
      
        $this->add('<div id="'.$wid.'-image-list" class="AlpinePhotoTiles_image_list_class" style="display:none;visibility:hidden;">'); 
        
          for($i=0;$i<$opts[$src.'_photo_number'];$i++){

            $this->add_image($i); // Add image
            
            // Load original image size
            $original = $this->get_photo_info($i,'image_original');
            if( isset($opts['style_option']) && "gallery" == $opts['style_option'] && !empty( $original ) ){
              $this->add('<img class="AlpinePhotoTiles-original-image" src="' . $original . '" />');
            }
          }
        $this->add('</div>');
        
        $this->add_credit_link();       
      
      $this->add('</div>'); // Close parent  

      $this->add_user_link();
      
    $this->add('</div>'); // Close container
    
    $disable = $this->get_option("general_loader");

    $lightbox = $this->get_option('general_lightbox');
    $prevent = $this->get_option('general_lightbox_no_load');    
    $hasLight = false;
    $lightScript = '';
    $lightStyle = '';
    if( empty($prevent) && isset($opts[$this->get_private('src').'_image_link_option']) && $opts[$src.'_image_link_option'] == 'fancybox' ){
      $lightScript = $this->get_script( $lightbox );
      $lightStyle = $this->get_style( $lightbox );
      if( !empty($lightScript) && !empty($lightStyle) ){
        $hasLight = true;
      }
    }
    
    $this->add('<script>');
      if(!$disable){
        $this->add(
    "
    jQuery(document).ready(function() {
      jQuery('#".$wid."-AlpinePhotoTiles_container').addClass('loading'); 
    });
    ");
    
      }
  
    $pluginCall = $this->get_loading_call($opts,$wid,$src,$lightbox,$hasLight,$lightScript,$lightStyle);
    
    $this->add("
    // Check for on() ( jQuery 1.7+ )
    if( jQuery.isFunction( jQuery(window).on ) ){
      jQuery(window).on('load', function(){".$pluginCall."});
    }else{ 
      // Otherwise, use bind()
      jQuery(window).bind('load', function(){".$pluginCall."});
    }
</script>");    
 
  }
/**
 *  Get jQuery loading string
 *  
 *  @ Since 1.2.6.5
 */
  function get_loading_call($opts,$wid,$src,$lightbox,$hasLight,$lightScript,$lightStyle){
    $return = "
        jQuery('#".$wid."-AlpinePhotoTiles_container').removeClass('loading');
        
        var alpineLoadPlugin = function(){".$this->get_plugin_call($opts,$wid,$src,$hasLight)."}
        
        // Load Alpine Plugin
        if( jQuery().AlpinePhotoTilesPlugin ){
          alpineLoadPlugin();
        }else{ // Load Alpine Script and Style
          var css = '".($this->get_private('url').'/css/'.$this->get_private('wcss').'.css')."';
          var link = jQuery(document.createElement('link')).attr({'rel':'stylesheet','href':css,'type':'text/css','media':'screen'});
          jQuery.getScript('".($this->get_private('url').'/js/'.$this->get_private('wjs').'.js')."', function(){
            if(document.createStyleSheet){
              document.createStyleSheet(css);
            }else{
              jQuery('head').append(link);
            }";
          if( $hasLight ){    
          $check = ($lightbox=='fancybox'?'fancybox':($lightbox=='prettyphoto'?'prettyPhoto':($lightbox=='colorbox'?'colorbox':'fancyboxForAlpine')));    
          $return .="
            if( !jQuery().".$check." ){ // Load Lightbox
              jQuery.getScript('".$lightScript."', function(){
                css = '".$lightStyle."';
                link = jQuery(document.createElement('link')).attr({'rel':'stylesheet','href':css,'type':'text/css','media':'screen'});
                if(document.createStyleSheet){
                  document.createStyleSheet(css);
                }else{
                  jQuery('head').append(link);
                }
                alpineLoadPlugin();
              }); // Close getScript
            }else{
              alpineLoadPlugin();
            }";
          }else{
            $return .= "
            alpineLoadPlugin();";
          }
            $return .= "
          }); // Close getScript
        }
      ";
    return $return;
  }
/**
 *  Get jQuery plugin string
 *  
 *  @ Since 1.2.6.5
 */
  function get_plugin_call($opts,$wid,$src,$hasLight){
    $highlight = $this->get_option("general_highlight_color");
    $highlight = (!empty($highlight)?$highlight:'#64a2d8');
    $return = "
          jQuery('#".$wid."-hidden-parent').AlpinePhotoTilesPlugin({
            id:'".$wid."',
            style:'".(isset($opts['style_option'])?$opts['style_option']:'windows')."',
            shape:'".(isset($opts['style_shape'])?$opts['style_shape']:'square')."',
            perRow:".(isset($opts['style_photo_per_row'])?$opts['style_photo_per_row']:'3').",
            imageBorder:".(!empty($opts['style_border'])?'1':'0').",
            imageShadow:".(!empty($opts['style_shadow'])?'1':'0').",
            imageCurve:".(!empty($opts['style_curve_corners'])?'1':'0').",
            imageHighlight:".(!empty($opts['style_highlight'])?'1':'0').",
            lightbox:".((isset($opts[$src.'_image_link_option']) && $opts[$src.'_image_link_option'] == 'fancybox')?'1':'0').",
            galleryHeight:".(isset($opts['style_gallery_height'])?$opts['style_gallery_height']:'0').", // Keep for Compatibility
            galRatioWidth:".(isset($opts['style_gallery_ratio_width'])?$opts['style_gallery_ratio_width']:'800').",
            galRatioHeight:".(isset($opts['style_gallery_ratio_height'])?$opts['style_gallery_ratio_height']:'600').",
            highlight:'".$highlight."',
            pinIt:".(!empty($opts['pinterest_pin_it_button'])?'1':'0').",
            siteURL:'".get_option( 'siteurl' )."',
            callback: ".(!empty($hasLight)?'function(){'.$this->get_lightbox_call().'}':"''")."
          });
        ";
    return $return;
  }
 
/**
 *  Update photo number count
 *  
 *  @ Since 1.2.2
 */
  function update_count(){
    $src = $this->get_private('src');
    $found = ( $this->check_active_result('photos') && is_array($this->get_active_result('photos') ))?count( $this->get_active_result('photos') ):0;
    $num = $this->get_active_option( $src.'_photo_number' );
    $this->set_active_option( $src.'_photo_number', min( $num, $found ) );
  }  
/**
 *  Function for shuffleing photo feed
 *  
 *  @ Since 1.2.4
 */
  function randomize_display(){
    if( $this->check_active_option('photo_feed_shuffle') && function_exists('shuffle') ){ // Shuffle the results
      $photos = $this->get_active_result('photos');
      @shuffle( $photos );
      $this->set_active_result('photos',$photos);
    }  
  }  
/**
 *  Get Parent CSS
 *  
 *  @ Since 1.2.2
 *  @ Updated 1.2.5
 */
  function get_parent_css(){
    $max = $this->check_active_option('widget_max_width')?$this->get_active_option('widget_max_width'):100;
    $return = 'width:100%;max-width:'.$max.'%;padding:0px;';
    $align = $this->check_active_option('widget_alignment')?$this->get_active_option('widget_alignment'):'';
    if( 'center' == $align ){                          //  Optional: Set text alignment (left/right) or center
      $return .= 'margin:0px auto;text-align:center;';
    }
    elseif( 'right' == $align  || 'left' == $align  ){                          //  Optional: Set text alignment (left/right) or center
      $return .= 'float:' . $align  . ';text-align:' . $align  . ';';
    }
    else{
      $return .= 'margin:0px auto;text-align:center;';
    }
    return $return;
 }
 
/**
 *  Add Image Function
 *  
 *  @ Since 1.2.2
 *  @ Updated 1.2.4
 ** Possible change: place original image as 'alt' and load image as needed
 */
  function add_image($i,$css="",$pin=false){
    $light = $this->get_option( 'general_lightbox' );
    $title = $this->get_photo_info($i,'image_title');
    $src = $this->get_photo_info($i,'image_source');
    $shadow = ($this->check_active_option('style_shadow')?'AlpinePhotoTiles-img-shadow':'AlpinePhotoTiles-img-noshadow');
    $border = ($this->check_active_option('style_border')?'AlpinePhotoTiles-img-border':'AlpinePhotoTiles-img-noborder');
    $curves = ($this->check_active_option('style_curve_corners')?'AlpinePhotoTiles-img-corners':'AlpinePhotoTiles-img-nocorners');
    $highlight = ($this->check_active_option('style_highlight')?'AlpinePhotoTiles-img-highlight':'AlpinePhotoTiles-img-nohighlight');
    $onContextMenu = ($this->check_active_option('general_disable_right_click')?'onContextMenu="return false;"':'');
    
    if( $pin ){ $this->add('<div class="AlpinePhotoTiles-pinterest-container" style="position:relative;display:block;" >'); }
    
    //$src = $this->getImageCache( $this->photos[$i]['image_source'] );
    //$src = ( $src?$src:$this->photos[$i]['image_source']);
    
    $has_link = $this->get_link($i); // Add link
    $this->add('<img id="'.$this->get_private('wid').'-tile-'.$i.'" class="AlpinePhotoTiles-image '.$shadow.' '.$border.' '.$curves.' '.$highlight.'" src="' . $src . '" ');
    $this->add('title='."'". $title ."'".' alt='."'". $title ."' "); // Careful about caps with ""
    $this->add('border="0" hspace="0" vspace="0" style="'.$css.'" '.$onContextMenu.' />'); // Override the max-width set by theme
    if( $has_link ){ $this->add('</a>'); } // Close link
    
    if( $pin ){ 
      $original = $this->get_photo_info($i,'image_original');
      $this->add('<a href="http://pinterest.com/pin/create/button/?media='.$original.'&url='.get_option( 'siteurl' ).'" class="AlpinePhotoTiles-pin-it-button" count-layout="horizontal" target="_blank">');
      $this->add('<div class="AlpinePhotoTiles-pin-it"></div></a>');
      $this->add('</div>'); 
    }
  }
/**
 *  Get Image Link
 *  
 *  @ Since 1.2.2
 *  @ Updated 1.2.6.5
 */
  function get_link($i){
    $src = $this->get_private('src');
    $link = $this->get_active_option($src.'_image_link_option');
    $url = $this->get_active_option('custom_link_url');

    $phototitle = $this->get_photo_info($i,'image_title'); 
    $photourl = $this->get_photo_info($i,'image_source');
    $linkurl = $this->get_photo_info($i,'image_link');
    $originalurl = $this->get_photo_info($i,'image_original');

    if( 'original' == $link && !empty($photourl) ){
      $this->add('<a href="' . $photourl . '" class="AlpinePhotoTiles-link" target="_blank" title=" '. $phototitle .' " alt=" '. $phototitle .' ">');
      return true;
    }elseif( ($src == $link || '1' == $link) && !empty($linkurl) ){
      $this->add('<a href="' . $linkurl . '" class="AlpinePhotoTiles-link" target="_blank" title=" '. $phototitle .' " alt=" '. $phototitle .' ">');
      return true;
    }elseif( 'link' == $link && !empty($url) ){
      $this->add('<a href="' . $url . '" class="AlpinePhotoTiles-link" title=" '. $phototitle .' " alt=" '. $phototitle .' ">'); 
      return true;
    }elseif( 'fancybox' == $link && !empty($originalurl) ){
      $light = $this->get_option( 'general_lightbox' );
      $this->add('<a href="' . $originalurl . '" class="AlpinePhotoTiles-link AlpinePhotoTiles-lightbox" title=" '. $phototitle .' " alt=" '. $phototitle .' ">'); 
      return true;
    }  
    return false;    
  }
/**
 *  Credit Link Function
 *  
 *  @ Since 1.2.2
 */
  function add_credit_link(){
    if( !$this->get_active_option('widget_disable_credit_link') ){
      $this->add('<div id="'.$this->get_private('wid').'-by-link" class="AlpinePhotoTiles-by-link"><a href="http://thealpinepress.com/" style="COLOR:#C0C0C0;text-decoration:none;" title="Widget by The Alpine Press">TAP</a></div>');
    }  
  }
  
/**
 *  User Link Function
 *  
 *  @ Since 1.2.2
 */
  function add_user_link(){
    if( $this->check_active_result('userlink') ){
      $userlink = $this->get_active_result('userlink');
      if($this->get_active_option('widget_alignment') == 'center'){                          //  Optional: Set text alignment (left/right) or center
        $this->add('<div id="'.$this->get_private('wid').'-display-link" class="AlpinePhotoTiles-display-link-container" ');
        $this->add('style="width:100%;margin:0px auto;">'.$userlink.'</div>');
      }
      else{
        $this->add('<div id="'.$this->get_private('wid').'-display-link" class="AlpinePhotoTiles-display-link-container" ');
        $this->add('style="float:'.$this->get_active_option('widget_alignment').';max-width:'.$this->get_active_option('widget_max_width').'%;"><center>'.$userlink.'</center></div>'); 
        $this->add('<div class="AlpinePhotoTiles_breakline"></div>'); // Only breakline if floating
      }
    }
  }
  
/**
 *  Setup Lightbox call
 *  
 *  @ Since 1.2.3
 *  @ Updated 1.2.6.5
 */
  function add_lightbox_call(){
    $src = $this->get_private('src');
    $lightbox = $this->get_option('general_lightbox');
    $prevent = $this->get_option('general_lightbox_no_load');
    $check = ($lightbox=='fancybox'?'fancybox':($lightbox=='prettyphoto'?'prettyPhoto':($lightbox=='colorbox'?'colorbox':'fancyboxForAlpine')));
    if( empty($prevent) && $this->check_active_option($src.'_image_link_option') && $this->get_active_option($src.'_image_link_option') == 'fancybox' ){
      $lightScript = $this->get_script( $lightbox );
      $lightStyle = $this->get_style( $lightbox );
      if( !empty($lightScript) && !empty($lightStyle) ){
        $lightCall = $this->get_lightbox_call();
        $lightboxSetup = "
      if( !jQuery().".$check." ){
        var css = '".$lightStyle."';
        var link = jQuery(document.createElement('link')).attr({'rel':'stylesheet','href':css,'type':'text/css','media':'screen'});
        jQuery.getScript('".($lightScript)."', function(){
          if(document.createStyleSheet){
            document.createStyleSheet(css);
          }else{
            jQuery('head').append(link);
          }
          ".$lightCall."
        }); // Close getScript
      }else{
        ".$lightCall."
      }
    ";
        $this->add("
  <script>
  // Check for on() ( jQuery 1.7+ )
  if( jQuery.isFunction( jQuery(window).on ) ){
    jQuery(window).on('load', function(){".$lightboxSetup."}); // Close on()
  }else{
    // Otherwise, use bind()
    jQuery(window).bind('load', function(){".$lightboxSetup."}); // Close bind()
  }
  </script>"); 
      }
    }
  }
  
/**
 *  Get Lightbox Call
 *  
 *  @ Since 1.2.3
 *  @ Updated 1.2.5
 */
  function get_lightbox_call(){
    $this->set_lightbox_rel();
  
    $lightbox = $this->get_option('general_lightbox');
    $lightbox_style = $this->get_option('general_lightbox_params');
    $lightbox_style = str_replace( array("{","}"), "", $lightbox_style);
    
    $setRel = "jQuery( '#".$this->get_private('wid')."-AlpinePhotoTiles_container a.AlpinePhotoTiles-lightbox' ).attr( 'rel', '".$this->get_active_option('rel')."' );";
    
    if( 'fancybox' == $lightbox ){
      $default = "titleShow: false, overlayOpacity: .8, overlayColor: '#000', titleShow: true, titlePosition: 'inside'";
      $lightbox_style = (!empty($lightbox_style)? $default.','.$lightbox_style : $default );
      return $setRel."if(jQuery().fancybox){jQuery( 'a[rel^=\'".$this->get_active_option('rel')."\']' ).fancybox( { ".$lightbox_style." } );}";  
    }elseif( 'prettyphoto' == $lightbox ){
      //theme: 'pp_default', /* light_rounded / dark_rounded / light_square / dark_square / facebook
      $default = "theme:'facebook',social_tools:false, show_title:true";
      $lightbox_style = (!empty($lightbox_style)? $default.','.$lightbox_style : $default );
      return $setRel."if(jQuery().prettyPhoto){jQuery( 'a[rel^=\'".$this->get_active_option('rel')."\']' ).prettyPhoto({ ".$lightbox_style." });}";  
    }elseif( 'colorbox' == $lightbox ){
      $default = "maxHeight:'85%'";
      $lightbox_style = (!empty($lightbox_style)? $default.','.$lightbox_style : $default );
      return $setRel."if(jQuery().colorbox){jQuery( 'a[rel^=\'".$this->get_active_option('rel')."\']' ).colorbox( {".$lightbox_style."} );}";  
    }elseif( 'alpine-fancybox' == $lightbox ){
      $default = "titleShow: false, overlayOpacity: .8, overlayColor: '#000', titleShow: true, titlePosition: 'inside'";
      $lightbox_style = (!empty($lightbox_style)? $default.','.$lightbox_style : $default );
      return $setRel."if(jQuery().fancyboxForAlpine){jQuery( 'a[rel^=\'".$this->get_active_option('rel')."\']' ).fancyboxForAlpine( { ".$lightbox_style." } );}";  
    }
    return "";
  }
  
 /**
  *  Set Lightbox "rel"
  *  
  *  @ Since 1.2.3
  */
  function set_lightbox_rel(){
    $lightbox = $this->get_option('general_lightbox');
    $custom = $this->get_option('hidden_lightbox_custom_rel');
    if( !empty($custom) && $this->check_active_option('custom_lightbox_rel') ){
      $rel = $this->get_active_option('custom_lightbox_rel');
      $rel = str_replace('{rtsq}',']',$rel); // Decode right and left square brackets
      $rel = str_replace('{ltsq}','[',$rel);
    }elseif( 'fancybox' == $lightbox ){
      $rel = 'alpine-fancybox-'.$this->get_private('wid');
    }elseif( 'prettyphoto' == $lightbox ){
      $rel = 'alpine-prettyphoto['.$this->get_private('wid').']';
    }elseif( 'colorbox' == $lightbox ){
      $rel = 'alpine-colorbox['.$this->get_private('wid').']';
    }else{
      $rel = 'alpine-fancybox-safemode-'.$this->get_private('wid');
    }
    $this->set_active_option('rel',$rel);
  }


}





?>
