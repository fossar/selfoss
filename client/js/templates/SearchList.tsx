import React, { useCallback, useMemo } from 'react';
import classNames from 'classnames';
import { NavigateFunction, useNavigate } from 'react-router';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Location, useLocation } from '../helpers/uri';
import { makeEntriesLink } from '../helpers/uri';
import * as icons from '../icons';

function splitTerm(term: string): string[] {
    if (term == '') {
        return [];
    } else if (term.match(/^\/.+\/$/)) {
        return [term];
    }

    const words = term.match(/"[^"]+"|\S+/g);
    for (let i = 0; i < words.length; i++) {
        words[i] = words[i].replace(/"/g, '');
    }
    return words;
}

function joinTerm(words: string[]): string {
    if (!words || words.length <= 0) {
        return '';
    }
    for (let i = 0; i < words.length; i++) {
        if (words[i].indexOf(' ') >= 0) {
            words[i] = '"' + words[i] + '"';
        }
    }
    return words.join(' ');
}

// remove search term
function handleRemove({
    index,
    location,
    navigate,
    regexSearch,
}: {
    index: number;
    location: Location;
    navigate: NavigateFunction;
    regexSearch: boolean;
}): void {
    let newterm;
    if (regexSearch) {
        newterm = '';
    } else {
        const queryString = new URLSearchParams(location.search);
        const oldTerm = queryString.get('search');

        const termArray = splitTerm(oldTerm);
        termArray.splice(index, 1);
        newterm = joinTerm(termArray);
    }

    navigate(makeEntriesLink(location, { search: newterm, id: null }));
}

type SearchWordProps = {
    regexSearch: boolean;
    index: number;
    item: string;
};

function SearchWord(props: SearchWordProps): React.JSX.Element {
    const { regexSearch, index, item } = props;

    const location = useLocation();
    const navigate = useNavigate();

    const removeOnClick = useCallback(
        () => handleRemove({ index, location, navigate, regexSearch }),
        [index, location, navigate, regexSearch],
    );

    return (
        <li
            onClick={removeOnClick}
            className={classNames({ 'regex-search-term': regexSearch })}
        >
            {item} <FontAwesomeIcon icon={icons.remove} />
        </li>
    );
}

/**
 * Component for showing list of search terms at the top of the page.
 */
export default function SearchList(): React.JSX.Element[] {
    const location = useLocation();

    const searchText = useMemo(() => {
        const queryString = new URLSearchParams(location.search);

        return queryString.get('search') ?? '';
    }, [location.search]);

    const regexSearch = searchText.match(/^\/.+\/$/) !== null;
    const terms = regexSearch ? [searchText] : splitTerm(searchText);

    return terms.map((item, index) => (
        <SearchWord
            key={index}
            index={index}
            item={item}
            regexSearch={regexSearch}
        />
    ));
}
