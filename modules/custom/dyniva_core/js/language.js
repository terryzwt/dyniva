(function($, _) {

  Drupal.behaviors.admin_language = {
    attach: function(context, settings) {
      $('#block-adminlanguageswitcher .form-select').on("click", function(e) {
        e.stopPropagation();
        e.preventDefault();
      }).on('change', function() {
        location.href = '/manage/user/admin_language_switch/'+this.value+'?destination='+document.location.pathname;
      });
    }
  };
})(jQuery, _);
