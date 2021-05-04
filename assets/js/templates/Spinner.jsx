import React from 'react';
import PropTypes from 'prop-types';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import * as icons from '../icons';


export function Spinner({ size }) {
    return (
        <FontAwesomeIcon icon={icons.spinner} size={size} spin />
    );
}

Spinner.propTypes = {
    size: PropTypes.string.isRequired,
};

export function SpinnerBig() {
    return (
        <div className="spinner-big">
            <Spinner size="10x" />
        </div>
    );
}
