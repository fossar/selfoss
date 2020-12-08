import React from 'jsx-dom';
import selfoss from './selfoss-base';
import * as sourceRequests from './requests/sources';
import SourceParams from './templates/SourceParams';
import Source from './templates/Source';

/**
 * initialize source editing events for loggedin users
 */
selfoss.events.sources = function() {
    // cancel source editing
    $('.source-cancel').unbind('click').click(function(event) {
        event.preventDefault();

        var parent = $(this).parents('.source');
        if (parent.hasClass('source-new')) {
            parent.fadeOut('fast', function() {
                $(this).remove();
            });
        } else {
            $(this).parents('.source-edit-form').hide();
        }
    });

    // add new source
    $('.source-add').unbind('click').click(function(event) {
        event.preventDefault();

        sourceRequests.getSpouts().then(({spouts}) => {
            $('.source-opml').after(<Source spouts={spouts} />);
            selfoss.events.sources();
        }).catch((error) => {
            parent.find('.source-edit-delete').removeClass('loading');
            selfoss.ui.showError(selfoss.ui._('error_add_source') + ' ' + error.message);
        });
    });

    // save source
    $('.source-save').unbind('click').click(function(event) {
        event.preventDefault();

        var parent = $(this).parents('form.source');

        // remove old errors
        parent.find('span.error').remove();
        parent.find('.error').removeClass('error');

        // show loading
        parent.find('.source-action').addClass('loading');

        // get id
        let id = parent.attr('data-source-id');

        // get values and params
        var values = new FormData(parent.get(0));

        // make tags into a list
        let oldTags = values.get('tags').split(',');
        values.delete('tags');
        oldTags.map(tag => tag.trim())
            .filter(tag => tag !== '')
            .forEach(tag => values.append('tags[]', tag));

        sourceRequests.update(id, values)
            .then((response) => {
                if (!response.success) {
                    selfoss.showErrors(parent, response);
                } else {
                    var id = response['id'];
                    parent.attr('data-source-id', id);

                    // show saved text
                    parent.find('.source-showparams').addClass('saved').html(selfoss.ui._('source_saved'));
                    window.setTimeout(function() {
                        parent.find('.source-showparams').removeClass('saved').html(selfoss.ui._('source_edit'));
                    }, 10000);

                    // hide input form
                    parent.find('.source-edit-form').hide();

                    // update title
                    var title = $('<p>').html(response.title).text();
                    parent.find('.source-title').text(title);
                    parent.find("input[name='title']").val(title);

                    // show all links for new items
                    parent.removeClass('source-new');

                    // update tags
                    selfoss.refreshTags(response.tags, true);

                    // update sources
                    selfoss.refreshSources(response.sources, true);

                    selfoss.events.navigation();
                }
            }).catch((error) => {
                selfoss.ui.showError(selfoss.ui._('error_edit_source') + ' ' + error.message);
            }).finally(() => {
                parent.find('.source-action').removeClass('loading');
            });
    });

    // delete source
    $('.source-delete').unbind('click').click(function(event) {
        event.preventDefault();

        var answer = confirm(selfoss.ui._('source_warn'));
        if (answer == false) {
            return;
        }

        // get id
        var parent = $(this).parents('.source');
        var id = parent.attr('data-source-id');

        // show loading
        parent.find('.source-edit-delete').addClass('loading');

        // delete on server
        sourceRequests.remove(id).then(() => {
            parent.fadeOut('fast', function() {
                $(this).remove();
            });

            // reload tags and remove source from navigation
            selfoss.reloadTags();
            $(`#nav-sources [data-source-id="${id}"]`)?.parents('li')?.get(0)?.remove();
        }).catch((error) => {
            parent.find('.source-edit-delete').removeClass('loading');
            selfoss.ui.showError(selfoss.ui._('error_delete_source') + ' ' + error.message);
        });
    });

    // show params
    $('.source-showparams').unbind('click').click(function(event) {
        event.preventDefault();
        $(this).parent().parent().find('.source-edit-form').show();
    });

    // select new source spout type
    $('.source-spout').unbind('change').change(function() {
        var spoutClass = $(this).val();
        var params = $(this).parents('ul').find('.source-params');

        // save param values
        var savedParamValues = {};
        params.find('input').each(function(index, param) {
            if (param.value) {
                savedParamValues[param.name] = param.value;
            }
        });

        params.show();
        if (spoutClass.trim().length === 0) {
            params.html('');
            return;
        }
        params.addClass('loading');
        sourceRequests.getSpoutParams(spoutClass).then(({spout, id}) => {
            params.removeClass('loading').html(<SourceParams spout={spout} sourceId={id} />);

            // restore param values
            params.find('input').each(function(index, param) {
                if (savedParamValues[param.name]) {
                    param.value = savedParamValues[param.name];
                }
            });
        }).catch((error) => {
            params.removeClass('loading').append(`<li class="error">${error.message}</li>`);
        });
    });
};
