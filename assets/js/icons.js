'use strict';

// Icons must be imported deeply because Parcel’s tree shaking is shaky.
import { faDiaspora } from '@fortawesome/free-brands-svg-icons/faDiaspora';
import { faFacebookSquare } from '@fortawesome/free-brands-svg-icons/faFacebookSquare';
import { faGetPocket } from '@fortawesome/free-brands-svg-icons/faGetPocket';
import { faTwitter } from '@fortawesome/free-brands-svg-icons/faTwitter';
import { faWordpressSimple } from '@fortawesome/free-brands-svg-icons/faWordpressSimple';
import { faCheckCircle as faCheckCircleRegular } from '@fortawesome/free-regular-svg-icons/faCheckCircle';
import { faStar as faStarRegular } from '@fortawesome/free-regular-svg-icons/faStar';
import { faTimesCircle } from '@fortawesome/free-regular-svg-icons/faTimesCircle';
import { faArrowAltCircleDown } from '@fortawesome/free-solid-svg-icons/faArrowAltCircleDown';
import { faArrowRight } from '@fortawesome/free-solid-svg-icons/faArrowRight';
import { faCheckCircle } from '@fortawesome/free-solid-svg-icons/faCheckCircle';
import { faCaretDown } from '@fortawesome/free-solid-svg-icons/faCaretDown';
import { faCaretRight } from '@fortawesome/free-solid-svg-icons/faCaretRight';
import { faCloudUploadAlt } from '@fortawesome/free-solid-svg-icons/faCloudUploadAlt';
import { faCog } from '@fortawesome/free-solid-svg-icons/faCog';
import { faEnvelope } from '@fortawesome/free-solid-svg-icons/faEnvelope';
import { faExternalLinkAlt } from '@fortawesome/free-solid-svg-icons/faExternalLinkAlt';
import { faKey } from '@fortawesome/free-solid-svg-icons/faKey';
import { faSearch } from '@fortawesome/free-solid-svg-icons/faSearch';
import { faSignOutAlt } from '@fortawesome/free-solid-svg-icons/faSignOutAlt';
import { faStar } from '@fortawesome/free-solid-svg-icons/faStar';
import { faSyncAlt } from '@fortawesome/free-solid-svg-icons/faSyncAlt';
import { faTimes } from '@fortawesome/free-solid-svg-icons/faTimes';
// ¡dom needs to be renamed to something else because jsx-dom takes precedence!
import { library, dom as faDom } from '@fortawesome/fontawesome-svg-core';
import wallabag from '../images/wallabag';

/**
 * Register the icons we use in the FontAwesome framework.
 * We do not include all icons by default, since it would make our bundle huge.
 */
export function initIcons() {
    const wallabagIcon = {
        prefix: 'fac',
        iconName: 'wallabag',
        icon: wallabag
    };

    library.add(
        faDiaspora,
        faFacebookSquare,
        faGetPocket,
        faTwitter,
        faWordpressSimple,
        faCheckCircleRegular,
        faStarRegular,
        faTimesCircle,
        faArrowAltCircleDown,
        faArrowRight,
        faCaretDown,
        faCaretRight,
        faCheckCircle,
        faCloudUploadAlt,
        faCog,
        faEnvelope,
        faExternalLinkAlt,
        faKey,
        faSearch,
        faSignOutAlt,
        faStar,
        faSyncAlt,
        faTimes,
        wallabagIcon
    );

    faDom.watch();
}
