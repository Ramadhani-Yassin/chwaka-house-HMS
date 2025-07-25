{**
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
*}

<div class="col-sm-12">
	<section id="dashproducts" class="panel widget {if $allow_push} allow_push{/if}">
		<header class="panel-heading">
			<i class="icon-bar-chart"></i> {l s='Sales' mod='dashproducts'}
			<span class="panel-heading-action">
				<a class="list-toolbar-btn" href="#" onclick="toggleDashConfig('dashproducts'); return false;" title="{l s="Configure" mod='dashproducts'}">
					<i class="process-icon-configure"></i>
				</a>
				<a class="list-toolbar-btn" href="#"  onclick="refreshDashboard('dashproducts'); return false;"  title="{l s="Refresh" mod='dashproducts'}">
					<i class="process-icon-refresh"></i>
				</a>
			</span>
		</header>

		<section id="dashproducts_config" class="dash_config hide">
			<header><i class="icon-wrench"></i> {l s='Configuration' mod='dashproducts'}</header>
			{$dashproducts_config_form}
		</section>

		<section>
			<nav>
				<ul class="nav nav-pills row">
					<li class="col-xs-6 col-sm-3 nav-item active">
						<a href="#dash_recent_orders" data-toggle="tab">
							<span>{l s="New Bookings" mod='dashproducts'}</span>
						</a>
					</li>
					<li class="col-xs-6 col-sm-3 nav-item">
						<a href="#dash_best_sellers" data-toggle="tab">
							<span>{l s="Best Selling" mod='dashproducts'}</span>
						</a>
					</li>
					<li class="col-xs-6 col-sm-3 nav-item">
						<a href="#dash_most_viewed" data-toggle="tab">
							<span>{l s="Most Viewed" mod='dashproducts'}</span>
						</a>
					</li>
					<li class="col-xs-6 col-sm-3 nav-item">
						<a href="#dash_top_search" data-toggle="tab">
							<span>{l s="Top Searches" mod='dashproducts'}</span>
						</a>
					</li>
				</ul>
			</nav>

			<div class="tab-content panel">
				<div class="tab-pane active" id="dash_recent_orders">
					<h3>{l s="Last %d bookings" sprintf=$DASHPRODUCT_NBR_SHOW_LAST_ORDER|intval mod='dashproducts'}</h3>
					<div class="table-responsive">
						<table class="table data_table" id="table_recent_orders">
							<thead></thead>
							<tbody></tbody>
						</table>
					</div>
				</div>
				<div class="tab-pane" id="dash_best_sellers">
					<h3>
						{l s="Top %d room types" sprintf=$DASHPRODUCT_NBR_SHOW_BEST_SELLER|intval mod='dashproducts'} (<span>{l s="From" mod='dashproducts'} {$date_from} {l s="to" mod='dashproducts'} {$date_to}</span>)
					</h3>
					<div class="table-responsive">
						<table class="table data_table" id="table_best_sellers">
							<thead></thead>
							<tbody></tbody>
						</table>
					</div>
				</div>
				<div class="tab-pane" id="dash_most_viewed">
					<h3>
						{l s="Most viewed room types" mod='dashproducts'} (<span>{l s="From" mod='dashproducts'} {$date_from} {l s="to" mod='dashproducts'} {$date_to}</span>)
					</h3>
					<div class="table-responsive">
						<table class="table data_table" id="table_most_viewed">
							<thead></thead>
							<tbody></tbody>
						</table>
					</div>
				</div>
				<div class="tab-pane" id="dash_top_search">
					<h3>
						{l s="Top %d most searched hotels" sprintf=$DASHPRODUCT_NBR_SHOW_TOP_SEARCH|intval mod='dashproducts'} (<span>{l s="From" mod='dashproducts'} {$date_from} {l s="to" mod='dashproducts'} {$date_to}</span>)
					</h3>
                    <div class="alert alert-info">{l s="Top searched hotel list is independent of hotel selection at the top. It will always display top searched hotels among all hotels." mod='dashproducts'}</div>
					<div class="table-responsive">
						<table class="table data_table" id="table_top_10_most_search">
							<thead></thead>
							<tbody></tbody>
						</table>
					</div>
				</div>
			</div>
		</section>
	</section>
</div>
<div class="clearfix"></div>
