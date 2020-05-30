/**
 * @file
 * JavaScript functionality for the private message notification block.
 */

(function ($, Drupal, drupalSettings, window) {

  "use strict";

  Drupal.behaviors.dynivaPrivateMessageNotificationBlock = {
    attach:function () {
      $('[data-action="private_message_load_thread"]').on('click', function(e) {
        var link = this;
        e.preventDefault();
        $.ajax({
          url:drupalSettings.dynivaPrivateMessageNotificationBlock.loadThreadCallback,
          success:function (data) {
            location.href = $(link).attr('href');
          }
        });
      });

      $("time.timeago").timeago();
    }
  };
}(jQuery, Drupal, drupalSettings, window));
