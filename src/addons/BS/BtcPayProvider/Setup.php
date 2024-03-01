<?php

namespace BS\BtcPayProvider;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
	use StepRunnerInstallTrait;
	use StepRunnerUpgradeTrait;
	use StepRunnerUninstallTrait;

    public function installStep1()
    {
        $db = $this->db();

        foreach ($this->getPaymentProviders() AS $providerId => $providerClass) {
            $db->insert('xf_payment_provider', [
                'provider_id'    => $providerId,
                'provider_class' => $providerClass,
                'addon_id'       => 'BS/BtcPayProvider'
            ]);
        }
    }

    public function uninstallStep1()
    {
        $providerIds = array_keys($this->getPaymentProviders());

        $this->db()->delete('xf_payment_provider', 'provider_id IN (?)', $providerIds);
    }

    protected function getPaymentProviders()
    {
        return [
            'btcPayServer' => 'BS\BtcPayProvider:BTCPayServer'
        ];
    }
}
