selfoss.shares = {
  urlBuilders: {},
  openInNewWindows: {},
  ids: {},
  enabledShares: '',

  init: function() {
    this.enabledShares = $('#config').data('share');

    this.register('delicious', 'd', true, function(url, title) {
      return "https://delicious.com/save?url="+encodeURIComponent(url) +"&title="+ encodeURIComponent(title);
    });
    this.register('google', 'g', true, function(url, title) {
      return "https://plus.google.com/share?url="+encodeURIComponent(url);
    });
    this.register('twitter', 't', true, function(url, title) {
      return "https://twitter.com/intent/tweet?source=webclient&text="+encodeURIComponent(title)+" "+encodeURIComponent(url);
    });
    this.register('facebook', 'f', true, function(url, title) {
      return "https://www.facebook.com/sharer/sharer.php?u="+encodeURIComponent(url)+"&t="+encodeURIComponent(title);
    });
    this.register('pocket', 'p', true, function(url, title) {
      return "https://getpocket.com/save?url="+encodeURIComponent(url)+"&title="+encodeURIComponent(title);
    });
    this.register('readability', 'r', true, function(url, title) {
      return "http://www.readability.com/save?url="+encodeURIComponent(url);
    });
    this.register('wallabag', 'w', true, function(url, title) {
      return $('#config').data('wallabag')+'/?action=add&url='+btoa(url);
    });
    this.register('email', 'e', false, function(url, title) {
      return "mailto:?body="+encodeURIComponent(url)+"&subject="+encodeURIComponent(title);
    });
  },

  register: function(name, id, openInNewWindow, urlBuilder) {
    this.urlBuilders[name] = urlBuilder;
    this.openInNewWindows[name] = openInNewWindow;
    this.ids[name] = id;
  },

  getAll: function() {
    var keys = new Array();
    for (var key in this.ids) {
      if (this.enabledShares.indexOf(this.ids[key]) >= 0) {
        keys.push(key);
      }
    }
    return keys;
  },

  share: function(key, url, title) {
    var url = this.urlBuilders[key](url, title);
    if (this.openInNewWindows[key]) {
      window.open(url);
    } else {
      document.location.href = url;
    }
  }
};