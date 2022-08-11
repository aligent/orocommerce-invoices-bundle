define(function(require) {
    'use strict';

    var PaymentsFormView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');

    PaymentsFormView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            formPaymentMethodSelector: '[name$="[payment_method]"]',
            originPaymentMethodSelector: '[name="paymentMethod"]'
        },

        /**
         * @inheritDoc
         */
        events: {
            change: 'onChange',
            submit: 'onSubmit'
        },

        /**
         * @inheritDoc
         */
        listen: {

        },

        /**
         * @property {number}
         */
        timeout: 50,

        /**
         * @property {string}
         */
        lastSerializedData: null,

        /**
         * @inheritDoc
         */
        constructor: function PaymentsFormView() {
            this.onChange = _.debounce(this.onChange, this.timeout);
            PaymentsFormView.__super__.constructor.apply(this, arguments);
        },

        initialize: function (options) {
            this.options = _.extend({}, this.options, options || {});

            this._changePaymentMethod();

            PaymentsFormView.__super__.initialize.call(this, arguments);
        },

        onChange: function(event) {
            // Do not execute logic when hidden element (form) is refreshed
            if (!$(event.target).is(':visible')) {
                return;
            }

            this._changePaymentMethod();
            this.afterCheck($(event.target), false);
        },

        afterCheck: function($el, force) {
            if (!$el) {
                $el = this.$el;
            }
            const serializedData = this.getSerializedData();

            if (this.lastSerializedData === serializedData && !force) {
                return;
            }

            this.trigger('after-check-form', serializedData, $el);
            this.lastSerializedData = serializedData;
        },

        onSubmit: function (event) {
            event.preventDefault();

            var validate = this.$el.validate();
            if (!validate.form()) {
                return;
            }

            var paymentMethod = this.$el.find(this.options.formPaymentMethodSelector).val();
            var eventData = {
                stopped: false,
                resume: _.bind(this.transit, this),
                data: {paymentMethod: paymentMethod}
            };

            mediator.trigger('checkout:payment:before-transit', eventData);

            if (eventData.stopped) {
                return;
            }

            this.transit();
        },

        transit: function() {
            this._changePaymentMethod();

            var paymentMethod = this.$el.find(this.options.formPaymentMethodSelector).val();
            var eventData = {paymentMethod: paymentMethod};
            mediator.trigger('checkout:payment:before-form-serialization', eventData);

            this.trigger('submit-form', this.getSerializedData());
        },

        getSerializedData: function() {
            var $form = this.$el.closest('form');
            return $form.serialize();
        },

        _changePaymentMethod: function() {
            var $selectedMethodVal = this.$el.find(this.options.originPaymentMethodSelector).filter(':checked').val();

            if (!$selectedMethodVal) {
                return;
            }

            this.$el.find(this.options.formPaymentMethodSelector).val($selectedMethodVal);
        }
    });

    return PaymentsFormView;
});
