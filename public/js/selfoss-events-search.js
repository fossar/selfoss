/**
 * initialize search events
 */
selfoss.events.search = function() {

    var executeSearch = function(term) {
        // show words in top of the page
        var words = term.split(" ");
        $('#search-list').html('');
        var itemId = 0;
        $.each(words, function(index, item) {
            $('#search-list').append('<li id="search-item-' + itemId + '">' + item + '</li>');
            itemId++;
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
    
    // search button shows search input or executes search
    $('#search-button').unbind('click').click(function () {
        if($('#search').hasClass('active')==false) {
            $('#search').addClass('active');
            $('#search-term').focus().select();
            return;
        }
        executeSearch($('#search-term').val());
        $('#search-term').blur();
    });
    
    // navigation search button for mobile navigation
    $('#nav-search-button').unbind('click').click(function () {
        executeSearch($('#nav-search-term').val());
        $('#nav-mobile-settings').click();
    });
    
    // keypress enter in search inputfield
    $('#search-term').unbind('keyup').keyup(function(e) {
        if(e.which == 13)
            $('#search-button').click();
        if(e.keyCode == 27)
            $('#search-remove').click();
    });
    
    // search term list in top of the page
    $('#search-list li').unbind('click').click(function () {
        var termArray = $('#search-term').val().split(" ");
        termId = $(this).attr('id').replace("search-item-", "");
        termArray.splice(termId, 1);
        var newterm = termArray.join(" ");
        $('#search-term').val(newterm);
        executeSearch($('#search-term').val());
    });
    
    // remove button of search
    $('#search-remove').unbind('click').click(function () {
        if(selfoss.filter.search=='') {
            $('#search').removeClass('active');
            $('#search-term').blur();
            return;
        }
        
        selfoss.filter.offset = 0;
        selfoss.filter.search = '';
        $('#search-list').hide();
        $('#search-list').html('');
        $('#search').removeClass('active');
        selfoss.reloadList();
    });
};