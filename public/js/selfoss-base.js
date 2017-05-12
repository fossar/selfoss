/**
 * base javascript application
 *
 * @package    public_js
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 */
var selfoss = {

    /**
     * current filter settings
     * @var mixed
     */
    filter: {
        offset: 0,
        fromDatetime: undefined,
        fromId: undefined,
        itemsPerPage: 0,
        search: '',
        type: 'newest',
        tag: '',
        source: '',
        sourcesNav: false,
        extraIds: [],
        ajax: true
    },

    /**
     * instance of the currently running XHR that is used to reload the items list
     */
    activeAjaxReq: null,
    
    /**
     * last stats update
     */
    lastSync: Date.now(),

    /**
     * last db timestamp known client side
     */
    lastUpdate: null,
    
    /**
     * the html title configured
     */
    htmlTitle: 'selfoss',

    /**
     * initialize application
     */
    init: function() {
        jQuery(document).ready(function() {
            // reduced init on login
            if($('#login').length>0) {
                $('#username').focus();
                return;
            }
        
            // set items per page
            selfoss.filter.itemsPerPage = $('#config').data('items_perpage');

            // read the html title configured
            selfoss.htmlTitle = $('#config').data('html_title')

            // init shares
            selfoss.shares.init($('#config').data('share'));

            // init events
            selfoss.events.init();

            // init FancyBox
            selfoss.initFancyBox();

            // init shortcut handler
            selfoss.shortcuts.init();

            // setup periodic stats reloader
            window.setInterval(selfoss.dbOnline.sync, 60*1000);

            window.setInterval(selfoss.ui.refreshEntryDatetimes, 60*1000);
        });
    },
    
    
    /**
     * returns an array of name value pairs of all form elements in given element
     *
     * @return void
     * @param element containing the form elements
     */
    getValues: function(element) {
        var values = {};
        
        $(element).find(':input').each(function (i, el) {
            // get only input elements with name
            if($.trim($(el).attr('name')).length!=0) {
                values[$(el).attr('name')] = $(el).val();
                if($(el).attr('type')=='checkbox')
                    values[$(el).attr('name')] = $(el).attr('checked') ? 1 : 0;
            }
        });
        
        return values;
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
            form.find("[name='"+key+"']").addClass('error').parent('li').append('<span class="error">'+val+'</span>');
        });
    },
    
    
    /**
     * indicates whether a mobile device is host
     *
     * @return true if device resolution smaller equals 1024
     */
    isMobile: function() {
        // first check useragent
        if((/iPhone|iPod|iPad|Android|BlackBerry/).test(navigator.userAgent))
            return true;
        
        // otherwise check resolution
        return selfoss.isTablet() || selfoss.isSmartphone();
    },
    
    
    /**
     * indicates whether a tablet is the device or not
     *
     * @return true if device resolution smaller equals 1024
     */
    isTablet: function() {
        if($(window).width()<=1024)
            return true;
        return false;
    },

    
    /**
     * indicates whether a tablet is the device or not
     *
     * @return true if device resolution smaller equals 1024
     */
    isSmartphone: function() {
        if($(window).width()<=640)
            return true;
        return false;
    },


    /**
     * reset filter
     *
     * @return void
     */
    filterReset: function() {
        selfoss.filter.offset = 0;
        selfoss.filter.fromDatetime = undefined;
        selfoss.filter.fromId = undefined;
        selfoss.filter.extraIds.length = 0;
    },


    /**
     * refresh stats.
     *
     * @return void
     * @param new all stats
     * @param new unread stats
     * @param new starred stats
     */
    refreshStats: function(all, unread, starred) {
        $('.nav-filter-newest span').html(all);
        $('.nav-filter-starred span').html(starred);

        selfoss.refreshUnread(unread);
    },

    
    /**
     * refresh unread stats.
     *
     * @return void
     * @param new unread stats
     */
    refreshUnread: function(unread) {
        $('span.unread-count').html(unread);

        // make unread itemcount red and show the unread count in the document
        // title
        if(unread>0) {
            $('span.unread-count').addClass('unread');
            $(document).attr('title', selfoss.htmlTitle+' ('+unread+')');
        } else {
            $('span.unread-count').removeClass('unread');
            $(document).attr('title', selfoss.htmlTitle);
        }
    },


    /**
     * refresh current tags.
     *
     * @return void
     */
    reloadTags: function() {
        $('#nav-tags').addClass('loading');
        $('#nav-tags li:not(:first)').remove();
        
        $.ajax({
            url: $('base').attr('href')+'tagslist',
            type: 'GET',
            success: function(data) {
                $('#nav-tags').append(data);
                selfoss.events.navigation();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                selfoss.ui.showError('Load tags error: '+
                                     textStatus+' '+errorThrown);
            },
            complete: function(jqXHR, textStatus) {
                $('#nav-tags').removeClass('loading');
            }
        });
    },
    
    
    /**
     * refresh taglist.
     *
     * @return void
     * @param tags the new taglist as html
     */
    refreshTags: function(tags) {
        $('.color').spectrum('destroy');
        $('#nav-tags li:not(:first)').remove();
        $('#nav-tags').append(tags);
        if( selfoss.filter.tag ) {
            if(!selfoss.db.isValidTag(selfoss.filter.tag))
                selfoss.ui.showError('Unknown tag: ' + selfoss.filter.tag);

            $('#nav-tags li:first').removeClass('active');
            $('#nav-tags > li').filter(function( index ) {
                if( $('.tag', this) )
                    return $('.tag', this).html() == selfoss.filter.tag;
                else
                    return false;
            }).addClass('active');
        } else
            $('.nav-tags-all').addClass('active');

        selfoss.events.navigation();
    },
    
    
    sourcesNavLoaded: false,

    /**
     * refresh sources list.
     *
     * @return void
     * @param sources the new sourceslist as html
     * @param currentSource the index of the active source
     */
    refreshSources: function(sources, currentSource) {
        $('#nav-sources li').remove();
        $('#nav-sources').append(sources);
        if( selfoss.filter.source ) {
            if(!selfoss.db.isValidSource(selfoss.filter.source))
                selfoss.ui.showError('Unknown source id: '
                                     + selfoss.filter.source);

            $('#source' + selfoss.filter.source).addClass('active');
            $('#nav-tags > li').removeClass('active');
        }

        selfoss.sourcesNavLoaded = true;
        if( $('#nav-sources-title').hasClass("nav-sources-collapsed") )
            $('#nav-sources-title').click(); // expand sources nav

        selfoss.events.navigation();
    },
    
    
    /**
     * anonymize links
     *
     * @return void
     * @param parent element
     */
    anonymize: function(parent) {
        var anonymizer = $('#config').data('anonymizer');
        if(anonymizer.length>0) {
            parent.find('a').each(function(i,link) {
                link = $(link);
                if(typeof link.attr('href') != "undefined" && link.attr('href').indexOf(anonymizer)!=0) {
                    link.attr('href', anonymizer + link.attr('href'));
                }
            });
        }
    },


    /**
     * Setup fancyBox image viewer
     * @param content element
     * @param int
     */
    setupFancyBox: function(content, id) {
        // Close existing fancyBoxes
        $.fancybox.close();
        var images = $(content).find('a[href$=".jpg"], a[href$=".jpeg"], a[href$=".png"], a[href$=".gif"], a[href$=".jpg:large"], a[href$=".jpeg:large"], a[href$=".png:large"], a[href$=".gif:large"]');
        $(images).attr('data-fancybox', 'gallery-'+id).unbind('click');
        $(images).attr('data-type', 'image');
    },


    /**
     * Initialize FancyBox globally
     */
    initFancyBox: function() {
        $.fancybox.defaults.hash = false;
    },


    /**
     * Mark all visible items as read
     */
    markVisibleRead: function () {
        var ids = new Array();
        $('.entry.unread').each(function(index, item) {
            ids.push( $(item).attr('id').substr(5) );
        });

        if( ids.length === 0 ) {
            $('.entry').remove();
            if( selfoss.filter.type == 'unread' &&
                parseInt($('span.unread-count').html()) > 0 )
                selfoss.dbOnline.reloadList()
            else
                selfoss.ui.refreshStreamButtons(true);
            return;
        }

        // show loading
        var content = $('#content');
        var articleList = content.html();
        $('#content').addClass('loading').html("");
        var hadMore = $('.stream-more').is(':visible');
        selfoss.ui.refreshStreamButtons();

        // close opened entry and list
        selfoss.events.setHash();
        selfoss.filterReset();

        $.ajax({
            url: $('base').attr('href') + 'mark',
            type: 'POST',
            dataType: 'json',
            data: {
                ids: ids
            },
            success: function(response) {
                $('.entry').removeClass('unread');

                // update unread stats
                var unreadstats = parseInt($('.nav-filter-unread span').html()) - ids.length;
                selfoss.refreshUnread(unreadstats);

                // hide nav on smartphone if visible
                if(selfoss.isSmartphone() && $('#nav').is(':visible')==true)
                    $('#nav-mobile-settings').click();

                // refresh list
                selfoss.dbOnline.reloadList();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                content.html(articleList);
                $('#content').removeClass('loading');
                selfoss.ui.refreshStreamButtons(true, true, hadMore);
                selfoss.events.entries();
                selfoss.ui.showError('Can not mark all visible item: '+
                                     textStatus+' '+errorThrown);
            }
        });
    }

};

selfoss.init();
