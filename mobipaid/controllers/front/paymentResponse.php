<?php
/**
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
*/

class MobipaidPaymentResponseModuleFrontController extends ModuleFrontController
{
    /**
     * Process payment response from the gateway in the background process.
     *
     * @return void
     */
    public function postProcess()
    {
        $cartId = Tools::getValue('cart_id');
        $paymentResponse = Tools::getValue('response');

        if (!empty($paymentResponse)) {
            $paymentResponse = json_decode($paymentResponse, 1);
        } else {
            $messageLog = 'Mobipaid - no payment response from gateway';
            $this->module->addPluginLogger($messageLog, 3, null, 'Cart', 0, true);
            die('no response from gateway.');
        }
        
        $messageLog = 'Mobipaid - payment response from payment gateway : ' . json_encode($paymentResponse);
        $this->module->addPluginLogger($messageLog, 1, null, 'Cart', $cartId, true);
        
        if ($paymentResponse['result'] == "ACK") {
            $this->module->addPluginLogger('Mobipaid - use payment gateway', 1, null, 'Cart', $cartId, true);
            $isTransactionLogExist = $this->isTransactionLogExist($cartId);

            if (!$isTransactionLogExist) {
                Context::getContext()->cart = new Cart((int)$cartId);
                $transactionLog = $this->setTransactionLog($paymentResponse);
                $secretkey = $this->module->generateSecretKey(
                    $cartId,
                    $paymentResponse['currency']
                );

                if ($secretkey != Tools::getValue('secure_payment')) {
                    $this->module->addPluginLogger(
                        'Mobipaid - FRAUD Transaction',
                        1,
                        null,
                        'Cart',
                        $cartId,
                        true
                    );
                }

                $this->module->addPluginLogger(
                    'Mobipaid - save transaction log from status URL',
                    1,
                    null,
                    'Cart',
                    $cartId,
                    true
                );
                $this->module->saveTransactionLog($transactionLog, 0);
                $this->validatePayment($cartId);
            } else {
                $this->module->addPluginLogger(
                    'Mobipaid - process existing order ',
                    1,
                    null,
                    'Cart',
                    $cartId,
                    true
                );
                $this->updatePrestashopOrderStatus($cartId, Configuration::get('PS_OS_PAYMENT'), $paymentResponse);
            }
        } else {
            die('payment failed');
        }
        die('end');
    }

    /**
     * validate payment response from gateway.
     * @param  string $cartId
     *
     * @return void
     */
    public function validatePayment($cartId)
    {
        Context::getContext()->cart = new Cart((int)$cartId);
        $cart = $this->context->cart;
        Context::getContext()->currency = new Currency((int)$cart->id_currency);
        $customer = new Customer($cart->id_customer);

        $messageLog =
            'Mobipaid - Module Status : '. $this->module->active .
            ', Customer Id : '. $cart->id_customer .
            ', Delivery Address : '. $cart->id_address_delivery .
            ', Invoice Address : '. $cart->id_address_invoice;
        $this->module->addPluginLogger($messageLog, 1, null, 'Cart', $cart->id, true);
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0
            || $cart->id_address_invoice == 0 || !$this->module->active
            || !Validate::isLoadedObject($customer)) {
            $this->module->addPluginLogger('Mobipaid - customer datas are not valid', 3, null, 'Cart', $cart->id, true);
            die('Erreur etc.');
        }

        $this->processSuccessPayment($customer);
    }

    /**
     * create order for success payment.
     * @param  Object $customer
     *
     * @return void
     */
    protected function processSuccessPayment($customer)
    {
        $cart = $this->context->cart;
        $cartId = $cart->id;
        $currency = $this->context->currency;
        $total = (float)($cart->getOrderTotal(true, Cart::BOTH));
        
        $this->module->validateOrder(
            $cartId,
            Configuration::get('PS_OS_PAYMENT'),
            $total,
            $this->module->displayName,
            null,
            array(),
            $currency->id,
            false,
            $customer->secure_key
        );

        $orderId = $this->module->currentOrder;
        $this->module->addPluginLogger("get order_id ".$orderId, 1, null, 'Cart', $cartId, true);

        $this->updateTransactionLog(
            $orderId,
            $cartId
        );
        $messageLog = 'Mobipaid - order ('. $orderId .') has been successfully created';
        $this->module->addPluginLogger($messageLog, 1, null, 'Cart', $cartId, true);
    }


    /**
     * set Transaction Log from payment response.
     * @param array $paymentResponse
     *
     * @return array
     */
    public function setTransactionLog($paymentResponse)
    {
        $cart = $this->context->cart;
        $transactionLog = array();
        $transactionLog['transaction_id'] = $paymentResponse['transaction_id'];
        $transactionLog['cart_id'] = Tools::getValue('cart_id');
        $transactionLog['order_status'] = Configuration::get('PS_OS_PAYMENT');
        $transactionLog['payment_id'] = $paymentResponse['payment_id'];
        $transactionLog['currency'] = $paymentResponse['currency'];
        $transactionLog['amount'] = (float)($cart->getOrderTotal(true, Cart::BOTH));
        $transactionLog['payment_response'] = serialize($paymentResponse);

        return $transactionLog;
    }


    /**
     * update Transaction Log from table mobipaid_order_ref.
     * @param  string $orderId
     * @param  string $cart_id
     *
     * @return void
     */
    protected function updateTransactionLog($orderId, $cart_id)
    {
        $sql = "UPDATE mobipaid_order_ref SET
            order_id = '".pSQL($orderId)."' 
            where cart_id = '".pSQL($cart_id)."'";

        $messageLog = 'Mobipaid - update payment response from payment gateway : ' . $sql;
        $this->module->addPluginLogger($messageLog, 1, null, 'Order', $orderId, true);

        if (!Db::getInstance()->execute($sql)) {
            $messageLog = 'Mobipaid - failed when updating payment response from payment gateway';
            $this->module->addPluginLogger($messageLog, 3, null, 'Order', $orderId, true);
            die('Erreur etc.');
        }
        $this->module->addPluginLogger(
            'Mobipaid - payment gateway response succefully updated',
            1,
            null,
            'Order',
            $orderId,
            true
        );
    }

    /**
     * validate if Transaction log exists on database.
     * @param  string  $cartId
     *
     * @return boolean
     */
    public function isTransactionLogExist($cartId)
    {
        $order = $this->module->getOrderByCartId($cartId);

        $messageLog = 'Mobipaid - existing order : ' . json_encode($order);
            $this->module->addPluginLogger($messageLog, 1, null, 'Cart', $this->context->cart->id, true);

        if (!empty($order)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * update order status in table mobipaid_order_ref.
     * @param  string $orderId
     * @param  string $orderStatus
     *
     * @return void
     */
    protected function updateTransactionLogOrderStatus($orderId, $orderStatus, $paymentResponse = "")
    {
        $sql = "UPDATE mobipaid_order_ref SET
            order_status = '".pSQL($orderStatus)."',
            payment_response = '".pSQL(serialize($paymentResponse))."'
            where order_id = '".pSQL($orderId)."'";

        $messageLog = 'Mobipaid - update order status : ' . $sql;
        $this->module->addPluginLogger($messageLog, 1, null, 'Order', $orderId, true);

        if (!Db::getInstance()->execute($sql)) {
            $messageLog = 'Mobipaid - failed when updating order status';
            $this->module->addPluginLogger($messageLog, 3, null, 'Order', $orderId, true);
            die('Erreur etc.');
        }
        $this->module->addPluginLogger(
            'Mobipaid - order status succefully updated',
            1,
            null,
            'Order',
            $orderId,
            true
        );
    }

    /**
     * update order status from existing order base on cartId.
     * @param  string $cartId
     * @param  string $orderStatus
     *
     * @return void
     */
    protected function updatePrestashopOrderStatus($cartId, $orderStatus, $paymentResponse = "")
    {
        $orderLog = $this->module->getOrderByCartId($cartId);
        $orderId= $orderLog['order_id'];
        $history = new OrderHistory();
        $history->id_order = (int)$orderId;
        $history->changeIdOrderState($orderStatus, (int)($orderId));
        $history->addWithemail(true);
        $this->updateTransactionLogOrderStatus($orderId, $orderStatus, $paymentResponse);
    }
}
