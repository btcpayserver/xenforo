<?php

namespace BS\BtcPayProvider\Payment;

use BS\BtcPayProvider\Helpers\Purchase as PurchaseHelper;
use BTCPayServer\Client\Invoice;
use BTCPayServer\Client\InvoiceCheckoutOptions;
use BTCPayServer\Client\Store;
use BTCPayServer\Client\Webhook;
use BTCPayServer\Util\PreciseNumber;
use XF\Entity\PaymentProfile;
use XF\Entity\PurchaseRequest;
use XF\Mvc\Controller;
use XF\Payment\AbstractProvider;
use XF\Payment\CallbackState;
use XF\Purchasable\Purchase;
use BTCPayServer\Result\Invoice as InvoiceResult;

class BTCPayServer extends AbstractProvider
{
    use Concerns\Webhook;

    public function getTitle()
    {
        return 'BTCPay Server';
    }

    public function initiatePayment(
        Controller $controller,
        PurchaseRequest $purchaseRequest,
        Purchase $purchase
    ) {
        $invoice = $this->createInvoice($purchaseRequest, $purchase, $error);

        if (! $invoice || $error) {
            throw $controller->exception($controller->error($error));
        }

        $purchaseRequest->fastUpdate('provider_metadata', $invoice->getId());

        $scriptUrl = $purchaseRequest->PaymentProfile->options['host'] . '/modal/btcpay.js';

        if ($purchaseRequest->PaymentProfile->options['invoice_alert'] ?? false) {
            $this->sendAlertWithInvoice($purchase, $invoice, $scriptUrl);
        }

        return $controller->view(
            'BS\BtcPayServer:Initiate\BTCPayServer',
            'btcpay_show_invoice',
            compact('purchaseRequest', 'invoice', 'scriptUrl')
        );
    }

    public function processPayment(
        Controller $controller,
        PurchaseRequest $purchaseRequest,
        PaymentProfile $paymentProfile,
        Purchase $purchase
    ) {
        $invoice = $this->getInvoice($purchaseRequest->provider_metadata, $paymentProfile);

        if (! $invoice) {
            throw $controller->exception($controller->noPermission());
        }

        $scriptUrl = $purchaseRequest->PaymentProfile->options['host'] . '/modal/btcpay.js';

        return $controller->view(
            'BS\BtcPayServer:Initiate\BTCPayServer',
            'btcpay_show_invoice',
            compact('purchaseRequest', 'invoice', 'scriptUrl')
        );
    }

    protected function sendAlertWithInvoice(
        Purchase $purchase,
        InvoiceResult $invoice,
        $scriptUrl
    ): void {
        $visitor = \XF::visitor();

        /** @var \XF\Repository\UserAlert|\XF\Repository\UserAlertRepository $alertRepo */
        $alertRepo = \XF::repository('XF:UserAlert');
        $alertRepo->alert(
            \XF::visitor(),
            0,
            '',
            'user',
            $visitor->user_id,
            'btcpayprovider_invoice_created',
            [
                'purchase' => PurchaseHelper::purchaseToArray($purchase),
                'invoice' => $invoice->getData(),
                'scriptUrl' => $scriptUrl,
            ]
        );
    }

    protected function createInvoice(
        PurchaseRequest $purchaseRequest,
        Purchase $purchase,
        &$error
    ) {
        $options = $purchaseRequest->PaymentProfile->options;

        $invoiceClient = new Invoice($options['host'], $options['api_key']);

        $checkoutOptions = new InvoiceCheckoutOptions();
        $checkoutOptions->setRedirectURL($purchase->returnUrl);
        $checkoutOptions->setRedirectAutomatically(true);

        try {

            return $invoiceClient->createInvoice(
                $options['store_id'],
                $purchaseRequest->cost_currency,
                PreciseNumber::parseFloat($purchaseRequest->cost_amount),
                null,
                null,
                [
                    'request_key' => $purchaseRequest->request_key,
                    'purchase_request_id' => $purchaseRequest->purchase_request_id,
                ],
                $checkoutOptions
            );
        } catch (\Exception $e) {
            \XF::logException($e, false, 'BTCPay Server invoice creation failed: ');
            $error = \XF::phrase('btcpayprovider_invoice_creation_failed');
            return null;
        }
    }

    protected function getInvoice(string $invoiceId, PaymentProfile $paymentProfile): ?InvoiceResult
    {
        $invoiceClient = new Invoice($paymentProfile->options['host'], $paymentProfile->options['api_key']);
        try {
            return $invoiceClient->getInvoice($paymentProfile->options['store_id'], $invoiceId);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @inheritDoc
     */
    public function setupCallback(\XF\Http\Request $request)
    {
        $state = new CallbackState();

        $state->signature = $request->getServer('HTTP_BTCPAY_SIG');
        $state->inputRaw = $request->getInputRaw();

        $payload = @json_decode($state->inputRaw, true);

        $metadata = $payload['metadata'] ?? [];

        $state->requestKey = $metadata['request_key'] ?? '';
        $state->transactionId = $payload['invoiceId'] ?? '';
        $state->hookType = $payload['type'] ?? '';
        $state->payload = $payload;

        return $state;
    }

    public function validateCallback(CallbackState $state)
    {
        $paymentProfile = $state->getPaymentProfile();

        if (! $paymentProfile) {
            $state->logType = 'error';
            $state->logMessage = 'Invalid payment profile.';
            return false;
        }

        $secret = $paymentProfile->options['secret'];

        if (! Webhook::isIncomingWebhookRequestValid(
            $state->inputRaw,
            $state->signature,
            $secret
        )) {
            $state->logType = 'error';
            $state->logMessage = 'Invalid signature.';
            return false;
        }

        return true;
    }

    public function validateTransaction(CallbackState $state)
    {
        if (! $state->requestKey) {
            $state->logType = 'info';
            $state->logMessage = 'No purchase request key. Unrelated payment, no action to take.';
            return false;
        }

        if (! $state->getPurchaseRequest()) {
            $state->logType = 'info';
            $state->logMessage = 'Invalid request key. Unrelated payment, no action to take.';
            return false;
        }

        if (! $state->transactionId) {
            $state->logType = 'info';
            $state->logMessage = 'No invoice ID. No action to take.';
            return false;
        }

        /** @var \XF\Repository\Payment $paymentRepo */
        $paymentRepo = \XF::repository('XF:Payment');
        $matchingLogsFinder = $paymentRepo->findLogsByTransactionIdForProvider(
            $state->transactionId,
            $this->providerId
        )->where('log_type', '=', 'payment');
        if ($matchingLogsFinder->total()) {
            $state->logType = 'info';
            $state->logMessage = 'Transaction already processed. Skipping.';
            return false;
        }

        return true;
    }

    public function getPaymentResult(CallbackState $state)
    {
        $result = $this->getWebhookPaymentResult($state);
        if (! $result) {
            return null;
        }

        $state->paymentResult = $result;
        return $result;
    }

    public function prepareLogData(CallbackState $state)
    {
        $state->logDetails = [
            'signature' => $state->signature,
            'type'      => $state->hookType,
            'body'      => $state->inputRaw
        ];
    }

    public function verifyConfig(array &$options, &$errors = [])
    {
        $host = $options['host'] ?? '';
        $apiKey = $options['api_key'] ?? '';
        $storeId = $options['store_id'] ?? '';
        $secret = $options['secret'] ?? '';
        $invoiceAlert = (bool) ($options['invoice_alert'] ?? false);

        $host = $options['host'] = rtrim($host, '/');

        if (! $host) {
            $errors[] = \XF::phrase('btcpayprovider_host_required');
        }

        if (! $apiKey) {
            $errors[] = \XF::phrase('btcpayprovider_api_key_required');
        }

        if (! $storeId) {
            $errors[] = \XF::phrase('btcpayprovider_store_id_required');
        }

        if (! $secret) {
            $errors[] = \XF::phrase('btcpayprovider_secret_required');
        }

        $storeClient = new Store($options['host'], $options['api_key']);

        try {
            $storeClient->getStore($options['store_id']);
        } catch (\Exception $e) {
            \XF::logException($e, false, 'BTCPay Server store validation failed');

            $errors[] = \XF::phrase('btcpayprovider_cannot_connect_to_store');
        }
    }

    public function supportsRecurring(
        PaymentProfile $paymentProfile,
        $unit,
        $amount,
        &$result = self::ERR_NO_RECURRING
    ) {
        $result = self::ERR_NO_RECURRING;

        return false;
    }
}
