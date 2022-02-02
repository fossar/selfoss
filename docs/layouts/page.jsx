'use strict';

const React = require('react');
const Layout = require('./default.jsx');

function PageLayout({ mdxContent, meta, pageContext }) {
    const doc_section = get_section("docs/_index.md");

    const side = (
        <nav>
        {doc_section.subsections.map((sec_path) => {
            const sec = get_section(sec_path);
            return (
                <React.Fragment>
                    <h3>{sec.title}</h3>
                    <ul>
                        {sec.pages.map((page) => (
                            <li class={current_path === page.path ? 'active' : ''}>
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
        >
            <h1>{meta.title}</h1>
            {mdxContent}
        </Layout>
    );
}

module.exports = PageLayout;
