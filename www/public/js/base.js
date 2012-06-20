/**
 * base javascript application
 *
 * @package    public_js
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
var selfoss = {

    /**
     * touch scroller object for refreshing on resize
     * @var bool
     */
    scroller: false,


    /**
     * current offset for stream items
     * @var int
     */
    offset: 0,

    
    /**
     * items per page
     * @var int
     */
    itemsPerPage: 0,


    /**
     * indicates whether the mark as read
     * event was started
     * @var bool
     */
    markAsReadStarted: false,

    
    /**
     * indicates which was the last marked item
     * @var bool|false
     */
    markAsReadUntil: false,
    

    /**
     * initializes the application
     *
     * @return void
     */
    init: function() {
        // before elasitc css framework init
        jQuery(document).bind('elastic:beforeInitialize', function() {
            // smartphone version
            if($(window).width()<600) {
                $('body, html').addClass('mobile');
                $('#stream, nav').removeClass('on-3');
                $('#stream, nav').addClass('on-1');
            }
        });
        
        // after elastic css framework init
        jQuery(document).bind('elastic:initialize', function() {
            
            if($('html').hasClass('mobile')==true) {
                $('nav, #stream').css('visibility','visible');
                $('#stream').css('margin-top', '0');
            } else {
                $('nav, #stream').css('visibility','visible');
                $('#wrapper').css('height', $(window).height()+'px');
                $('#stream').css('margin-top', $(window).height()+'px');
            }
            
            // init touch scroller and anmiate stream
            if($('html').hasClass('mobile')==false && $('html').hasClass('touch')) {
                selfoss.scroller = new iScroll('wrapper', { 
                    hScroll: false, 
                    bounce: false, 
                    onScrollEnd: selfoss.markAsRead
                });
            
            // no touch devices
            } else {
                $('#wrapper').unbind('scroll').scroll(function () {
                    if(selfoss.markAsReadStarted==false) {
                        selfoss.markAsReadStarted = true;
                        window.setTimeout(
                            function() {
                                var wrapper = $('#wrapper');
                                if($('.stream-more').length > 0 
                                       && $('.stream-more').position().top < wrapper.height() + wrapper.scrollTop() 
                                       && $('.stream-more').hasClass('loading')==false)
                                    $('.stream-more').click();
                            
                                selfoss.markAsReadStarted = false;
                                selfoss.markAsRead();
                            },
                            1000
                        );
                    }
                });
            }
            
            selfoss.setStreamPosition(true);
            $(window).resize(selfoss.resize);
            
            selfoss.itemsPerPage = $('.entry').length;
            
            // init events
            selfoss.events();
            
            // init shortcut handler
            selfoss.shortcuts();
        });
    },
    
    
    /**
     * stream position and height
     *
     * @return void
     * @param bool animate or not
     */
    setStreamPosition: function(animate) {
        var windowHeight = $(window).height();
        var streamHeight = $('.stream-content').height();
        var newHeight = { marginTop: $('body').hasClass('mobile') ? 0 : 10 };
        
        // set top margin
        if(streamHeight < windowHeight)
            newHeight = { marginTop: (windowHeight - streamHeight - 50)+'px' };
        
        // animate
        if(typeof animate != "undefined")
            $('#stream').animate(newHeight);
        else
            $('#stream').css(newHeight);
    
        // timeout recommended by iScroll developer
        setTimeout(function () {
            Elastic.refresh($('#stream'));
            $('.full-height').each(function(item, el) {
                $(el).css({height: streamHeight+'px'});
            });
            if(selfoss.scroller!=false) {
                selfoss.scroller.refresh();
            }
        }, 0);
    },

    
    /**
     * event handler for window resize
     *
     * @return void
     */
    resize: function() {
        if($('html').hasClass('mobile')==false)
            $('#wrapper').css('height', $(window).height()+'px');
        selfoss.setStreamPosition();
    },
    
    
    /**
     * init events
     *
     * @return void
     */
    events: function() {
        // select entry
        $('.entry').unbind('click').click(function() {
            $('.entry.selected').removeClass('selected');
            $(this).addClass('selected');
        });
        
        // fix stream size after loading an image
        $('.entry-content img').unbind('load').load(function() {
            selfoss.setStreamPosition();
        });
        
        // show/hide entry content
        $('.entry-title').unbind('click').click(function() {
            var next = $(this).parent().find('.entry-content');
            if($.trim(next.html()).length==0)
                return;
            if(next.is(':visible'))
                next.slideUp('fast', selfoss.setStreamPosition);
            else
                next.slideDown('fast', selfoss.setStreamPosition);
            next.lazyLoadImages();
        });
        
        // search
        $('#search-submit').unbind('click').click(function() {
            $(this).parents('form').submit();
        });

        // more
        $('.stream-more').unbind('click').click(function () {
            var streamMore = $(this);
            streamMore.addClass('loading');
            
            var data = {};
            if($('.nav-favorites a').hasClass('active'))
                data.starred = 1;
                
            var search = $('#search').val();
            if(search.length!=0)
                data.search = search;
                
            selfoss.offset += selfoss.itemsPerPage;
            data.offset = selfoss.offset;
            
            $.ajax({
                url: $('base').attr('href'),
                type: 'GET',
                data: data,
                success: function(data) {
                    $('.stream-more').replaceWith(data);
                    selfoss.resize();
                    selfoss.events();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    streamMore.removeClass('loading');
                    alert('Load more error: '+errorThrown);
                }
            });
        });
        
        // only loggedin users
        if($('body').hasClass('loggedin')==true) {
            // add new source
            $('.source-add').unbind('click').click(function() {
                var sourceAdd = $(this);
                $.post($('base').attr('href')+'source',null,function(result) {
                    sourceAdd.before(result);
                    if(($('.source').length+1)%2==0)
                        $('.source:last').addClass('even');
                    selfoss.resize();
                    selfoss.events();
                });
            });
            
            // save source
            $('.source-save').unbind('click').click(function() {
                var parent = $(this).parents('.source');
                parent.find('span.error').remove();
                parent.find('.error').removeClass('error');
                
                var id = false;
                parent.find('.source-action').addClass('loading');
                if(typeof parent.attr('id') != "undefined")
                    id = parent.attr('id').substr(6);
                    
                var url = $('base').attr('href')+'source';
                if(id!=false)
                    url = url + '/' + id;
                
                $.ajax({
                    url: url,
                    type: 'PUT',
                    dataType: 'json',
                    data: selfoss.getValues(parent),
                    success: function(response) {
                        parent.find('.source-action').removeClass('loading').addClass('saved');
                        var id = response['id'];
                        parent.find('.source-save').html('saved');
                        window.setTimeout(function() {
                            parent.find('.source-save').html('save');
                            parent.find('.source-action').removeClass('saved');
                        }, 10000);
                        parent.attr('id', 'source'+id);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        parent.find('.source-action').removeClass('loading');
                        selfoss.showErrors(parent, $.parseJSON(jqXHR.responseText));
                    }
                });
            });
            
            // delete source
            $('.source-delete').unbind('click').click(function() {
                var answer = confirm('really delete this source?');
                if(answer==false)
                    return;
                
                var parent = $(this).parents('.source');
                parent.find('li.error').remove();
                
                var id = false;
                parent.find('.source-action').addClass('loading');
                if(typeof parent.attr('id') != "undefined")
                    id = parent.attr('id').substr(6);
                
                // remove unsaved new source
                if(id==false) {
                    parent.fadeOut('fast', function() {
                        $(this).remove();
                        selfoss.resize();
                        selfoss.fixSourcesEvenOdd();
                    });
                
                // delete existing source
                } else {
                   $.ajax({
                    url: $('base').attr('href')+'source/'+id,
                    type: 'DELETE',
                    success: function() {
                        parent.fadeOut('fast', function() {
                            $(this).remove();
                            selfoss.resize();
                            selfoss.fixSourcesEvenOdd();
                        });
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        parent.removeClass('loading');
                        parent.append('<li class="error">'+errorThrown+'</li>');
                    }
                   }); 
                }
            });
            
            // show params
            $('.source-showparams').unbind('click').click(function() {
                $(this).parent().next().show();
                $(this).remove();
                selfoss.setStreamPosition();
            });
            
            // select new source spout type
            $('.source-spout').unbind('change').change(function() {
                var val = $(this).val();
                var params = $(this).parents('ul').find('.source-params');
                $(this).parents('ul').find('.source-showparams').remove();
                params.show();
                if($.trim(val).length==0) {
                
                    params.html('');
                    selfoss.resize();
                    return;
                }
                params.addClass('loading');
                $.ajax({
                    url: $('base').attr('href')+'source/params/',
                    data: { spout: val},
                    type: 'GET',
                    success: function(data) {
                        params.removeClass('loading').html(data);
                        selfoss.resize();
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        params.removeClass('loading').append('<li class="error">'+errorThrown+'</li>');
                        selfoss.resize();
                    }
                });
            });
            
            // starr/unstarr
            $('.entry-starr').unbind('click').click(function() {
                var parent = $(this).parents('.entry');
                var id = parent.attr('id').substr(5);
                
                var starr = $(this).hasClass('active')==false;
                if(starr)
                    $(this).addClass('active');
                else
                    $(this).removeClass('active');
                    
                $.ajax({
                    url: $('base').attr('href') + (starr ? 'starr/' : 'unstarr/') + id,
                    type: 'GET',
                    success: function(data) {
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        alert('Can not starr/unstarr item: '+errorThrown);
                    }
                });
            });
        }
    },
    
    
    /**
     * set even classes for entries (after removing or
     * adding a new entry)
     *
     * @return void
     */
    fixSourcesEvenOdd: function() {
        $('.source').each(function(index, item) {
            if(index%2==0)
                $(item).addClass('even');
            else
                $(item).removeClass('even');
        });
    },
    
    
    /**
     * insert error messages in form
     *
     * @return void
     * @param form target where input fields in
     * @param errors an array with all error messages
     */
    showErrors: function(form, errors) {
        $(form).find('span.error').remove();
        $.each(errors, function(key, val) {
            form.find('#'+key).addClass('error').parent('li').append('<span class="error">'+val+'</span>');
        });
    },
    
    
    /**
     * returns an array of id value pairs of all form elements in given element
     *
     * @return void
     * @param element containing the form elements
     */
    getValues: function(element) {
        var values = {};
        
        $(element).find(':input').each(function (i, el) {
            // get only input elements with id
            if($.trim($(el).attr('id')).length!=0) {
                values[$(el).attr('id')] = $(el).val();
                if($(el).attr('type')=='checkbox')
                    values[$(el).attr('id')] = $(el).attr('checked') ? 1 : 0;
            }
        });
        
        return values;
    },
    
    
    /**
     * register shortcuts
     */
    shortcuts: function() {  
        var options = {"disable_in_input": true};
        
        // next
        shortcut.add('Space', function() { selfoss.shortcuts_nextprev('next', true); return false; }, options);
        shortcut.add('n', function() { selfoss.shortcuts_nextprev('next', false); return false; }, options);
        shortcut.add('j', function() { selfoss.shortcuts_nextprev('next', true); return false; }, options);
        
        // prev
        shortcut.add('Shift+Space', function() { selfoss.shortcuts_nextprev('prev', true); return false; }, options);
        shortcut.add('p', function() { selfoss.shortcuts_nextprev('prev', false); return false; }, options);
        shortcut.add('k', function() { selfoss.shortcuts_nextprev('prev', true); return false; }, options);
        
        // star/unstar
        shortcut.add('s', function() {
            $('.entry.selected .entry-starr').click();
        }, options);
        
        // open target
        shortcut.add('v', function() {
            window.open($('.entry.selected .entry-source').attr('href'));
        }, options);
    },
    
    
    /**
     * get next/prev item
     * @param direction
     */
    shortcuts_nextprev: function(direction, open) {
        if(typeof direction == "undefined" || (direction!="next" && direction!="prev"))
            direction = "next";
       
        // helper functions
        var scroll = function(value) {
            // scroll down (negative value) and up (positive value)
            $('#wrapper').scrollTop($('#wrapper').scrollTop()+value);
        } 
        // select current        
        var old = $('.entry.selected');
        
        // select next/prev and save it to "current"
        if(direction=="next") {
            if(old.length==0) {
                current = $('.entry:eq(0)');
            } else {
                current = old.next().length==0 ? old : old.next();
            }
            
            // if distance between top border of "current"
            // and bottom border of the viewport is more than -70,
            // which means that "current" is out of view.
            //
            // I took -70 because of the bar at the bottom.
            if((current.offset().top - $(window).height()) > -70) {
                scroll(20);
                return;
            }
        } else {
            if(old.length==0) {
                return;
            }
            else {
                current = old.prev().length==0 ? old : old.prev();
            }
            
            // if distance between the top border of "current"
            // and top border of the viewport is negative,
            // which means "current" is out of view.
            if(current.offset().top < 0) {
                scroll(-20);
                return;
            }
        }

        // remove active
        old.removeClass('selected');
        old.find('.entry-content').removeClass('open');
        
        if(current.length==0)
            return;


        current.addClass('selected');
            
        // show content on message
        if(current.find('.message-full-content').length>0)
            current.click();
        
        // load more
        if(current.hasClass('stream-more'))
            current.click().removeClass('selected').prev().addClass('selected');
        
        // open?
        if(open && current.find('.entry-thumbnail').length==0) {
            var content = current.find('.entry-content');
            content.lazyLoadImages();
            content.addClass('open');
        }
        
        // scroll to element
        selfoss.shortcuts_autoscroll(current);
    },
    
    
    /**
     * autoscroll
     */
    shortcuts_autoscroll: function(next) {
        
        // scroll: get content size
        var contentsize = next.height()+80;
        
        var css = new Array(
            'padding-top',
            'padding-bottom',
            'border-top',
            'border-bottom',
            'margin-top',
            'margin-bottom'
        );
        
        $(css).each(function(i, item) {
            var val = parseInt(next.css(item));
            contentsize = isNaN(val) == false ? contentsize+val : contentsize;
        });
        
        var wrapper = $('#wrapper');
        var fold = wrapper.height() + wrapper.scrollTop();
        
        // scroll down
        if(fold <= next.position().top+contentsize)
            if(contentsize>wrapper.height())
                selfoss.scrollTo(next.position().top);
            else
                selfoss.scrollTo(wrapper.scrollTop()+contentsize);
        
        // scroll up
        var top = wrapper.scrollTop();
        if(top >= next.position().top)
            selfoss.scrollTo(next.position().top);
    },
    
    
    /**
     * mark items as read
     */
    markAsRead: function() {
        if($('body').hasClass('loggedin')==false)
            return;
    
        // get last visible item
        var lastVisibleItem = false;
        
        var height;
        if($('html').hasClass('mobile')==false && $('html').hasClass('touch'))
            height = this.wrapperH - 50;
        else
            height = $('#wrapper').height() - 50;
        
        var lastItem = false;
        var allUnread = true;
        $('.entry').each(function(index, item) {
            var it = $(item);
            if(allUnread==true && it.hasClass('unread')==true) {
                if(selfoss.markAsReadUntil==false || parseInt(it.attr('id').substr(5)) < selfoss.markAsReadUntil)
                    allUnread = false;
            }
                
            if(it.offset().top+it.height() > height && lastVisibleItem==false) {
                lastVisibleItem = lastItem;
                return false;
            }
            lastItem = it;
        });
        
        if(lastVisibleItem==false)
            return;
        
        if(allUnread==true)
            return;
        
        // mark items as read
        var id = lastVisibleItem.attr('id').substr(5);
        selfoss.markAsReadUntil = parseInt(id);
        $.ajax({
            url: $('base').attr('href') + 'mark/' + id,
            type: 'GET',
            error: function(jqXHR, textStatus, errorThrown) {
                //alert('Can not mark items as read: '+errorThrown);
            }
        });    
    },
    
    
    /**
     * scroll to
     */
    scrollTo: function(y) {
        if(selfoss.scroller!=false)
            selfoss.scroller.scrollToElement(y, 0, 100);
        else
            $('#wrapper').scrollTop(y);
    }
};


/**
 * load images on showing an message entry
 */
(function($){
$.fn.lazyLoadImages = function() {
    $(this).find('img').each(function(i, self) {
        $(self).attr('src', $(self).attr('ref'));
    });
}
})(jQuery);
