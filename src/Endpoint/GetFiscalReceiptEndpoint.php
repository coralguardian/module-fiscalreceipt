<?php

namespace D4rk0snet\FiscalReceipt\Endpoint;

use D4rk0snet\Coralguardian\Entity\CompanyCustomerEntity;
use D4rk0snet\Coralguardian\Enums\CustomerType;
use D4rk0snet\Coralguardian\Enums\Language;
use D4rk0snet\Donation\Entity\DonationEntity;
use D4rk0snet\FiscalReceipt\Plugin;
use D4rk0snet\FiscalReceipt\Service\FiscalReceiptService;
use D4rk0snet\FiscalReceipt\Model\FiscalReceiptModel;
use Hyperion\Doctrine\Service\DoctrineService;
use Hyperion\RestAPI\APIEnpointAbstract;
use Hyperion\RestAPI\APIManagement;
use NumberFormatter;
use WP_REST_Request;
use WP_REST_Response;

class GetFiscalReceiptEndpoint extends APIEnpointAbstract
{
    public const ORDER_UUID_PARAM = 'order_uuid';

    public static function callback(WP_REST_Request $request): WP_REST_Response
    {
        $orderUUID = $request->get_param(self::ORDER_UUID_PARAM);
        if ($orderUUID === null) {
            return APIManagement::APIError('Missing order uuid', 400);
        }

        try {
            /** @var DonationEntity $order */
            $order = DoctrineService::getEntityManager()->getRepository(DonationEntity::class)->find($orderUUID);
            if ($order === null) {
                return APIManagement::APIError('Order not found', 404);
            }

            if(!$order->isPaid()) {
                return APIManagement::APIError('Order not paid, can not generate fiscal receipt', 400);
            }

            $customer = $order->getCustomer();
            $nf2 = new NumberFormatter(Language::FR->value, NumberFormatter::SPELLOUT);

            if ($customer instanceof CompanyCustomerEntity) {
                /** @var CompanyCustomerEntity $customer */
                $fiscalReceiptModel = new FiscalReceiptModel(
                    articles: '200, 238 bis et 885-0VBISA',
                    receiptCode: self::createReceiptCode(),
                    customerFullName: $customer->getCompanyName(),
                    customerAddress: $customer->getAddress(),
                    customerPostalCode: $customer->getPostalCode(),
                    customerCity: $customer->getCity(),
                    fiscalReductionPercentage: CustomerType::COMPANY->getFiscalReduction(),
                    paymentMethod: $order->getPaymentMethod()->getMethodName(),
                    priceWord: $nf2->format($order->getAmount()),
                    price: $order->getAmount(),
                    date: $order->getDate(),
                    orderUuid: $orderUUID
                );
            } else {
                $fiscalReceiptModel = new FiscalReceiptModel(
                    articles: '200, 238 bis et 978',
                    receiptCode: self::createReceiptCode($order->getFiscalReceiptNumber()),
                    customerFullName: $customer->getFirstname() . " " . $customer->getLastname(),
                    customerAddress: $customer->getAddress(),
                    customerPostalCode: $customer->getPostalCode(),
                    customerCity: $customer->getCity(),
                    fiscalReductionPercentage: CustomerType::INDIVIDUAL->getFiscalReduction(),
                    paymentMethod: $order->getPaymentMethod()->getMethodName(),
                    priceWord: $nf2->format($order->getAmount()),
                    price: $order->getAmount(),
                    date: $order->getDate(),
                    orderUuid: $orderUUID
                );
            }
            // @todo: pr??voir un zip avec tous les re??us fiscaux en cas de don r??current
            $fileURL = FiscalReceiptService::createReceipt($fiscalReceiptModel, $order);
        } catch (\Exception $exception) {
            return APIManagement::APIError($exception->getMessage(),404);
        }

        return APIManagement::APIClientDownloadWithURL($fileURL, "receipt-coralguardian-".$fiscalReceiptModel->getReceiptCode().".pdf", "inline");
    }

    private static function createReceiptCode(int $nextReceiptNumber = null) : string
    {
        return "CG2-" . (new \DateTime())->format("Y") . "-" . str_pad($nextReceiptNumber ?? (int) get_option(Plugin::NEXT_RECEIPT_NUM), 10, "0", STR_PAD_LEFT);
    }

    public static function getEndpoint(): string
    {
        return "getFiscalReceipt";
    }

    public static function getMethods(): array
    {
        return ["GET"];
    }

    public static function getPermissions(): string
    {
        return "__return_true";
    }
}
