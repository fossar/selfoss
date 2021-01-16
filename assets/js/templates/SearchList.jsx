import React from 'react';
import { useLocation, useHistory } from 'react-router-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { makeEntriesLink } from '../helpers/uri';


function splitTerm(term) {
    if (term == '') {
        return [];
    }
    var words = term.match(/"[^"]+"|\S+/g);
    for (var i = 0; i < words.length; i++) {
        words[i] = words[i].replace(/"/g, '');
    }
    return words;
}


function joinTerm(words) {
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


// remove search term
function handleRemove({ index, location, history }) {
    const queryString = new URLSearchParams(location.search);
    const oldTerm = queryString.get('search');

    const termArray = splitTerm(oldTerm);
    termArray.splice(index, 1);
    const newterm = joinTerm(termArray);

    history.push(makeEntriesLink(location, { search: newterm, id: null }));
}


/**
 * Component for showing list of search terms at the top of the page.
 */
export default function SearchList() {
    const location = useLocation();
    const history = useHistory();

    const searchText = React.useMemo(() => {
        const queryString = new URLSearchParams(location.search);

        return queryString.get('search') ?? '';
    }, [location.search]);

    const terms = splitTerm(searchText);

    return (
        terms.map((item, index) => {
            return (
                <li key={index} onClick={() => handleRemove({ index, location, history })}>
                    {item} <FontAwesomeIcon icon={['fas', 'times']} />
                </li>
            );
        })
    );
}
