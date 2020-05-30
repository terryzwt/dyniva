(function($, _, Drupal, drupalSettings, Lockr) {

    function notice(title, content) {
        var sended = false;
        if(typeof Notification == 'function') {
            Notification.requestPermission(function (perm) {  
                if (perm == "granted") {
                    var notification = new Notification(title, {
                        dir: "auto",
                        tag: "website",
                        body: content
                    });
                    sended = true;
                }
            });
            if(!sended) {
                alert(content);
            }
        }
    }

    Lockr.prefix = 'dyniva';

    Drupal.dyniva_notifications = {};

    Drupal.behaviors.dyniva_notifications = {
        attach: function(context, settings) {
            if (typeof drupalSettings.dyniva_message.unread_notices == 'undefined') return;
            if (typeof drupalSettings.dyniva_message.browser_notification == 'undefined') return;
            if(!drupalSettings.dyniva_message.browser_notification) return;
            var notices = drupalSettings.dyniva_message.unread_notices;
            if(notices.length > 0 && notices.length != Lockr.get('dyniva_notifications.count')) {
                // Notice
                notice(drupalSettings.dyniva_message.site_name, Drupal.t("You have @total notifications", {"@total": notices.length}));
                Lockr.set('dyniva_notifications.count', notices.length);
            }
        }
    };

})(jQuery, _, Drupal, drupalSettings, Lockr);