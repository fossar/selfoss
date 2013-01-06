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

	
	init: function() {
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
		
		selfoss.filter.itemsPerPage = $('.entry').length;
		
		// init events
		selfoss.events();
		
		// init shortcut handler
        selfoss.shortcuts();
	},
	
	
	
	events: function() {
		// select entry
        $('.entry').unbind('click').click(function() {
            $('.entry.selected').removeClass('selected');
            $(this).addClass('selected');
        });
	
		// show/hide entry
		$('.entry-title').unbind('click').click(function() {
			var parent = $(this).parent();
			var content = parent.find('.entry-content');
			if(content.is(':visible')) {
				parent.find('.entry-toolbar').hide();
                content.slideUp('fast');
            } else {
                content.slideDown('fast', function() { parent.find('.entry-toolbar').show(); });
			}
			
			// load images not on mobile devices
			if(selfoss.isMobile()==false)
				content.lazyLoadImages();
		});
	
		// scroll load more
		$(window).unbind('scroll').scroll(function() {
			var content = $('#content');
			if($('.stream-more').length > 0 
			   && $('.stream-more').position().top < content.height() + content.scrollTop() 
			   && $('.stream-more').hasClass('loading')==false)
				$('.stream-more').click();
		});
		
		// more
        $('.stream-more').unbind('click').click(function () {
            var streamMore = $(this);
            selfoss.filter.offset += selfoss.filter.itemsPerPage;
            
			streamMore.addClass('loading');
            $.ajax({
                url: $('base').attr('href'),
                type: 'GET',
                data: selfoss.filter,
                success: function(data) {
                    $('.stream-more').replaceWith(data);
                    selfoss.events();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    streamMore.removeClass('loading');
                    alert('Load more error: '+errorThrown);
                }
            });
        });
		
		
		// starr/unstarr
		$('.entry-starr').unbind('click').click(function() {
			var button = $(this);
			var parent = $(this).parents('.entry');
			var id = parent.attr('id').substr(5);
			var starr = $(this).hasClass('active')==false;
			
			var setButton = function(starr) {
				if(starr) {
					button.addClass('active');
					button.html('unstar');
				} else {
					button.removeClass('active');
					button.html('star');
				}
			};
			setButton(starr);
			
			$.ajax({
				url: $('base').attr('href') + (starr ? 'starr/' : 'unstarr/') + id,
				type: 'POST',
				error: function(jqXHR, textStatus, errorThrown) {
					setButton(!starr);
					alert('Can not starr/unstarr item: '+errorThrown);
				}
			});
		});
		
		// read/unread
		$('.entry-unread').unbind('click').click(function() {
			var button = $(this);
			var parent = $(this).parents('.entry');
			var id = parent.attr('id').substr(5);
			var unread = $(this).hasClass('active')==true;
			var setButton = function(unread) {
				if(unread) {
					button.removeClass('active');
					button.html('mark as unread');
					parent.removeClass('unread');
				} else {
					button.addClass('active');
					button.html('mark as read');
					parent.addClass('unread');
				}
			};
			
			setButton(unread);
			
			$.ajax({
				url: $('base').attr('href') + (unread ? 'mark/' : 'unmark/') + id,
				type: 'POST',
				error: function(jqXHR, textStatus, errorThrown) {
					setButton(!unread);
					alert('Can not mark/unmark item: '+errorThrown);
				}
			});
		});
		
		// load images
		$('.entry-loadimages').unbind('click').click(function() {
			$(this).parents('.entry').lazyLoadImages();
			$(this).fadeOut();
		});
		
		// toolbar: mark as read
		$('#nav-mark').unbind('click').click(function () {
			var ids = new Array();
			$('.entry').each(function(index, item) {
				ids.push( $(item).attr('id').substr(5) );
			});
			
			$.ajax({
				url: $('base').attr('href') + 'mark',
				type: 'POST',
				data: {
					ids: ids
				},
				success: function() {
					$('.entry').removeClass('unread');
				},
				error: function(jqXHR, textStatus, errorThrown) {
					alert('Can not mark all visible item: ' + errorThrown);
				}
			});
		});
		
		// toolbar: sources
		$('#nav-filter > li').unbind('click').click(function () {
			if($(this).hasClass('nav-filter-newest'))
				selfoss.filter.type='newest';
			else if($(this).hasClass('nav-filter-unread'))
				selfoss.filter.type='unread';
			else if($(this).hasClass('nav-filter-starred'))
				selfoss.filter.type='starred';
			
			$('#nav-filter > li').removeClass('active');
			$(this).addClass('active');
			
			selfoss.filter.offset = 0;
			selfoss.reloadList();
		});
		
		
		// toolbar: sources
		$('#nav-settings').unbind('click').click(function () {
			$('#content').addClass('loading').html("");
			$.ajax({
                url: $('base').attr('href')+'sources',
                type: 'GET',
                success: function(data) {
                    $('#content').html(data);
                    selfoss.events();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    alert('Load sources error: '+errorThrown);
                },
				complete: function(jqXHR, textStatus) {
					$('#content').removeClass('loading');
				}
            });
		});
		
		// search
		var executeSearch = function() {
			// show words in top of the page
			var term = $('#search-term').val();
			var words = term.split(" ");
			$('#search-list').html('');
			$.each(words, function(index, item) {
				$('#search-list').append('<li>' + item + '</li>');
			});
			
			// execute search
			$('#search').removeClass('active');
			selfoss.filter.search = term;
			selfoss.reloadList();
			
			if(term=="")
				$('#search-list').hide();
			else
				$('#search-list').show();
		}
		
		$('#search-button').unbind('click').click(function () {
			if($('#search').hasClass('active')==false) {
				$('#search').addClass('active');
				return;
			}
			executeSearch();
		});
		
		$('#search-term').unbind('keypress').keypress(function(e) {
			if(e.which == 13)
				$('#search-button').click();
		});
		
		$('#search-list li').unbind('click').click(function () {
			var term = $('#search-term').val();
			term = term.replace($(this).html(), "").split(" ");
			var newterm = "";
			$.each(term, function(index, item) {
				newterm = newterm + " " + $.trim(item);
			});
			newterm = $.trim(newterm);
			$('#search-term').val(newterm);
			executeSearch();
		});
		
		$('#search-remove').unbind('click').click(function () {
			if(selfoss.filter.search=='') {
				$('#search').removeClass('active');
				return;
			}
			
			selfoss.filter.offset = 0;
			selfoss.filter.search = '';
			$('#search-list').hide();
			$('#search-list').html('');
			$('#search').removeClass('active');
			selfoss.reloadList();
		});
		
		// only loggedin users
        if($('body').hasClass('loggedin')==true) {
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
						sourceAdd.after(response);
						selfoss.events();
					},
					error: function(jqXHR, textStatus, errorThrown) {
						parent.find('.source-edit-delete').removeClass('loading');
						alert('Error deleting source: '+errorThrown);
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
                
                $.ajax({
                    url: url,
                    type: 'POST',
                    dataType: 'json',
                    data: selfoss.getValues(parent),
                    success: function(response) {
                        var id = response['id'];
						parent.attr('id', 'source'+id);
						
						// show saved text
                        parent.find('.source-showparams').addClass('saved').html('saved');
                        window.setTimeout(function() {
							parent.find('.source-showparams').removeClass('saved').html('edit');
                        }, 10000);
                        
						// hide input form
						parent.find('.source-edit-form').hide();
						
						// update title
						parent.find('.source-title').html(parent.find('#title').val());
						
						// show all links for new items
						parent.removeClass('source-new');
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
                var answer = confirm('really delete this source?');
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
					url: $('base').attr('href')+'source/'+id,
					type: 'DELETE',
					success: function() {
						parent.fadeOut('fast', function() {
							$(this).remove();
						});
					},
					error: function(jqXHR, textStatus, errorThrown) {
						parent.find('.source-edit-delete').removeClass('loading');
						alert('Error deleting source: '+errorThrown);
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
        }
	},
    
    
    /**
     * register shortcuts
     */
    shortcuts: function() {  
        var options = {"disable_in_input": true};
        
        // next
        shortcut.add('Space', function() { selfoss.shortcuts_nextprev('next', true, false); return false; }, options);
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
        
		// mark/unmark
		shortcut.add('m', function() {
            $('.entry.selected .entry-unread').click();
        }, options);
		
        // open target
        shortcut.add('v', function() {
            window.open($('.entry.selected .entry-source').attr('href'));
        }, options);
		
		// mark all as read
        shortcut.add('Ctrl+m', function() {
            $('#nav-mark').click();
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
            $('#content').scrollTop($('#content').scrollTop()+value);
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
            
        } else {
            if(old.length==0) {
                return;
            }
            else {
                current = old.prev().length==0 ? old : old.prev();
            }
        }

        // remove active
        old.removeClass('selected');
        old.find('.entry-content').hide();
        old.find('.entry-toolbar').hide();
		
        if(current.length==0)
            return;

        current.addClass('selected');
        
        // load more
        if(current.hasClass('stream-more'))
            current.click().removeClass('selected').prev().addClass('selected');
        
        // open?
        if(open && current.find('.entry-thumbnail').length==0) {
            var content = current.find('.entry-content');
            // load images not on mobile devices
			if(selfoss.isMobile()==false)
				content.lazyLoadImages();
            content.show();
			current.find('.entry-toolbar').show();
        }
        
        // scroll to element
        selfoss.shortcuts_autoscroll(current);
    },
	
	
	/**
     * autoscroll
     */
    shortcuts_autoscroll: function(next) {
		var viewportHeight = $(window).height();
		var viewportScrollTop = $(document).scrollTop();
		
		// scroll down
		if(viewportScrollTop + viewportHeight < next.position().top + next.height() + 80) {
			if(next.height() > viewportHeight) {
                $(document).scrollTop(next.position().top);
            } else {
                $(document).scrollTop(windowScrollTop + next.height());
			}
		}
		
		// scroll up
        if(next.position().top <= viewportScrollTop) {
			$(document).scrollTop(next.position().top);
		}
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
				selfoss.events();
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

            