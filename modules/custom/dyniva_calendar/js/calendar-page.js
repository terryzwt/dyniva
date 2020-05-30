(function($, Drupal) {
  Drupal.behaviors.calendarDate = {
    attach: function(context) {
      $('select#edit-year, select#edit-month').bind('change',function() {
        var y = $('select#edit-year').val();
        var m = $('select#edit-month').val();
        var _class = window.location.pathname; 
        _class = _class.replace(/\/\d{1,6}/g,'');
        window.location = _class + '/' + y + m;
      })
    }
  }
})(jQuery, Drupal)
