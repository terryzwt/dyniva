(function ($) {

  Drupal.behaviors.siteTheme = {
    attach: function (context) {

    }
  }

  /* - Use drupal system ---------------- */
  /* - info.yml ------------------------- */
  /* - libraries themeName/plugins.code - */

  Drupal.behaviors.davyinui = {
    first: true,
    attach: function (context) {
      var self = this
      if(!this.first) return false;

      // prettyPrint
      if($('.prettyprint').length){
        PR.prettyPrint()
      }

      this.first = false;
    }
  }
  $(document).ready(function(){

  })
  /* - !Use frontEnd npm --------------- */
  /* - @see assets/scripts/index.js ---- */
  /* - Need npm install first ---------- */

}(jQuery))
