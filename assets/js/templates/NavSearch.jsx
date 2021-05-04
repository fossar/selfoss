import React from 'react';
import PropTypes from 'prop-types';
import { useLocation, useHistory } from 'react-router-dom';
import classNames from 'classnames';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { makeEntriesLink } from '../helpers/uri';
import * as icons from '../icons';

// search button shows search input or executes search
function handleSubmit({ active, setActive, searchField, searchText, history, location, setNavExpanded }) {
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

export default function NavSearch({ setNavExpanded }) {
    const [active, setActive] = React.useState(false);
    const [offlineState, setOfflineState] = React.useState(
        selfoss.offlineState.value
    );

    const searchField = React.useRef(null);
    const searchButton = React.useRef(null);
    const searchRemoveButton = React.useRef(null);

    const location = useLocation();
    const history = useHistory();

    const queryString = new URLSearchParams(location.search);
    const oldTerm = queryString.get('search') ?? '';
    const [searchText, setSearchText] = React.useState('');

    React.useEffect(() => {
        // Update the search term when the query string changes.
        setSearchText(oldTerm);
    }, [oldTerm]);

    React.useEffect(() => {
        const offlineStateListener = (event) => {
            setOfflineState(event.value);
        };

        // It might happen that value changes between creating the component and setting up the event handlers.
        offlineStateListener({ value: selfoss.offlineState.value });

        selfoss.offlineState.addEventListener('change', offlineStateListener);

        return () => {
            selfoss.offlineState.removeEventListener(
                'change',
                offlineStateListener
            );
        };
    }, []);

    const termOnKeyUp = React.useCallback(
        (event) =>
            handleFieldKeyUp({
                event,
                searchButton,
                searchRemoveButton
            }),
        []
    );

    const termOnChange = React.useCallback(
        (event) => setSearchText(event.target.value),
        []
    );

    const removeOnClick = React.useCallback(
        () => handleRemove({ setActive, searchField, history, location }),
        [history, location]
    );

    const searchOnClick = React.useCallback(
        () => handleSubmit({ active, setActive, searchField, searchText, history, location, setNavExpanded }),
        [active, searchText, history, location, setNavExpanded]
    );

    return (
        <div
            id="search"
            className={classNames({
                offline: offlineState,
                online: !offlineState,
                active
            })}
            role="search"
        >
            <input
                aria-label={selfoss.ui._('search_label')}
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
                title={selfoss.ui._('searchremove')}
                accessKey="h"
                aria-label={selfoss.ui._('searchremove')}
                onClick={removeOnClick}
                ref={searchRemoveButton}
            >
                <FontAwesomeIcon icon={icons.remove} />
            </button>
            <button
                id="search-button"
                title={selfoss.ui._('searchbutton')}
                aria-label={selfoss.ui._('searchbutton')}
                accessKey="e"
                onClick={searchOnClick
                }
                ref={searchButton}
            >
                <FontAwesomeIcon icon={icons.search} />{' '}
                <span className="search-button-label">
                    {selfoss.ui._('searchbutton')}
                </span>
            </button>
            <hr />
        </div>
    );
}

NavSearch.propTypes = {
    setNavExpanded: PropTypes.func.isRequired,
};
