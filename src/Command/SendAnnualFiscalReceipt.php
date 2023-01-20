<?php
namespace D4rk0snet\FiscalReceipt\Command;

use Hyperion\Stripe\Service\StripeService;

class SendAnnualFiscalReceipt
{
    public function runCommand()
    {
        // Récupère tous les paiements sur abonnement pour stripe
        $searchResult = StripeService::getStripeClient()
            ->subscriptions
            ->all(
                [
                    'current_period_start' => (new \DateTime('2022-01-01'))->getTimestamp(),
                    'current_period_end' => (new \DateTime('2022-12-31'))->getTimestamp(),
                    'collection_method' => 'charge_automatically',
                    'expand' => ['customer']
                ]
            );

        var_dump($searchResult); die;

    }
}