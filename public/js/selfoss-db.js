/**
 * db functions: client data repository
 */
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
    },


};
