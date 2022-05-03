<?php

require  '../vendor/autoload.php';

AditumPayments\ApiSDK\Configuration::initialize();
AditumPayments\ApiSDK\Configuration::setUrl(AditumPayments\ApiSDK\Configuration::DEV_URL);
AditumPayments\ApiSDK\Configuration::setCnpj("83032272000109");
AditumPayments\ApiSDK\Configuration::setMerchantToken("mk_P1kT7Rngif1Xuylw0z96k3");
AditumPayments\ApiSDK\Configuration::setlog(true);
AditumPayments\ApiSDK\Configuration::login();

$gateway = new AditumPayments\ApiSDK\Gateway;
$pix = new AditumPayments\ApiSDK\Domains\Pix;

$pix->setMerchantChargeId("");

// Products = Nome, SKU, Valor, Quantidade
$pix->products->add("Jackson 2", "32424242", 1001, 1);
$pix->products->add("Jackson 3", "32424242", 1002, 2);
$pix->products->add("Jackson 4", "32424242", 1003, 3);

// Customer
$pix->customer->setId("00002");
$pix->customer->setName("fulano");
$pix->customer->setEmail("fulano@aditum.co");
$pix->customer->setDocumentType(AditumPayments\ApiSDK\Enum\DocumentType::CPF);
$pix->customer->setDocument("14533859755");

// Customer->address
$pix->customer->address->setStreet("Avenida Salvador");
$pix->customer->address->setNumber("5401");
$pix->customer->address->setNeighborhood("Recreio dos bandeirantes");
$pix->customer->address->setCity("Rio de janeiro");
$pix->customer->address->setState("RJ");
$pix->customer->address->setCountry("BR");
$pix->customer->address->setZipcode("2279714");
$pix->customer->address->setComplement("");

// Customer->phone
$pix->customer->phone->setCountryCode("55");
$pix->customer->phone->setAreaCode("21");
$pix->customer->phone->setNumber("98491715");
$pix->customer->phone->setType(AditumPayments\ApiSDK\Enum\PhoneType::MOBILE);

// Transactions
$pix->transactions->setAmount(10);

$res = $gateway->charge($pix);

echo "\n\nResposta:\n";
print_r(json_encode($res));

if (isset($res["status"])) {
    if ($res["status"] == AditumPayments\ApiSDK\Enum\ChargeStatus::PRE_AUTHORIZED) 
        echo "\n\nPIX!\n";
} else {
    if ($res != NULL)
        echo "\nhttStatus: ".$res["httpStatus"]
            ."\nhttpMsg: ".$res["httpMsg"]
            ."\n";
}