/**
 * initialize source editing events for loggedin users
 */
selfoss.events.sources = function() {
    // cancel source editing
    $('.source-cancel').unbind('click').click(function() {
        var parent = $(this).parents('.source');
        if(parent.hasClass('source-new')) {
            parent.fadeOut('fast', function() {
                $(this).remove();
            });
        } else {
            $(this).parents('.source-edit-form').hide();
        }
    });
    
    // add new source
    $('.source-add').unbind('click').click(function() {
        var sourceAdd = $(this);
        
        $.ajax({
            url: $('base').attr('href')+'source',
            type: 'GET',
            success: function(response) {
                $('.source-opml').after(response);
                selfoss.events.sources();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                parent.find('.source-edit-delete').removeClass('loading');                     
                selfoss.showError('Error adding source: '+
                                  textStatus+' '+errorThrown);
            }
        });
    });
    
    // save source
    $('.source-save').unbind('click').click(function() {
        var parent = $(this).parents('.source');
        
        // remove old errors
        parent.find('span.error').remove();
        parent.find('.error').removeClass('error');
        
        // show loading
        parent.find('.source-action').addClass('loading');
        
        // get id
        var id = false;
        if(typeof parent.attr('id') != "undefined")
            id = parent.attr('id').substr(6);
        
        // set url
        var url = $('base').attr('href')+'source';
        if(id!=false)
            url = url + '/' + id;
        
        // get values and params
        var values = selfoss.getValues(parent);
        values['ajax'] = true;
        
        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: values,
            success: function(response) {
                var id = response['id'];
                parent.attr('id', 'source'+id);
                
                // show saved text
                parent.find('.source-showparams').addClass('saved').html($('#lang').data('source_saved'));
                window.setTimeout(function() {
                    parent.find('.source-showparams').removeClass('saved').html($('#lang').data('source_edit'));
                }, 10000);
                
                // hide input form
                parent.find('.source-edit-form').hide();

                // update title
                parent.find('.source-title').text(parent.find("input[name='title']").val());

                // show all links for new items
                parent.removeClass('source-new');
                
                // update tags
                $('#nav-tags li:not(:first)').remove();
                $('#nav-tags').append(response.tags);
                
                // update sources
                $('#nav-sources li').remove();
                $('#nav-sources').append(response.sources);
                
                selfoss.events.navigation();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                selfoss.showErrors(parent, $.parseJSON(jqXHR.responseText));
            },
            complete: function(jqXHR, textStatus) {
                parent.find('.source-action').removeClass('loading');
            }
        });
    });
    
    // delete source
    $('.source-delete').unbind('click').click(function() {
        var answer = confirm($('#lang').data('source_warn'));
        if(answer==false)
            return;
        
        // get id
        var parent = $(this).parents('.source');
        var id = false;
        if(typeof parent.attr('id') != "undefined")
            id = parent.attr('id').substr(6);
        
        // show loading
        parent.find('.source-edit-delete').addClass('loading');
        
        // delete on server
        $.ajax({
            url: $('base').attr('href')+'source/delete/'+id,
            type: 'POST',
            success: function() {
                parent.fadeOut('fast', function() {
                    $(this).remove();
                });
                
                // reload tags and remove source from navigation
                selfoss.reloadTags();
                $('#nav-sources li#'+parent.attr('id')).remove();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                parent.find('.source-edit-delete').removeClass('loading');
                selfoss.showError('Error deleting source: '+errorThrown); 
            }
        }); 
    });
    
    // show params
    $('.source-showparams').unbind('click').click(function() {
        $(this).parent().next().show();
    });
    
    // select new source spout type
    $('.source-spout').unbind('change').change(function() {
        var val = $(this).val();
        var params = $(this).parents('ul').find('.source-params');
        params.show();
        if($.trim(val).length==0) {
            params.html('');
            selfoss.events.resize();
            return;
        }
        params.addClass('loading');
        $.ajax({
            url: $('base').attr('href')+'source/params',
            data: { spout: val },
            type: 'GET',
            success: function(data) {
                params.removeClass('loading').html(data);
                selfoss.events.resize();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                params.removeClass('loading').append('<li class="error">'+errorThrown+'</li>');
                selfoss.events.resize();
            }
        });
    });
};
