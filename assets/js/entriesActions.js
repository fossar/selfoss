import { UpdateWithSideEffect, Update } from 'use-reducer-with-side-effects';
import useReducerWithSideEffects, { Update } from 'use-reducer-with-side-effects';

const SET_ENTRIES = 'set-entries';
const APPEND_ENTRIES = 'append-entries';
const STAR_ENTRY_IN_VIEW = 'star-entry-in-view';
const MARK_ENTRY_IN_VIEW = 'mark-entry-in-view';
const REFRESH_ENTRY_STATUSES = 'refresh-entry-statuses';
const DISMISS_ITEM = 'dismiss-item';

function entriesReducer(state, action) {
    switch (action.type) {
    case SET_ENTRIES: {
      return Update({
        ...state,
        entries: typeof action.entries === 'function' ? action.entries(state.entries) : action.entries,
    });
    }
    case APPEND_ENTRIES: {
      return Update({
        ...state,
        entries: (entries) => [...state.entries, ...action.extraEntries],
    });
  }
    case STAR_ENTRY_IN_VIEW: {
      return Update({
        ...state,
        entries: state.entries.map((entry) => {
                if (entry.id === action.id) {
                    return {
                        ...entry,
                        starred: action.starred
                    };
                } else {
                    return entry;
                }
            }),
    });
    }
    case MARK_ENTRY_IN_VIEW: {
      return Update({
        ...state,
        entries: state.entries.map((entry) => {
                if (entry.id === action.id) {
                    return {
                        ...entry,
                        unread: action.unread
                    };
                } else {
                    return entry;
                }
            }),
    });
    }
    case REFRESH_ENTRY_STATUSES: {
      return Update({ ...state, entries: state.entries.map((entry) => {
        const newStatus = action.entryStatuses.find((entryStatus) => entryStatus.id == entry.id);
        if (newStatus) {
            const { unread, starred } = newStatus;
        return {
            ...entry,
            unread,
            starred
        };
        }

        return entry;
    }) });
    }
    // case REFRESH_ENTRY_STATUSES: {
    //   return UpdateWithSideEffect({ ...state, fetchingAvatar: true }, (state, dispatch) => { // the second argument can also be an array of side effects
    //               fetch(`/avatar/${userName}`).then(
    //                 avatar =>
    //                   dispatch({
    //                     type: FETCH_AVATAR_SUCCESS,
    //                     avatar
    //                   }),
    //                 dispatch({ type: FETCH_AVATAR_FAILURE })
    //               );
    //         });
    // }
    case DISMISS_ITEM: {
      return Update({
        ...state,
        entries: state.entries.filter(({ id }) => id !== action.itemId),
    });
    }
    }
}

export function setEntries(entries) {
    return {
        type: SET_ENTRIES,
        entries,
    }
}

export function appendEntries(entries) {
    return {
        type: APPEND_ENTRIES,
        entries,
    }
}

export function starEntryInView(id, starred) {
    return {
        type: STAR_ENTRY_IN_VIEW,
        id,
        starred,
    };
}

export function markEntryInView(id, unread) {
    return {
        type: MARK_ENTRY_IN_VIEW,
        id,
        unread,
    };
}

export function refreshEntryStatuses(entryStatuses) {
    return {
        type: REFRESH_ENTRY_STATUSES,
        entryStatuses,
    };
}

export function useEntriesReducer() {
    return useReducer(entriesReducer, {
        entries: [],
    hasMore: false,
    /**
     * Currently selected entry.
     * The id in the location.hash should imply the selected entry.
     * It will also be used for keyboard navigation (for finding previous/next).
     */
    selectedEntry: null,
    expandedEntries: {},
    loadingState: LoadingState.INITIAL,
    /**
     * HACK: A counter that is increased every time reload action (r key) is triggered.
     */
    forceReload: 0,
    });
}
