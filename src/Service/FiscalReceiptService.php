<?php

namespace D4rk0snet\FiscalReceipt\Service;

use D4rk0snet\Coralguardian\Entity\CompanyCustomerEntity;
use D4rk0snet\Coralguardian\Enums\CustomerType;
use D4rk0snet\Coralguardian\Enums\Language;
use D4rk0snet\Donation\Entity\DonationEntity;
use D4rk0snet\Donation\Entity\RecurringDonationEntity;
use D4rk0snet\FiscalReceipt\Endpoint\GetFiscalReceiptEndpoint;
use D4rk0snet\FiscalReceipt\Model\FiscalReceiptModel;
use D4rk0snet\FiscalReceipt\Plugin;
use Hyperion\Api2pdf\Service\Api2PdfService;
use Hyperion\Doctrine\Service\DoctrineService;
use NumberFormatter;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class FiscalReceiptService
{
    public static function createReceipt(FiscalReceiptModel $fiscalReceiptModel, DonationEntity $order) : string
    {
        // Generate fiscal receipt
        $loader = new FilesystemLoader(__DIR__."/../Template");
        $twig = new Environment($loader); // @todo : Activer le cache

        $html = $twig->load('receipt.twig')->render(
            [
                'data' => $fiscalReceiptModel->toArray(),
                'stampImg' => base64_encode(file_get_contents(__DIR__."/../Template/img/stamp.png")),
                'logoImg' => base64_encode(file_get_contents(__DIR__."/../Template/img/logo.png"))
            ]
        );

        // Incrémentation du code des reçus si on l'a généré
        if($order->getFiscalReceiptNumber() === null) {
            $currentReceiptNum = (int) get_option(Plugin::NEXT_RECEIPT_NUM);
            $order->setFiscalReceiptNumber($currentReceiptNum);
            DoctrineService::getEntityManager()->flush();

            update_option(Plugin::NEXT_RECEIPT_NUM, $currentReceiptNum + 1);
        }

        return Api2PdfService::convertHtmlToPdf(
            $html,
            false,
            "receipt-".$fiscalReceiptModel->getReceiptCode().".pdf"
        );
    }

    public static function getURl(string $uuid) : string
    {
        return GetFiscalReceiptEndpoint::getUrl()."?".GetFiscalReceiptEndpoint::ORDER_UUID_PARAM."=".$uuid;
    }

    public static function generateFiscalReceipts(DonationEntity $donation): string
    {
        $customer = $donation->getCustomer();
        $nf2 = new NumberFormatter(Language::FR->value, NumberFormatter::SPELLOUT);

        if ($donation instanceof RecurringDonationEntity) {
            $startDate = $donation->getDate();
            $startYear = $startDate->format("Y");
            $incrementalYear = $startYear;
            $currentYear = date("Y");

            while ($incrementalYear < $currentYear) {
                if ($startYear === $incrementalYear) {
                    $startMonth = $startDate->format("m");
                    $numberOfMonth = 12 - $startMonth - 1;
                } else {
                    $numberOfMonth = 12;
                }
                $amount = $donation->getAmount() * $numberOfMonth;
                $date = date_create_from_format("Ymd", $incrementalYear . "1231");
            }
        } else {

        }

        if ($customer instanceof CompanyCustomerEntity) {
            /** @var CompanyCustomerEntity $customer */
            $fiscalReceiptModel = new FiscalReceiptModel(
                articles: '200, 238 bis et 885-0VBISA',
                receiptCode: self::createReceiptCode($donation->getFiscalReceiptNumber() ?? null),
                customerFullName: $customer->getCompanyName(),
                customerAddress: $customer->getAddress(),
                customerPostalCode: $customer->getPostalCode(),
                customerCity: $customer->getCity(),
                fiscalReductionPercentage: CustomerType::COMPANY->getFiscalReduction(),
                paymentMethod: $donation->getPaymentMethod()->getMethodName(),
                priceWord: $nf2->format($donation->getAmount()),
                price: $donation->getAmount(),
                date: $donation->getDate(),
                orderUuid: $donation->getUuid()
            );
        } else {
            $fiscalReceiptModel = new FiscalReceiptModel(
                articles: '200, 238 bis et 978',
                receiptCode: self::createReceiptCode($donation->getFiscalReceiptNumber() ?? null),
                customerFullName: $customer->getFirstname() . " " . $customer->getLastname(),
                customerAddress: $customer->getAddress(),
                customerPostalCode: $customer->getPostalCode(),
                customerCity: $customer->getCity(),
                fiscalReductionPercentage: CustomerType::INDIVIDUAL->getFiscalReduction(),
                paymentMethod: $donation->getPaymentMethod()->getMethodName(),
                priceWord: $nf2->format($donation->getAmount()),
                price: $donation->getAmount(),
                date: $donation->getDate(),
                orderUuid: $donation->getUuid()
            );
        }
        // @todo: prévoir un zip avec tous les reçus fiscaux en cas de don récurrent
        return FiscalReceiptService::createReceipt($fiscalReceiptModel, $donation);
    }

    private static function createReceiptCode(int $nextReceiptNumber = null) : string
    {
        return "CG2-" . (new \DateTime())->format("Y") . "-" . str_pad($nextReceiptNumber ??
                (int) get_option(Plugin::NEXT_RECEIPT_NUM), 10, "0", STR_PAD_LEFT);
    }
}