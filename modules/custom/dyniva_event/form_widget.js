(function($){
  function daterange_bind(wrapper, index) {
    var $startWrapper = $('.form-date-'+index+'-start', wrapper);
    var $endWrapper = $('.form-date-'+index+'-end', wrapper);

    $('input[type="date"],input[type="time"]', $startWrapper).on('change', function() {
      if(this.value && needReset($startWrapper, $endWrapper)) {
        // end = start
        $('input[type="date"]', $endWrapper).val($('input[type="date"]', $startWrapper).val());
        $('input[type="time"]', $endWrapper).val($('input[type="time"]', $startWrapper).val());
      }
    });
    $('input[type="date"],input[type="time"]', $endWrapper).on('change', function() {
      if(this.value && needReset($startWrapper, $endWrapper)) {
        // start = end
        $('input[type="date"]', $startWrapper).val($('input[type="date"]', $endWrapper).val());
        $('input[type="time"]', $startWrapper).val($('input[type="time"]', $endWrapper).val());
      }
    });
  }

  function buildDate($wrapper) {
    var dateArr, timeArr;
    $date = $('input[type="date"]', $wrapper);
    $time = $('input[type="time"]', $wrapper);
    if($date.length == 0) throw "date input not found";
    if(!$date.val()) throw "date value is empty";

    dateArr = $date.val().split('-');

    if($time.length > 0 && $time.val()) {
      timeArr = $time.val().split(':');
      return new Date(parseInt(dateArr[0]), parseInt(dateArr[1])-1, parseInt(dateArr[2]), parseInt(timeArr[0]), parseInt(timeArr[1]), parseInt(timeArr[2]));
    }
    return new Date(parseInt(dateArr[0]), parseInt(dateArr[1])-1, parseInt(dateArr[2]));
  }

  function hasValue($wrapper) {
    $date = $('input[type="date"]', $wrapper);
    if($date.length > 0 && $date.val()) return true;
    return false;
  }

  function needReset($startWrapper, $endWrapper) {
    if(hasValue($startWrapper) && hasValue($endWrapper)) {
      if(buildDate($endWrapper).getTime() > 0 && buildDate($startWrapper).getTime() > buildDate($endWrapper).getTime()) {
        return true;
      }
    } else {
      return true;
    }
    return false;
  }

  $('.field--widget-daterange-default').each(function() {
    var count = $('.fieldset-wrapper', this).length;
    for(var i = 0; i < count; i++) {
      daterange_bind(this, i);
    }
  });
})(jQuery);