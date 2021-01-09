import selfoss from './selfoss-base';


export function executeSearch(term) {
    // execute search
    selfoss.filterReset({ search: term }, true);
    selfoss.db.reloadList();
}
