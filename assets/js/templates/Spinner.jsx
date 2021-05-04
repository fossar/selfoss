import React from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import * as icons from '../icons';


export function Spinner({ size }) {
    return (
        <FontAwesomeIcon icon={icons.spinner} size={size} spin />
    );
}


export function SpinnerBig() {
    return (
        <div className="spinner-big">
            <Spinner size="10x" />
        </div>
    );
}
