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
    <section id="dashguestcycle" class="panel widget allow_push">
        <header class="panel-heading">
            <i class="icon-bar-chart"></i> {l s='Operations Today' mod='dashguestcycle'}
            <span class="panel-heading-action">
                <a class="list-toolbar-btn" href="#" onclick="refreshDashboard('dashguestcycle'); return false;"
                    title="{l s='Refresh' mod='dashguestcycle'}">
                    <i class="process-icon-refresh"></i>
                </a>
            </span>
            {hook h='displayDashGuestCycleHeader'}
        </header>

        <section>
            <ul class="nav nav-tabs">
                <li class="active">
                    <a href="#dgc_current_arrivals" data-toggle="tab">
                        <span>{l s='Arrivals' mod='dashguestcycle'}</span>
                        <span class="label label-info" id="dgc_count_upcoming_arrivals">0</span>
                    </a>
                </li>
                <li>
                    <a href="#dgc_current_departures" data-toggle="tab">
                        <span>{l s='Departures' mod='dashguestcycle'}</span>
                        <span class="label label-info" id="dgc_count_upcoming_departures">0</span>
                    </a>
                </li>
                <li>
                    <a href="#dgc_current_in_house" data-toggle="tab">
                        <span>{l s='In-house' mod='dashguestcycle'}</span>
                        <span class="label label-info" id="dgc_count_current_in_house">0</span>
                    </a>
                </li>
                <li>
                    <a href="#dgc_current_new_bookings" data-toggle="tab">
                        <span>{l s='New Bookings' mod='dashguestcycle'}</span>
                        <span class="label label-info" id="dgc_count_new_bookings">0</span>
                    </a>
                </li>
                <li>
                    <a href="#dgc_current_cancellations" data-toggle="tab">
                        <span>{l s='Cancellations' mod='dashguestcycle'}</span>
                        <span class="label label-info" id="dgc_count_cancellations">0</span>
                    </a>
                </li>
                {hook h='displayDashGuestCycleTab'}
            </ul>

            <div class="tab-content panel panel-sm">
                <div class="tab-pane active" id="dgc_current_arrivals">
                    <table class="table table-striped" id="dgc_table_current_arrivals">
                        <thead></thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="tab-pane" id="dgc_current_departures">
                    <table class="table table-striped" id="dgc_table_current_departures">
                        <thead></thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="tab-pane" id="dgc_current_in_house">
                    <table class="table table-striped" id="dgc_table_current_in_house">
                        <thead></thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="tab-pane" id="dgc_current_new_bookings">
                    <table class="table table-striped" id="dgc_table_new_bookings">
                        <thead></thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="tab-pane" id="dgc_current_cancellations">
                    <table class="table table-striped" id="dgc_table_cancellations">
                        <thead></thead>
                        <tbody></tbody>
                    </table>
                </div>
                {hook h='displayDashGuestCycleTabContent'}
            </div>
        </section>
    </section>
</div>
<div class="clearfix"></div>
