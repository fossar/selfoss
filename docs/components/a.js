import React from 'react';
import { getUrl } from '../helpers/url';

function a(props) {
    return (
        <a {...props} href={getUrl(props.href)} />
    );
}

module.exports = a;
