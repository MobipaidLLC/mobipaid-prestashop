{*
* 2020 Mobipaid
*
* NOTICE OF Mobipaid
*
* This source file is subject to the General Public License) (GPL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/gpl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
*  @author    Mobipaid <info@mobipaid.com>
*  @copyright 2020 Mobipaid
*  @license   https://www.gnu.org/licenses/gpl-3.0.html  General Public License (GPL 3.0)
*  International Registered Trademark & Property of Mobipaid
*}

{if $status == 'ok'}
	<p>
		{l s='Your order on' mod='mobipaid'} {$shop_name|escape:'htmlall':'UTF-8'} {l s='is complete.' mod='mobipaid'}
		<br/>
		{l s='Thank you for your purchase!' mod='mobipaid'}
	</p>
{/if}
