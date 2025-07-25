/**
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License version 3.0
* that is bundled with this package in the file LICENSE.md
* It is also available through the world-wide-web at this URL:
* https://opensource.org/license/osl-3-0-php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to support@qloapps.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade this module to a newer
* versions in the future. If you wish to customize this module for your needs
* please refer to https://store.webkul.com/customisation-guidelines for more information.
*
* @author Webkul IN
* @copyright Since 2010 Webkul
* @license https://opensource.org/license/osl-3-0-php Open Software License version 3.0
*/

$(document).ready(function() {
    // manage refund options checkboxes as per the refund paramenters
    if ($('#order_return_form .table input[name^="refund_amounts"]').length) {
        manageRefundOptions();
    }

    $('#order_return_form').on('submit', function() {
        if ($("#id_refund_state option:selected").attr('refunded') == 1) {
            if ($('#generateDiscount:checked').length) {
                if ($('#voucher_expiry').val() == '') {
                    $('#voucher_expiry').parent().addClass('has-error');
                    return false;
                }
            }
        }
    });

    $('#voucher_expiry').on('focus', function() {
        $('#voucher_expiry').parent().removeClass('has-error');
    });

    // initialize tootip for room price
    if ($('#rooms_refund_info .price_info').length) {
        $('#rooms_refund_info .price_info').each(function (i, element) {
			$(this).find('img').tooltip({
				content: $(this).closest('td').find('.price_info_container').html(),
				items: "span",
				trigger : 'hover',
				tooltipClass: "price_info-tooltip",
				open: function(event, ui)
				{
					if (typeof(event.originalEvent) === 'undefined')
					{
						return false;
					}

					var $id = $(ui.tooltip).attr('id');

					if ($('div.ui-tooltip').not('#' + $id).length) {
						return false;
					}
				},
				close: function(event, ui)
				{
					ui.tooltip.hover(function()
					{
						$(this).stop(true).fadeTo(400, 1);
					},
					function()
					{
						$(this).fadeOut('400', function()
						{
							$(this).remove();
						});
					});
				}
			});
		});
	}

    $(document).on('change', '#refundTransactionAmount', function() {
		if ($(this).is(':checked')) {
			$(".refund_transaction_fields").show(200);
		} else {
            $(".refund_transaction_fields").hide(200);
        }
    });

    $('#voucher_expiry').datepicker({
        dateFormat: 'dd-mm-yy',
        altFormat: 'yy-mm-dd',
        altField: '#voucher_expiry_date',
        minDate: +1,
    });

    $(document).on('change', '#generateDiscount', function() {
		if ($(this).is(':checked')) {
			$(".generate_discount_fields").show(200);
		} else {
            $(".generate_discount_fields").hide(200);
        }
    });

    $('#id_refund_state').on('change', function() {
        if ($("#id_refund_state option:selected").attr('refunded') == 1) {
            $(".refunded_state_fields").show(200);
        } else {
            $(".refunded_state_fields").hide(200);
        }
    });

    $('#payment_methods').on('change', function() {
        if ($(this).val() == 0) {
            $(".other_payment_mode").show(200);
        } else {
            $(".other_payment_mode").hide(200);
        }
    });

    $('#generateCreditSlip, #generateDiscount').click(function() {
        if ($(this).is(':checked')) {
            $('#generateCreditSlip, #generateDiscount').prop('checked', false);
			$(this).prop('checked', true);
		}
        $('#generateCreditSlip, #generateDiscount').change();
	});

    $(document).on('keyup', '#order_return_form .table input[name^="refund_amounts"]', function() {
        manageRefundOptions();
    });
});

// This function manages (Enable/disable) the refund options check-boxes according to the refund parameters like refund amount
function manageRefundOptions()
{
    let refundAmountInputs = $('#order_return_form .table input[name^="refund_amounts"]');

    let disableRefundOptions = false;
    $(refundAmountInputs).each(function(index, element) {
        let val = parseFloat($(element).val().trim());
        if (isNaN(val)) { // if at least one amount input is empty
            disableRefundOptions = true;
            return;
        }
    });

    if (!disableRefundOptions) {
        let hasAllZero = true;
        $(refundAmountInputs).each(function(index, element) {
            let val = parseFloat($(element).val().trim());
            if (val != 0) {
                hasAllZero = false;
                return;
            }
        });

        if (hasAllZero) { // if all amount inputs are 0
            disableRefundOptions = true;
        }
    }

    $('#generateCreditSlip, #refundTransactionAmount, #generateDiscount').attr('disabled', disableRefundOptions);
}
