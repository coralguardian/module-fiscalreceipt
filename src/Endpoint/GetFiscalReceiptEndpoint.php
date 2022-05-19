<?php

namespace D4rk0snet\FiscalReceipt\Endpoint;

use D4rk0snet\Adoption\Entity\AdoptionEntity;
use D4rk0snet\Coralguardian\Entity\CompanyCustomerEntity;
use D4rk0snet\Coralguardian\Entity\IndividualCustomerEntity;
use D4rk0snet\Donation\Entity\DonationEntity;
use D4rk0snet\FiscalReceipt\Service\FiscalReceiptService;
use D4rk0snet\FiscalReceipt\Model\FiscalReceiptModel;
use Hyperion\Doctrine\Service\DoctrineService;
use Hyperion\RestAPI\APIEnpointAbstract;
use Hyperion\RestAPI\APIManagement;
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

        // @todo : faire la recherche ensuite dans les dons si non trouvé
        /** @var DonationEntity $order */
        $order = DoctrineService::getEntityManager()->getRepository(DonationEntity::class)->find($orderUUID);
        if ($order === null) {
            return APIManagement::APIError('Order not found', 404);
        }

        $customer = $order->getCustomer();

        if($customer instanceof IndividualCustomerEntity) {
            $fiscalReceiptModel = new FiscalReceiptModel(
                articles: '45/407',
                receiptCode: 1,
                customerFullName: $customer->getFirstname() . " " . $customer->getLastname(),
                customerAddress: $customer->getAddress(),
                customerPostalCode: "xxx",
                customerCity: $customer->getCity(),
                fiscalReductionPercentage: 60,
                paymentMethod: 'Carte bancaire',
                priceWord: "soixante",
                price: $order->getAmount(),
                date: new \DateTime(),
                orderUuid: $orderUUID
            );
        } else {
            /** @var CompanyCustomerEntity $customer */
            $fiscalReceiptModel = new FiscalReceiptModel(
                articles: '45/407',
                receiptCode: 1,
                customerFullName: $customer->getCompanyName(),
                customerAddress: $customer->getAddress(),
                customerPostalCode: "xxx",
                customerCity: $customer->getCity(),
                fiscalReductionPercentage: 60,
                paymentMethod: 'Carte bancaire',
                priceWord: "soixante",
                price: $order->getAmount(),
                date: new \DateTime(),
                orderUuid: $orderUUID
            );
        }

        $fileURL = FiscalReceiptService::createReceipt($fiscalReceiptModel);

        return APIManagement::APIClientDownloadWithURL($fileURL, "receipt-coralguardian-".$fiscalReceiptModel->getReceiptCode().".pdf");
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
