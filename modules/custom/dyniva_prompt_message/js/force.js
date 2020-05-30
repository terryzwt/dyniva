(function ($) {

  toastr.options = {
    closeButton: true,
    positionClass: "toast-top-right",
    showDuration: "300",
    hideDuration: "1000",
    timeOut: 10000,
    extendedTimeOut: 1000,
    showEasing: "swing",
    hideEasing: "linear",
    showMethod: "fadeIn",
    hideMethod: "fadeOut",
    tapToDismiss: true
  }

  Drupal.behaviors.dyniva_prompt_message_force = {
    attach: function (context, settings) {
      
      var forceOptions = {
        containerId: 'foreto-tast-container',
        positionClass: "toast-top-center",
        timeOut: 0,
        extendedTimeOut: 0,
        tapToDismiss: false
      };

      // messages
      var $messages = $('.messages', context).not('.messages--normal');
      $messages.each(function () {
        let options = {};
        if ($(this).hasClass('messages--force')) {
          options = forceOptions;
        }
        if ($(this).hasClass('messages--error')) {
          toastr.error(this.innerHTML, '', options);
        } else if ($(this).hasClass('messages--warning')) {
          toastr.warning(this.innerHTML, '', options);
        } else if ($(this).hasClass('messages--status')) {
          toastr.success(this.innerHTML, '', options);
        } else {
          toastr.info(this.innerHTML, '', options);
        }
      });
    }
  }
})(jQuery);
