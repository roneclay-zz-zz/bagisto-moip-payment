<?php

namespace Fineweb\Wirecard\Http\Controllers;

use Fineweb\Wirecard\Helper\Helper;
use Fineweb\Wirecard\Payment\Wirecard;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use InvalidArgumentException;
use Webkul\Checkout\Facades\Cart;
use Webkul\Sales\Repositories\OrderRepository;

/**
 * Class WirecardController
 * @package Fineweb\Wirecard\Http\Controllers
 */
class WirecardController extends Controller
{
    /**
     * OrderRepository object
     *
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * Create a new controller instance.
     *
     * @param OrderRepository $orderRepository
     * @param Helper $helper
     */
    public function __construct(
        OrderRepository $orderRepository,
        Helper $helper
    )
    {
        $this->orderRepository = $orderRepository;
        $this->helper = $helper;
    }

    /**
     * @return Factory|View
     */
    public function redirect()
    {
        return view('wirecard::redirect');
    }

    /**
     * Cancel payment from wirecard.
     *
     * @return Response
     */
    public function cancel()
    {
        session()->flash('error', 'Você cancelou o pagamento, pedido não finalizado');

        return redirect()->route('shop.checkout.cart.index');
    }

    /**
     * @return RedirectResponse
     * @throws Exception
     */
    public function success(Request $request, Wirecard $wirecard)
    {
        // @todo: Analisar request do retorno do success
        /**
         * @var \Webkul\Sales\Models\Order $order
         */
        $order = $this->orderRepository->create(Cart::prepareDataForOrder());

        try {
            $response = $wirecard->transaction($order->cart_id);
            if (isset($response->transactions)) {
                foreach ($response->transactions as $transaction) {
                    // Update order with transaction info
                    $this->helper->updateOrder($transaction);
                }
            }
        } catch (Exception $exception) {
            Log::error($exception);
        }

        Cart::deActivateCart();

        session()->flash('order', $order);

        return redirect()->route('shop.checkout.success');
    }

    /**
     * @param Request $request
     * @param Wirecard $wirecard
     */
    public function notify(Request $request, Wirecard $wirecard)
    {
        try {
            $response = $wirecard->notification($request->get('notificationCode'), $request->get('notificationType'));
            if ($response) {
                $this->helper->updateOrder($response);
            }
        } catch (Exception $exception) {
            Log::error($exception);
        }
    }
}