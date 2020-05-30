/**
 * data-toggle="showif" data-target=".edit-radio" data-value-eq="person"
 */
(function ($) { "use strict";

    var ShowIf = function (element) {
        this.$element = $(element);
        this.$target = $(this.$element.data('target'));
        this.value = this.$element.data('value-eq');
        if(this.$target.length == 0) {
            console.error('jquery.showif: No match target.');
        }
    }

    ShowIf.prototype.update = function() {
        var _self = this;
        this.$target.each(function(){
            if(this.tagName == 'INPUT' && this.type == 'radio') {
                if(this.value != _self.value) return;
                if(this.checked) {
                    _self.$element.removeClass('hide');
                } else {
                    _self.$element.addClass('hide');
                }
            } else if(this.tagName == 'SELECT') {
                if(this.value == _self.value) {
                    _self.$element.removeClass('hide');
                } else {
                    _self.$element.addClass('hide');
                }
            } else {
                console.error('jquery.showif: Target must be SELECT or RADIO.');
            }
        });
    }

    ShowIf.prototype.init = function () {
        var _self = this;

        this.$target.on('change', function() {
            _self.update();
        });
        _self.update();
    };

    var old = $.fn.ShowIf;

    $.fn.ShowIf = function () {
        return this.each(function () {
            var c = new ShowIf(this);
            c.init();
        })
    }

    $.fn.ShowIf.Constructor = ShowIf;

    $.fn.ShowIf.noConflict = function () {
        $.fn.ShowIf = old;
        return this;
    }

    $(document).ready(function(){
        $('[data-toggle="showif"]', document).ShowIf();
    });

})(window.jQuery);