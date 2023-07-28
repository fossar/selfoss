import React from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import * as icons from '../icons';
import { SizeProp } from '@fortawesome/fontawesome-svg-core';

type SpinnerProps = {
    label: string;
    size?: SizeProp;
};

export function Spinner(props: SpinnerProps): JSX.Element {
    const { label, size } = props;

    return (
        <>
            <FontAwesomeIcon
                icon={icons.spinner}
                size={size}
                spin
                aria-hidden="true"
                title={label}
            />
            <span className="visually-hidden" role="alert">
                {label}
            </span>
        </>
    );
}

type SpinnerBigProps = {
    label: string;
};

export function SpinnerBig(props: SpinnerBigProps): JSX.Element {
    const { label } = props;

    return (
        <div className="spinner-big">
            <Spinner size="10x" label={label} />
        </div>
    );
}
