(function ($, _, Drupal, Backbone) {

  /** 
   * show qrcode 
   */
  Drupal.behaviors.qrcode = {
    // mode
    Model: Backbone.Model.extend({
      defaults: {
        target: '.toolbar-item[data-qrcode-src]',
        imgSrc: '',
        width: '150px',
        text: Drupal.t(''), // qrcode text
        showMenu: false // toolbar menu show/hide text
      },

      initialize: function() {
        this.setTitleStatu()
      },

      setTitleStatu: function() {
        this.set('showMenu', this.getTitleStatu())
      },

      getTitleStatu: function() {
        var titleStatu = $(this.attributes.target).data('title');
        return titleStatu;
      }
    }),

    // view
    View: Backbone.View.extend({
      className: 'qrcode-content',

      initialize: function () {
        this.setHostStatus();
        this.render();
      },

      template: _.template('<img src="<%= imgSrc %>" alt="qrcode" /><p class="qrcode-footer <%= text ? "isShow" : "isHide" %>"><%= text %></p>'),
      render: function () {
        this.$el.width(this.model.get('width'));
        this.$el.html(this.template(this.model.attributes));
        $(this.model.get('target')).append(this.$el);
      },

      setHostStatus: function() {
        if (this.model.get('showMenu') === false){
          $(this.model.get('target')).addClass('hideTitle');
        }
      }

    }),

    // Drupal attach hook
    attach: function () {

      /**
       * new backbone
       */
      var data = $('#toolbar-bar').find('[data-qrcode-src]').data();

      var qrcode = new this.Model({
        imgSrc: data.qrcodeSrc
      });

      var qrcodeView = new this.View({
        model: qrcode
      })

      /** 
       * jQUery events
       */
      $('.toolbar-tab').on('click', qrcode.get('target'), function(event){
        event.preventDefault();
        qrcodeView.$el.toggle();
      })
    }
  }
}(jQuery, _, Drupal, Backbone))
