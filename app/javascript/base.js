var selfoss = {
    
    /**
     * current settings
     */
    params: {
        username: '',
        password: '',
        authtype: 'none',
        url: '',
        
        offset: 0,
        items: 25,
        starred: false,
        search: false
    },
    
    
    /**
     * mutex for mark as read started
     */
    markAsReadStarted: false,
    
    
    /**
     * mark as read was done till this id
     */
    markAsReadUntil: false,
    
    
    /**
     * jquery domready init
     */
    init: function() {
        $(document).ready(function() {
            $.support.cors = true;
            selfoss.events();
            
            $.mobile.allowCrossDomainPages = true;
            $.mobile.fixedToolbars.setTouchToggleEnabled(false);
            $.mobile.touchOverflowEnabled = false;
            
            selfoss.loadSettings();
        });
    },
    
    
    /**
     * events
     */
    events: function() {
        // events before page loading
        $(document).bind("pagebeforechange", selfoss.eventPageLoading);
    
        // login
        $('#login-submit').click(function() {
            $.mobile.showPageLoadingMsg();
            selfoss.saveSettings();
            
            // reset settings
            selfoss.params.offset = 0;
            selfoss.params.starred = false;
            selfoss.params.search = '';
            
            // purge all old cached items and then refresh itemlist
            selfoss.service.purgeItems();
            selfoss.refreshItems();
        });

        // logout
        $('#nav-logout').click(function() {
            $.mobile.changePage('#login');
        });
        
        // starred option
        $('#nav-starred').click(function() {
            $(this).toggleClass('ui-btn-up-e');
            $(this).toggleClass('ui-btn-up-a');
            
            selfoss.params.offset = 0;
            selfoss.params.starred = false;
            if($(this).hasClass('ui-btn-up-e'))
                selfoss.params.starred = true;
            
            selfoss.refreshItems();
        });
        
        // refresh (load entries)
        $('#nav-refresh').click(function() {
            selfoss.params.offset = 0;
            selfoss.refreshItems();
        });
        
        // search
        $('#search-submit').click(function() {
            selfoss.params.offset = 0;
            selfoss.params.search = $('#search').val();
            selfoss.refreshItems();
        });
        
        // scroll: mark as read
        $(document).scroll(function () {
            if(selfoss.markAsReadStarted==false 
                    && $.mobile.activePage.attr('id')=="stream") {
                selfoss.markAsReadStarted = true;
                window.setTimeout(
                    function() {
                        // check whether we have to load more
                        if ($(document).height()-$(window).height() <= $(window).scrollTop()) {
                            selfoss.params.offset = selfoss.params.offset + selfoss.params.items;
                            selfoss.refreshItems();
                        }
                        
                        selfoss.markAsReadStarted = false;
                        
                        // mark items as read
                        selfoss.markAsRead();
                    },
                    1000
                );
            }
        });
                
        // starr/unstarr
        $('#content-starred').click(function() {
            var button = $('#content-starred');
            var id = button.data('id');
            if(typeof id=="undefined")
                return selfoss.helpers.showMessage('no id set');
            
            if(selfoss.service.getItem(id).starred==false) {
                selfoss.service.starr(selfoss.params, id, function() {
                    selfoss.helpers.formatAsStarredButton();
                });
            } else {
                selfoss.service.unstarr(selfoss.params, id, function() {
                    selfoss.helpers.formatAsUnstarredButton();
                });
            }
        });
        
        // show images
        $('#content-showimages').click(function() {
            var content = $('#content-content').html();
            content = content.replace(/<img([^<]+)ref=(['\"])([^\"']*)(['\"])([^<]*)>/ig,"<img$1src='$3'$5>");
            $('#content-content').html(content);
            $(this).hide();
        });
    },
    
    
    /**
     * page loading event handler
     */
    eventPageLoading: function(e, data) {
        if(typeof data.toPage == "string")
            return;
        
        var page = $(data.toPage).attr('id');
        
        // fill login form
        if(page=="login")
            selfoss.loadSettings();
    },
    
    
    /**
     * load settings login form
     */
    loadSettings: function() {
        $('#username').val(window.localStorage.getItem("username"));
        $('#password').val(window.localStorage.getItem("password"));
        $('#authtype').val(window.localStorage.getItem("authtype"));
        $('#url').val(window.localStorage.getItem("url"));
        
        $('#authtype').selectmenu('refresh');
    },
    
    
    /**
     * save settings login form
     */
    saveSettings: function() {
        window.localStorage.setItem("username", $('#username').val());
        window.localStorage.setItem("password", $('#password').val());
        window.localStorage.setItem("authtype", $('#authtype').val());
        window.localStorage.setItem("url", $('#url').val());
        
        selfoss.params.username = $('#username').val();
        selfoss.params.password = $('#password').val();
        selfoss.params.authtype = $('#authtype').val();
        selfoss.params.url = selfoss.helpers.prependSlash($('#url').val());
    },
    
    
    /**
     * refresh itemlist
     */
    refreshItems: function() {
        $.mobile.showPageLoadingMsg();
        selfoss.service.loadItems(selfoss.params, function(items) {
            selfoss.helpers.list(items);
            $.mobile.hidePageLoadingMsg();
            $.mobile.changePage('#stream');
        });
    },
    
    
    /**
     * mark as read after scroll event
     */
    markAsRead: function() {
        var lastVisibleItem = false;
        var lastItem = false;
        var allUnread = true;
        var top = $(window).scrollTop() + $(window).height();
        
        // get last visible item
        $('#stream-content li').each(function(index, item) {
            var it = $(item);
            
            if(allUnread==true && it.hasClass('unread')==true) {
                if(selfoss.markAsReadUntil==false 
                    || parseInt(it.attr('id').substr(4)) < selfoss.markAsReadUntil)
                    allUnread = false;
            }
            
            if(it.offset().top+it.height() > top && lastVisibleItem==false) {
                lastVisibleItem = lastItem;
                return false;
            }
            
            lastItem = it;
        });
        
        if(lastVisibleItem==false || allUnread==true)
            return;
        
        // mark items as read
        var id = lastItem.attr('id').substring(4);
        selfoss.service.markAsRead(selfoss.params, id);
    }
    
};
