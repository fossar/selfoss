function getUrl(path) {
    if (path.startsWith('@')) {
        const url = new URL(path.substr(1), 'http://localhost/');
        const newPath = url.pathname.replace(/\/index\.mdx$/, '/').replace(/\.mdx$/, '/');
        return newPath + url.search + url.hash;
    }

    return path;
}

module.exports = {
    getUrl,
};
