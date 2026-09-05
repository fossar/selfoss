import React, { ReactNode, RefObject, use } from 'react';
import { LocalizationContext } from '../helpers/i18n';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import * as icons from '../icons';

type DialogProps = {
    ref: RefObject<HTMLDialogElement>;
    children: ReactNode;
};

export default function Dialog(props: DialogProps): React.JSX.Element {
    const { ref, children } = props;
    const _ = use(LocalizationContext);

    return (
        <dialog
            ref={ref}
            onClick={({ target, currentTarget }) => {
                if (target === currentTarget) {
                    // Thanks to argyleink https://web.dev/articles/building/a-dialog-component#adding_light_dismiss
                    ref.current.close('dismiss');
                }
            }}
        >
            <button
                aria-label={_('close_dialog')}
                className="close"
                onClick={() => {
                    ref.current.close();
                }}
            >
                <FontAwesomeIcon icon={icons.close} />
            </button>

            {children}
        </dialog>
    );
}
