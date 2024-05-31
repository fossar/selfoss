import React, {
    useCallback,
    useContext,
    useEffect,
    useRef,
    useState,
} from 'react';
import PropTypes from 'prop-types';
import { useLocation, useHistory } from 'react-router-dom';
import classNames from 'classnames';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { makeEntriesLink } from '../helpers/uri';
import * as icons from '../icons';
import { LocalizationContext } from '../helpers/i18n';

// search button shows search input or executes search
function handleSubmit({
    active,
    setActive,
    searchField,
    searchText,
    history,
    location,
    setNavExpanded,
}) {
    if (!selfoss.isSmartphone() && !active) {
        setActive(true);
        searchField.current.focus();
        searchField.current.select();
        return;
    }

    history.push(makeEntriesLink(location, { search: searchText, id: null }));
    setActive(false);
    searchField.current.blur();

    setNavExpanded(false);
}

function handleFieldKeyUp({ event, searchButton, searchRemoveButton }) {
    // keypress enter in search inputfield
    if (event.which == 13) {
        searchButton.current.click();
    }
    if (event.keyCode == 27) {
        searchRemoveButton.current.click();
    }
}

// remove button of search
function handleRemove({ setActive, searchField, history, location }) {
    const queryString = new URLSearchParams(location.search);
    const oldTerm = queryString.get('search');

    setActive(false);

    if (oldTerm == '') {
        searchField.current.blur();
        return;
    }

    history.push(makeEntriesLink(location, { search: '', id: null }));
}

export default function NavSearch({ setNavExpanded, offlineState }) {
    const [active, setActive] = useState(false);

    const searchField = useRef(null);
    const searchButton = useRef(null);
    const searchRemoveButton = useRef(null);

    const location = useLocation();
    const history = useHistory();

    const queryString = new URLSearchParams(location.search);
    const oldTerm = queryString.get('search') ?? '';
    const [searchText, setSearchText] = useState('');

    useEffect(() => {
        // Update the search term when the query string changes.
        setSearchText(oldTerm);
    }, [oldTerm]);

    const termOnKeyUp = useCallback(
        (event) =>
            handleFieldKeyUp({
                event,
                searchButton,
                searchRemoveButton,
            }),
        [],
    );

    const termOnChange = useCallback(
        (event) => setSearchText(event.target.value),
        [],
    );

    const removeOnClick = useCallback(
        () => handleRemove({ setActive, searchField, history, location }),
        [history, location],
    );

    const searchOnClick = useCallback(
        () =>
            handleSubmit({
                active,
                setActive,
                searchField,
                searchText,
                history,
                location,
                setNavExpanded,
            }),
        [active, searchText, history, location, setNavExpanded],
    );

    const _ = useContext(LocalizationContext);

    return (
        <div
            id="search"
            className={classNames({
                offline: offlineState,
                online: !offlineState,
                active,
            })}
            role="search"
        >
            <input
                aria-label={_('search_label')}
                type="search"
                id="search-term"
                accessKey="s"
                ref={searchField}
                value={searchText}
                onKeyUp={termOnKeyUp}
                onChange={termOnChange}
            />
            <button
                id="search-remove"
                title={_('searchremove')}
                accessKey="h"
                aria-label={_('searchremove')}
                onClick={removeOnClick}
                ref={searchRemoveButton}
            >
                <FontAwesomeIcon icon={icons.remove} />
            </button>
            <button
                id="search-button"
                title={_('searchbutton')}
                aria-label={_('searchbutton')}
                accessKey="e"
                onClick={searchOnClick}
                ref={searchButton}
            >
                <FontAwesomeIcon icon={icons.search} />{' '}
                <span className="search-button-label">{_('searchbutton')}</span>
            </button>
            <hr />
        </div>
    );
}

NavSearch.propTypes = {
    setNavExpanded: PropTypes.func.isRequired,
    offlineState: PropTypes.bool.isRequired,
};
