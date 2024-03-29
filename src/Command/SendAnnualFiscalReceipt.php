<?php
namespace D4rk0snet\FiscalReceipt\Command;

use D4rk0snet\CoralCustomer\Entity\CompanyCustomerEntity;
use D4rk0snet\CoralCustomer\Entity\CustomerEntity;
use D4rk0snet\CoralCustomer\Enum\CustomerType;
use D4rk0snet\Coralguardian\Enums\Language;
use D4rk0snet\Coralguardian\Event\SubscriptionSummary;
use D4rk0snet\CoralOrder\Enums\PaymentMethod;
use D4rk0snet\Donation\Entity\DonationEntity;
use D4rk0snet\Donation\Entity\RecurringDonationEntity;
use D4rk0snet\FiscalReceipt\Model\FiscalReceiptModel;
use D4rk0snet\FiscalReceipt\Plugin;
use D4rk0snet\FiscalReceipt\Service\FiscalReceiptService;
use Hyperion\Doctrine\Service\DoctrineService;
use Hyperion\Stripe\Service\StripeService;
use NumberFormatter;
use stdClass;
use Stripe\Invoice;

class SendAnnualFiscalReceipt
{
    public function runCommand()
    {
        // Récupère tous les paiements sur abonnement pour stripe
        $invoices = [];
        try {
            $next_page = null;
            do {
                $query = [
                    'query' => 'created>' . (new \DateTime('01/01/2022'))->getTimestamp().' AND created<'.(new \DateTime('01/01/2023'))->getTimestamp(),
                    'limit' => 100,
                    'expand' => ['data.customer']
                ];
                if($next_page !== null) {
                    $query['page'] = $next_page;
                }
                $searchResult = StripeService::getStripeClient()
                    ->invoices->search($query);

                /** @var Invoice[] $invoicesFiltered */
                $invoicesFiltered = array_filter($searchResult->data, function(Invoice $invoice) {
                    return in_array($invoice->billing_reason, ['subscription_cycle', 'subscription_create']);
                });

                foreach ($invoicesFiltered as $invoice) {
                    preg_match_all('/(.*?)(\d{5})(.*)/ms',$invoice->customer_address, $info);
                    if($invoice->customer->address === null) {
                        /** @var CustomerEntity $customer */
                        $customer = DoctrineService::getEntityManager()->getRepository(CustomerEntity::class)->findOneBy(['email' => $invoice->customer_email]);
                        if($customer === null) {
                            var_dump("/!\ Impossible de trouver l'utilisateur : ".$invoice->customer_email);
                            continue;
                        }
                        /** @var DonationEntity[] $donations */
                        $donations = DoctrineService::getEntityManager()->getRepository(RecurringDonationEntity::class)->findBy([
                            'customer' => $customer->getUuid(),
                        ]);

                        if(count($donations) === 0) {
                            var_dump("/!\ Impossible de trouver un don pour : ".$invoice->customer_email);
                            continue;
                        }

                        if(strtolower(trim($donations[0]->getCountry())) !== "france") {
                            var_dump("Reçu non envoyé car $invoice->customer_email n'est pas en France");
                            continue;
                        }

                        if(!array_key_exists($invoice->customer_email, $invoices)) {
                            $invoices[$invoice->customer_email] = (object) [
                                'amount' => 0,
                                'fullName' => trim($donations[0]->getFirstName()." ".$donations[0]->getLastName()),
                                'address' => trim($donations[0]->getAddress()),
                                'postCode' => $donations[0]->getPostalCode(),
                                'city' => trim($donations[0]->getCity())
                            ];
                        }

                        $invoices[$invoice->customer_email]->amount += $invoice->amount_paid / 100;
                    } else {
                        if(strtolower(trim($invoice->customer->address->country)) !== "france") {
                            var_dump("Reçu non envoyé car $invoice->customer_email n'est pas en France");
                            continue;
                        }
                        if(!array_key_exists($invoice->customer_email, $invoices)) {
                            $invoices[$invoice->customer_email] = (object) [
                                'amount' => 0,
                                'fullName' => trim($invoice->customer->name),
                                'address' => trim($invoice->customer->address->line1."\n".$invoice->customer->address->line2),
                                'postCode' => current($info[2]),
                                'city' => trim($invoice->customer->address->city)
                            ];
                        }
                        $invoices[$invoice->customer_email]->amount += $invoice->amount_paid / 100;
                    }

                }

                $next_page = $searchResult->next_page;
            } while($searchResult->has_more);

            $nf2 = new NumberFormatter(Language::FR->value, NumberFormatter::SPELLOUT);

            // Récupération des customers
            foreach($invoices as $email => $info) {
                $customer = DoctrineService::getEntityManager()->getRepository(CustomerEntity::class)->findOneBy(['email' => $email]);
                if (null === $customer) {
                    var_dump('/!\ Customer inconnu : ' . $email);
                    continue;
                }

                if($info->amount === 0) {
                    var_dump("Reçu non envoyé car $email à un montant à 0");
                    continue;
                }

                if ($customer instanceof CompanyCustomerEntity) {
                    /** @var CompanyCustomerEntity $customer */
                    $fiscalReceiptModel = new FiscalReceiptModel(
                        articles: '200, 238 bis et 978',
                        receiptCode: $this->createReceiptCode(),
                        customerFullName: $info->fullName,
                        customerAddress: $info->address,
                        customerPostalCode: $info->postCode,
                        customerCity: $info->city,
                        fiscalReductionPercentage: CustomerType::COMPANY->getFiscalReduction(),
                        paymentMethod: PaymentMethod::CREDIT_CARD->getMethodName(),
                        priceWord: $nf2->format($info->amount),
                        price: $info->amount,
                        date: (new \DateTime())->setDate(date("Y"), 1, 1),
                        orderUuid: null
                    );
                } else {
                    $fiscalReceiptModel = new FiscalReceiptModel(
                        articles: '200, 238 bis et 978',
                        receiptCode: $this->createReceiptCode(),
                        customerFullName: $info->fullName,
                        customerAddress: $info->address,
                        customerPostalCode: $info->postCode,
                        customerCity: $info->city,
                        fiscalReductionPercentage: CustomerType::INDIVIDUAL->getFiscalReduction(),
                        paymentMethod: PaymentMethod::CREDIT_CARD->getMethodName(),
                        priceWord: $nf2->format($info->amount),
                        price: $info->amount,
                        date: (new \DateTime())->setDate(date("Y"), 1, 1),
                        orderUuid: null
                    );
                }

                $fileURL = FiscalReceiptService::createReceipt($fiscalReceiptModel, null);
                SubscriptionSummary::sendEvent(email: $email, fiscalReceiptUrl: $fileURL);
                var_dump("Certificat envoyé pour $email.");
            }
        } catch (\Exception $exception) {
            var_dump($exception->getMessage());
            die('ok');
        }
    }

    private function createReceiptCode(int $nextReceiptNumber = null) : string
    {
        return "CG2-" . (new \DateTime())->format("Y") . "-" . str_pad($nextReceiptNumber ?? (int) get_option(Plugin::NEXT_RECEIPT_NUM), 10, "0", STR_PAD_LEFT);
    }
}