<?php

namespace D4rk0snet\FiscalReceipt\Service;

use D4rk0snet\FiscalReceipt\Endpoint\GetFiscalReceiptEndpoint;
use D4rk0snet\FiscalReceipt\Model\FiscalReceiptModel;
use D4rk0snet\FiscalReceipt\Plugin;
use Hyperion\Api2pdf\Service\Api2PdfService;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class FiscalReceiptService
{
    public static function createReceipt(FiscalReceiptModel $fiscalReceiptModel, bool $incrementFiscalReceiptNumber) : string
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
        if($incrementFiscalReceiptNumber) {
            update_option(Plugin::NEXT_RECEIPT_NUM, (int)get_option(Plugin::NEXT_RECEIPT_NUM) + 1);
        }

        return Api2PdfService::convertHtmlToPdf(
            $html,
            false,
            "receipt-".$fiscalReceiptModel->getReceiptCode().".pdf"
        );
    }

    public static function getURl(string $uuid) : string
    {
        $urlParts = parse_url(GetFiscalReceiptEndpoint::getUrl()."?".GetFiscalReceiptEndpoint::ORDER_UUID_PARAM."=".$uuid);

        return $urlParts["host"].$urlParts["path"]."?".$urlParts["query"];
    }
}