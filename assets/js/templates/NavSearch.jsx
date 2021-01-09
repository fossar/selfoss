import React from 'react';
import ReactDOM from 'react-dom';
import classNames from 'classnames';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { executeSearch } from '../SearchHandler';

// search button shows search input or executes search
function handleSubmit({ active, setActive, searchField, searchText }) {
    if (!selfoss.isSmartphone() && !active) {
        setActive(true);
        searchField.current.focus();
        searchField.current.select();
        return;
    }

    executeSearch(searchText);
    setActive(false);
    searchField.current.blur();

    if (selfoss.isSmartphone()) {
        $('#nav-mobile-settings').click();
    }
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
function handleRemove({ setActive, searchField }) {
    if (selfoss.filter.search == '') {
        setActive(false);
        searchField.current.blur();
        return;
    }

    selfoss.filterReset({ search: '' }, true);
    $('#search-list').hide();
    $('#search-list').html('');
    setActive(false);
    selfoss.db.reloadList();
}

export function NavSearch() {
    const [active, setActive] = React.useState(false);
    const [searchText, setSearchText] = React.useState(selfoss.filter.search);
    const [offlineState, setOfflineState] = React.useState(
        selfoss.offlineState.value
    );

    const searchField = React.useRef(null);
    const searchButton = React.useRef(null);
    const searchRemoveButton = React.useRef(null);

    React.useEffect(() => {
        const filterListener = (event) => {
            setSearchText(event.filter.search);
        };

        const offlineStateListener = (event) => {
            setOfflineState(event.value);
        };

        // It might happen that value changes between creating the component and setting up the event handlers.
        filterListener({ filter: selfoss.filter });
        offlineStateListener({ value: selfoss.offlineState.value });

        selfoss.filter.addEventListener('change', filterListener);
        selfoss.offlineState.addEventListener('change', offlineStateListener);

        return () => {
            selfoss.filter.removeEventListener('change', filterListener);
            selfoss.offlineState.removeEventListener(
                'change',
                offlineStateListener
            );
        };
    }, []);

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
                onKeyUp={(event) =>
                    handleFieldKeyUp({
                        event,
                        searchButton,
                        searchRemoveButton
                    })
                }
                onChange={(event) => setSearchText(event.target.value)}
            />
            <button
                id="search-remove"
                title={selfoss.ui._('searchremove')}
                accessKey="h"
                aria-label={selfoss.ui._('searchremove')}
                onClick={() => handleRemove({ setActive, searchField })}
                ref={searchRemoveButton}
            >
                <FontAwesomeIcon icon={['fas', 'times']} />
            </button>
            <button
                id="search-button"
                title={selfoss.ui._('searchbutton')}
                aria-label={selfoss.ui._('searchbutton')}
                accessKey="e"
                onClick={() =>
                    handleSubmit({ active, setActive, searchField, searchText })
                }
                ref={searchButton}
            >
                <FontAwesomeIcon icon={['fas', 'search']} />{' '}
                <span className="search-button-label">
                    {selfoss.ui._('searchbutton')}
                </span>
            </button>
            <hr />
        </div>
    );
}

export function anchor(element) {
    ReactDOM.render(<NavSearch />, element);
}
