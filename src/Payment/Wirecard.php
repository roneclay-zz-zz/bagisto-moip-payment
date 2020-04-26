<?php

namespace Fineweb\Wirecard\Payment;

use Fineweb\Wirecard\Helper\Helper;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Webkul\Checkout\Models\Cart;
use Webkul\Checkout\Models\CartAddress;
use Webkul\Payment\Payment\Payment;
use Moip\Moip;
use Fineweb\Wirecard\src\Models\MoipPayment;


use Moip\Auth\BasicAuth;



use function core;

/**
 * Class Wirecard
 * @package Fineweb\Wirecard\Payment
 */
class Wirecard extends Payment
{
    /**
     *
     */

    const WIRECARD_API_VERSION = 'v2';
    const WIRECARD_CUSTOMER_URL = 'customers';
    const WIRECARD_ORDER_URL = 'orders';
    const CUSTOMER_DATA_BASE = 'customers';
    const WIRECARD_CUSTOMER_ENTITY = 'customers';

    const CONFIG_ACCESS_KEY = 'sales.paymentmethods.wirecardconfig.access_key';
    /**
     *
     */
    const CONFIG_TOKEN = 'sales.paymentmethods.wirecardconfig.token';
    /**
     *
     */
    const CONFIG_SANDBOX = 'sales.paymentmethods.wirecardconfig.sandbox';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $code = 'wirecardboleto';
    /**
     * @var
     */
    protected $sessionCode;
    /**
     * @var \Wirecard\Domains\Requests\Payment
     */
    protected $payment;







    /**
     * @var array
     */
    protected $urls = [];
    /**
     * @var bool
     */
    protected $sandbox = false;
    /**
     * @var string
     */
    protected $environment = 'production';
    /**
     * @var
     */
    protected $accessKey;
    /**
     * @var
     */
    protected $token;



    /**
     * Wirecard constructor.
     */
    public function __construct(

    )
    {
        $this->accessKey = core()->getConfigData(self::CONFIG_ACCESS_KEY);
        $this->token = core()->getConfigData(self::CONFIG_TOKEN);



        if (core()->getConfigData(self::CONFIG_SANDBOX)) {
            $this->sandbox = true;
            $this->environment = 'sandbox';
        }

        $this->setUrls();
    }

    public function validateCustomerDob($dob)
    {
        return strtotime(Carbon::parse($dob)) < 0 || Carbon::parse($dob)->age < 18 ? null : $dob;
    }

    /**
     * @throws Exception
     */
    public function init()
    {
        if (!$this->accessKey || !$this->token)
            throw new Exception('Wirecard: To use this payment method you need to inform the token and acess key account of Wirecard account.');

        $currentUser = auth()->guard('customer')->user();

        if (!$this->validateCustomerDob($currentUser->date_of_birth))
            throw new Exception('Wirecard: Please update your account with Date of birth information.');

        /** @var Cart $cart */
        $cart = $this->getCart();

        $moip = $this->initWirecardObject(self::WIRECARD_CUSTOMER_ENTITY);




        // Cria o pedido

        // Cria o pagamento

        $customer = $moip
            ->customers()->setOwnId(uniqid())
            ->setFullname($cart->customer_first_name . ' ' . $cart->customer_last_name)
            ->setEmail($currentUser->email)
            ->setBirthDate($currentUser->date_of_birth)
            ->setTaxDocument('77763360500')
            ->setPhone(11, 66778899)
            ->addAddress('BILLING',
                'Rua de teste', 123,
                'Bairro', 'Sao Paulo', 'SP',
                '01234567', 8)
            ->addAddress('SHIPPING',
                'Rua de teste do SHIPPING', 123,
                'Bairro do SHIPPING', 'Sao Paulo', 'SP',
                '01234567', 8)
            ->create();

        $order = $moip->orders()->setOwnId(uniqid())
            ->addItem("bicicleta 1",1, "sku1", 10000)
            ->addItem("bicicleta 2",1, "sku2", 11000)
            ->addItem("bicicleta 3",1, "sku3", 12000)
            ->addItem("bicicleta 4",1, "sku4", 13000)
            ->addItem("bicicleta 5",1, "sku5", 14000)
            ->addItem("bicicleta 6",1, "sku6", 15000)
            ->addItem("bicicleta 7",1, "sku7", 16000)
            ->addItem("bicicleta 8",1, "sku8", 17000)
            ->addItem("bicicleta 9",1, "sku9", 18000)
            ->addItem("bicicleta 10",1, "sku10", 19000)
            ->setShippingAmount(3000)->setAddition(1000)->setDiscount(5000)
            ->setCustomer($customer)
            ->create();






        try {
            $payment = $this->applyPayment( $order );

            $moipPayment = new MoipPayment();
            $moipPayment->cart_id = $cart->id;
            $moipPayment->moip_order_data = json_encode($order );
            $moipPayment->moip_payment_data = json_encode($payment );
            $moipPayment->save();

        } catch (Exception $e) {
            throw new Exception('Wirecard: ' . $e->getMessage());
        }
    }

    public function applyPayment ( $order )
    {
        return $this->code === 'wirecardboleto' ? $this->applyBoletoPayment( $order ) : $this->applyCcPayment( $order ) ;
    }

    public function applyBoletoPayment($order)
    {
        $logo_uri = 'https://cdn.moip.com.br/wp-content/uploads/2016/05/02163352/logo-moip.png';
        $expiration_date = new \DateTime();
        $instruction_lines = ['INSTRUÇÃO 1', 'INSTRUÇÃO 2', 'INSTRUÇÃO 3'];
        $payment = $order->payments()
            ->setBoleto($expiration_date, $logo_uri, $instruction_lines)
            ->execute();



        return $payment;
    }

    public function initWirecardObject()
    {
        $moip = new Moip( new BasicAuth($this->token, $this->accessKey), $this->getWirecardEndpoint() );

        return $moip;
    }

    public function getWirecardEndpoint()
    {
        return $this->environment === 'production' ? Moip::ENDPOINT_PRODUCTION  : Moip::ENDPOINT_SANDBOX ;
    }

    /**
     * @param Cart $cart
     */
    public function configurePayment(Cart $cart)
    {
        $this->payment->setCurrency('BRL');
        $this->payment->setReference($cart->id);
        $this->payment->setRedirectUrl(route('wirecard.success'));
        $this->payment->setNotificationUrl(route('wirecard.notify'));
    }

    /**
     *
     */
    public function addItems()
    {
        /**
         * @var \Webkul\Checkout\Models\CartItem[] $items
         */
        $items = $this->getCartItems();

        foreach ($items as $cartItem) {
            $this->payment->addItems()->withParameters(
                $cartItem->product_id,
                $cartItem->name,
                $cartItem->quantity,
                $cartItem->price
            );
        }
    }

    /**
     *
     */
    public function addCustomer(Cart $cart)
    {
        $this->payment->setSender()->setName($cart->customer_first_name . ' ' . $cart->customer_last_name);
        $this->payment->setSender()->setEmail($cart->customer_email);
    }

    /**
     *
     */
    public function addShipping(Cart $cart)
    {
        /**
         * @var CartAddress $billingAddress
         */
        $billingAddress = $cart->getBillingAddressAttribute();

        // Add telephone
        $telephone = Helper::phoneParser($billingAddress->phone);

        if ($telephone) {
            $this->payment->setSender()->setPhone()->withParameters(
                $telephone['ddd'],
                $telephone['number']
            );
        }

        // Add CPF
        if ($billingAddress->vat_id) {
            $this->payment->setSender()->setDocument()->withParameters(
                'CPF',
                Helper::justNumber($billingAddress->vat_id)
            );
        }

        if ($cart->selected_shipping_rate) {
            $addresses = explode(PHP_EOL, $billingAddress->address1);

            // Add address
            $this->payment->setShipping()->setAddress()->withParameters(
                isset($addresses[0]) ? $addresses[0] : null,
                isset($addresses[1]) ? $addresses[1] : null,
                isset($addresses[2]) ? $addresses[2] : null,
                $billingAddress->postcode,
                $billingAddress->city,
                $billingAddress->state,
                $billingAddress->country,
                isset($addresses[3]) ? $addresses[3] : null
            );

            // Add Shipping Method
            $this->payment->setShipping()->setCost()->withParameters($cart->selected_shipping_rate->price);
            if (Str::contains($cart->selected_shipping_rate->carrier, 'correio')) {
                if (Str::contains($cart->selected_shipping_rate->method, 'sedex')) {
                    $this->payment->setShipping()->setType()->withParameters(Type::SEDEX);
                }
                if (Str::contains($cart->selected_shipping_rate->method, 'pac')) {
                    $this->payment->setShipping()->setType()->withParameters(Type::PAC);
                }
            } else {
                $this->payment->setShipping()->setType()->withParameters(Type::NOT_SPECIFIED);
            }
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    public function send()
    {

    }

    /**
     * @return string
     */
    public function getRedirectUrl()
    {
           $this->init();
    }

    /**
     * @return string
     */
    public function getWirecardUrl()
    {
        return $this->urls['redirect'];
    }

    /**
     * @param array $urls
     */
    public function setUrls(): void
    {
        $env = $this->sandbox ? $this->environment . '.' : '';
        $this->urls = [
            'preApprovalRequest' => 'https://ws.' . $env . 'wirecard.uol.com.br/v2/pre-approvals/request',
            'preApproval' => 'https://ws.' . $env . 'wirecard.uol.com.br/pre-approvals',
            'preApprovalCancel' => 'https://ws.' . $env . 'wirecard.uol.com.br/v2/pre-approvals/cancel/',
            'cancelTransaction' => 'https://ws.' . $env . 'wirecard.uol.com.br/v2/transactions/cancels',
            'preApprovalNotifications' => 'https://ws.' . $env . 'wirecard.uol.com.br/v2/pre-approvals/notifications/',
            'session' => 'https://ws.' . $env . 'wirecard.uol.com.br/v2/sessions',
            'transactions' => 'https://ws.' . $env . 'wirecard.uol.com.br/v2/transactions',
            'notifications' => 'https://ws.' . $env . 'wirecard.uol.com.br/v3/transactions/notifications/',
            'javascript' => 'https://stc.' . $env . 'wirecard.uol.com.br/wirecard/api/v2/checkout/wirecard.directpayment.js',
            'lightbox' => 'https://stc.' . $env . 'wirecard.uol.com.br/wirecard/api/v2/checkout/wirecard.lightbox.js',
            'boletos' => 'https://ws.wirecard.uol.com.br/recurring-payment/boletos',
            'redirect' => 'https://' . $env . 'wirecard.uol.com.br/v2/checkout/payment.html?code=',
        ];
    }

    /**
     * @return mixed
     */
    public function getJavascriptUrl()
    {
        return core()->getConfigData(self::CONFIG_TYPE) == 'lightbox' ? $this->urls['lightbox'] : $this->urls['javascript'];
    }

    /**
     * @param $notificationCode
     * @param string $notificationType
     * @return \SimpleXMLElement
     * @throws Exception
     */
    public function notification($notificationCode, $notificationType = 'transaction')
    {
        if ($notificationType == 'transaction') {
            return $this->sendTransaction([
                'email' => $this->email,
                'token' => $this->token,
            ], $this->urls['notifications'] . $notificationCode, false);
        } elseif ($notificationType == 'preApproval') {
            return $this->sendTransaction([
                'email' => $this->email,
                'token' => $this->token,
            ], $this->urls['preApprovalNotifications'] . $notificationCode, false);
        }
    }

    /**
     * @param $reference
     * @return \SimpleXMLElement
     * @throws Exception
     */
    public function transaction($reference)
    {
        return $this->sendTransaction([
            'reference' => $reference,
            'email' => $this->email,
            'token' => $this->token,
        ], $this->urls['transactions'], false);
    }

    /**
     * @param array $parameters
     * @param null $url
     * @param bool $post
     * @param array $headers
     * @return \SimpleXMLElement
     * @throws Exception
     */
    protected function sendTransaction(
        array $parameters,
        $url = null,
        $post = true,
        array $headers = ['Content-Type: application/x-www-form-urlencoded; charset=ISO-8859-1']
    )
    {
        if ($url === null) {
            $url = $this->url['transactions'];
        }

        $parameters = Helper::array_filter_recursive($parameters);

        $data = '';
        foreach ($parameters as $key => $value) {
            $data .= $key . '=' . $value . '&';
        }
        $parameters = rtrim($data, '&');

        $method = 'POST';

        if (!$post) {
            $url .= '?' . $parameters;
            $parameters = null;
            $method = 'GET';
        }

        $result = $this->executeCurl($parameters, $url, $headers, $method);

        return $this->formatResult($result);
    }

    /**
     * @param $result
     * @return \SimpleXMLElement
     * @throws Exception
     */
    private function formatResult($result)
    {
        if ($result === 'Unauthorized' || $result === 'Forbidden') {
            Log::error('Erro ao enviar a transação', ['Retorno:' => $result]);

            throw new Exception($result . ': Não foi possível estabelecer uma conexão com o Wirecard.', 1001);
        }
        if ($result === 'Not Found') {
            Log::error('Notificação/Transação não encontrada', ['Retorno:' => $result]);

            throw new Exception($result . ': Não foi possível encontrar a notificação/transação no Wirecard.', 1002);
        }

        try {
            $encoder = new XmlEncoder();
            $result = $encoder->decode($result, 'xml');
            $result = json_decode(json_encode($result), FALSE);
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
        }

        if (isset($result->error) && isset($result->error->message)) {
            Log::error($result->error->message, ['Retorno:' => $result]);

            throw new Exception($result->error->message, (int)$result->error->code);
        }

        return $result;
    }

    /**
     * @param $parameters
     * @param $url
     * @param array $headers
     * @param $method
     * @return bool|string
     * @throws Exception
     */
    private function executeCurl($parameters, $url, array $headers, $method)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        if ($method == 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
        } elseif ($method == 'PUT') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        }

        if ($parameters !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, !$this->sandbox);

        $result = curl_exec($curl);

        $getInfo = curl_getinfo($curl);
        if (isset($getInfo['http_code']) && $getInfo['http_code'] == '503') {
            Log::error('Serviço em manutenção.', ['Retorno:' => $result]);

            throw new Exception('Serviço em manutenção.', 1000);
        }
        if ($result === false) {
            Log::error('Erro ao enviar a transação', ['Retorno:' => $result]);

            throw new Exception(curl_error($curl), curl_errno($curl));
        }

        curl_close($curl);

        return $result;
    }
}