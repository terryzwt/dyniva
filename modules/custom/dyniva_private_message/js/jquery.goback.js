/**
 * data-action="goback"
 */
(function ($) { "use strict";

    $(document).ready(function(){
        $('[data-action="goback"]', document).on('click', function() {
            history.back();
            return false;
        });
    });

})(window.jQuery);