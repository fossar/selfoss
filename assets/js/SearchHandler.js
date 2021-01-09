import selfoss from './selfoss-base';

export function splitTerm(term) {
    if (term == '') {
        return [];
    }
    var words = term.match(/"[^"]+"|\S+/g);
    for (var i = 0; i < words.length; i++) {
        words[i] = words[i].replace(/"/g, '');
    }
    return words;
}


export function joinTerm(words) {
    if (!words || words.length <= 0) {
        return '';
    }
    for (var i = 0; i < words.length; i++) {
        if (words[i].indexOf(' ') >= 0) {
            words[i] = '"'  + words[i] + '"';
        }
    }
    return words.join(' ');
}


export function executeSearch(term) {
    // show words in top of the page
    var words = splitTerm(term);
    term = joinTerm(words);
    $('#search-list').html('');
    words.forEach((item, index) => {
        $('#search-list').append('<li id="search-item-' + index + '">' + item + ' <i class="fas fa-times"></i></li>');
    });

    // execute search
    selfoss.filterReset({ search: term }, true);
    selfoss.db.reloadList();

    if (term == '') {
        $('#search-list').hide();
    } else {
        $('#search-list').show();
    }
}


/**
 * initialize search events
 */
export function initSearchEvents() {
    // search term list in top of the page
    $('#search-list li').unbind('click').click(function() {
        var termArray = splitTerm($('#search-term').val());
        var termId = $(this).attr('id').replace('search-item-', '');
        termArray.splice(termId, 1);
        var newterm = joinTerm(termArray);
        executeSearch(newterm);
    });
}
