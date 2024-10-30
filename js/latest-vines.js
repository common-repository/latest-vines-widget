/*!
 * latest-vines.js 
 * @author Tim Whitlock http://timwhitlock.info 
 */
!function( window, document, $, undefined ){
    
    try {

        function stderr( e ){
            e = e.message|| String(e) || 'Unknown error';
            window.console && console.error && console.error( 'latest-vines: '+e );
        }
    
        if( ! $ ){
            throw new Error('jQuery required');
        }

        // find base URL by looking for self.
        var baseurl = function(){
            var i = -1, src, pos, scripts = document.getElementsByTagName('script');
            while( ++i < scripts.length ){
                src = scripts[i].getAttribute('src');
                pos = src.indexOf( '/js/latest-vines.');
                if( pos !== -1 ){
                    return src.substr( 0, pos );
                }
            }
            throw new Error('Failed to find baseurl');
        }();
        
        
        // event utility
        function killEvent( event ){
            event.preventDefault();
            event.stopPropagation();
            return false;
        }
        
        //  touch support
        var msPointerEnabled  = window.navigator.msPointerEnabled,
            EVENT_TOUCH_START = msPointerEnabled ? 'MSPointerDown' : 'touchstart', 
            //EVENT_TOUCH_MOVE  = msPointerEnabled ? 'MSPointerMove' : 'touchmove', 
            EVENT_TOUCH_END   = msPointerEnabled ? 'MSPointerUp'   : 'touchend';
        
        // find all vine divs and add handler to link
        var nvines = 0;
        $('div.vine').each( function( i, elWrap ){
            var div = $(elWrap).children('div.vine-video'),
                id = div.attr('id');
            if( 0 !== id.indexOf('latest-vines-') ){
                return;
            }
            nvines++;
            var player,
                playing,
                touching,
                vidId = id + '-video',
                div = $(elWrap).children('div.vine-video'),
                link = div.children('a.vine-thumb'),
                size = String( link.children('img').outerWidth() ),
                mp4 = div.attr('data-mp4'),
                jpg = div.attr('data-jpg'),
                vid = $('<video class="video-js vjs-default-skin" id="'+vidId+'"><source src="'+mp4+'" type="video/mp4"></source></video>'),
                opts = {
                    controls: false,
                    preload: false,
                    poster: jpg,
                    loop: true,
                    width: size,
                    height: size
               };
           // init function replaces link containing img thumbnail with video
           function init(){
                div.addClass('vine-loading');
                vid.css('width',size+'px').css('height',size+'px').attr('width',size).attr('height',size);
                vid.replaceAll( link );
                // <video> is in DOM - initialize video.js for flash fallback
                player = _V_( vidId, opts, function(){
                    div.addClass('vine-initialized');
                } );
                player.on('loadeddata', function(){
                    div.addClass('vine-loaded').removeClass('vine-loading');
                    //flash.length && flash.show() && thumb.hide();
                } );
                player.on('play', function(){
                    playing = true;
                } );
                player.on('pause', function(){
                    playing = false;
                } );
                player.on('ended', function(){
                    playing = false;
                } );
                /*/ Fix Flash problems
                var thumb, flash = div.find('object');
                if( flash.length ){
                    flash.hide();
                    thumb = div.find('div.vjs-poster').show()
                        .css('width',size+'px')
                        .css('height',size+'px')
                        .css('background-size',size+'px');
                }*/
           }
           function play(){
               if( window._V_ ){
                   if( ! player ){
                       opts.autoplay = true;
                       init();
                   }
                   else if( ! playing ){
                       player.play();
                   }
               }
           }
           function pause(){
               if( player ){
                   player.pause();
               }
           }
           // stop actually destroys video, and puts thumbnail back again
           function stop(){
               if( player ){
                   vid.replaceWith( link );
                   div.removeClass('vine-loaded');
                   player = playing = null;
               }
           }
           /*/ convert immediately if video.js available?
           if( window._V_ ){
                opts.autoplay = false;
                init();
            }*/
            // toggle video play with mouseover/leave
            div.mouseover( function(event){ 
                if( touching ){
                    return false;
                }
                var nodeName = event.target.tagName.toUpperCase();
                if( ( 'IMG' === nodeName && ! player ) || ( /(VIDEO|OBJECT)/.test(nodeName) && ! playing ) ){
                    play();
                }
                return true;
            } )
            .mouseout( function(event){
                if( touching ){
                    return false;
                }
                var nodeName = event.target.tagName.toUpperCase();
                if( /(VIDEO|OBJECT)/.test(nodeName) && playing ){
                    pause();
                }
                return true;
            } )
            // open original link on click
            .click( function(event){
                playing && player.pause();
                touching = false;
                window.open( link.attr('href') );
                return killEvent(event);
            } )
            // no real touch support, just prevent mouseover, but allow click
            if( elWrap.addEventListener ){ 
                function bind( type, callback ){
                    elWrap.addEventListener( type, callback, false );
                }
                function onTouchStart( event ){
                    touching = true;
                    return true;
                }
                function onTouchEnd( event ){
                    touching = false;
                    return true;
                }
                bind( EVENT_TOUCH_END, onTouchStart );
                bind( EVENT_TOUCH_START, onTouchStart );
            }
            // next vine ..
        } );


        // dynamic load of runtime assets only if needed
        if( nvines && ! window._V_ ){
            $('<script src="'+baseurl+'/js/video.js"></script>').appendTo( document.body );
            //$('<link href="'+baseurl+'/css/latest-vines.css" rel="stylesheet" />').appendTo(document.body);            
        }
        
    }
    catch( e ){
        stderr(e);
    }
    
}( window, document, window.jQuery );
