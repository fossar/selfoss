import React from 'react';
import ReactDOM from 'react-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { executeSearch } from '../SearchHandler';


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
function handleRemove(index) {
    const termArray = splitTerm(selfoss.filter.search);
    termArray.splice(index, 1);
    const newterm = joinTerm(termArray);
    executeSearch(newterm);
}


/**
 * Component for showing list of search terms at the top of the page.
 */
export default function SearchList() {
    const [searchText, setSearchText] = React.useState(selfoss.filter.search);

    const terms = splitTerm(searchText);

    React.useEffect(() => {
        const filterListener = (event) => {
            setSearchText(event.filter.search);
        };

        // It might happen that value changes between creating the component and setting up the event handlers.
        filterListener({ filter: selfoss.filter });

        selfoss.filter.addEventListener('change', filterListener);

        return () => {
            selfoss.filter.removeEventListener('change', filterListener);
        };
    }, []);

    return (
        terms.map((item, index) => {
            return (
                <li key={index} onClick={() => handleRemove(index)}>
                    {item} <FontAwesomeIcon icon={['fas', 'times']} />
                </li>
            );
        })
    );
}


export function anchor(element) {
    ReactDOM.render(<SearchList />, element);
}
