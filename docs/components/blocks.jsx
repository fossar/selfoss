import React from 'react';

function DocSection({ children }) {
    return (
        <div className="documentation-entry">
            {children}
        </div>
    );
}

function Deprecation({ deprecated, children }) {
    if (deprecated) {
        return (
            <React.Fragment>
                <del>{children}</del> (deprecated)
            </React.Fragment>
        );
    } else {
        return children;
    }
}

function Option({ children, name, deprecated = false }) {
    return (
        <div className="config-option">
            <h3 id={name.replace('_', '-')}>
                <Deprecation deprecated={deprecated}>
                    <code>{name}</code>
                </Deprecation>
            </h3>
            {children}
        </div>
    );
}

module.exports = {
    DocSection,
    Option,
};
