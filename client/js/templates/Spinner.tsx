import React from 'react';
import PropTypes from 'prop-types';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import * as icons from '../icons';

export function Spinner({ label, size }) {
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

Spinner.propTypes = {
    label: PropTypes.string.isRequired,
    size: PropTypes.string,
};

export function SpinnerBig({ label }) {
    return (
        <div className="spinner-big">
            <Spinner size="10x" label={label} />
        </div>
    );
}

SpinnerBig.propTypes = {
    label: PropTypes.string.isRequired,
};
