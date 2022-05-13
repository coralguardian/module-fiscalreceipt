<?php

namespace D4rk0snet\FiscalReceipt\Service;

use D4rk0snet\FiscalReceipt\Model\FiscalReceiptModel;
use Hyperion\Api2pdf\Service\Api2PdfService;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class FiscalReceiptService
{
    public static function createReceipt(FiscalReceiptModel $fiscalReceiptModel) : string
    {
        // Generate fiscal receipt
        $loader = new FilesystemLoader(__DIR__."/../Template");
        $twig = new Environment($loader); // @todo : Activer le cache

        $html = $twig->load('receipt.twig')->render($fiscalReceiptModel->toArray());

        return Api2PdfService::convertHtmlToPdf(
            $html,
            false,
            "receipt-".$fiscalReceiptModel->getReceiptCode().".pdf"
        );
    }
}