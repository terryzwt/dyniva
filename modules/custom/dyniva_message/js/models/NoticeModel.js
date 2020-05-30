(function (_, $, Backbone, Drupal, drupalSettings) {

  'use strict';

  Drupal.dyniva_notifications.NoticeViewModel = Backbone.Model.extend({
    defaults: {
      total: 0,
      notices: []
    },

    initialize: function() {
      var notices = new Drupal.dyniva_notifications.NoticeCollection();
      this.set('total', notices.length)
      this.set('notices', notices);
    }
  });

  Drupal.dyniva_notifications.NoticeModel = Backbone.Model.extend({
    defaults: {
      text: "",
      link: "",
      time: 0
    }
  });

  Drupal.dyniva_notifications.NoticeCollection = Backbone.Collection.extend({

    model: Drupal.dyniva_notifications.NoticeModel,

    initialize: function(options) {
      var self = this;
      if (typeof drupalSettings.dyniva_message.unread_notices == 'undefined') return;
      _.each(drupalSettings.dyniva_message.unread_notices, function(notice) {
        var model = new Drupal.dyniva_notifications.NoticeModel();
        model.set(notice);
        self.add(model);
      });
    }
  });
})(_, jQuery, Backbone, Drupal, drupalSettings);