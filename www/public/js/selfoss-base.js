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
			// init colorpicker
			$(".color").spectrum({
				showPaletteOnly: true,
				color: 'blanchedalmond',
				palette: [
					['#ffccc9', '#ffce93', '#fffc9e', '#ffffc7', '#9aff99', '#96fffb', '#cdffff' , '#cbcefb', '#fffe65', '#cfcfcf', '#fd6864', '#fe996b','#fcff2f', '#67fd9a', '#38fff8', '#68fdff', '#9698ed', '#c0c0c0', '#fe0000', '#f8a102', '#ffcc67', '#f8ff00', '#34ff34', '#68cbd0', '#34cdf9', '#6665cd', '#9b9b9b', '#cb0000', '#f56b00', '#ffcb2f', '#ffc702', '#32cb00', '#00d2cb', '#3166ff', '#6434fc', '#656565', '#9a0000', '#ce6301', '#cd9934', '#999903', '#009901', '#329a9d', '#3531ff', '#6200c9', '#343434', '#680100', '#963400', '#986536', '#646809', '#036400', '#34696d', '#00009b', '#303498', '#000000', '#330001', '#643403', '#663234', '#343300', '#013300', '#003532', '#010066', '#340096']
				],
				change: function(color) {
					$(this).css('backgroundColor', color.toHexString());
				}
			});
			
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
	}

};

selfoss.init();