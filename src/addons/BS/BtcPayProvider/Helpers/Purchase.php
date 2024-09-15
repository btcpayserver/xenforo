<?php

namespace BS\BtcPayProvider\Helpers;

class Purchase
{
    public static function purchaseToArray(\XF\Purchasable\Purchase $purchase): array
    {
        return [
            'title' => $purchase->title,
            'description' => $purchase->description,
            'cost' => $purchase->cost,
            'currency' => $purchase->currency,
            'recurring' => $purchase->recurring,
            'lengthAmount' => $purchase->lengthAmount,
            'lengthUnit' => $purchase->lengthUnit,
            'purchaser' => $purchase->purchaser,
            'paymentProfile' => $purchase->paymentProfile,
            'purchasableTypeId' => $purchase->purchasableTypeId,
            'purchasableId' => $purchase->purchasableId,
            'purchasableTitle' => $purchase->purchasableTitle,
            'extraData' => $purchase->extraData,
            'cancelUrl' => $purchase->cancelUrl,
            'returnUrl' => $purchase->returnUrl,
            'updateUrl' => $purchase->updateUrl,
            'requestKey' => $purchase->requestKey,
        ];
    }
}
