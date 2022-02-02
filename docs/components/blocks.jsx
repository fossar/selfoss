const React = require('react');

function DocSection({ children }) {
    return (
        <div className="documentation-entry">
            {children}
        </div>
    );
}

function Option({ children }) {
    return (
        <div className="config-option">
            {children}
        </div>
    );
}

module.exports = {
    DocSection,
    Option,
}
