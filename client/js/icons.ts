'use strict';

// Icons must be imported deeply because Parcel’s tree shaking is shaky.
import { faDiaspora } from '@fortawesome/free-brands-svg-icons/faDiaspora';
import { faFacebookSquare } from '@fortawesome/free-brands-svg-icons/faFacebookSquare';
import { faGetPocket } from '@fortawesome/free-brands-svg-icons/faGetPocket';
import { faTwitter } from '@fortawesome/free-brands-svg-icons/faTwitter';
import { faWordpressSimple } from '@fortawesome/free-brands-svg-icons/faWordpressSimple';
import { faMastodon } from '@fortawesome/free-brands-svg-icons/faMastodon';
import { faCheckCircle as faCheckCircleRegular } from '@fortawesome/free-regular-svg-icons/faCheckCircle';
import { faStar as faStarRegular } from '@fortawesome/free-regular-svg-icons/faStar';
import { faTimesCircle } from '@fortawesome/free-regular-svg-icons/faTimesCircle';
import { faArrowAltCircleDown } from '@fortawesome/free-solid-svg-icons/faArrowAltCircleDown';
import { faArrowRight } from '@fortawesome/free-solid-svg-icons/faArrowRight';
import { faCheck } from '@fortawesome/free-solid-svg-icons/faCheck';
import { faCheckCircle } from '@fortawesome/free-solid-svg-icons/faCheckCircle';
import { faCaretDown } from '@fortawesome/free-solid-svg-icons/faCaretDown';
import { faCaretRight } from '@fortawesome/free-solid-svg-icons/faCaretRight';
import { faCloudUploadAlt } from '@fortawesome/free-solid-svg-icons/faCloudUploadAlt';
import { faCog } from '@fortawesome/free-solid-svg-icons/faCog';
import { faCopy } from '@fortawesome/free-solid-svg-icons/faCopy';
import { faEnvelope } from '@fortawesome/free-solid-svg-icons/faEnvelope';
import { faExternalLinkAlt } from '@fortawesome/free-solid-svg-icons/faExternalLinkAlt';
import { faKey } from '@fortawesome/free-solid-svg-icons/faKey';
import { faSearch } from '@fortawesome/free-solid-svg-icons/faSearch';
import { faShareAlt } from '@fortawesome/free-solid-svg-icons/faShareAlt';
import { faSignOutAlt } from '@fortawesome/free-solid-svg-icons/faSignOutAlt';
import { faSlash } from '@fortawesome/free-solid-svg-icons/faSlash';
import { faSpinner } from '@fortawesome/free-solid-svg-icons/faSpinner';
import { faStar } from '@fortawesome/free-solid-svg-icons/faStar';
import { faSyncAlt } from '@fortawesome/free-solid-svg-icons/faSyncAlt';
import { faTimes } from '@fortawesome/free-solid-svg-icons/faTimes';
import { faWifi } from '@fortawesome/free-solid-svg-icons/faWifi';
import wallabagIcon from '../images/wallabag';

export const wallabag = {
    prefix: 'fac',
    iconName: 'wallabag',
    icon: wallabagIcon,
};

/**
 * Register the icons we use in the FontAwesome framework.
 * We do not include all icons by default, since it would make our bundle huge.
 */
export {
    faDiaspora as diaspora,
    faFacebookSquare as facebook,
    faGetPocket as pocket,
    faTwitter as twitter,
    faWordpressSimple as wordpress,
    faMastodon as mastodon,
    faCheckCircleRegular as markUnread,
    faStarRegular as star,
    faTimesCircle as close,
    faArrowAltCircleDown as loadImages,
    faArrowRight as next,
    faCaretDown as arrowExpanded,
    faCaretRight as arrowCollapsed,
    faCheck as check,
    faCheckCircle as markRead,
    faCloudUploadAlt as settings,
    faCog as menu,
    faCopy as copy,
    faEnvelope as email,
    faExternalLinkAlt as openWindow,
    faKey as logIn,
    faSearch as search,
    faShareAlt as share,
    faSignOutAlt as signOut,
    faSlash as slash,
    faSpinner as spinner,
    faStar as unstar,
    faSyncAlt as reload,
    faTimes as remove,
    faWifi as connection,
};
