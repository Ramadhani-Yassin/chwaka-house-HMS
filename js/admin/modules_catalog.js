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

$('document').ready( function() {
	initPagination();
    // ScrollTo
    $('#module-search').on('keyup', function(){
        val = this.value;
        setTimeout(function () {
            filterPanel(val, 'suggested-modules-list');
        }, 200);
    }).on('keydown', function(e){
        if (e.keyCode == 13)
            return false;
		if (e.keyCode == 27) {
			this.value = '';
		}
    });

	$('#theme-search').on('keyup', function(){
        val = this.value;
        setTimeout(function () {
            filterPanel(val, 'suggested-theme-list');
        }, 200);
    }).on('keydown', function(e){
        if (e.keyCode == 13)
            return false;
		if (e.keyCode == 27) {
			this.value = '';
		}
    });

	$('#theme-sort, #module-sort').on('change', function(){
		setFilter();
	});

	function filterPanel(val, element_class)
	{
		val = $.trim(val);
		$('#'+element_class+' .list-empty').hide();
        $('#'+element_class+' .element-panel').css('display', 'flex').removeClass('hidden');

		if (val != '') {
            var reg = new RegExp(val, "i");
            $('#'+element_class+' .element-panel .name').each(function(id, ele_name) {
                if (!reg.test($(ele_name).text()) && !reg.test($(ele_name).data('name'))){
                // if (!$(mod_name).text().includes(val)) {
                    $(ele_name).closest('.element-panel').hide().addClass('hidden');
                }
            });
        }
		if (!$('#'+element_class+' .element-panel:visible').length) {
			$('#'+element_class+' .list-empty').css('display', 'flex');
		}
		initPagination(element_class);
	}

	function setFilter()
	{
		let theme_sorting = $("#theme-sort").val();
		let module_sorting = $("#module-sort").val();
		$.ajax({
			type: 'POST',
			url: 'index.php',
			async: true,
			dataType: 'JSON',
			data: {
				action: 'setSorting',
				theme_sorting: theme_sorting,
				module_sorting: module_sorting,
				tab: 'AdminModulesCatalog',
				ajax: 1,
				token: token
			},
			success: function(res) {
				location.reload();
			},
		});
	}

	function initPagination(selected_element_class) {
		$.each($('.suggested-elements'), function(i, element) {
			if (selected_element_class) {
				if ($(element).closest('.list-container').attr('id') != selected_element_class)
					return;
			}
			$(element).siblings('.pagination-container').find('.pagination-block').twbsPagination('destroy');
			let items = $(element).children(':not(.hidden)');
			if (items.length) {
				let total_pages = Math.ceil(items.length / num_block_per_page);
				$(element).siblings('.pagination-container').find('.pagination-block').twbsPagination({
					totalPages: total_pages,
					visiblePages: 5,
					onPageClick: function(e, pageNumber) {
						let showFrom = num_block_per_page * (pageNumber - 1);
						let showTo = showFrom + num_block_per_page;
						items.hide().slice(showFrom, showTo).css('display', 'flex');
					}
				});
			}
		});
	}
});
