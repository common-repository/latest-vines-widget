<?php
/*
Plugin Name: Latest Vines
Plugin URI: http://wordpress.org/extend/plugins/latest-vines-widget/
Description: Provides a sidebar widget showing your latest Vines 
Author: Tim Whitlock
Version: 1.1.0
Author URI: http://timwhitlock.info/
*/




/**
 * Get plugin base URL path.
 */
function latest_vines_baseurl(){
    static $url;
    if( ! isset($url) ){
        $here = __FILE__;
        if( 0 !== strpos( WP_CONTENT_DIR, $here ) ){
            // something along this path has been symlinked into the document path
            // temporary measure assumes name of plugin folder is unchanged.
            $here = WP_CONTENT_DIR.'/plugins/latest-vines-widget/latest-vines.php';
        }
        $url = plugins_url( '', $here );
    }
    return $url;
}



/**
 * Scrape Vine URL for meta properties 
 */
function latest_vines_pull_meta( $v ){
    $url = 'http://vine.co/v/'.$v;
    $cachekey = 'vines_meta_'.$v;
    $meta = _twitter_api_cache_get($cachekey) or $meta = null;
    if( ! isset($meta) || ! is_array($meta) ){
        $http = wp_remote_get( $url );
        $meta = array();
        if( $http instanceof WP_Error ){
            foreach( $http->get_error_messages() as $message ){
                wp_trigger_error( $message, E_USER_NOTICE );
            }
        }
        else if( ! preg_match_all('!<meta property="twitter:([\w:]+)" content="([^"]+)"!', $http['body'], $metas, PREG_SET_ORDER ) ){
            trigger_error('Failed to pull meta data from '.$url, E_USER_NOTICE );
        }
        else {
            foreach( $metas as $m ){
                $meta[$m[1]] = $m[2];
            }
            // permanent cache for meta, as it supposedly doesn't ever change
            if( isset($cachekey) ){
                _twitter_api_cache_set( $cachekey, $meta, 0 );
            }
        }
    }
    $meta['id']  = $v;
    $meta['url'] = $url;
    return $meta;
}




/**
 * Render vine mata properties into HTML blocks
 */
function latest_vines_render_vine( array $meta ){
    //return '<pre>'.esc_html( var_export($meta,1) ).'</pre>';
    $img_u = esc_html( $meta['image'] );
    $img_w = $meta['player:width']; 
    $img_h = $meta['player:height']; 
    // render thumbnail via filter
    $thumb = apply_filters( 'latest_vines_render_thumb', $img_u, $img_w, $img_h  );
    if( $thumb === $img_u ){
        $img_w = $img_h = '145'; // 435 / 3 ??
        $thumb = '<img src="'.$img_u.'" width="'.$img_w.'" height="'.$img_h.'" style="display:block" />';
    }
    // render vine display via filter
    $html = apply_filters('latest_vines_render_vine', $meta['url'], $thumb, $meta );
    if( $html === $meta['url'] ){
        $id    = esc_html( 'latest-vines-'.$meta['id'] );
        $url   = esc_html( $meta['url'] );
        $mp4   = esc_html( $meta['player:stream'] );
        $jpg   = esc_html( $meta['image'] );
        $card  = esc_html( $meta['player'] );
        $title = esc_html( $meta['description'] );
        // use tweeted date as relative time 
        function_exists('twitter_api_relative_date') or twitter_api_include('utils');
        $date  = esc_html( twitter_api_relative_date($meta['tweeted_at']) );
        // render final vine block
        $html = '<div class="vine">
                   <div class="vine-video" id="'.$id.'" data-mp4="'.$mp4.'" data-jpg="'.$jpg.'">
                     <a href="'.$url.'" target="_blank" rel="no-follow" class="vine-thumb" style="display:block">'.$thumb.'</a>
                   </div>
                   <div class="vine-details">
                     <p class="vine-title">'.$title.'</p>
                     <p><a class="vine-date" href="'.$url.'" target="_blank">'.$date.'</a></p>
                   </div>
                 </div>';
    }
    return $html;
}





/**
 * @param string Twitter handle to search for vines
 * @param int number of vines to try and fetch
 * @param int number of tweets to search before giving up trying to get $num
 * @return array blocks of HTML for each vine
 */
function latest_vines_render( $screen_name, $num, $max ){
    try {
        if( ! function_exists('twitter_api_get') ){
            require_once dirname(__FILE__).'/lib/twitter-api.php';
        }
        if( ! function_exists('_twitter_api_cache_get') ){
            twitter_api_include('core');
        }
        // caching full data set, not just twitter api caching
        $cachekey = 'vines_'.$screen_name.'_'.$num;
        $vines = _twitter_api_cache_get($cachekey) or $vines = null;
        if( ! isset($vines) || ! is_array($vines) ) {
            $nstat = 0;
            $nvine = 0;
            $vines = array();
            // search user timeline for vines in blocks of 100 at most (3,200 allowed by API)
            $count = min( 100, max( $num, $max ) );
            $trim_user = true;
            $include_rts = false;
            $exclude_replies = true;
            $params = compact('count','exclude_replies','include_rts','trim_user','screen_name');
            while( $count ){
                foreach( twitter_api_get('statuses/user_timeline', $params ) as $status ){
                    if( ++$nstat > $max ){
                        // exhausted safety number of tweets to search
                        break 2;
                    }
                    if( isset($status['entities']['urls']) ){
                        foreach( $status['entities']['urls'] as $u ){
                            if( preg_match('!^https?://vine.co/v/([^/]+)!', $u['expanded_url'], $u ) ){
                                list( $url, $v ) = $u;
                                if( ! isset($vines[$v]) ){
                                    $vines[$v] = latest_vines_pull_meta( $v );
                                    $vines[$v]['tweeted_at'] = $status['created_at']; // <- vines have no temporal data - tweets do
                                    if( ++$nvine >= $num ){
                                        // exhausted maximum number we want to display
                                        break 3;
                                    }
                                } 
                            }
                        }
                    }
                }
                // prep poor man's paging using max_id (which will start next batch - ho hum)
                // deliberately no decrementing this in case of 32 bit systems, etc.. 
                $params['max_id'] = $status['id_str'];
            }
            // cache for ten minutes if enabled
            // @todo configure cache length
            if( isset($cachekey) && $vines ){
                _twitter_api_cache_set( $cachekey, $vines, 600 );
            }
        }
        // render what vines we have
        $rendered = array();
        foreach( $vines as $v => $meta ){
            $rendered[] = latest_vines_render_vine( $meta );
        }
        return $rendered;
    }
    catch( Exception $Ex ){
        return array( '<p class="tweet-text"><strong>Error:</strong> '.esc_html($Ex->getMessage()).'</p>' );
    }
}




/**
 * latest vines widget class
 */
class Latest_Vines_Widget extends WP_Widget {
    
    /** @see WP_Widget::__construct */
    public function __construct( $id_base = false, $name = 'Latest Vines', $widget_options = array(), $control_options = array() ){
        if( ! function_exists('_twitter_api_init_l10n') ){
            require_once dirname(__FILE__).'/lib/twitter-api.php';
        }
        _twitter_api_init_l10n();
        $this->options = array(
            array (
                'name'  => 'title',
                'label' => __('Widget title'),
                'type'  => 'text'
            ),
            array (
                'name'  => 'screen_name',
                'label' => __('Twitter handle'),
                'type'  => 'text'
            ),
            array (
                'name'  => 'num',
                'label' => __('Number of vines to display'),
                'type'  => 'text'
            ),
            array (
                'name'  => 'max',
                'label' => __('Max tweets to search before giving up'),
                'type'  => 'text'
            ),
        );
        parent::__construct( $id_base, $name, $widget_options, $control_options );  
    }    
    
    /* ensure no missing keys in instance params */
    private function check_instance( $instance ){
        if( ! is_array($instance) ){
            $instance = array();
        }
        $instance += array (
            'title' => __('Latest Vines'),
            'screen_name' => '',
            'num' => '5',
            'max' => '100',
        );
        return $instance;
    }
    
    /** @see WP_Widget::form */
    public function form( $instance ) {
        $instance = $this->check_instance( $instance );
        foreach ( $this->options as $val ) {
            $elmid = $this->get_field_id( $val['name'] );
            $fname = $this->get_field_name($val['name']);
            $value = isset($instance[ $val['name'] ]) ? $instance[ $val['name'] ] : '';
            $label = '<label for="'.$elmid.'">'.$val['label'].'</label>';
            if( 'bool' === $val['type'] ){
                 $checked = $value ? ' checked="checked"' : '';
                 echo '<p><input type="checkbox" value="1" id="'.$elmid.'" name="'.$fname.'"'.$checked.' /> '.$label.'</p>';
            }
            else {
                $attrs = '';
                echo '<p>'.$label.'<br /><input class="widefat" type="text" value="'.esc_attr($value).'" id="'.$elmid.'" name="'.$fname.'" /></p>';
            }
        }
    }

    /** @see WP_Widget::widget */
    public function widget( array $args, $instance ) {
        extract( $this->check_instance($instance) );
        // title is themed via Wordpress widget theming techniques
        $title = $args['before_title'] . apply_filters('widget_title', $title) . $args['after_title'];
        // by default vines are rendered as an unordered list
        $items = latest_vines_render( $screen_name, (int) $num, (int) $max );
        $list  = apply_filters('latest_vines_render_list', $items );
        if( is_array($list) ){
            $list = '<ul><li>'.implode('</li><li>',$items).'</li></ul>';
        }
        // output widget applying filters to each element
        echo 
        $args['before_widget'], 
            $title,
            '<div class="latest-vines">', 
                apply_filters( 'latest_vines_render_before', '' ),
                $list,
                apply_filters( 'latest_vines_render_after', '' ),
            '</div>',
         $args['after_widget'];
    }
    
}
 


function latest_vines_register_widget(){
    return register_widget('Latest_Vines_Widget');
}

add_action( 'widgets_init', 'latest_vines_register_widget' );


if( is_admin() ){
    if( ! function_exists('twitter_api_get') ){
        require_once dirname(__FILE__).'/lib/twitter-api.php';
    }    
    // extra visibility of API settings link
    function latest_vines_plugin_row_meta( $links, $file ){
        if( false !== strpos($file,'/latest-vines.php') ){
            $links[] = '<a href="options-general.php?page=twitter-api-admin"><strong>Connect to Twitter</strong></a>';
        } 
        return $links;
    }
    add_action('plugin_row_meta', 'latest_vines_plugin_row_meta', 10, 2 );
}
else {

    // add public assets
    function latest_vines_enqueue_scripts( $whatever ){
        $js = latest_vines_baseurl().'/js';
        if( WP_DEBUG ){
            $vn = time();
            wp_enqueue_script( 'latest-vines', $js.'/latest-vines.js', array('jquery'), $vn, true );
        }
        else {
            $vn = '1.1.0';
            wp_enqueue_script( 'latest-vines', $js.'/latest-vines.min.js', array('jquery'), $vn, true );
        }
        return $whatever;
    }
    add_action( 'wp_enqueue_scripts', 'latest_vines_enqueue_scripts' );
    
}
