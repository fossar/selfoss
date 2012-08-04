/**
 * Service for selfoss server access
 */
selfoss.service = {

    items: [],
    
    /**
     * login
     */
    login: function(params) {
        $.ajax(selfoss.service.auth(params, {
            url: params.url + "api/login",
            type: 'POST',
            dataType: 'json',
            error: function(jqXHR, textStatus, errorThrown) {
                if(typeof params.onError != "undefined")
                    params.onError(errorThrown);
            },
            success: function(response) {
                if(response.success == false) {
                    if(typeof params.onError != "undefined") {
                        params.onError("Can't login. Please check your credentials!");
                    }
                } else {
                    if(typeof params.onSuccess != "undefined") {
                        params.onSuccess();
                    }
                }
            }
        }));
    },
    
    
    /**
     * downloads items
     */
    loadItems: function(params, callback) {
        // get ids of items which are still in cache
        var paramsWithIds = {};
        $.extend(paramsWithIds, params);
        paramsWithIds.ids = selfoss.service.getCachedIds();
        
        // load items from selfoss Server
        selfoss.service.loadItemsFromSelfossServer(paramsWithIds, callback);
    },
    
    
    /**
     * downloads items from selfoss server
     */
    loadItemsFromSelfossServer: function(params, callback) {
        $.ajax(selfoss.service.auth(params, {
            url: params.url + "api/items",
            type: 'POST',
            dataType: 'json',
            data: params,
            error: function(jqXHR, textStatus, errorThrown) {
                selfoss.helpers.showMessage("Communication error: " + errorThrown);
                selfoss.service.getCachedItems(params, callback);
            },
            success: function(response) {
                var baseUrl = params.url;
                if(params.authtype=='both' || params.authtype=='base') {
                    var search = params.url.indexOf('https://')!=-1 ? "https://" : "http://";
                    baseUrl = baseUrl.replace(search, search + params.username + ':' + params.password + "@");
                }
                
                for(var i=0;i<response.length;i++) {
                    response[i].id = parseInt(response[i].id);
                    response[i].source = parseInt(response[i].source);
                    response[i].starred = response[i].starred=="1" ? true : false;
                    response[i].unread = response[i].unread=="1" ? true : false;
                    
                    if(response[i].thumbnail.length!=0 && response[i].thumbnail!='0') {
                        var image = new Image();
                        image.src = baseUrl + 'thumbnails/' + response[i].thumbnail
                        response[i].thumbnail = image;
                    }
                    
                    if(response[i].icon.length!=0 && response[i].icon!='0') {
                        var image = new Image();
                        image.src = baseUrl + 'favicons/' + response[i].icon
                        response[i].icon = image;
                    }
                    
                    selfoss.service.items[selfoss.service.items.length] = response[i];
                }
                
                selfoss.service.sortItems();
                
                selfoss.service.getCachedItems(params, callback);
            }
        }));
    },
    
    
    /**
     * give cached items to given callback
     */
    getCachedItems: function(params, callback) {
        var items = [];
        var counter = 0;
        
        for(var i=params.offset; counter<params.offset+params.items && i<selfoss.service.items.length; i++) {
            if(params.starred==true && selfoss.service.items[i].starred==false)
                continue;
                
            if(params.search!=false) {
                if(selfoss.service.items[i].title.indexOf(params.search)==-1 && 
                    selfoss.service.items[i].content.indexOf(params.search)==-1) {
                    continue;
                }
            }
            
            items[items.length] = selfoss.service.items[i];
            counter++;
        }
        
        callback(items);
    },
    
    
    /**
     * return cached item
     */
    getItem: function(id) {
        var rItem = false;
        $.each(selfoss.service.items, function(index, item) {
            if(item.id==id) {
                rItem = item;
                return false;
            }
        });
        return rItem;
    },
    
    
    /**
     * return item ids from cache
     */
    getCachedIds: function() {
        var ids = [];
        $.each(selfoss.service.items, function(index, item) {
            ids[ids.length] = item.id;
        });
        return ids;
    },
    
    
    /**
     * sort itemlist
     */
    sortItems: function() {
        selfoss.service.items.sort(function(a, b) {
            return b.id - a.id;
        });
    },
    
    
    /**
     * purge items
     */
    purgeItems: function() {
        selfoss.service.items = [];
    },
    
    
    /**
     * starr item
     */
    starr: function(params, id, callback) {
        $.ajax(selfoss.service.auth(params, {
            'url': params.url + "api/starr/" + id,
            'type': 'GET',
            'dataType': 'json',
            'error': function(jqXHR, textStatus, errorThrown) {
                selfoss.helpers.showMessage("Communication error: " + errorThrown);
            },
            'success': function(response) {
                // set cached item to starred
                selfoss.service.getItem(id).starred = true;
                callback();
            }
        }));
    },
    
    
    /**
     * unstarr item
     */
    unstarr: function(params, id, callback) {
        $.ajax(selfoss.service.auth(params, {
            'url': params.url + "api/unstarr/" + id,
            'type': 'GET',
            'dataType': 'json',
            'error': function(jqXHR, textStatus, errorThrown) {
                selfoss.helpers.showMessage("Communication error: " + errorThrown);
            },
            'success': function(response) {
                // set cached item to unstarred
                selfoss.service.getItem(id).starred = false;
                callback();
            }
        }));
    },
    
    
    /**
     * mark as read
     */
    markAsRead: function(params, id) {
        $.ajax(selfoss.service.auth(params, {
            'url': params.url + "api/mark/" + id,
            'type': 'GET',
            'success': function(response) {
                // set cached items to unread
                for(var i=0;i<selfoss.service.items.length;i++) {
                    if(selfoss.service.items[i].id < id)
                        break;
                    selfoss.service.items[i].unread = false;
                }
            }
        }));
    },
    
    
    /**
     * add authentication params
     */
    auth: function(params, data) {
        if(params.authtype=='both' || params.authtype=='login') {
            data.data.username = params.username;
            data.data.password = params.password;
        }
        
        if(params.authtype=='both' || params.authtype=='base') {
            data.username = params.username;
            data.password = params.password;
        }
        
        return data;
    }
};
