{*
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
* needs please refer to http://www.prestashop.com for more information.n
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2017 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

	</div>
</div>
{if $display_footer}
<div id="footer" class="bootstrap hide">

	<div class="col-sm-2 hidden-xs">
		<a href="https://chwakahouse.co.tz/" class="_blank">The Chwaka House</a>
		-
		<span id="footer-load-time"><i class="icon-time" title="{l s='Load time: '}"></i> {number_format(microtime(true) - $timer_start, 3, '.', '')}s</span>
	</div>

	<div class="col-sm-2 hidden-xs">
		<div class="social-networks">
			<a class="link-social link-facebook _blank" href="https://facebook.com/people/The-Chwaka-House/61577186942001/" title="Facebook">
				<i class="icon-facebook"></i>
			</a>
			<a class="link-social link-instagram _blank" href="https://www.instagram.com/the_chwaka_house/" title="Instagram">
				<i class="icon-instagram"></i>
			</a>
			<a class="link-social link-phone _blank" href="tel:+255676456679" title="Call Us">
				<i class="icon-phone"></i>
			</a>
		</div>
	</div>
	<div class="col-sm-5">
		<div class="footer-contact">
			<a href="https://chwakahouse.co.tz/contact/" class="footer_link _blank">
				<i class="icon-envelope"></i>
				{l s='Contact Us'}
			</a>
			/&nbsp;
			<a href="https://chwakahouse.co.tz/about/" class="footer_link _blank">
				<i class="icon-info"></i>
				{l s='About Us'}
			</a>
			/&nbsp;
			<a href="https://chwakahouse.co.tz/rooms/" class="footer_link _blank">
				<i class="icon-home"></i>
				{l s='Our Rooms'}
			</a>
			/&nbsp;
			<a href="https://chwakahouse.co.tz/booking/" class="footer_link _blank">
				<i class="icon-calendar"></i>
				{l s='Book Now'}
			</a>
			{if $host_mode}
			/&nbsp;
			<a href="http://status.prestashop.com/" class="footer_link _blank">
				<i class="icon-circle status-page-dot"></i>
				<span class="status-page-description"></span>
			</a>
			{/if}
			{if $iso_is_fr && !$host_mode}
			<p>Questions • Renseignements • Formations :
				<strong>+33 (0)1.40.18.30.04</strong>
			</p>
			{/if}
		</div>
	</div>

	<div class="col-sm-3">
		{hook h="displayBackOfficeFooter"}
	</div>

	<div id="go-top" class="hide"><i class="icon-arrow-up"></i></div>
</div>
{/if}
{if isset($php_errors)}
	{include file="error.tpl"}
{/if}

{if isset($modals)}
<div class="bootstrap">
	{$modals}
</div>
{/if}

</body>
</html>
