(function ($, _, Backbone, Drupal, drupalSettings) {

  'use strict';

  function url(path) {
    var pathPrefix = drupalSettings.path.baseUrl+drupalSettings.path.pathPrefix;
    return pathPrefix + path;
  }

  Drupal.dyniva_notifications.NoticeItemView = Backbone.View.extend({

    tagName: 'li',

    template: _.template('<a href="<%=link%>"><%=text%></a>'),

    events: {
      "click a": "onClick"
    },

    onClick: function(e) {
      e.preventDefault();
      if(this.$el.find('a').attr('href'))
        window.open(this.$el.find('a').attr('href'));
    },

    render: function() {
      this.el.innerHTML = this.template(this.model.toJSON());
      return this;
    }
  });

  Drupal.dyniva_notifications.MakeAllReadView = Drupal.Dyniva.GeneralView.extend({

    events: {
      "click a": "onClick"
    },

    onClick: function(e) {
      var self = this;
      e.preventDefault();
      // make all read
      $.get(url("ajax/notifications/readall"), {}, function(result){
        self.model.set('notices', []);
        self.model.set('total', 0);
        var notices = Drupal.Dyniva.instanceLoader('Drupal.dyniva_notifications.NoticeCollection');
        notices.reset();
      }, "json");
    }
  });

})(jQuery, _, Backbone, Drupal, drupalSettings);