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

{if $module == "mobipaid"}
    {if !empty($successMessage)}
        <div class="alert alert-success">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {if $successMessage == "refund"}
                {l s='Mobipaid full refund successfull.' mod='mobipaid'}
            {/if}
            {if $successMessage == "partial-refund"}
                {l s='Mobipaid partial refund successfull.' mod='mobipaid'}
            {/if}
        </div>
    {/if}
    {if !empty($errorMessage)}
        <div class="alert alert-danger">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {if $errorMessage == "refund"}
                {l s='Refund Failed' mod='mobipaid'}
            {/if}
            {if $errorMessage == "partial-refund"}
                {l s='Refund Failed' mod='mobipaid'}
            {/if}
        </div>
    {/if}


    {if !empty($additionalInfo)}
        <div class="alert alert-warning">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {$additionalInfo|escape:'htmlall':'UTF-8'}
        </div>
    {/if}

{/if}
