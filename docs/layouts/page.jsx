'use strict';

const React = require('react');
const Layout = require('./default.jsx');

function PageLayout({ mdxContent, meta, pageContext }) {
    const docSection = getSection('docs/_index.md');

    const side = (
        <nav>
        {docSection.subsections.map((sec_path) => {
            const sec = getSection(sec_path);
            return (
                <React.Fragment>
                    <h3>{sec.title}</h3>
                    <ul>
                        {sec.pages.map((page) => (
                            <li className={meta.url === page.path ? 'active' : ''}>
                                <a href={page.permalink}>{page.title}</a>
                            </li>
                        ))}
                    </ul>
                </React.Fragment>
            );
        })}
        </nav>
    );

    return (
        <Layout
            title={meta.title}
            postHeader={postHeader}
            scripts={scripts}
            mdxContent={mdxContent}
            meta={meta}
            pageContext={pageContext}
        >
            <h1>{meta.title}</h1>
            {mdxContent}
        </Layout>
    );
}

module.exports = PageLayout;
