<?php

namespace D4rk0snet\FiscalReceipt\Model;

class FiscalReceiptModel
{
    private ?string $orderUuid;
    private string $articles;
    private string $receiptCode;
    private string $customerFullName;
    private string $companyName;
    private string $customerAddress;
    private string $customerPostalCode;
    private string $customerCity;
    private float $fiscalReductionPercentage;
    private string $priceWord;
    private float $price;
    private string $paymentMethod;
    private \DateTime $date;
    private ?string $siret;

    public function __construct(
        string    $articles,
        string    $receiptCode,
        string    $customerFullName,
        string    $customerAddress,
        string    $customerPostalCode,
        string    $customerCity,
        float     $fiscalReductionPercentage,
        string    $paymentMethod,
        string    $priceWord,
        float     $price,
        \DateTime $date,
        ?string $orderUuid,
        ?string $companyName = null,
        ?string $siret = null
    ) {
        $this->articles = $articles;
        $this->receiptCode = $receiptCode;
        $this->customerFullName = $customerFullName;
        $this->customerAddress = $customerAddress;
        $this->customerPostalCode = $customerPostalCode;
        $this->customerCity = $customerCity;
        $this->fiscalReductionPercentage = $fiscalReductionPercentage;
        $this->priceWord = $priceWord;
        $this->price = $price;
        $this->date = $date;
        $this->paymentMethod = $paymentMethod;
        $this->orderUuid = $orderUuid;
        $this->siret = $siret;
        $this->companyName = $companyName;
    }

    public function toArray() : array
    {
        return [
            'articles' => $this->articles,
            'receiptCode' => $this->receiptCode,
            'customerFullName' => $this->customerFullName,
            'customerAddress' => $this->customerAddress,
            'customerPostalCode' => $this->customerPostalCode,
            'customerCity' => $this->customerCity,
            'fiscalReductionPercentage' => $this->fiscalReductionPercentage,
            'priceWord' => $this->priceWord,
            'price' => $this->price,
            'paymentMethod' => $this->paymentMethod,
            'date' => $this->date->format("d-m-Y"),
            'siret' => $this->siret,
            'companyName' => $this->companyName
        ];
    }

    public function getReceiptCode(): string
    {
        return $this->receiptCode;
    }
}
