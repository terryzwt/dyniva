// require('bootstrap/js/transition');
// require('bootstrap/js/alert');
// require('bootstrap/js/tab');
// require('bootstrap/js/modal');
// require('bootstrap/js/dropdown');
// require('bootstrap/js/collapse');

// ----- 上面部分按需使用 ----- //
// ----- ==  ReadMe  == ----- //
// ----- 下面部分自行修改 ----- //

let Drupal = window.Drupal || {};

(function ($) {

  Drupal.behaviors.siteScript = {
    first: true,
    attach(context) {

      if (!this.first) return false;

      $('#content-modal-nav').modal({
        backdrop: false,
        'show': true,
      });

      // Message alert button
      this.librarys.messages;

      // Default Set with drupal.js
      this.librarys.footer($);

      // Set Swiper 2.7.3

      this.first = false;

    },
    plugins: {
      // Plugins
      // 'swipers2' : require('./plugins/swipers2')
    },
    librarys: {
      // Core
      'messages': require('./drupal/davyinui.messages'),
      'footer': require('./drupal/davyinui.footer')
    }
  }

}(jQuery));
