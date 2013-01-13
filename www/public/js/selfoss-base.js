/**
 * base javascript application
 *
 * @package    public_js
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
var selfoss = {

	/**
     * current filter settings
     * @var mixed
     */
	filter: {
		offset: 0,
		itemsPerPage: 0,
		search: '',
		type: 'newest',
		tag: '',
		ajax: true
	},

	
	/**
     * initialize application
     */
	init: function() {
		jQuery(document).ready(function() {
			// set items per page
			selfoss.filter.itemsPerPage = $('.entry').length;
			
			// init events
			selfoss.events.init();
			
			// init shortcut handler
			selfoss.shortcuts.init();
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
	 * returns true if we are on a mobile device
	 *
	 * @return true if device resolution smaller equals 1024
	 */
	isMobile: function() {
		if($(window).width()<=1024)
            return true;
		return false;
	},

	
	/**
	 * refresh current items.
	 *
	 * @return void
	 */
	reloadList: function() {
		$('#content').addClass('loading').html("");
		
		$.ajax({
			url: $('base').attr('href'),
			type: 'GET',
			data: selfoss.filter,
			success: function(data) {
				$('#content').html(data);
				$(document).scrollTop(0);
				selfoss.events.entries();
				selfoss.events.search();
			},
			error: function(jqXHR, textStatus, errorThrown) {
				alert('Load list error: '+errorThrown);
			},
			complete: function(jqXHR, textStatus) {
				$('#content').removeClass('loading');
			}
		});
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
			url: $('base').attr('href')+'tags',
			type: 'GET',
			success: function(data) {
				$('#nav-tags').append(data);
				selfoss.events.navigation();
			},
			error: function(jqXHR, textStatus, errorThrown) {
				alert('Load tags error: '+errorThrown);
			},
			complete: function(jqXHR, textStatus) {
				$('#nav-tags').removeClass('loading');
			}
		});
	}
};

selfoss.init();