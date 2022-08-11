define(function(require) {
    'use strict';

    var PaymentsComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var PaymentsFormView = require('aligentinvoice/js/app/views/payments-form-view');

    PaymentsComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            saveStateRoute: null,
        },

        /**
         * @inheritDoc
         */
        constructor: function PaymentsComponent(options) {
            PaymentsComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options || {});

            this.$form = this.options._sourceElement.find('form');
            this.formView = new PaymentsFormView({
                el: this.$form
            });

            this.formView.on('submit-form', _.bind(this.onSubmit, this));

            this.formView.on('after-check-form', _.bind(this.onAfterCheckForm, this));

            PaymentsComponent.__super__.initialize.call(this, this.options);

            /**
             * Fire the OnChange event so we trigger a SaveState, which will assign the
             * currently selected Payment Method to the InvoicePayment,
             * and trigger an accurate Recalculation of the Subtotals (which can apply
             * processing fees etc if configured)
             */
            this.$form.trigger('change');
        },

        /**
         * @param {string} serializedData
         * @param {jQuery} $field
         */
        onAfterCheckForm: function(serializedData, $field) {
            let data = this.$form.serialize();

            let paymentId = this.$form.find('[name$="[id]').val();
            $.ajax({
                url: routing.generate(this.options.saveStateRoute, {id: paymentId}),
                method: 'POST',
                data: data
            })
            .done(function() {
                mediator.trigger('aligent:invoice:payment:changed');
            })
            .fail(function(data) {
                mediator.execute('hideLoading');
                let errorMessage = 'Could not save Payment State';
                if ('responseJSON' in data) {
                    errorMessage = data.responseJSON.message;
                }
                mediator.execute('showFlashMessage', 'error', errorMessage);
            });
        },

        onSubmit: function (data) {
            mediator.execute('showLoading');

            var url = this.formView.$el.prop('action');

            $.ajax({
                url: url,
                method: 'POST',
                data: data
            })
                .done(this.onSuccess.bind(this))
                .fail(function() {
                    mediator.execute('hideLoading');
                    mediator.execute('showFlashMessage', 'error', 'Could not perform action');
                });
        },

        onSuccess: function (response) {
            if (response.hasOwnProperty('responseData')) {
                var eventData = {stopped: false, responseData: response.responseData};
                mediator.trigger('checkout:place-order:response', eventData);
                if (eventData.stopped) {
                    return;
                }
            }

            if (response.hasOwnProperty('redirectUrl')) {
                mediator.execute('redirectTo', {url: response.redirectUrl}, {redirect: true});
            } else if (response.hasOwnProperty('errors')) {
                // Display form validation errors as a flash message
                var type = 'error';
                var errorMessage = '';

                _.each(
                    response.errors,
                    function (message) {
                        errorMessage += message + '</br>';
                    }
                );

                _.delay(function() {
                    mediator.execute('showFlashMessage', type, errorMessage);

                }, 100);
            } else {
                var type = 'error';
                var message = 'An error occurred while processing your payment';
                _.delay(function() {
                    mediator.execute('showFlashMessage', type, message);

                }, 100);
            }

            mediator.execute('hideLoading');
        }
    });

    return PaymentsComponent;
});
