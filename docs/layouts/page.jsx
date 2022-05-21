'use strict';

import React from 'react';
import Layout from './default.jsx';

function buildTree(otherPageMetas) {
    let sections = {
        pages: {},
        subsections: {},
    };
    otherPageMetas.forEach(({ url, title, weight = 0 }) => {
        let path = url.replace(/^\/|\/$/g, '').split('/');
        const page = path.pop();
        let currentSection = sections;
        path.forEach((segment) => {
            if (!(segment in currentSection.subsections)) {
                currentSection.subsections[segment] = {
                    pages: {},
                    subsections: {},
                };
            }
            currentSection = currentSection.subsections[segment];
        });

        currentSection.pages[page] = {
            title,
            weight,
            /* TODO: ensure trailing slash */
            url: url + '/',
        };

        if (!(page in currentSection.subsections)) {
            currentSection.subsections[page] = {
                pages: {},
                subsections: {},
            };
        }
        // TODO: Cannot distinguish between pages and sections
        currentSection.subsections[page].title = title;
        currentSection.subsections[page].weight = weight;
    });

    return sections;
}

const weightComparator = (a, b) => a.weight - b.weight;

function PageLayout({ mdxContent, meta, pageContext }) {
    const docSection = buildTree(pageContext.otherPageMetas).subsections.docs;

    const side = (
        <nav>
            {Object.entries(docSection.subsections).sort(weightComparator).map(([key, sec]) => {
                return (
                    <React.Fragment key={key}>
                        <h3>{sec.title}</h3>
                        <ul>
                            {/* TODO: ensure trailing slash */}
                            {Object.entries(sec.pages).sort(weightComparator).map(([key, page]) => (
                                <li key={key} className={meta.url + '/' === page.url ? 'active' : ''}>
                                    <a href={page.url}>{page.title}</a>
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
            meta={meta}
            side={side}
            pageContext={pageContext}
            mdxContent={
                <React.Fragment>
                    <h1>{meta.title}</h1>
                    {mdxContent}
                </React.Fragment>
            }
        />
    );
}

module.exports = PageLayout;
