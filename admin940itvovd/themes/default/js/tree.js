var Tree = function (element, options)
{
	this.$element = $(element);
	this.options = $.extend({}, $.fn.tree.defaults, options);
	this.init();
};

function getCategoryById(param) {
	var elem = null;
	$('input[name=id_parent]').each(function (index) {
		if ($(this).val() === param + '') {
			elem = $(this);
		}
	});
	return elem;
}

function disableTreeItem(item) {
	item.find('input[name=id_parent]').attr('disabled', 'disabled');
	if (item.hasClass('tree-folder')) {
		item.find('span.tree-folder-name').addClass('tree-folder-name-disable');
		item.find('ul li').each(function (index) {
			disableTreeItem($(this));
		});
	} else if (item.hasClass('tree-item')) {
		item.addClass('tree-item-disable');
	}
}

function organizeTree() {
	if ($('#id_category').length != 0) {
		var id = $('#id_category').val();
		var item = getCategoryById(id).parent().parent();
		disableTreeItem(item);
	}
}

Tree.prototype =
{
	constructor: Tree,

	init: function ()
	{
		var that = $(this);
		var name = this.$element.parent().find('ul.tree input').first().attr('name');
		var idTree = this.$element.parent().find('.cattree.tree').first().attr('id');
		this.$element.find("label.tree-toggler, .icon-folder-close, .icon-folder-open").unbind('click');
		this.$element.find("label.tree-toggler, .icon-folder-close, .icon-folder-open").click(
			function ()
			{
				if ($(this).parent().parent().children("ul.tree").is(":visible"))
				{
					$(this).parent().children(".icon-folder-open")
						.removeClass("icon-folder-open")
						.addClass("icon-folder-close");

					that.trigger('collapse');
					$(this).parent().parent().children("ul.tree").toggle(300);
				}
				else
				{
					$(this).parent().children(".icon-folder-close")
						.removeClass("icon-folder-close")
						.addClass("icon-folder-open");

					var load_tree = (typeof(idTree) != 'undefined'
									 && $(this).parent().closest('.tree-folder').find('ul.tree .tree-toggler').first().html() == '');
					if (load_tree)
					{
						var category = $(this).parent().children('ul.tree input').first().val();
						var inputType = $(this).parent().children('ul.tree input').first().attr('type');
						var useCheckBox = 0;
						if (inputType == 'checkbox')
						{
							useCheckBox = 1;
						}

						var thatOne = $(this);
						$.get(
							'ajax-tab.php',
							{controller:'AdminProducts',token:currentToken,action:'getCategoryTree',type:idTree,category:category,inputName:name,useCheckBox:useCheckBox},
							function(content)
							{
								thatOne.parent().closest('.tree-folder').find('ul.tree').html(content);
								$('#'+idTree).tree('collapse', thatOne.closest('.tree-folder').children("ul.tree"));
								that.trigger('expand');
								thatOne.parent().parent().children("ul.tree").toggle(300);
								$('#'+idTree).tree('init');
							}
						);
					}
					else
					{
						that.trigger('expand');
						$(this).parent().parent().children("ul.tree").toggle(300);
					}
				}
			}
		);
		this.$element.find("li").unbind('click');
		this.$element.find("li").click(
			function ()
			{
				$('#'+idTree+' .tree-selected').removeClass("tree-selected");
				$('#'+idTree+' li input:checked').parent().addClass("tree-selected");
			}
		);

		if (typeof(idTree) != 'undefined')
		{
			if ($('select#id_category_default').length)
			{
				this.$element.find(':input[type=checkbox]').unbind('click');
				this.$element.find(':input[type=checkbox]').click(function()
				{
					if ($(this).prop('checked'))
						addDefaultCategory($(this));
					else
					{
						$('select#id_category_default option[value=' + $(this).val() + ']').remove();
						if ($('select#id_category_default option').length == 0)
						{
							$('select#id_category_default').closest('.form-group').hide();
							$('#no_default_category').show();
						}
					}
				});
			}
			if (typeof(treeClickFunc) != 'undefined')
			{
				this.$element.find(":input[type=radio]").unbind('click');
				this.$element.find(":input[type=radio]").click(treeClickFunc);
			}
		}

		return $(this);
	},

	collapse : function(elem, $speed)
	{
		elem.find("label.tree-toggler").each(
			function()
			{
				$(this).parent().children(".icon-folder-open")
					.removeClass("icon-folder-open")
					.addClass("icon-folder-close");
				$(this).parent().parent().children("ul.tree").hide($speed);
			}
		);

		return $(this);
	},

	collapseAll : function($speed)
	{
		this.$element.find("label.tree-toggler").each(
			function()
			{
				$(this).parent().children(".icon-folder-open")
					.removeClass("icon-folder-open")
					.addClass("icon-folder-close");
				$(this).parent().parent().children("ul.tree").hide($speed);
			}
		);

		return $(this);
	},

	expandAll : function($speed)
	{
		this.$element.find("label.tree-toggler").each(
			function()
			{
				$(this).parent().children(".icon-folder-close")
					.removeClass("icon-folder-close")
					.addClass("icon-folder-open");
				$(this).parent().parent().children("ul.tree").show($speed);
			}
		);

		return $(this);
	}
};

$.fn.tree = function (option, value)
{
	var methodReturn;
	var $set = this.each(
		function ()
		{
			var $this = $(this);
			var data = $this.data('tree');
			var options = typeof option === 'object' && option;

			if (!data){
				$this.data('tree', (data = new Tree(this, options)));
			}
			if (typeof option === 'string') {
				methodReturn = data[option](value);
			}
		}
	);

	return (methodReturn === undefined) ? $set : methodReturn;
};

$.fn.tree.Constructor = Tree;