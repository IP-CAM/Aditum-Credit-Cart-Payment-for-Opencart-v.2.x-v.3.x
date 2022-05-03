<?php

namespace AditumPayments\ApiSDK\Domains;

class Products {
    private $productsArray = array();


    public function add($name, $sku, $amount, $quantity) {
        array_push($this->productsArray, array(
            "name" => $name,
            "sku" => $sku,
            "amount" => $amount,
            "quantity" => $quantity
        ));
    }

    public function toString() {
        return $this->productsArray;
    }

    public function toJson() {
        return json_encode($this->toString());
    }
}