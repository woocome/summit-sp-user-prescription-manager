(function($) {
    const app = {
        currentData: null,
        modalId: "",
        $activeModal: "",
        entryId: 0,
        buttonAction: "",
        eventButtonClickPrefix: 'sp_upm_button_action_',
        entryData: null,
        errors: {
            noselected: 'Please select a product'
        },
        init: function() {
            this.handleEntryButtonClick();
            this.onApprovePrescriptionButtonClick();
            this.handleEditEntry();
            this.onSelectProduct();
            this.onContinueButtonClick();
            this.onCloseClick();
            this.handleOnClickDelete();

            // events hook
            this.handlePrescribeAction();

            $('#treatment_category').on('change', app.handleOnTreatmentCategoryChange);
        },
        handleEntryButtonClick: function() {
            $('.btn-entry-action').on('click', function(e) {
                e.preventDefault();
                app.clearRow();

                app.entryId = e.target.dataset.entryid;
                app.buttonAction = e.target.dataset.action;
                app.entryData = JSON.parse(jQuery(`#form_entry_data_${app.entryId}`).html());

                $(document).trigger(app.eventButtonClickPrefix + app.buttonAction);
                app.toggleModal(app.buttonAction);
            });
        },
        handlePrescribeAction: function() {
            $(document).on(app.eventButtonClickPrefix + 'approve', function(e) {
                $('#treatment_category').val(parseInt(app.entryData.treatment_id)).trigger('change');
            });
        },
        onCloseClick: function() {
            $(document).on('click', '#sp-upm-modal-close-button, .sp-upm-modal-cancel-btn, .sp-upm-modal-overlay', function(e) {
                e.preventDefault();

                app.toggleModal(app.buttonAction);
                app.toggleBannerMessage(false);
                app.resetCurrentData();
            });
        },
        toggleModal: function(action) {
            $(`#sp-upm-modal-${action}`).toggleClass('is-active');
        },
        toggleLoadingIndicator: function() {
            $('.sp-upm-loading-indicator').toggleClass('is-active');
        },
        toggleBannerMessage: function(show = true) {
            if (show) {
                $('.sp-upm-banner-message').addClass('is-active');
            } else {
                $('.sp-upm-banner-message').removeClass('is-active');
            }
        },
        onApprovePrescriptionButtonClick: function() {
            $(document).on(app.eventButtonClickPrefix + 'approve', function(e) {
                const _userData = $(`.pending-prescription-${app.entryId} td[data-colname="Customer"] a`).html();
                const _userTreatment = $(`.pending-prescription-${app.entryId} td[data-colname="Treatment"] > strong`).text();

                $('.user-data').html(_userData);
                $('.user-treatment').text(_userTreatment);
            });
        },
        onContinueButtonClick: function() {
            $(document).on('click', '.sp-upm-modal-proceed-btn', function(e) {
                e.preventDefault();

                app.toggleBannerMessage(false);

                app.ajaxProcessAction();
            });
        },
        handleOnClickDelete: function() {
            $(document).on(app.eventButtonClickPrefix + 'delete', function(e) {
                app.setDeleteModalContent();
            });

        },
        handleEditEntry: function() {
            $(document).on(app.eventButtonClickPrefix + 'edit', function(e) {
                $('#booking_date').val(app.entryData.appointment_date);
                $('#booking_time').val(app.entryData.appointment_time);
            });
        },
        setDeleteModalContent: function() {
            $(`#sp-upm-modal-${app.buttonAction} .sp-upm-modal-body`).html(`
                <p>Please click <strong>"Yes, continue"</strong> to proceed with the deletion of entry with an ID of ${app.entryId}</p>
            `);
        },
        setAjaxData: function() {
            const $row = $(`.pending-prescription-${app.entryId}`);
            const userId = $row.data().userId;
            const formId = $row.data().formId;

            const treatmentId = $('#treatment_category').val();
            const selectedProduct = $(`#treatment_medication_${treatmentId}`).find('option:selected');

            // set the data to be used on the ajax request
            app.currentData = {
                button_action: app.buttonAction,
                entry_id: app.entryId,
                form_id: formId,
                treatment_id: treatmentId,
                user_id: userId,
                product_id: selectedProduct.val(),
                date: $('input[name="expiration_date"]').val(),
                product_name: selectedProduct.text(),
                prescriber_id: $('#treatment_prescriber').val(),
                appointment_date: $('#booking_date').val(),
                appointment_time: $('#booking_time').val(),
                max_repeat_count: $('#js-repeat-count').val(),
                initial: app.entryData
            }

            return app.currentData;
        },
        onSelectProduct: function() {
            $('.prescribe-product').on('change', function() {
                app.clearRow();
            });
        },
        ajaxProcessAction: function() {
            app.toggleLoadingIndicator();
            app.setAjaxData();

            const defaultData = {
                action: sp_upm_ajax[app.currentData.button_action + '_action'],
                ajax_nonce: sp_upm_ajax[app.currentData.button_action + '_ajax_nonce'],
            }

            const data = {
                ...defaultData,
                ...app.currentData
            }

            $.ajax({
                type : 'post',
                url: sp_upm_ajax.admin_url,
                dataType: 'json',
                data: data,
                success:function(response) {
                    app.toggleBannerMessage(response);
                    app.setAjaxMessage(response);

                    if (response.success) {
                        $('.sp-upm-modal-main-content').hide();

                        $(document).trigger('sp_upm_ajax_success_' + app.currentData.button_action, [response]);

                        app.resetCurrentData();

                        setTimeout(function() {
                            $('.sp-upm-modal').removeClass('is-active');
                            $('.sp-upm-modal-main-content').show();
                        }, 2000)
                    }
                },
                error: function(response) {
                    alert(response.message)
                },
                complete: function() {
                    app.toggleLoadingIndicator();
                }
            })
        },
        setAjaxMessage: function(response) {
            $('.sp-upm-banner-message').addClass(`sp-upm-banner-message--${response.success ? 'success' : 'error'}`);
            $('.sp-upm-message-header').text(response.success ? 'Success' : 'Fatal error');
            $('.sp-upm-message-body').text(response.data.message);
        },
        clearRow: function() {
            app.toggleBannerMessage(false);
            $('.sp-upm-modal-error').remove();
            $('.pending-prescription--highlight').removeClass('pending-prescription--highlight');
        },
        resetCurrentData: function() {
            app.currentData = null;
        },
        handleOnTreatmentCategoryChange: function(e) {
            const value = $(e.target).val();
            $('.js-treatment-medication-select').hide();

            if (value) {
                $(`#treatment_medication_${value}`).fadeIn(300);
            } else {
                $('#treatment_medication_initial').fadeIn(300);
            }
        },
    }

    $(document).ready(function() {
        app.init();
    });
})(jQuery)