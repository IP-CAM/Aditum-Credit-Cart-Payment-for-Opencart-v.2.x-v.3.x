<?php

namespace AditumPayments\ApiSDK\Enum;

abstract class InstallmentType {
    public const NONE       = 0; // Transação a vista
    public const MERCHANT   = 1; // Transação parcelada pelo lojista, ou seja, sem juros
    public const ISSUER     = 2; // Transação parcelada pelo emissor do cartão, ou seja, com juros
}
