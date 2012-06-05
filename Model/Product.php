<?php

namespace Club\Account\EconomicBundle\Model;

class Product
{
  public $type;
  public $account_number;
  public $currency;
  public $price;
  public $quantity;
  public $voucher_text;

  public function getType()
  {
    return $this->type;
  }

  public function getAccountNumber()
  {
    return $this->account_number;
  }

  public function getCurrency()
  {
    return $this->currency;
  }

  public function getPrice()
  {
    return $this->price;
  }

  public function getQuantity()
  {
    return $this->quantity;
  }

  public function getVoucherText()
  {
    return $this->voucher_text;
  }
}

