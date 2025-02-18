(function($) {
    const app = {
        totalPrice: 0,
        starterKitData: [],
        modal: '#sp-upm-modal-starter-kit',
        init: function() {
            this.onStarterKitQuantityChange();
            this.proceedToCheckout();
            this.onCloseButtonClick();
            this.onCartRefreshed();
            this.computeTotal();
            this.triggerFreeBattery();
            this.setNRTPriceTier();
        },
        setStartreckPremium: function() {
            const startreckPremium = document.getElementById('shipping_method_0_flat_rate12');

            if (startreckPremium && startreckPremium.checkVisibility()) {
                startreckPremium.click()
            }
        },
        onStarterKitQuantityChange: function(e) {
            // Change event is fine on mobile devices
            $('.sp-sk-quantity-js').on('change', function() {
                setTimeout(() => {
                  app.computeTotal();
                }, 100);
            });
        },
        computeTotal: function() {
            let total = 0;
            app.starterKitData = [];

            $('.sp-sk-quantity-js').each(function() {
                const quantity = parseInt($(this).val());

                if (quantity) {
                    const $parent = $(this).parents('.nrt-product-cusstomization-wrapper');
                    const product_id = $(this).parents('.product-custom-item').data().productId;
                    const price = parseFloat($parent.data().price);

                    const itemTotal = price * quantity;

                    app.starterKitData.push({
                        product_id: product_id,
                        quantity: quantity
                    });

                    total += itemTotal;
                }
            });

            app.totalPrice = total;

            $('#starter-kit-total-price .elementor-heading-title').text(`Total: $${app.totalPrice.toFixed(2)}`);

            if (total) {
              jQuery('#starter-kit-add-to-cart, #starter-kit-checkout').removeAttr('disabled')
            }
        },
        setNRTPriceTier: function() {
          $('.nrt-choose--nrt-pods .sp-sk-quantity-js').on('change', function() {
            const totalPodsQuantity = app.getPodsTotalQuantity();
            console.log(totalPodsQuantity)
            const $parent = $(this).parents('.nrt-product-cusstomization-wrapper');

            if (totalPodsQuantity >= 3 && totalPodsQuantity <= 4) {
              $parent.data().price = '22.50';
            } else if (totalPodsQuantity >= 5) {
              $parent.data().price = '20.00';
            } else {
              $parent.data().price = '25.00';
            }

            $('.nrt-choose--nrt-pods .nrt-heading span').text(`$${$parent.data().price} / each`);
          });
        },
        getPodsTotalQuantity: function() {
          let quantity = 0;

          $('.nrt-choose--nrt-pods .sp-sk-quantity-js').each(function() {
            quantity += parseInt($(this).val());
          });

          return quantity;
        },
        proceedToCheckout: function() {
            // Add support for both click and touchstart to work on mobile devices
            $('#starter-kit-checkout, #starter-kit-add-to-cart').on('click touchstart', function(e) {
                e.preventDefault();
                e.stopPropagation();
    
                if (! app.validateStarterKit()) return;
    
                let buttonText = $('.elementor-button-text', this).text();
                const product_category_id = $('.starter-kit-items').data().productCategoryId;
                const $parentContainer = $(e.target).parents('[data-action-type]');
                const actionType = $parentContainer.data().actionType;
    
                const ajaxData = {
                    action: sp_upm_ajax_public.starter_kit_action,
                    ajax_nonce: sp_upm_ajax_public.ajax_nonce,
                    data: app.starterKitData,
                    product_category_id: product_category_id,
                    action_type: actionType
                }
    
                $.ajax({
                    type : 'post',
                    url: sp_upm_ajax_public.admin_url,
                    dataType: 'json',
                    data: ajaxData,
                    beforeSend: function(xhr) {
                        $('.sp-upm-loading-indicator').addClass('is-active');
                        $('[data-action-type] .elementor-button').addClass('is-disabled');
                        $parentContainer.find('.elementor-button-text').text('Processing...');
                    },
                    success:function(response) {
                        if (response.success && actionType == 'checkout') {
                            window.location.href = response.data.redirect_url;
                        } else {
                            $(document.body).trigger("wc_fragment_refresh");
                        }
                    },
                    error: function(response) {
                        $('.sp-upm-loading-indicator').removeClass('is-active');
                        $('[data-action-type] .elementor-button').removeClass('is-disabled');

                        if (response) console.log(response);
                    },
                    complete: function({ responseJSON }) {
                        $parentContainer.find('.elementor-button-text').text(buttonText);

                        if (responseJSON.data.message) {
                            $(app.modal).addClass('is-active');
                            $('.sp-upm-modal-header-text', app.modal).html(responseJSON.data.header);
                            $('.sp-upm-modal-body', app.modal).html(responseJSON.data.message);
                        }

                        $('.sp-upm-loading-indicator').removeClass('is-active');
                    }
                });
            });
        },
        validateStarterKit: function() {
            app.computeTotal();

            let isQuantityValidated = true;

            if ($('.nrt-choose--nrt-vaporizer').length) {
                isQuantityValidated = app.validateNRTStarterKit();
            } else {
                isQuantityValidated = app.validateTHCStarterKit();
            }

            // Check if any required product is missing
            if (app.totalPrice == 0 || !isQuantityValidated) {
                elementorProFrontend.modules.popup.showPopup({ id: 55848 });
                return false;
            }

            return true;
        },
        validatePanelQuantity: function(requiredQuantity = 2) {
            const has_previous_order = parseInt(sp_upm_ajax_public.has_previous_nrt);
            if (has_previous_order) return true;
    
            const $kits = $('.starter-kit-items > div');
            let requiredProductsCount = 0;
    
            $kits.each(function (i, el) {
                let hasRequiredItem = false;
    
                // Check if this is the lanyard, which is optional
                const isLanyard = $(el).hasClass('nrt-choose--nrt-lanyard');
    
                // Only require a quantity for non-lanyard products
                if (!isLanyard) {
                    $('.sp-sk-quantity-js', el).each(function(k, $quantity) {
                        if (parseInt($quantity.value)) {
                            hasRequiredItem = true;
                        }
                    });
                }

                if (hasRequiredItem) requiredProductsCount++;
            });

            // Ensure that the device and pods (required products) have been selected (minimum 2)
            return requiredProductsCount >= requiredQuantity;
        },
        validateTHCStarterKit: function() {
            return this.validatePanelQuantity(1);
        },
        validateNRTStarterKit: function() {
            return this.validatePanelQuantity();
        },
        onCloseButtonClick: function() {
            // Also add touchstart to make it responsive for mobile
            $('.js-sp-upm-button--confirm, #sp-upm-modal-close-button').on('click touchstart', function() {
                $(`#sp-upm-modal-starter-kit`).removeClass('is-active');
            });
        },
        onCartRefreshed: function() {
            if (! $('.starter-kit-items').length) return;

            $('body').on("wc_fragments_refreshed", function() {
                $('#elementor-menu-cart__toggle_button').trigger('click');
                $('.sp-upm-loading-indicator').removeClass('is-active');
                $('[data-action-type] .elementor-button').removeClass('is-disabled');
            });
        },
        triggerFreeBattery: function() {
            if (! $('.coupon-free_thc_battery').length) return;
            // $(document.body).trigger('update_checkout');
            jQuery('body').trigger('update_checkout')

                // var text = $('.coupon-free_thc_battery .woocommerce-Price-amount').text(); // Get the text content
                // var amount = text.match(/[\d,.]+/); // Extract the number
                // if (parseInt(amount[0])) return;

                // document.querySelector('.coupon-free_thc_battery .woocommerce-remove-coupon').click()
            // });
        }
    };

    const WPFormAutoNext = {
        init: function() {
            this.cacheDom();
            this.bindEvents();
            this.skipToConfirmAppointment();
        },

        cacheDom: function() {
            this.$document = $(document);
            this.$formSteps = $('.wpforms-page');
            this.$pageIndicator = $('.wpforms-page-indicator');
            this.$submitButton = $('[name="wpforms[submit]"]');
            this.$promoInput = $('.sp-marketing-promotion input');
            this.$allFields = $('.wpforms-page:not(.sp-form-auto-next--disabled) input:not([type="hidden"]), .wpforms-page:not(.sp-form-auto-next--disabled) textarea, .wpforms-page:not(.sp-form-auto-next--disabled) select');
        },

        bindEvents: function() {
            const self = this;
            let timeoutId;

            self.$allFields.each(function() {
                $(this).on('input', function() {
                    clearTimeout(timeoutId); // Clear the previous timeout

                    let currentPage = parseInt(self.$pageIndicator.attr('aria-valuenow'));
                    const timeOutSeconds = self.$formSteps.eq(currentPage - 1).find('.wpforms-conditional-field:not(.wpforms-field-password)').length ? 3500 : 2000;

                    timeoutId = setTimeout(() => {
                        if ($(this).val().trim() !== '') {
                            self.autoNext();
                        }
                    }, timeOutSeconds);
                });
            });
        },

        autoNext: function() {
            let currentPage = parseInt(this.$pageIndicator.attr('aria-valuenow'));
            if (this.arePageFieldsFilled(currentPage)) {
                this.nextPrev(currentPage);
            }
        },

        nextPrev: function(currentPage) {
            const nextPage = currentPage + 1;
            const hasPromoCode = this.$promoInput.val();

            if (hasPromoCode && $(`.wpforms-page-${nextPage}`).hasClass('last')) {
                this.$submitButton.click();
            } else {
                $(`.wpforms-page-${currentPage}`).find('.wpforms-page-next').trigger('click');
            }
        },

        arePageFieldsFilled: function(currentPage) {
            const fields = this.$formSteps.eq(currentPage - 1).find('.wpforms-field:not(.wpforms-conditional-hide) input:not([type="hidden"]), .wpforms-field:not(.wpforms-conditional-hide) textarea, .wpforms-field:not(.wpforms-conditional-hide) select');

            return fields.toArray().every(field => {
                if (field.type == 'radio' || field.type == 'checkbox') {
                    const selectedValue = $(`[name="${field.name}"]:checked`).val();
                    return selectedValue && selectedValue.trim() !== '';
                } else {
                    return field.value.trim() !== '';
                }
            });
        },

        skipToConfirmAppointment: function() {
            $(document).on('click', '.confirm-appointment', function(e) {
                e.stopPropagation();
                e.stopImmediatePropagation();
                e.preventDefault();

                self.$submitButton.click();
            })
        }
    };

    $(document).ready(function() {
        app.init();
        WPFormAutoNext.init();
    });

    $(window).on('load', function() {
        app.triggerFreeBattery();
        app.setStartreckPremium();
    });
})(jQuery)