+++
title = "Monkey patching"
weight = 30
+++

selfoss will load `user.css` and `user.js` files from the root directory, if present. This allows administrator to adjust the behaviour and appearance of the web client without having to modify the source code itself. This is known as [monkey patching](https://en.wikipedia.org/wiki/Monkey_patch).

<div class="admonition warning">

## Warning

This feature is provided for convenience, **without any promise of backwards compatibility**. Unless explicitly documented, the structure of the HTML elements, their IDs and classes, and available JavaScript symbols can change without any notice.

</div>

## Styling {#css}

Paste custom CSS to `user.css`.

You can find various community-maintained stylesheet snippets [on the wiki](https://github.com/fossar/selfoss/wiki/#style-customizations).

## JavaScript API {#js}

Paste custom JavaScript to `user.js`.

### Custom sharers {#sharers}

You can register custom share buttons. Then, you can enable them by adding the key (`m` in the example below) to [`share` option](@/docs/administration/options.md#share).

```javascript
selfoss.customSharers = {
  m: {
    label: "Share using Moo",
    icon: "🐄",
    action: ({ url, title }) => {
      const u = encodeURIComponent(url);
      const t = encodeURIComponent(title);
      window.open(`https://moo.test/share?u=${u}&t=${t}`);
    },
  },
};
```

You can use arbitrary text (e.g. emoji) or HTML as the icon. For example, `<svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><path fill="#613583" d="m8 4 8 8-8 8L.246 8.117Z"/></svg>` will work too (<svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><path fill="#613583" d="m8 4 8 8-8 8L.246 8.117Z"/></svg>).
