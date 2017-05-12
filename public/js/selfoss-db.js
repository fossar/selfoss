/**
 * db functions: client data repository
 */


selfoss.dbOnline = {


    /**
     * sync server status.
     *
     * @return void
     */
    sync: function(force) {
        var force = (typeof force !== 'undefined') ? force : false;

        if( !force && (selfoss.lastUpdate == null ||
                       Date.now() - selfoss.lastSync < 5*60*1000) ) {
            var d = $.Deferred();
            d.resolve();
            return d; // ensure any chained function runs
        }

        var getStatuses = true;
        if (selfoss.lastUpdate == null) {
            selfoss.lastUpdate = new Date(0);
            getStatuses = undefined;
        }

        return $.ajax({
            url: 'items/sync',
            type: 'GET',
            dataType: 'json',
            data: {
                since:         selfoss.lastUpdate.toISOString(),
                tags:          true,
                sources:       selfoss.filter.sourcesNav ? true : undefined,
                itemsStatuses: getStatuses
            },
            success: function(data) {
                selfoss.lastSync = Date.now();

                var dataDate = new Date(data.lastUpdate);

                if( dataDate <= selfoss.lastUpdate )
                    return;

                if( data.stats.unread>0 &&
                    ($('.stream-empty').is(':visible') ||
                     $('.stream-error').is(':visible')) ) {
                    selfoss.dbOnline.reloadList();
                } else {
                    selfoss.refreshStats(data.stats.all,
                                         data.stats.unread,
                                         data.stats.starred);
                    selfoss.refreshTags(data.tagshtml);

                    if( 'sourceshtml' in data )
                        selfoss.refreshSources(data.sourceshtml);

                    if( 'itemUpdates' in data ) {
                        selfoss.ui.refreshEntryStatuses(data.itemUpdates);
                    }

                    if( selfoss.filter.type == 'unread' &&
                        data.stats.unread > $('.entry.unread').length )
                        $('.stream-more').show();
                }
                selfoss.lastUpdate = dataDate;
            },
            error: function(jqXHR, textStatus, errorThrown) {
                selfoss.ui.showError('Could not sync last changes from server: '+
                                     textStatus+' '+errorThrown);
            }
        });
    },


    /**
     * refresh current items.
     *
     * @return void
     */
    reloadList: function() {
        if (selfoss.activeAjaxReq !== null)
            selfoss.activeAjaxReq.abort();

        if (location.hash == "#sources") {
            return;
        }

        if( selfoss.events.entryId && selfoss.filter.fromId == null )
            selfoss.filter.extraIds.push(selfoss.events.entryId);

        selfoss.ui.refreshStreamButtons();
        $('#content').addClass('loading').html("");

        selfoss.activeAjaxReq = $.ajax({
            url: $('base').attr('href'),
            type: 'GET',
            dataType: 'json',
            data: selfoss.filter,
            success: function(data) {
                selfoss.lastSync = Date.now();
                selfoss.lastUpdate = new Date(data.lastUpdate);

                selfoss.refreshStats(data.all, data.unread, data.starred);

                $('#content').html(data.entries);
                selfoss.ui.refreshStreamButtons(true,
                    $('.entry').not('.fullscreen').length > 0, data.hasMore);
                $(document).scrollTop(0);
                selfoss.ui.refreshEntryDatetimes();
                selfoss.events.entries();
                selfoss.events.search();

                // update tags
                selfoss.refreshTags(data.tags);

                // drop loaded sources
                var currentSource = -1;
                if(selfoss.sourcesNavLoaded) {
                    currentSource = $('#nav-sources li').index($('#nav-sources .active'));
                    $('#nav-sources li').remove();
                    selfoss.sourcesNavLoaded = false;
                }
                if(selfoss.filter.sourcesNav)
                    selfoss.refreshSources(data.sources, currentSource);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                if (textStatus == "abort")
                    return;
                else if (errorThrown)
                    selfoss.ui.showError('Load list error: '+
                                         textStatus+' '+errorThrown);
                selfoss.events.entries();
                selfoss.ui.refreshStreamButtons();
                $('.stream-error').show();
            },
            complete: function(jqXHR, textStatus) {
                // clean up
                $('#content').removeClass('loading');
                selfoss.activeAjaxReq = null;
            }
        });
    },


};


selfoss.db = {


    isValidTag: function(tag) {
        var isValid = false;
        $('#nav-tags > li:not(:first)').each(function(key, value) {
            isValid = $('.tag', this).html() == tag;
            return !isValid; // break the loop if valid
        });
        return isValid;
    },


    isValidSource: function(id) {
        var isValid = false;
        $('#nav-sources > li').each(function(key, value) {
            isValid = $(this).data('source-id') == id;
            return !isValid; // break the loop if valid
        });
        return isValid;
    }


};
