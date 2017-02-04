/**
 * initialize search events
 */
selfoss.events.search = function() {

    var splitTerm = function(term) {
        if(term=="")
            return [];
        var words = term.match(/"[^"]+"|\S+/g);
        for(var i = 0; i < words.length; i++)
            words[i] = words[i].replace(/"/g, "");
        return words;
    };

    var joinTerm = function(words) {
        if(!words || words.length <= 0)
            return "";
        for(var i = 0; i < words.length; i++) {
            if(words[i].indexOf(" ") >= 0)
                words[i] = '"'  + words[i] + '"';
        }
        return words.join(" ");
    };

    var executeSearch = function(term) {
        // show words in top of the page
        var words = splitTerm(term);
        term = joinTerm(words);
        $('#search-list').html('');
        var itemId = 0;
        $.each(words, function(index, item) {
            $('#search-list').append('<li id="search-item-' + itemId + '"></li>');
            $('#search-item-' + itemId).text(item);          
            itemId++;
        });
        
        // execute search
        $('#search').removeClass('active');
        selfoss.filter.offset = 0;
        selfoss.filter.search = term;
        selfoss.reloadList();
        
        if(term=="")
            $('#search-list').hide();
        else
            $('#search-list').show();
    };
    
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
        var termArray = splitTerm($('#search-term').val());
        termId = $(this).attr('id').replace("search-item-", "");
        termArray.splice(termId, 1);
        var newterm = joinTerm(termArray);
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
