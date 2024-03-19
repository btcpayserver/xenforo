<?php

namespace BS\BtcPayProvider\Payment\Concerns;

use BTCPayServer\Client\Invoice;
use XF\Payment\CallbackState;

use function BS\BtcPayProvider\Helpers\data_get;

trait Webhook
{
    protected function getWebhookPaymentResult(CallbackState $state): ?int
    {
        $payload = $state->payload ?? [];

        switch ($state->hookType) {
            case 'InvoiceSettled':
                if ($payload['overPaid'] ?? false) {
                    $state->logType = 'info';
                    $state->logMessage = 'Invoice payment settled but was overpaid.';
                }

                return CallbackState::PAYMENT_RECEIVED;

            case 'InvoicePaymentSettled':
                if (! data_get($state->purchaseRequest->extra_data, 'invoiceExpired')) {
                    $state->logType = 'info';
                    $state->logMessage = 'Invoice (partial) payment settled.';
                    return null;
                }

                // here invoice expired
                $state->logType = 'info';
                if ($this->invoiceIsFullyPaid($state->paymentProfile->options, $state->transactionId)) {
                    $state->logMessage = 'Invoice fully settled after invoice was already expired. Needs manual checking.';
                } else {
                    $state->logMessage = '(Partial) payment settled but invoice not settled yet (could be more transactions incoming). Needs manual checking.';
                }
                return null;

            case 'InvoiceReceivedPayment':
                $state->logType = 'info';
                if (data_get($payload, 'afterExpiration')) {
                    $state->logMessage = 'Invoice (partial) payment incoming (unconfirmed) after invoice was already expired.';
                } else {
                    $state->logMessage = 'Invoice (partial) payment incoming (unconfirmed). Waiting for settlement.';
                }
                return null;

            case 'InvoiceProcessing':
                $state->logType = 'info';
                if (data_get($payload, 'overPaid')) {
                    $state->logMessage = 'Invoice payment received fully with overpayment, waiting for settlement.';
                } else {
                    $state->logMessage = 'Invoice payment received fully, waiting for settlement.';
                }
                return null;

            case 'InvoiceExpired':
                $state->logType = 'info';
                if (data_get($payload, 'partiallyPaid')) {
                    $state->logMessage = 'Invoice expired but was paid partially, please check.';
                } else {
                    $state->logMessage = 'Invoice expired. No action to take.';
                }
                $this->updateStatePurchaseRequestExtraData($state, 'invoiceExpired', true);
                return null;

            case 'InvoiceInvalid':
                $state->logType = 'info';
                if (data_get($payload, 'manuallyMarked')) {
                    $state->logMessage = 'Invoice manually marked invalid.';
                } else {
                    $state->logMessage = 'Invoice became invalid.';
                }
                return null;

            default:
                return null;
        }
    }

    protected function updateStatePurchaseRequestExtraData(
        CallbackState $state,
        array|string $key,
        mixed $value = null
    ): void {
        $request = $state->purchaseRequest;
        $extraData = $request->extra_data;

        if (is_array($key)) {
            $extraData = array_merge($extraData, $key);
        } else {
            $extraData[$key] = $value;
        }

        $request->extra_data = $extraData;
        $request->save();
    }

    protected function invoiceIsFullyPaid(array $options, string $invoiceId): bool
    {
        $client = new Invoice($options['host'], $options['api_key']);

        try {
            return $client->getInvoice($options['store_id'], $invoiceId)
                ->isSettled();
        } catch (\Throwable $e) {
            \XF::logException($e, false, 'BTCPay Server invoice retrieval failed: ');
        }

        return false;
    }
}
