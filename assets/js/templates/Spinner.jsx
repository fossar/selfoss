import React from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';

export default function Spinner() {
    return (
        <div className="spinner-big">
            <FontAwesomeIcon icon="spinner" size="10x" spin />
        </div>
    );
}
