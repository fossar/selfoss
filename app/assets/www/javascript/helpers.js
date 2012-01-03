/**
 * helper functions
 */
selfoss.helpers = {
    
    listrefresh: false,
    
    /**
     * list items
     */
    list: function(items) {
        var itemsHtml = "";
        
        $.each(items, function(index, item) {
            var unread = item.unread==true ? "unread" : "";
            
            // strip tags
            var text = item.content.replace(/<\/?[^>]+>/gi, '');
            if(text.length>100)
                text = text.substring(0,100);
            
            itemsHtml = itemsHtml + '\
                <li id="item' + item.id + '" class="' + unread + '">\
                    <a href="#content">\
                        <h3>' + item.title + '</h3>\
                        <p class="source">' + item.datetime + ' @ ' + item.sourcetitle + '</p>\
                        <p class="preview">' + text + '</p>\
                    </a>\
                </li>';
        });
        
        if(selfoss.params.offset!=0)
            $('#stream-content ul').append(itemsHtml);
        else
            $('#stream-content ul').html(itemsHtml);
        
        // insert images
        $.each(items, function(index, item) {
            if(typeof item.icon == "object") {
                item.icon.className = 'ui-li-icon';
                $('#item'+item.id+' h3').before(item.icon);
            } else {
                $('#item'+item.id+' h3').before('<img class="ui-li-icon" src="stylesheets/spacer.gif" />');
            }
            
            if(typeof item.thumbnail=="object") {
                $('#item'+item.id+' p.preview').html(item.thumbnail);
            }
        });
        
        // no list refresh on initialisation
        if(selfoss.helpers.listrefresh==true)
            $('#stream-content ul').listview('refresh');
        selfoss.helpers.listrefresh = true;
        
        // events for li elements
        $('#stream-content ul li').unbind('click').click(selfoss.helpers.showContent);
    },
    
    
    /**
     * show single item
     */
    showContent: function() {
        var id = $(this).attr('id').substring(4);
        var item = selfoss.service.getItem(id);
        $('#content-starred').data('id', id);
        
        // set starred button
        if(item.starred==false)
            selfoss.helpers.formatAsUnstarredButton();
        else
            selfoss.helpers.formatAsStarredButton();

        var content = item.content.replace(/<img([^<]+)src=(['\"])([^\"']*)(['\"])([^<]*)>/ig,"<img$1ref='$3'$5>");
        
        if(content!=item.content)
            $('#content-showimages').show();
        else
            $('#content-showimages').hide();
        
        // insert text
        $('.content-title').html(item.title);
        $('#content-meta').html(item.datetime + " @ " + item.sourcetitle);
        
        if(typeof item.icon == "object") {
            var img = new Image();
            img.src = item.icon.src;
            img.className="ul-li-icon";
            $('h3.content-title').html(img);
            $('h3.content-title').append(" <a href='"+item.link+"'>"+item.title+"</a>");
        }
        
        if(typeof item.thumbnail == "object") {
            var thumb = new Image();
            thumb.src = item.thumbnail.src;
            $('#content-content').html(thumb);
            $('#content-content').append("<br /><br />"+content);
        } else
            $('#content-content').html(content);
        
        $.mobile.changePage('#content');
    },
    
    
    /**
     * shows message box
     */
    showMessage: function(message, duration) {
        if(typeof duration == "undefined")
            duration = 2000;
        
        $("<div class='ui-loader ui-overlay-shadow ui-body-e ui-corner-all'><h1>" + message + "</h1></div>")
            .css({
                display: "block",
                opacity: 0.96,
                top: window.pageYOffset+100
            })
            .appendTo("body")
            .delay(duration)
            .fadeOut(400, function(){
                $(this).remove();
            });
    },
    
    
    /**
     * prepend slash on given url if not set
     */
    prependSlash: function(url) {
        if(url.length > 0 && url.substr(url.length - 1, 1)!="/")
            url = url + "/";
        return url;
    },
    
    
    /**
     * format button for "starr" item
     */
    formatAsUnstarredButton: function() {
        $('#content-starred').removeClass('ui-btn-up-e').addClass('ui-btn-up-a');
        if($('#content-starred .ui-btn-text').length>0)
            $('#content-starred .ui-btn-text').html('starr');
        else
            $('#content-starred').html('starr');
    },
    
    
    /**
     * format button for "starr" item
     */
    formatAsStarredButton: function() {
        $('#content-starred').removeClass('ui-btn-up-a').addClass('ui-btn-up-e');
        if($('#content-starred .ui-btn-text').length>0)
            $('#content-starred .ui-btn-text').html('unstarr');
        else
            $('#content-starred').html('unstarr');
    }
};