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

            if (!$order->isPaid()) {
                return APIManagement::APIError('Order not paid, can not generate fiscal receipt', 400);
            }

            $fileURL = FiscalReceiptService::generateFiscalReceipts($order);
        } catch (\Exception $exception) {
            return APIManagement::APIError("Not found", 404);
        }

        return APIManagement::APIClientDownloadWithURL($fileURL, "receipt-coralguardian-" . $order->getFiscalReceiptNumber() . ".pdf", "inline");
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
