/*
 * 2007-2017 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author PrestaShop SA <contact@prestashop.com>
 *  @copyright  2007-2017 PrestaShop SA
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
$(document).ready(function() {
    ajaxCart.overrideButtonsInThePage();

    $(document).on('click', '.block_cart_collapse', function(e) {
        e.preventDefault();
        ajaxCart.collapse();
    });
    $(document).on('click', '.block_cart_expand', function(e) {
        e.preventDefault();
        ajaxCart.expand();
    });

    var current_timestamp = parseInt(new Date().getTime() / 1000);

    if (typeof $('.ajax_cart_quantity').html() == 'undefined' || (typeof generated_date != 'undefined' && generated_date != null && (parseInt(generated_date) + 30) < current_timestamp))
        ajaxCart.refresh();

    /* roll over cart */
    var cart_block = new HoverWatcher('#header .cart_block');
    var shopping_cart = new HoverWatcher('#header .shopping_cart');
    var is_touch_enabled = false;

    if ('ontouchstart' in document.documentElement)
        is_touch_enabled = true;

    $(document).on('click', '#header .shopping_cart > a:first', function(e) {
        e.preventDefault();
        e.stopPropagation();

        // Simulate hover when browser says device is touch based
        if (is_touch_enabled) {
            if ($(this).next('.cart_block:visible').length && !cart_block.isHoveringOver())
                $("#header .cart_block").stop(true, true).slideUp(450);
            else if (ajaxCart.nb_total_products > 0 || parseInt($('.ajax_cart_quantity').html()) > 0)
                $("#header .cart_block").stop(true, true).slideDown(450);
            return;
        } else
            window.location.href = $(this).attr('href');
    });

    $("#header .shopping_cart a:first").hover(
        function() {
            if (!is_touch_enabled) {
                if (ajaxCart.nb_total_products > 0 || parseInt($('.ajax_cart_quantity').html()) > 0)
                    $("#header .cart_block").stop(true, true).slideDown(450);
            }
        },
        function() {
            setTimeout(function() {
                if (!shopping_cart.isHoveringOver() && !cart_block.isHoveringOver())
                    $("#header .cart_block").stop(true, true).slideUp(450);
            }, 200);
        }
    );

    $("#header .cart_block").hover(
        function() {},
        function() {
            setTimeout(function() {
                if (!shopping_cart.isHoveringOver())
                    $("#header .cart_block").stop(true, true).slideUp(450);
            }, 200);
        }
    );

    $(document).on('click', '.delete_voucher', function(e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            headers: {
                "cache-control": "no-cache"
            },
            async: true,
            cache: false,
            url: $(this).attr('href') + '?rand=' + new Date().getTime()
        });
        $(this).parent().parent().remove();
        ajaxCart.refresh();
        if ($('body').attr('id') == 'order' || $('body').attr('id') == 'order-opc') {
            if (typeof(updateAddressSelection) != 'undefined')
                updateAddressSelection();
            else
                location.reload();
        }
    });

    $(document).on('click', '#cart_navigation input', function(e) {
        $(this).prop('disabled', 'disabled').addClass('disabled');
        $(this).closest("form").get(0).submit();
    });

    $(document).on('click', '#layer_cart .cross, #layer_cart .continue, .layer_cart_overlay', function(e) {
        e.preventDefault();
        $('.layer_cart_overlay').hide();
        $('#layer_cart').fadeOut('fast');
    });

    $('#columns #layer_cart, #columns .layer_cart_overlay').detach().prependTo('#columns');
});

//JS Object : update the cart by ajax actions
var ajaxCart = {
    dateFormat: 'DD-MM-YYYY',
    dateFormatFull: 'DD-MM-YYYY HH:mm:ss',
    nb_total_products: 0,
    _overrideButtonsInThePage: function () {
        //for every 'add' buttons...
        $(document).off('click', '.ajax_add_to_cart_button').on('click', '.ajax_add_to_cart_button', function (e) {
            e.preventDefault();
            var idProduct = parseInt($(this).data('id-product'));
            var idProductAttribute = parseInt($(this).data('id-product-attribute'));

            var dateFrom = $(this).attr('cat_rm_check_in');
            var dateTo = $(this).attr('cat_rm_check_out');
            var occupancy = getBookingOccupancyDetails($(this).closest('.booking_room_fields'), true);
            /* By Webkul
             * Note : In our case minimalQuantity is taken from Qty. field
             */
            // var minimalQuantity =  parseInt($(this).data('minimal_quantity'));
            // var minimalQuantity = parseInt($(this).data('minimal_quantity'));
            // var minimalQuantity = $("#quantity_wanted_" + idProduct).val();
            // if (!minimalQuantity)
            //     minimalQuantity = 1;
            if ($(this).prop('disabled') != 'disabled' && occupancy)
                ajaxCart.add(idProduct, false, idProductAttribute, false, this, occupancy, null, dateFrom, dateTo, 1);
        });
        //for product page 'add' button...
        if ($('.cart_block').length) {
            $(document).off('click', '#add_to_cart button').on('click', '#add_to_cart button', function (e) {
                e.preventDefault();
                var booking_product = $('#product_page_booking_product').val()

                var date_from = $('#room_check_in').val();
                var date_to = $('#room_check_out').val();
                var id_hotel = $('#service_id_hotel').length ? $('#service_id_hotel').val() : 0;
                var occupancy = getBookingOccupancyDetails($(this).closest('.booking_room_fields'), parseInt(booking_product));

                if (occupancy) {
                    ajaxCart.add($('#product_page_product_id').val(), id_hotel, $('[name="id_product_option"]:checked').val(), true, null, occupancy, null, date_from, date_to, booking_product);
                }

            });
        }

        /*
         * By webkul to delete a single room from cart datewise
         */
        $(document).on('click', '.rooms_remove_container .remove_rooms_from_cart_link', function (e) {
            e.preventDefault();
            var id_product = $(this).attr('id_product');
            // this condition is added by webkul to delete a single room form current cart.
            if ($('#booking_dates_container_' + id_product).find('.rooms_remove_container .remove_rooms_from_cart_link').length == 1) {
                $(this).closest(".rooms_remove_container").parents("div.cart_prod_cont").siblings(".remove_link").find("a.ajax_cart_block_remove_link").click();
            } else {

                var date_from = $(this).attr('date_from');
                var date_to = $(this).attr('date_to');
                var num_rooms = $(this).attr('num_rooms');
                var $current = $(this);
                ajaxCart.update(id_product, false, false, true, num_rooms, false, date_from, date_to);
            }
        });

        $(document).on('click', '.ajax_remove_product_option', function (e) {
            e.preventDefault();
            var id_product = $(this).attr('id_product');
            var id_hotel = $(this).attr('id_hotel');
            var id_product_option = $(this).attr('id_product_option');
            ajaxCart.remove(id_product, id_hotel, id_product_option);
        });

        //for 'delete' buttons in the cart block...
        $(document).off('click', '.cart_block_list .ajax_cart_block_remove_link').on('click', '.cart_block_list .ajax_cart_block_remove_link', function (e) {
            e.preventDefault();
            // Customized product management
            var customizationId = 0;
            var productId = 0;
            var productAttributeId = 0;
            var customizableProductDiv = $($(this).parent().parent()).find("div[data-id^=deleteCustomizableProduct_]");
            var idAddressDelivery = false;
            var idHotel = 0;

            if (customizableProductDiv && $(customizableProductDiv).length) {
                var ids = customizableProductDiv.data('id').split('_');
                if (typeof (ids[1]) != 'undefined') {
                    customizationId = parseInt(ids[1]);
                    productId = parseInt(ids[2]);
                    if (typeof (ids[3]) != 'undefined')
                        productAttributeId = parseInt(ids[3]);
                    if (typeof (ids[4]) != 'undefined')
                        idAddressDelivery = parseInt(ids[4]);
                }
            }

            // Common product management
            if (!customizationId) {
                //retrieve idProduct and idCombination from the displayed product in the block cart
                var firstCut = $(this).closest('dt').data('id').replace('cart_block_product_', '');
                firstCut = firstCut.replace('deleteCustomizableProduct_', '');
                ids = firstCut.split('_');
                productId = parseInt(ids[0]);

                if (typeof (ids[1]) != 'undefined')
                    productAttributeId = parseInt(ids[1]);
                if (typeof (ids[2]) != 'undefined')
                    idAddressDelivery = parseInt(ids[2]);
                if (typeof (ids[3]) != 'undefined')
                    idHotel = parseInt(ids[3]);
            }

            /*by webkul*/
            if (pagename == 'product') {
                dateFrom = $('#room_check_in').val();
                dateTo = $('#room_check_out').val();
            } else if (pagename == 'category') {
                dateFrom = $('#check_in_time').val();
                dateTo = $('#check_out_time').val();
            } else {

                dateFrom = $.datepicker.formatDate('yy-mm-dd', new Date());;
                dateTo = $.datepicker.formatDate('yy-mm-dd', new Date());;
            }

            // Removing product from the cart
            ajaxCart.remove(productId, idHotel, productAttributeId, customizationId, idAddressDelivery, dateFrom, dateTo);
        });
    },
    get overrideButtonsInThePage() {
        return this._overrideButtonsInThePage;
    },
    set overrideButtonsInThePage(value) {
        this._overrideButtonsInThePage = value;
    },

    // try to expand the cart
    expand: function() {
        if ($('.cart_block_list').hasClass('collapsed')) {
            $('.cart_block_list.collapsed').slideDown({
                duration: 450,
                complete: function() {
                    $(this).parent().show(); // parent is hidden in global.js::accordion()
                    $(this).addClass('expanded').removeClass('collapsed');
                }
            });

            // save the expand statut in the user cookie
            $.ajax({
                type: 'POST',
                headers: {
                    "cache-control": "no-cache"
                },
                url: baseDir + 'modules/blockcart/blockcart-set-collapse.php' + '?rand=' + new Date().getTime(),
                async: true,
                cache: false,
                data: 'ajax_blockcart_display=expand',
                complete: function() {
                    $('.block_cart_expand').fadeOut('fast', function() {
                        $('.block_cart_collapse').fadeIn('fast');
                    });
                }
            });
        }
    },

    // try to collapse the cart
    collapse: function() {
        if ($('.cart_block_list').hasClass('expanded')) {
            $('.cart_block_list.expanded').slideUp('slow', function() {
                $(this).addClass('collapsed').removeClass('expanded');
            });

            // save the expand statut in the user cookie
            $.ajax({
                type: 'POST',
                headers: {
                    "cache-control": "no-cache"
                },
                url: baseDir + 'modules/blockcart/blockcart-set-collapse.php' + '?rand=' + new Date().getTime(),
                async: true,
                cache: false,
                data: 'ajax_blockcart_display=collapse' + '&rand=' + new Date().getTime(),
                complete: function() {
                    $('.block_cart_collapse').fadeOut('fast', function() {
                        $('.block_cart_expand').fadeIn('fast');
                    });
                }
            });
        }
    },
    // Fix display when using back and previous browsers buttons
    refresh: function() {
        $.ajax({
            type: 'POST',
            headers: {
                "cache-control": "no-cache"
            },
            url: baseUri + '?rand=' + new Date().getTime(),
            async: true,
            cache: false,
            dataType: "json",
            data: 'controller=cart&ajax=true&token=' + static_token,
            success: function(jsonData) {
                ajaxCart.updateCart(jsonData);
            }
        });
    },

    // Update the cart information
    updateCartInformation: function(jsonData, addedFromProductPage) {
        ajaxCart.updateCart(jsonData);
        //reactive the button when adding has finished
        if (addedFromProductPage) {
            $('#add_to_cart button').removeProp('disabled').removeClass('disabled');
            if (!jsonData.hasError || jsonData.hasError == false)
                $('#add_to_cart button').addClass('added');
            else
                $('#add_to_cart button').removeClass('added');
        } else
            $('.ajax_add_to_cart_button').removeProp('disabled');
    },
    // close fancybox
    updateFancyBox: function() {},
    // add a product in the cart via ajax
    add: function(idProduct, idHotel, idCombination, addedFromProductPage, callerElement, occupancy, whishlist, dateFrom, dateTo, booking_product) {
        if (addedFromProductPage && !checkCustomizations()) {
            if (contentOnly) {
                var productUrl = window.document.location.href + '';
                var data = productUrl.replace('content_only=1', '');
                window.parent.document.location.href = data;
                return;
            }
            if (!!$.prototype.fancybox)
                $.fancybox.open([{
                    type: 'inline',
                    autoScale: true,
                    minHeight: 30,
                    content: '<p class="fancybox-error">' + fieldRequired + '</p>'
                }], {
                    padding: 0
                });
            else
                alert(fieldRequired);
            return;
        }

        //disabled the button when adding to not double add if user double click
        if (addedFromProductPage) {
            $('#add_to_cart button').prop('disabled', 'disabled').addClass('disabled');
            $('.filled').removeClass('filled');
        } else {
            $(callerElement).prop('disabled', 'disabled');
        }

        if ($('.cart_block_list').hasClass('collapsed'))
            this.expand();

        // create from data in order to manage adding different type of products
        var req = new FormData();
        req.append('controller', 'cart');
        req.append('add', 1);
        req.append('ajax', true);
        req.append('id_product', idProduct);
        req.append('token', static_token);
        req.append('id_customization', ((typeof customizationId !== 'undefined') ? customizationId : 0));
        if (parseInt(idCombination) && idCombination != null){
            req.append('id_product_option', parseInt(idCombination));
        }
        req.append('id_hotel', idHotel);

        if (parseInt(booking_product)) {
            // get the selected extra demands by customer
            if (typeof dateFrom != 'undefined')
                req.append('dateFrom', dateFrom);
            if (typeof dateTo != 'undefined')
                req.append('dateTo', dateTo);

            if (occupancy_required_for_booking) {
                req.append('occupancy', JSON.stringify(occupancy));
            } else {
                req.append('qty', ((occupancy && occupancy != null) ? occupancy : '1'));
            }

            var roomDemands = getRoomsExtraDemands();
            req.append('roomDemands', JSON.stringify(roomDemands) );
            req.append('serviceProducts', JSON.stringify(getRoomsServiceProducts()) );
        } else {
            req.append('qty', ((occupancy && occupancy != null) ? occupancy : '1'));
        }

        //send the ajax request to the server
        $.ajax({
            type: 'POST',
            headers: {
                "cache-control": "no-cache"
            },
            url: baseUri + '?rand=' + new Date().getTime(),
            async: true,
            cache: false,
            dataType: "json",
            data: req,
            contentType: false,
            processData: false,
            success: function(jsonData, textStatus, jqXHR) {
                if (pagename == 'product') {
                    if (jsonData.avail_rooms == 0) {
                        disableRoomTypeDemands(1);
                    }

                    resetRoomtypeServices();

                    if (parseInt($('#product_page_booking_product').val()) == 0) {
                        $('#service_product_qty').val(1);
                    }

                    BookingForm.refresh(true);
                }

                if (pagename == 'category') {
                    if (jsonData.avail_rooms <= room_warning_num) {
                        $(callerElement).closest(".room_cont").find(".rm_left").show();
                    } else {
                        $(callerElement).closest(".room_cont").find(".rm_left").hide();
                    }

                    /*for category page add to cart quantity management*/
                    $(callerElement).closest(".room_cont").find(".remain_rm_qty").text(jsonData.avail_rooms);
                    $(callerElement).closest(".room_cont").find('.max_avail_type_qty').val(jsonData.avail_rooms);

                    if (jsonData.avail_rooms == 0) {
                        $(callerElement).closest(".room_cont").hide();
                    }
                    //$('#quantity_wanted_'+idProduct).val(1);

                    let addRoomButton = $(callerElement).closest('.room_info_cont').find('.booking_guest_occupancy_conatiner .dropdown-menu .add_occupancy_block .add_new_occupancy_btn');
                    if (jsonData.avail_rooms > 1) {
                        $(addRoomButton).removeClass('disabled');
                    } else {
                        $(addRoomButton).addClass('disabled');
                    }
                }


                // add appliance to whishlist module
                if (whishlist && !jsonData.errors)
                    WishlistAddProductCart(whishlist[0], idProduct, idCombination, whishlist[1]);

                if (!jsonData.hasError) {
                    if (contentOnly)
                        window.parent.ajaxCart.updateCartInformation(jsonData, addedFromProductPage);
                    else
                        ajaxCart.updateCartInformation(jsonData, addedFromProductPage);

                    if (jsonData.crossSelling)
                        $('.crossseling').html(jsonData.crossSelling);

                    if (jsonData.last_added_product) {
                        ajaxCart.updateLayer(jsonData.last_added_product);

                    }
                    if (contentOnly)
                        parent.$.fancybox.close();
                } else {
                    if (contentOnly)
                        window.parent.ajaxCart.updateCart(jsonData);
                    else
                        ajaxCart.updateCart(jsonData);
                    if (addedFromProductPage)
                        $('#add_to_cart button').removeProp('disabled').removeClass('disabled');
                    else
                        $(callerElement).removeProp('disabled');
                }
                if (!addedFromProductPage) {
                    resetOccupancyField($(callerElement).closest('.booking_room_fields').find('.booking_occupancy_wrapper'));
                }
                emptyCustomizations();

            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                if (!onlineFlag) {
                    showErrorMessage(no_internet_txt);
                } else {
                    var error = "Impossible to add the room to the cart.<br/>textStatus: '" + textStatus + "'<br/>errorThrown: '" + errorThrown + "'<br/>responseText:<br/>" + XMLHttpRequest.responseText;
                    if (!!$.prototype.fancybox)
                        $.fancybox.open([{
                            type: 'inline',
                            autoScale: true,
                            minHeight: 30,
                            content: '<p class="fancybox-error">' + error + '</p>'
                        }], {
                            padding: 0
                        });
                    else
                        alert(error);
                    //reactive the button when adding has finished
                    if (addedFromProductPage)
                        $('#add_to_cart button').removeProp('disabled').removeClass('disabled');
                    else
                        $(callerElement).removeProp('disabled');
                }
            }
        });
    },

    update: function(idProduct, idCombination, customizationId, updatedFromProductPage, quantity, whishlist, dateFrom, dateTo) {
        if (updatedFromProductPage && !checkCustomizations()) {
            if (contentOnly) {
                var productUrl = window.document.location.href + '';
                var data = productUrl.replace('content_only=1', '');
                window.parent.document.location.href = data;
                return;
            }
            if (!!$.prototype.fancybox)
                $.fancybox.open([{
                    type: 'inline',
                    autoScale: true,
                    minHeight: 30,
                    content: '<p class="fancybox-error">' + fieldRequired + '</p>'
                }], {
                    padding: 0
                });
            else
                alert(fieldRequired);
            return;
        }
        $.ajax({
            type: 'POST',
            headers: {
                "cache-control": "no-cache"
            },
            url: baseUri + '?rand=' + new Date().getTime(),
            async: true,
            cache: false,
            dataType: "json",
            data: 'controller=cart&add=1&op=down&dateFrom=' + dateFrom + '&dateTo=' + dateTo + '&ajax=true&qty=' + ((quantity && quantity != null) ? quantity : '1') + '&id_product=' + idProduct + '&token=' + static_token + ((parseInt(idCombination) && idCombination != null) ? '&ipa=' + parseInt(idCombination) : '' + '&id_customization=' + ((typeof customizationId !== 'undefined') ? customizationId : 0)),
            success: function(jsonData, textStatus, jqXHR) {
                /*by webkul checking and setting availability of rooms*/
                /*for product page add to cart quantity management*/
                if (pagename == 'product') {
                    var date_checkIn = $('#room_check_in').val();
                    var date_checkOut = $('#room_check_out').val();
                    var product_page_id_product = $('#product_page_product_id').val();
                    if (idProduct == product_page_id_product && dateFrom < date_checkOut && dateTo >= date_checkIn) {
                        $(".max_avail_type_qty").val(jsonData.avail_rooms);
                        if (jsonData.avail_rooms == 0) {
                            disableRoomTypeDemands(1);
                        }
                    }
                    BookingForm.refresh();
                }

                if (pagename == 'category') {
                    if (jsonData.avail_rooms <= room_warning_num) {
                        $(".room_cont").find('[data-id-product="'+idProduct+'"] .rm_left').show();
                    } else {
                        $("room_cont").find('[data-id-product="'+idProduct+'"] .rm_left').hide();
                    }

                    /*for category page add to cart quantity management*/

                    $(".room_cont").find('[data-id-product="'+idProduct+'"] .remain_rm_qty').text(jsonData.avail_rooms);
                    $(".room_cont").find('[data-id-product="'+idProduct+'"] .max_avail_type_qty').val(jsonData.avail_rooms);
                    if (jsonData.avail_rooms == 0) {
                        $(".room_cont").find('[data-id-product="'+idProduct+'"]').hide();
                    }
                    //$('#quantity_wanted_'+idProduct).val(1);
                }

                ajaxCart.updateCart(jsonData);
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                if (!onlineFlag) {
                    showErrorMessage(no_internet_txt);
                } else {
                    var error = "Impossible to add the room to the cart.<br/>textStatus: '" + textStatus + "'<br/>errorThrown: '" + errorThrown + "'<br/>responseText:<br/>" + XMLHttpRequest.responseText;
                    if (!!$.prototype.fancybox)
                        $.fancybox.open([{
                            type: 'inline',
                            autoScale: true,
                            minHeight: 30,
                            content: '<p class="fancybox-error">' + error + '</p>'
                        }], {
                            padding: 0
                        });
                    else
                        alert(error);
                    //reactive the button when adding has finished
                    if (addedFromProductPage)
                        $('#add_to_cart button').removeProp('disabled').removeClass('disabled');
                    else
                        $(callerElement).removeProp('disabled');
                }
            }
        });
    },

    //remove a product from the cart via ajax
    remove: function(idProduct, idHotel, idCombination, customizationId, idAddressDelivery, dateFrom, dateTo) {
        //send the ajax request to the server
        $.ajax({
            type: 'POST',
            headers: {
                "cache-control": "no-cache"
            },
            url: baseUri + '?rand=' + new Date().getTime(),
            async: true,
            cache: false,
            dataType: "json",
            data: 'controller=cart&delete=1&dateFrom=' + dateFrom + '&dateTo=' + dateTo + '&id_product=' + idProduct + '&id_hotel=' + idHotel + '&id_product_option=' + ((idCombination != null && parseInt(idCombination)) ? idCombination : '') + ((customizationId && customizationId != null) ? '&id_customization=' + customizationId : '') + '&id_address_delivery=' + idAddressDelivery + '&token=' + static_token + '&ajax=true',
            success: function(jsonData) {
                if (pagename == 'product') {
                    disableRoomTypeDemands(0);
                    BookingForm.refresh();
                }

                if (pagename == 'category') {
                    // for category page....
                    $('.room_cont[data-id-product="'+idProduct+'"]').find(".remain_rm_qty").text(jsonData.avail_rooms);
                    $('.room_cont[data-id-product="'+idProduct+'"]').find('.max_avail_type_qty').val(jsonData.avail_rooms);

                    $('.room_cont[data-id-product="'+idProduct+'"]').show(0, function() {
                        if (jsonData.avail_rooms <= room_warning_num) {
                            $('.room_cont[data-id-product="'+idProduct+'"]').find('.rm_left').show();
                        } else {
                            $('.room_cont[data-id-product="'+idProduct+'"]').find('.rm_left').hide();
                        }
                    });

                    let addRoomButton = $('.room_cont[data-id-product="'+idProduct+'"]').find('.room_info_cont').find('.booking_guest_occupancy_conatiner .dropdown-menu .add_occupancy_block .add_new_occupancy_btn');
                    if (jsonData.avail_rooms > 1) {
                        $(addRoomButton).removeClass('disabled');
                    } else {
                        $(addRoomButton).addClass('disabled');
                    }
                }
                if (pagename == 'orderopc') {
                    updateOpcData();
                }

                ajaxCart.updateCart(jsonData);
                // @TODO in future to sync with shopping cart delete from order-opc
                /*if ($('body').attr('id') == 'order' || $('body').attr('id') == 'order-opc') {
                    deleteProductFromSummary(idProduct + '_' + idCombination + '_' + customizationId + '_' + idAddressDelivery);
                }*/
            },
            error: function() {
                if (!onlineFlag) {
                    showErrorMessage(no_internet_txt);
                } else {
                    var error = 'ERROR: unable to delete the product';
                    if (!!$.prototype.fancybox) {
                        $.fancybox.open([{
                            type: 'inline',
                            autoScale: true,
                            minHeight: 30,
                            content: error
                        }], {
                            padding: 0
                        });
                    } else
                        alert(error);
                }
            }
        });
    },

    //hide the products displayed in the page but no more in the json data
    hideOldProducts: function(jsonData) {
        //delete an eventually removed product of the displayed cart (only if cart is not empty!)
        if ($('.cart_block_list:first dl.products').length > 0) {
            var removedProductId = null;
            var removedProductData = null;
            var removedProductDomId = null;
            //look for a product to delete...
            $('.cart_block_list:first dl.products dt').each(function() {
                //retrieve idProduct and idCombination from the displayed product in the block cart
                var productDomId = $(this).data('id');
                var firstCut = productDomId.replace('cart_block_product_', '');
                var ids = firstCut.split('_');

                //try to know if the current product is still in the new list
                var stayInTheCart = false;
                for (aProduct in jsonData.products) {
                    //we've called the variable aProduct because IE6 bug if this variable is called product
                    //if product has attributes
                    if ((parseInt(jsonData.products[aProduct]['selling_preference_type']) == SELLING_PREFERENCE_HOTEL_STANDALONE
                        || parseInt(jsonData.products[aProduct]['selling_preference_type']) == SELLING_PREFERENCE_HOTEL_STANDALONE_AND_WITH_ROOM_TYPE)
                        && (typeof jsonData.products[aProduct]['hotel_wise_data'] != 'undefined' && jsonData.products[aProduct]['hotel_wise_data'].length)
                    ) {
                        $.each(jsonData.products[aProduct]['hotel_wise_data'], function() {
                            if (!ids[3] || (ids[3] == this.id_hotel)) {
                                stayInTheCart = true;
                            }
                        });
                    } else {
                        if (jsonData.products[aProduct]['id'] == ids[0] && (!ids[1] || jsonData.products[aProduct]['idCombination'] == ids[1])) {
                            stayInTheCart = true;
                            // update the product customization display (when the product is still in the cart)
                            ajaxCart.hideOldProductCustomizations(jsonData.products[aProduct], productDomId);
                        }
                    }
                }
                //remove product if it's no more in the cart
                if (!stayInTheCart) {
                    removedProductId = $(this).data('id');
                    if (removedProductId != null) {
                        var firstCut = removedProductId.replace('cart_block_product_', '');
                        var ids = firstCut.split('_');

                        $('dt[data-id="' + removedProductId + '"]').addClass('strike').fadeTo('slow', 0, function() {
                            $(this).slideUp('slow', function() {
                                $(this).remove();
                                // If the cart is now empty, show the 'no product in the cart' message and close detail
                                if ($('.cart_block:first dl.products dt').length == 0) {
                                    $('.ajax_cart_quantity').html('0');
                                    $("#header .cart_block").stop(true, true).slideUp(200);
                                    $('.cart_block_no_products:hidden').slideDown(450);
                                    $('.cart_block dl.products').remove();
                                }
                            });
                        });
                        $('dd[data-id="cart_block_combination_of_' + ids[0] + (ids[1] ? '_' + ids[1] : '') + (ids[2] ? '_' + ids[2] : '') + '"]').fadeTo('fast', 0, function() {
                            $(this).slideUp('fast', function() {
                                $(this).remove();
                            });
                        });
                    }
                }
            });
        }
    },

    hideOldProductCustomizations: function(product, productDomId) {
        var customizationList = $('ul[data-id="customization_' + product['id'] + '_' + product['idCombination'] + '"]');
        if (customizationList.length > 0) {
            $(customizationList).find("li").each(function() {
                $(this).find("div").each(function() {
                    var customizationDiv = $(this).data('id');
                    var tmp = customizationDiv.replace('deleteCustomizableProduct_', '');
                    var ids = tmp.split('_');
                    if ((parseInt(product.idCombination) == parseInt(ids[2])) && !ajaxCart.doesCustomizationStillExist(product, ids[0]))
                        $('div[data-id="' + customizationDiv + '"]').parent().addClass('strike').fadeTo('slow', 0, function() {
                            $(this).slideUp('slow');
                            $(this).remove();
                        });
                });
            });
        }

        var removeLinks = $('.deleteCustomizableProduct[data-id="' + productDomId + '"]').find('.ajax_cart_block_remove_link');
        if (!product.hasCustomizedDatas && !removeLinks.length)
            $('div[data-id="' + productDomId + '"]' + ' span.remove_link').html('<a class="ajax_cart_block_remove_link" rel="nofollow" href="' + baseUri + '?controller=cart&amp;delete=1&amp;id_product=' + product['id'] + '&amp;ipa=' + product['idCombination'] + '&amp;token=' + static_token + '"> </a>');
        if (product.is_gift)
            $('div[data-id="' + productDomId + '"]' + ' span.remove_link').html('');
    },

    doesCustomizationStillExist: function(product, customizationId) {
        var exists = false;

        $(product.customizedDatas).each(function() {
            if (this.customizationId == customizationId) {
                exists = true;
                // This return does not mean that we found nothing but simply break the loop
                return false;
            }
        });
        return (exists);
    },

    //refresh display of vouchers (needed for vouchers in % of the total)
    refreshVouchers: function(jsonData) {
        if (typeof(jsonData.discounts) == 'undefined' || jsonData.discounts.length == 0)
            $('.vouchers').hide();
        else {
            $('.vouchers tbody').html('');

            for (i = 0; i < jsonData.discounts.length; i++) {
                if (parseFloat(jsonData.discounts[i].price_float) > 0) {
                    var delete_link = '';
                    if (jsonData.discounts[i].code.length)
                        delete_link = '<a class="delete_voucher" href="' + jsonData.discounts[i].link + '" title="' + delete_txt + '"><i class="icon-remove-sign"></i></a>';
                    $('.vouchers tbody').append($(
                        '<tr class="bloc_cart_voucher" data-id="bloc_cart_voucher_' + jsonData.discounts[i].id + '">' + '	<td class="quantity">1x</td>' + '	<td class="name" title="' + jsonData.discounts[i].description + '">' + jsonData.discounts[i].name + '</td>' + '	<td class="price">-' + jsonData.discounts[i].price + '</td>' + '	<td class="delete">' + delete_link + '</td>' + '</tr>'
                    ));
                }
            }
            $('.vouchers').show();
        }
    },

    // Update product quantity
    updateProductQuantity: function(product, quantity , hotel_wise_data = null) {
        var productDomId = product.id + '_' + (product.idCombination ? product.idCombination : '0') + '_' + (product.idAddressDelivery ? product.idAddressDelivery : '0') + '_' + ((hotel_wise_data && typeof(hotel_wise_data.id_hotel) != 'undefined') ? hotel_wise_data.id_hotel : '0');
        $('dt[data-id="cart_block_product_' + productDomId + '"] .quantity').fadeTo('fast', 0, function() {
            $(this).text(quantity);
            $(this).fadeTo('fast', 1, function() {
                $(this).fadeTo('fast', 0, function() {
                    $(this).fadeTo('fast', 1, function() {
                        $(this).fadeTo('fast', 0, function() {
                            $(this).fadeTo('fast', 1);
                        });
                    });
                });
            });
        });
    },

    //display the products witch are in json data but not already displayed
    displayNewProducts: function(jsonData) {
        //add every new products or update displaying of every updated products
        // var cart_booking_data = jsonData.cart_booking_data; //by webkul sent variable in ajax result
        $(jsonData.products).each(function(key, value) {
            //fix ie6 bug (one more item 'undefined' in IE6)
            if (this.id != undefined) {
                if (!this.booking_product && parseInt(this.selling_preference_type) == SELLING_PREFERENCE_WITH_ROOM_TYPE) {
                    return;
                }
                //create a container for listing the products and hide the 'no product in the cart' message (only if the cart was empty)
                if ($('.cart_block:first dl.products').length == 0) {
                    $('.cart_block_no_products').before('<dl class="products"></dl>');
                    $('.cart_block_no_products').hide();
                }

                if (parseInt(this.selling_preference_type) == SELLING_PREFERENCE_HOTEL_STANDALONE
                    || parseInt(this.selling_preference_type) == SELLING_PREFERENCE_HOTEL_STANDALONE_AND_WITH_ROOM_TYPE
                ) {
                    if (typeof value.hotel_wise_data != 'undefined') {
                        $(value.hotel_wise_data).each(function(i, val) {
                            ajaxCart.addProductRow(value, val);
                        });
                    }
                } else {
                    ajaxCart.addProductRow(value);
                }
            }
        });
    },

    addProductRow: function(product, hotel_wise_data) {
        //if product is not in the displayed cart, add a new product's line
        var productDomId = product.id + '_' + (product.idCombination ? product.idCombination : '0') + '_' + (product.idAddressDelivery ? product.idAddressDelivery : '0') + '_' + ((hotel_wise_data && typeof(hotel_wise_data.id_hotel) != 'undefined') ? hotel_wise_data.id_hotel : '0');
        var productAttributeDomId = product.id + '_' + (product.idCombination ? product.idCombination : '0');
        var productId = parseInt(product.id);

        if ($('dt[data-id="cart_block_product_' + productDomId + '"]').length == 0) {
            var productAttributeId = (product.hasAttributes ? parseInt(product.attributes) : 0);
            var content = '<dt class="unvisible" data-id="cart_block_product_' + productDomId + '">';
            var name = $.trim($('<span />').html(product.name).text());
            name = (name.length > 30 ? name.substring(0, 27) + '...' : name);
            content += '<a class="cart-images" href="' + product.link + '" title="' + name + '"><img  src="' + product.image_cart + '" alt="' + product.name + '"></a>';

            content += '<div class="cart-info">';
            content += '<div class="product-name">';
            content += '<a href="' + product.link + '" title="' + product.name + '" class="cart_block_product_name">' + name + '</a>';
            content += '</div>';
            if ((parseInt(product.selling_preference_type) == SELLING_PREFERENCE_HOTEL_STANDALONE
                || parseInt(product.selling_preference_type) == SELLING_PREFERENCE_HOTEL_STANDALONE_AND_WITH_ROOM_TYPE)
                && (typeof(hotel_wise_data) != 'undefined' && typeof(hotel_wise_data.hotel_name) != 'undefined')
            ) {
                content += '<div class="hotel-name">' + hotel_wise_data.hotel_name + '</div>';
            }

            if (product.hasAttributes)
                content += '<div class="product-atributes"><a href="' + product.link + '" title="' + product.name + '">' + product.attributes + '</a></div>';

                if (typeof(freeProductTranslation) != 'undefined') {
                content += '<div class="cart-info-sec rm_product_info_' + productId + '">';
                content += '<span class="product_info_label">Price:</span>';
                content += '<span class="price product_info_data">&nbsp;';

                if (product.booking_product) {
                    content += formatCurrency(parseFloat(product.bookingData.total_room_type_amount), currency_format, currency_sign, currency_blank);
                }else if ((parseInt(product.selling_preference_type) == SELLING_PREFERENCE_HOTEL_STANDALONE
                    || parseInt(product.selling_preference_type) == SELLING_PREFERENCE_HOTEL_STANDALONE_AND_WITH_ROOM_TYPE)
                    && (typeof(hotel_wise_data) != 'undefined')
                ) {
                    content += formatCurrency(parseFloat(hotel_wise_data.amount), currency_format, currency_sign, currency_blank);
                } else {
                    content += formatCurrency(parseFloat(product.amount), currency_format, currency_sign, currency_blank);
                }
                content += '</span>';
                content += '</div>';
            }
            content += '<div class="cart-info-sec rm_product_info_' + productId + '">';
            if (product.booking_product || product.allow_multiple_quantity) {
                content += '<span class="product_info_label">' + total_qty_txt + ':</span>';
            }
            content += '<span class="quantity-formated">';
            content += '<span class="quantity product_info_data">';
            if (product.booking_product) {
                content += product.bookingData.total_num_rooms;
            } else if (product.allow_multiple_quantity) {
                if (typeof(hotel_wise_data) != 'undefined' && hotel_wise_data.total_qty) {
                    content += hotel_wise_data.total_qty;
                } else {
                    content += product.cart_quantity;
                }
            }
            content += '</span>';
            content += '</span>';
            content += '</div>';
            content += '</div>';


            if (typeof(product.is_gift) == 'undefined' || product.is_gift == 0)
                content += '<span class="remove_link"><a rel="nofollow" class="ajax_cart_block_remove_link" href="' + baseUri + '?controller=cart&amp;delete=1&amp;id_product=' + productId + '&amp;token=' + static_token + (product.hasAttributes ? '&amp;ipa=' + parseInt(product.idCombination) : '') + (typeof(hotel_wise_data) != 'undefined' && hotel_wise_data && hotel_wise_data.id_hotel ? '&amp;id_hotel=' + parseInt(hotel_wise_data.id_hotel) : '') + '">&nbsp;</a></span>';
            else
                content += '<span class="remove_link"></span>';


            content += '<div style="clear:both;"></div>';
            // data for rooms only
            if (product.booking_product) {
                content += '<div id="booking_dates_container_' + productId + '" class="cart_prod_cont">';
                content += '<div class="table-responsive">';
                content += '<table class="table">';
                content += '<tbody>';
                content += '<tr>';
                content += '<th>' + duration_txt + '</th>';
                content += '<th>' + qty_txt + '.</th>';
                content += '<th>' + price_txt + '</th>';
                content += '<th>&nbsp;</th>';
                content += '</tr>';

                if (product.bookingData.date_diff !== 'undefined') {
                    $.each(product.bookingData.date_diff, function(date_diff_k, date_diff_v) {
                        from = $.datepicker.formatDate(ajaxCart.dateFormat, new Date(date_diff_v.data_form));
                        to = $.datepicker.formatDate(ajaxCart.dateFormat, new Date(date_diff_v.data_to));
                        let dateFormatToUse = from == to ? ajaxCart.dateFormatFull : ajaxCart.dateFormat;
                        content += '<tr class="rooms_remove_container">';
                        content += '<td>' + moment(new Date(date_diff_v.data_form)).format(dateFormatToUse) + '&nbsp;-&nbsp;' + moment(new Date(date_diff_v.data_to)).format(dateFormatToUse) + '</td>';
                        content += '<td class="num_rooms_in_date">' + date_diff_v.num_rm + '</td>';
                        content += '<td>' + formatCurrency(parseFloat(date_diff_v.amount) + parseFloat(date_diff_v.demand_price), currency_format, currency_sign, currency_blank) + '</td>';
                        content += '<td>';
                        content += '<a class="remove_rooms_from_cart_link" href="#" rm_price=' + date_diff_v.amount + ' id_product=' + productId + ' date_from=' + date_diff_v.data_form + ' date_to=' + date_diff_v.data_to + ' num_rooms=' + date_diff_v.num_rm + ' title="' + remove_rm_title + '"></a>';
                        content += '</td>';
                        content += '</tr>';
                    });
                }
                content += '</tbody>';
                content += '</table>';
                content += '</div>';
                content += '</div>';
            } else if (product.hasOptions) {
                content += '<div class="table-responsive cart_prod_cont">';
                content += '<table class="table">';
                content += '<tbody>';
                content += '<tr>';
                if (product.allow_multiple_quantity) {
                    content += '<th>' + variant_txt + '</th>';
                }
                content += '<th>' + qty_txt + '.</th>';
                content += '<th>' + price_txt + '</th>';
                content += '<th>&nbsp;</th>';
                content += '</tr>';
                if (parseInt(product.selling_preference_type) == SELLING_PREFERENCE_HOTEL_STANDALONE
                    || parseInt(product.selling_preference_type) == SELLING_PREFERENCE_HOTEL_STANDALONE_AND_WITH_ROOM_TYPE
                ) {
                    if (hotel_wise_data.options !== 'undefined') {
                        var options = hotel_wise_data.options;
                    }
                } else if (parseInt(product.selling_preference_type) == SELLING_PREFERENCE_STANDALONE) {
                    if (product.options !== 'undefined') {
                        var options = product.options;
                    }
                }
                if (options !== 'undefined') {
                    $.each(options, function(data_k, data_v) {
                        content += '<tr class="product_option_row">';
                        content += '<td>' + data_v.option_name + '</td>';
                        if (product.allow_multiple_quantity) {
                            content += '<td>' + data_v.quantity + '</td>';
                        }
                        content += '<td>' + formatCurrency(parseFloat(data_v.total_price_tax_excl), currency_format, currency_sign, currency_blank) + '</td>';
                        content += '<td class="text-right"><a class="ajax_remove_product_option" href="#" id_product="' + product.id_product + '" id_hotel="' + data_v.id_hotel + '" id_product_option="' + data_v.id_product_option + '" title="' + remove_rm_title + '">&nbsp;</a></td>';
                        content += '</tr>';
                    });
                }
                content += '</tbody>';
                content += '</table>';
                content += '</div>';
            }
            content += '</dt>';

            if (product.hasAttributes)
                content += '<dd data-id="cart_block_combination_of_' + productDomId + '" class="unvisible">';
            if (product.hasCustomizedDatas)
                content += ajaxCart.displayNewCustomizedDatas(product);
            if (product.hasAttributes) content += '</dd>';

            $('.cart_block dl.products').append(content);
        }
        //else update the product's line
        else {
            //by webkul to update rooms information on new room add to cart
            if (product.booking_product) {
                var booking_dates_content = '';
                // $("#booking_dates_container_"+product.id).empty();

                $("#booking_dates_container_" + product.id).find("table.table tbody tr.rooms_remove_container").remove();

                var product_price_float = product.price_float;
                if (product.bookingData.date_diff !== 'undefined') {
                    $.each(product.bookingData.date_diff, function(date_diff_k1, date_diff_v1) {
                        from = $.datepicker.formatDate(ajaxCart.dateFormat, new Date(date_diff_v1.data_form));
                        to = $.datepicker.formatDate(ajaxCart.dateFormat, new Date(date_diff_v1.data_to));
                        let dateFormatToUse = from == to ? ajaxCart.dateFormatFull : ajaxCart.dateFormat;
                        booking_dates_content += '<tr class="rooms_remove_container">';
                        booking_dates_content += '<td>' + moment(new Date(date_diff_v1.data_form)).format(dateFormatToUse) + '&nbsp;-&nbsp;' + moment(new Date(date_diff_v1.data_to)).format(dateFormatToUse) + '</td>';
                        booking_dates_content += '<td class="num_rooms_in_date">' + date_diff_v1.num_rm + '</td>';
                        booking_dates_content += '<td>' + formatCurrency(parseFloat(date_diff_v1.amount) + parseFloat(date_diff_v1.demand_price), currency_format, currency_sign, currency_blank) + '</td>';
                        booking_dates_content += '<td>';
                        booking_dates_content += '<a class="remove_rooms_from_cart_link" href="#" rm_price=' + date_diff_v1.amount + ' id_product=' + productId + ' date_from=' + date_diff_v1.data_form + ' date_to=' + date_diff_v1.data_to + ' num_rooms=' + date_diff_v1.num_rm + ' title="' + remove_rm_title + '"></a>';
                        booking_dates_content += '</td>';
                        booking_dates_content += '</tr>';
                    });
                }

                $("#booking_dates_container_" + product.id).find("table.table tbody").append(booking_dates_content);
            } else if (product.hasOptions) {
                $('dt[data-id="cart_block_product_' + productDomId + '"] .cart_prod_cont tr.product_option_row').remove();
                var product_options_content = '';
                if (parseInt(product.selling_preference_type) == SELLING_PREFERENCE_HOTEL_STANDALONE
                    || parseInt(product.selling_preference_type) == SELLING_PREFERENCE_HOTEL_STANDALONE_AND_WITH_ROOM_TYPE
                ) {
                    if (hotel_wise_data.options !== 'undefined') {
                        var options = hotel_wise_data.options;
                    }
                } else if (parseInt(product.selling_preference_type) == SELLING_PREFERENCE_STANDALONE) {
                    if (product.options !== 'undefined') {
                        var options = product.options;
                    }
                }
                if (options !== 'undefined') {
                    $.each(options, function(data_k, data_v) {
                        product_options_content += '<tr class="product_option_row">';
                        product_options_content += '<td>' + data_v.option_name + '</td>';
                        if (product.allow_multiple_quantity) {
                            product_options_content += '<td>' + data_v.quantity + '</td>';
                        }
                        product_options_content += '<td>' + formatCurrency(parseFloat(data_v.total_price_tax_excl), currency_format, currency_sign, currency_blank) + '</td>';
                        product_options_content += '<td class="text-right"><a class="ajax_remove_product_option" href="#" id_product="' + product.id_product + '" id_hotel="' + data_v.id_hotel + '" id_product_option="' + data_v.id_product_option + '" title="' + remove_rm_title + '">&nbsp;</a></td>';
                        product_options_content += '</tr>';
                    });
                }
                $('dt[data-id="cart_block_product_' + productDomId + '"] .cart_prod_cont table tbody').append(product_options_content);

            }
            //end

            var jsonProduct = product;
            if ($.trim($('dt[data-id="cart_block_product_' + productDomId + '"] .quantity').html()) != jsonProduct.quantity || $.trim($('dt[data-id="cart_block_product_' + productDomId + '"] .price').html()) != jsonProduct.priceByLine) {
                // Usual product
                if (!product.is_gift) {
                    if (product.booking_product) {
                        $('dt[data-id="cart_block_product_' + productDomId + '"] .price').text(formatCurrency(parseFloat(product.bookingData.total_room_type_amount), currency_format, currency_sign, currency_blank));
                    } else if ((parseInt(product.selling_preference_type) == SELLING_PREFERENCE_HOTEL_STANDALONE
                        || parseInt(product.selling_preference_type) == SELLING_PREFERENCE_HOTEL_STANDALONE_AND_WITH_ROOM_TYPE)
                        && typeof(hotel_wise_data) != 'undefined'
                    ) {
                        $('dt[data-id="cart_block_product_' + productDomId + '"] .price').text(formatCurrency(parseFloat(hotel_wise_data.amount), currency_format, currency_sign, currency_blank));
                    } else {
                        $('dt[data-id="cart_block_product_' + productDomId + '"] .price').text(formatCurrency(parseFloat(product.amount), currency_format, currency_sign, currency_blank));
                    }
                } else
                    $('dt[data-id="cart_block_product_' + productDomId + '"] .price').html(freeProductTranslation);

                //cart_booking_data[key].total_num_rooms argument sent to update num of rooms instead of quantity
                if (product.booking_product) {
                    ajaxCart.updateProductQuantity(jsonProduct, product.bookingData.total_num_rooms);
                } else if ((parseInt(product.selling_preference_type) == SELLING_PREFERENCE_HOTEL_STANDALONE
                        || parseInt(product.selling_preference_type) == SELLING_PREFERENCE_HOTEL_STANDALONE_AND_WITH_ROOM_TYPE
                    )
                    && typeof(hotel_wise_data) != 'undefined'
                    && hotel_wise_data.total_qty
                ) {
                    ajaxCart.updateProductQuantity(jsonProduct, hotel_wise_data.total_qty, hotel_wise_data);
                } else {
                    ajaxCart.updateProductQuantity(jsonProduct, jsonProduct.cart_quantity);
                }

                // Customized product
                if (jsonProduct.hasCustomizedDatas) {
                    customizationFormatedDatas = ajaxCart.displayNewCustomizedDatas(jsonProduct);
                    if (!$('ul[data-id="customization_' + productAttributeDomId + '"]').length) {
                        if (jsonProduct.hasAttributes)
                            $('dd[data-id="cart_block_combination_of_' + productDomId + '"]').append(customizationFormatedDatas);
                        else
                            $('.cart_block dl.products').append(customizationFormatedDatas);
                    } else {
                        $('ul[data-id="customization_' + productAttributeDomId + '"]').html('');
                        $('ul[data-id="customization_' + productAttributeDomId + '"]').append(customizationFormatedDatas);
                    }
                }
            }
        }
        $('.cart_block dl.products .unvisible').slideDown(450).removeClass('unvisible');

        var removeLinks = $('dt[data-id="cart_block_product_' + productDomId + '"]').find('a.ajax_cart_block_remove_link');
        if (product.hasCustomizedDatas && removeLinks.length)
            $(removeLinks).each(function() {
                $(product).remove();
            });
    },

    displayNewCustomizedDatas: function(product) {
        var content = '';
        var productId = parseInt(product.id);
        var productAttributeId = typeof(product.idCombination) == 'undefined' ? 0 : parseInt(product.idCombination);
        var hasAlreadyCustomizations = $('ul[data-id="customization_' + productId + '_' + productAttributeId + '"]').length;

        if (!hasAlreadyCustomizations) {
            if (!product.hasAttributes)
                content += '<dd data-id="cart_block_combination_of_' + productId + '" class="unvisible">';
            if ($('ul[data-id="customization_' + productId + '_' + productAttributeId + '"]').val() == undefined)
                content += '<ul class="cart_block_customizations" data-id="customization_' + productId + '_' + productAttributeId + '">';
        }

        $(product.customizedDatas).each(function() {
            var done = 0;
            customizationId = parseInt(this.customizationId);
            productAttributeId = typeof(product.idCombination) == 'undefined' ? 0 : parseInt(product.idCombination);
            content += '<li name="customization"><div class="deleteCustomizableProduct" data-id="deleteCustomizableProduct_' + customizationId + '_' + productId + '_' + (productAttributeId ? productAttributeId : '0') + '"><a rel="nofollow" class="ajax_cart_block_remove_link" href="' + baseUri + '?controller=cart&amp;delete=1&amp;id_product=' + productId + '&amp;ipa=' + productAttributeId + '&amp;id_customization=' + customizationId + '&amp;token=' + static_token + '"></a></div>';

            // Give to the customized product the first textfield value as name
            $(this.datas).each(function() {
                if (this['type'] == CUSTOMIZE_TEXTFIELD) {
                    $(this.datas).each(function() {
                        if (this['index'] == 0) {
                            content += ' ' + this.truncatedValue.replace(/<br \/>/g, ' ');
                            done = 1;
                            return false;
                        }
                    })
                }
            });

            // If the customized product did not have any textfield, it will have the customizationId as name
            if (!done)
                content += customizationIdMessage + customizationId;
            if (!hasAlreadyCustomizations) content += '</li>';
            // Field cleaning
            if (customizationId) {
                $('#uploadable_files li div.customizationUploadBrowse img').remove();
                $('#text_fields input').attr('value', '');
            }
        });

        if (!hasAlreadyCustomizations) {
            content += '</ul>';
            if (!product.hasAttributes) content += '</dd>';
        }
        return (content);
    },

    updateLayer: function(product) {
        // change text labels according to product type
        $('#layer_cart .layer_cart_room_txt').hide();
        $('#layer_cart .layer_cart_product_txt').hide();
        if(parseInt(product.booking_product)) {
            $('#layer_cart .layer_cart_room_txt').show();
        } else {
            $('#layer_cart .layer_cart_product_txt').show();
        }
        var product_name_txt = product.name;
        if (typeof(product.id_product_option) !== 'undefined' && product.option_name) {
            product_name_txt += ': ' + product.option_name;
        }
        $('#layer_cart_product_title').text(product_name_txt);
        $('#layer_cart_product_attributes').text('');
        if (product.hasAttributes && product.hasAttributes == true)
            $('#layer_cart_product_attributes').html(product.attributes);
        $('#layer_cart_product_price').text(product.price);

        //by webkul has to work on it more..
        $('#layer_cart_product_time_duration').text(
            product.date_from + ' - ' + product.date_to
        );
        $('#layer_cart_product_hotel_name').parent().hide();
        $('#layer_cart_product_quantity').parent().show();
        var product_quantity_text = '';
        if(parseInt(product.booking_product)) {
            let rooms = 0;
            let adults = 0;
            let children = 0;
            if (occupancy_required_for_booking) {
                $.each(product.occupancy, function(index, val) {
                    rooms++;
                    adults += parseInt(val.adults);
                    children += parseInt(val.children);
                });
                product_quantity_text = getRoomTypeGuestOccupancyFormated(adults, children, rooms);
            } else {
                product_quantity_text = product.occupancy;
            }
        } else {
            if (product.allow_multiple_quantity) {
                product_quantity_text = product.qty;
            } else {
                $('#layer_cart_product_quantity').parent().hide();
            }
            $('#layer_cart_product_unit_price').text(product.unit_price);
            if (product.hotel_name) {
                $('#layer_cart_product_hotel_name').text(product.hotel_name).parent().show();
            }
        }
        $('#layer_cart_product_quantity').text(product_quantity_text);

        $('.layer_cart_img').html('<img class="layer_cart_img img-responsive" src="' + product.image + '" alt="' + product.name + '" title="' + product.name + '" />');

        var n = parseInt($(window).scrollTop()+50) + 'px';

        $('.layer_cart_overlay').css('width', '100%');
        $('.layer_cart_overlay').css('height', '100%');
        $('.layer_cart_overlay').show();
        $('#layer_cart').css({
            'top': n
        }).fadeIn('fast');
        crossselling_serialScroll();
    },

    //genarally update the display of the cart
    updateCart: function(jsonData) {
        //user errors display
        if (jsonData.hasError) {
            var errors = '';
            for (error in jsonData.errors)
            //IE6 bug fix
                if (error != 'indexOf')
                    errors += $('<div />').html(jsonData.errors[error]).text() + "\n";
            if (!!$.prototype.fancybox)
                $.fancybox.open([{
                    type: 'inline',
                    autoScale: true,
                    minHeight: 30,
                    content: '<p class="fancybox-error">' + errors + '</p>'
                }], {
                    padding: 0
                });
            else
                alert(errors);
        } else {
            ajaxCart.updateCartEverywhere(jsonData);
            ajaxCart.hideOldProducts(jsonData);
            ajaxCart.displayNewProducts(jsonData);
            ajaxCart.refreshVouchers(jsonData);

            //update 'first' and 'last' item classes
            $('.cart_block .products dt').removeClass('first_item').removeClass('last_item').removeClass('item');
            $('.cart_block .products dt:first').addClass('first_item');
            $('.cart_block .products dt:not(:first,:last)').addClass('item');
            $('.cart_block .products dt:last').addClass('last_item');
        }
    },

    //update general cart informations everywhere in the page
    updateCartEverywhere: function(jsonData) {
        $('.ajax_cart_total').text($.trim(jsonData.product_total));

        if (typeof hasDeliveryAddress == 'undefined')
            hasDeliveryAddress = false;

        if (parseFloat(jsonData.shipping_cost_float) > 0)
            $('.ajax_cart_shipping_cost').text(jsonData.shipping_cost).parent().find('.unvisible').show();
        else if ((hasDeliveryAddress || typeof(orderProcess) !== 'undefined' && orderProcess == 'order-opc') && typeof(freeShippingTranslation) != 'undefined')
            $('.ajax_cart_shipping_cost').html(freeShippingTranslation);
        else if (!hasDeliveryAddress)
            $('.ajax_cart_shipping_cost').html(toBeDetermined);

        if (hasDeliveryAddress)
            $('.ajax_cart_shipping_cost').parent().find('.unvisible').show();

        $('.ajax_cart_tax_cost').text(jsonData.tax_cost);
        $('.cart_block_wrapping_cost').text(jsonData.wrapping_cost);

        $('.ajax_block_cart_total').text(jsonData.total);
        $('.ajax_block_cart_total').attr('total_cart_price', jsonData.totalToPay);

        $('.ajax_block_room_total').text(jsonData.room_total_format);
        if (jsonData.room_total || jsonData.last_added_product.booking_product) {
            $('.ajax_block_room_total').parent().show();
        } else {
            $('.ajax_block_room_total').parent().hide();
        }
        $('.ajax_block_product_total').text(jsonData.normal_products_total_format);
        if (jsonData.normal_products_total || !jsonData.last_added_product.booking_product) {
            $('.ajax_block_product_total').parent().show();
        } else {
            $('.ajax_block_product_total').parent().hide();
        }
        $('.ajax_cart_convenience_fee').text(jsonData.total_convenience_fee_format);
        if (jsonData.total_convenience_fee) {
            $('.ajax_cart_convenience_fee').parent().show();
        } else {
            $('.ajax_cart_convenience_fee').parent().hide();
        }
        $('.ajax_total_price_wt').text(jsonData.total_price_wt);

        if (parseFloat(jsonData.free_shipping_float) > 0) {
            $('.ajax_cart_free_shipping').html(jsonData.free_shipping);
            $('.freeshipping').fadeIn(0);
        } else if (parseFloat(jsonData.free_shipping_float) == 0)
            $('.freeshipping').fadeOut(0);

        this.total_products_in_cart = jsonData.total_products_in_cart;

        if (parseInt(jsonData.total_products_in_cart) > 0) {
            $('.ajax_cart_no_product').hide();
            $('.ajax_cart_quantity').text(jsonData.total_products_in_cart);
            $('.ajax_cart_quantity').fadeIn('slow');
            $('.ajax_cart_total').fadeIn('slow');

            if (parseInt(jsonData.total_products_in_cart) > 1) {
                $('.ajax_cart_product_txt').each(function() {
                    $(this).hide();
                });

                $('.ajax_cart_product_txt_s').each(function() {
                    $(this).show();
                });
            } else {
                $('.ajax_cart_product_txt').each(function() {
                    $(this).show();
                });

                $('.ajax_cart_product_txt_s').each(function() {
                    $(this).hide();
                });
            }
        } else {
            $('.ajax_cart_quantity, .ajax_cart_product_txt_s, .ajax_cart_product_txt, .ajax_cart_total').each(function() {
                $(this).hide();
            });
            $('.ajax_cart_no_product').show('slow');
        }
    }
};

function HoverWatcher(selector) {
    this.hovering = false;
    var self = this;

    this.isHoveringOver = function() {
        return self.hovering;
    }

    $(selector).hover(function() {
        self.hovering = true;
    }, function() {
        self.hovering = false;
    })
}

function crossselling_serialScroll() {
    if (!!$.prototype.bxSlider)
        $('#blockcart_caroucel').bxSlider({
            minSlides: 2,
            maxSlides: 4,
            slideWidth: 178,
            slideMargin: 20,
            moveSlides: 1,
            infiniteLoop: false,
            hideControlOnEnd: true,
            pager: false
        });
}

function resetRoomtypeServices(refresh = true) {
    $('.room_demands_container').find('input.id_room_type_demand:checked').prop('checked', false).uniform();
    $('#additional_products').empty();
    $('#additional_products div')
    $('.remove_roomtype_product').text(select_txt).removeClass('btn-danger remove_roomtype_product').addClass('btn-success add_roomtype_product');
    if (refresh) {
        BookingForm.refresh();
    }
}

function disableRoomTypeDemands(show) {
    if (show) {
        $('.room_demands_container_overlay').show();
        $('.room_demands_container').find('input:checkbox.id_room_type_demand').prop('checked', false);
        $('.room_demand_block').find('.id_room_type_demand').prop('checked', false).parent().removeClass('checked');
        $('.room_demands_container').find('input:checkbox.id_room_type_demand').attr('disabled', 'disabled');
    } else {
        $('.room_demands_container_overlay').hide();
        $('.room_demands_container').find('input:checkbox.id_room_type_demand').removeAttr('disabled');
        $('.room_demands_container').find('.checker').removeClass('disabled');
    }
}

function disableRoomTypeServices(disable) {
    if (disable) {
        resetRoomtypeServices(false);
        $('#service_products_cont').find('button.add_roomtype_product').attr('disabled', 'disabled');
        $('#service_products_cont').find('.qty_container .qty_direction a').attr('disabled', 'disabled');
    } else {
        $('#service_products_cont').find('button.add_roomtype_product').removeAttr('disabled');
        $('#service_products_cont').find('.qty_container .qty_direction a').removeAttr('disabled');
    }
}

function getRoomsExtraDemands()
{
    var roomDemands = [];

    $('input:checkbox.id_room_type_demand:checked').each(function () {
        roomDemands.push({
            'id_global_demand':$(this).val(),
            'id_option': $(this).closest('.room_demand_block').find('.id_option').val()
        });
    });

    return roomDemands;
}

function getRoomsServiceProducts()
{
    var serviceProducts = [];

    $('#additional_products input.service_product').each(function () {
        serviceProducts.push({
            'id_product': $(this).data('id_product'),
            'quantity':$(this).val(),
        });
    });

    return serviceProducts;
}

function getBookingOccupancyDetails(bookingform, booking_product)
{
    let occupancy;
    if (booking_product) {
        if (occupancy_required_for_booking) {
            $('.booking_guest_occupancy_conatiner .dropdown').removeClass('open');
            let selected_occupancy = $(bookingform).find(".occupancy_info_block.selected")
            if (selected_occupancy.length) {
                occupancy = [];
                $(selected_occupancy).each(function(ind, element) {
                    if (parseInt($(element).find('.num_adults').val())) {
                        let child_ages = [];
                        $(element).find('.guest_child_age').each(function(index) {
                            if ($(this).val() > -1) {
                                child_ages.push($(this).val());
                            }
                        });
                        if ($(element).find('.num_children').val()) {
                            if (child_ages.length != $(element).find('.num_children').val()) {
                                $(bookingform).find('.booking_occupancy_wrapper').parent().addClass('open')
                                occupancy = false;
                            }
                        }
                        occupancy.push({
                            'adults': $(element).find('.num_adults').val(),
                            'children': $(element).find('.num_children').val(),
                            'child_ages': child_ages
                        });
                    } else {
                        $(bookingform).find('.booking_occupancy_wrapper').parent().addClass('open')
                        occupancy = false;
                    }
                });
            } else {
                $(bookingform).find('.booking_occupancy_wrapper').parent().addClass('open')
                occupancy = false;
            }
        } else {
            occupancy = parseInt($(bookingform).find(".quantity_wanted").val());
        }
    } else {
        occupancy = parseInt($('#service_product_qty').val());
    }

    return occupancy;
}
