<?php

/**
 * PaypalWebCheckout for Symfony2
 *
 * This Bundle is part of Symfony2 Payment Suite
 *
 * @author Arkaitz Garro <hola@arkaitzgarro.com>
 *
 * Arkaitz Garro 2014
 */

namespace PaymentSuite\PaypalWebCheckoutBundle\Services\Wrapper;

use Symfony\Component\Form\FormFactory;
use Symfony\Component\Routing\RouterInterface;

use PaymentSuite\PaymentCoreBundle\Services\interfaces\PaymentBridgeInterface;
use PaymentSuite\PaypalWebCheckoutBundle\Exception\CurrencyNotSupportedException;

class PaypalFormTypeWrapper
{
    /**
     * @var FormFactory
     *
     * Form factory
     */
    protected $formFactory;

    /**
     * @var PaymentBridge
     *
     * Payment bridge
     */
    protected $paymentBridge;

    /**
     * @var Router
     *
     * Router
     */
    protected $router;

    /**
     * @var string $business
     *
     * Merchant identifier
     */
    protected $business;

    /**
     * @var string $paypalUrl
     *
     * Paypal web url
     */
    protected $paypalUrl;

    /**
     * @var string $returnUrl
     *
     * Route for success payment
     */
    protected $returnUrl;

    /**
     * @var string $cancelReturnUrl
     *
     * Route for fail payment
     */
    protected $cancelReturnUrl;

    /**
     * @var string $notifyUrl
     *
     * Route for process payment
     */
    protected $notifyUrl;

    /**
     * @var boolean $debug
     *
     * Debug enviroment
     */
    protected $debug;

    /**
     * @var string $env
     *
     * Environment
     */
    protected $env;

    /**
     * @var string $locale
     *
     * Locale
     */
    protected $locale;

    /**
     * Formtype construct method
     *
     * @param FormFactory            $formFactory             Form factory
     * @param PaymentBridgeInterface $paymentBridge           Payment bridge
     * @param RouterInterface        $router                  Routing service
     * @param string                 $bussines                merchant code
     * @param string                 $paypalUrl               gateway url
     * @param string                 $returnRouteName         merchant route ok
     * @param string                 $cancelReturnRouteName   merchant route ko
     * @param string                 $notifyRouteName         merchant payment proccess route
     * @param boolean                $debug                   debug mode
     */
    public function __construct(
        FormFactory $formFactory,
        PaymentBridgeInterface $paymentBridge,
        RouterInterface $router,
        $business,
        $paypalUrl,
        $returnRouteName,
        $cancelReturnRouteName,
        $notifyRouteName,
        $debug
    ) {
        $this->formFactory           = $formFactory;
        $this->paymentBridge         = $paymentBridge;
        $this->router                = $router;
        $this->business              = $business;
        $this->paypalUrl             = $paypalUrl;
        $this->returnRouteName       = $returnRouteName;
        $this->cancelReturnRouteName = $cancelReturnRouteName;
        $this->notifyRouteName       = $notifyRouteName;
        $this->debug                 = $debug;
        $this->env                   = 'www.sandbox';
    }

    /**
     * Set locale
     * @param string $locale Locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Builds form given return, success and fail urls
     *
     * @return \Symfony\Component\Form\FormView
     */
    public function buildForm()
    {
        $extraData = $this->paymentBridge->getExtraData();
        $formBuilder = $this
            ->formFactory
            ->createNamedBuilder(null);

        $itemNumber = $this->paymentBridge->getOrderId();
        $amount = $this->paymentBridge->getAmount()->getAmount()/100;
        $currency = $this->checkCurrency($this->paymentBridge->getCurrency());

        /**
         * Create routes
         */
        $returnUrl = $this->router->generate(
            $this->returnRouteName.'.'.$this->locale,
            [],
            true
        );

        $cancelReturnUrl = $this->router->generate(
            $this->cancelReturnRouteName.'.'.$this->locale,
            [],
            true
        );

        $notifyUrl = $this->router->generate(
            $this->notifyRouteName.'.'.$this->locale,
            [ 'order_id' => $this->paymentBridge->getOrderId() ],
            true
        );

        if (!$this->debug) {
            $this->paypalUrl = str_replace('.sandbox', '', $this->paypalUrl);
            $this->env       = str_replace('.sandbox', '', $this->env);
        }

        $formBuilder
            ->setAction($this->paypalUrl)
            ->setMethod('POST')

            ->add('amount', 'hidden', array(
                'data' => $amount,
            ))
            ->add('business', 'hidden', array(
                'data' => $this->business,
            ))
            ->add('return', 'hidden', array(
                'data' => $returnUrl,
            ))
            ->add('cancel_return', 'hidden', array(
                'data' => $cancelReturnUrl,
            ))
            ->add('notify_url', 'hidden', array(
                'data' => $notifyUrl,
            ))
            ->add('item_number', 'hidden', array(
                'data' => $itemNumber,
            ))
            ->add('currency_code', 'hidden', array(
                'data' => $currency,
            ))
            ->add('env', 'hidden', array(
                'data' => $this->env,
            ))
        ;

        return $formBuilder->getForm()->createView();
    }

    public function checkCurrency($currency)
    {
        $allowedCurrencies = [
            'AUD',
            'BRL',
            'CAD',
            'CZK',
            'DKK',
            'EUR',
            'HKD',
            'HUF',
            'ILS',
            'JPY',
            'MYR',
            'MXN',
            'NOK',
            'NZD',
            'PHP',
            'PLN',
            'GBP',
            'RUB',
            'SGD',
            'SEK',
            'CHF',
            'TWD',
            'THB',
            'TRY',
            'USD'
        ];

        if (!in_array($currency, $allowedCurrencies)) {
            throw new CurrencyNotSupportedException();
        }

        return $currency;
    }
}
