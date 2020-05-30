(function($, _) {

  Drupal.behaviors.admin_preview = {
    attach: function(context, settings) {
      var $previewButton = $('[data-drupal-selector="edit-preview"]');

      $previewButton.on('click', function(e) {
        $(this).parents('form').attr("target", "_blank");
        
        setTimeout(function() {
          $previewButton.parents('form').removeAttr("target").removeAttr('data-drupal-form-submit-last');
        }, 2000);

        if(typeof settings.page_load_progress.delay != 'undefined') {
          var delay = Number(settings.page_load_progress.delay);
          setTimeout(function() {
            $('.page-load-progress-lock-screen').remove();
            $('body').removeAttr('style');
          }, delay+100);
        }
      });
    }
  };
})(jQuery, _);
