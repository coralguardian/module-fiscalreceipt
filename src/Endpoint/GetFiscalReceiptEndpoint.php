<?php

namespace D4rk0snet\CoralFiscalreceipt\Endpoint;

use D4rk0snet\CoralOrder\Entity\AdoptionEntity;
use Hyperion\Api2pdf\Plugin;
use Hyperion\Doctrine\DoctrineService;
use Hyperion\RestAPI\APIEnpointAbstract;
use Hyperion\RestAPI\APIManagement;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use WP_REST_Request;
use WP_REST_Response;

class GetFiscalReceiptEndpoint extends APIEnpointAbstract
{
    private const ORDER_UUID_PARAM = 'order_uuid';

    public static function callback(WP_REST_Request $request): WP_REST_Response
    {
        $orderUUID = $request->get_param(self::ORDER_UUID_PARAM);
        if($orderUUID === null) {
            return APIManagement::APIError('Missing order uuid', 400);
        }

        $order = DoctrineService::getEntityManager()->getRepository(AdoptionEntity::class)->find($orderUUID);
        if($order === null) {
            return APIManagement::APIError('Order not found', 404);
        }

        // Generate fiscal receipt
        $loader = new FilesystemLoader(__DIR__."/../Template");
        $twig = new Environment($loader); // @todo : Activer le cache

        $html = $twig->load('receipt.twig')->render([]);
        do_action(Plugin::API2PDF_APIKEY_OPTION,$html, false, 'fiscalReceipt.pdf');
        die;
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