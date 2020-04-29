<?php
/**
 * Copyright (c) 2020. Rone Clay Brasil. All rights reserved.
 * @author    Rone Clay Brasil <roneclay@gmail.com>
 */

namespace Fineweb\Wirecard\Payment;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Webkul\Payment\Payment\Payment;
use Moip\Moip;
use Fineweb\Wirecard\src\Models\MoipPayment;
use Moip\Auth\BasicAuth;
use Moip\Resource\Account;
use Fineweb\Wirecard\Helper\Helper;
use function core;

/**
 * Class Wirecard
 * @package Fineweb\Wirecard\Payment
 */
class Wirecard extends Payment
{
    public const CONFIG_ACCESS_KEY = 'sales.paymentmethods.wirecardconfig.access_key';
    public const CONFIG_TOKEN = 'sales.paymentmethods.wirecardconfig.token';
    public const CONFIG_SANDBOX = 'sales.paymentmethods.wirecardconfig.sandbox';

    /**
     * @var string
     */
    protected $code = 'wirecardboleto';
    /**
     * @var
     */
    protected $payment;
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
     * @var
     */
    protected $currentUser;
    /**
     * @var Helper
     */
    protected $helper;

    /**
     * Wirecard constructor.
     */
    public function __construct(
        Helper $helper
    )
    {
        $this->accessKey = core()->getConfigData(self::CONFIG_ACCESS_KEY);
        $this->token = core()->getConfigData(self::CONFIG_TOKEN);
        $this->currentUser = auth()->guard('customer')->user();
        $this->helper = $helper;

        if (core()->getConfigData(self::CONFIG_SANDBOX)) {
            $this->sandbox = true;
            $this->environment = 'sandbox';
        }
    }

    /**
     * @param $dob
     * @return |null
     */
    public function validateCustomerDob($dob)
    {
        return strtotime(Carbon::parse($dob)) < 0 ? null : $dob;
    }

    /**
     * @throws Exception
     */
    public function init(): void
    {
        if (!$this->accessKey || !$this->token) {
            throw new RuntimeException('Wirecard: To use this payment method you need to inform the token and acess key account of Wirecard account.');
        }

        if (!$this->validateCustomerDob($this->currentUser->date_of_birth)) {
            throw new RuntimeException('Wirecard: Please update your account with Date of birth information.');
        }
        if (!$this->validateCustomerDob($this->currentUser->phone)) {
            throw new RuntimeException('To buy with this payment method you need to register your phone.');
        }

        $cart = $this->getCart();
        $customer = $this->createMoipCustomer($cart);
        $order = $this->createMoipOrder($cart, $customer);

        try {
            $payment = $this->applyPayment( $order );

            $moipPayment = new MoipPayment();
            $moipPayment->increment_id = $this->getIncrementId();
            $moipPayment->moip_order_data = json_encode($order );
            $moipPayment->moip_payment_data = json_encode($payment );
            $moipPayment->save();

        } catch (Exception $e) {
            throw new Exception('Wirecard: ' . $e->getMessage());
        }
    }

    /**
     * @return int|mixed
     */
    public function getIncrementId()
    {
        $increment_id = DB::table('orders')
                ->orderBy('id', 'desc')
                ->select('id')
                ->first()
                ->id ?? 0;

        $increment_id++;

        return $increment_id;;
    }

    /**
     * @param $moip
     * @param $cart
     * @param $customer
     * @return mixed
     */
    public function createMoipOrder($cart, $customer)
    {
        $moip = $this->initWirecardObject();
        $order = $moip->orders();
        $incrementId = $this->getIncrementId() + 1;

        $order
            ->setOwnId(uniqid('', true))
            ->setCustomer($customer)
            ->setShippingAmount($this->helper->formatPrice($cart->grand_total - $cart->discount_amount)) // TODO: Check how to get shipping price
            ->setDiscount($this->helper->formatPrice($cart->discount_amount));

        $items = $this->getCartItems();

        foreach ($items as $cartItem) {
            $price = $this->helper->formatPrice($cartItem->price);

            $order->addItem(
                $cartItem->name,
                $cartItem->quantity,
                $cartItem->product_id,
                $price
            );
        }

        $order->create();

        return $order;
    }

    /**
     * @param $moip
     * @param $cart
     * @return mixed
     */
    public function createMoipCustomer($cart)
    {
        $moip = $this->initWirecardObject();

        try {
            $customer = $moip->customers();
            $document = $this->helper->documentParser($this->currentUser->document);
            $phone = $this->helper->splitPhone($this->currentUser->phone);
            $billingAddress = $cart->getBillingAddressAttribute();
            $shippingAddress = $cart->getShippingAddressAttribute();

            $customer
                ->setOwnId(uniqid('', true))
                ->setEmail($this->currentUser->email)
                ->setBirthDate($this->currentUser->date_of_birth)
                ->setPhone($phone[0], $phone[1])
                ->addAddress(
                    'BILLING',
                    $billingAddress->address1,
                    $billingAddress->address2,
                    $billingAddress->address3 ?: '',
                    $billingAddress->city,
                    $billingAddress->state,
                    $billingAddress->postcode,
                    $billingAddress->address4 ?: ''
                )
                ->addAddress(
                    'SHIPPING',
                    $shippingAddress->address1,
                    $shippingAddress->address2,
                    $shippingAddress->address3 ?: '',
                    $shippingAddress->city,
                    $shippingAddress->state,
                    $shippingAddress->postcode,
                    $shippingAddress->address4 ?: ''
                );

            if ($this->currentUser->person_tye === 'company') {
                $customer
                    ->setTaxDocument($document,Account::COMPANY_TAX_DOCUMENT)
                    ->setFullname($this->currentUser->fantasy_name);
            } else {
                $customer
                    ->setTaxDocument($document)
                    ->setFullname($cart->customer_first_name . ' ' . $cart->customer_last_name);
            }

            $customer->create();

            return $customer;
        } catch (Exception $e) {
            throw new RuntimeException('Wirecard: ' . $e->getMessage());
        }
    }

    /**
     * @param $order
     * @return mixed
     */
    public function applyPayment ($order )
    {
        return $this->code === 'wirecardboleto' ? $this->applyBoletoPayment( $order ) : $this->applyCcPayment( $order ) ;
    }

    /**
     * @param $order
     * @return mixed
     */
    public function applyBoletoPayment($order)
    {
        $expiration_date = new \DateTime();
        $payment = $order->payments()
            ->setBoleto($expiration_date, '', [])
            ->execute();

        return $payment;
    }

    /**
     * @return Moip
     */
    public function initWirecardObject()
    {
        return new Moip( new BasicAuth($this->token, $this->accessKey), $this->getWirecardEndpoint() );
    }

    /**
     * @return string
     */
    public function getWirecardEndpoint()
    {
        return $this->environment === 'production' ? Moip::ENDPOINT_PRODUCTION  : Moip::ENDPOINT_SANDBOX ;
    }

    /**
     * @throws Exception
     */
    public function getRedirectUrl()
    {
           $this->init();
    }
}