<?php

namespace Club\Account\EconomicBundle\Model;

class Product
{
  protected $type;
  protected $account_number;
  protected $currency;
  protected $price;
  protected $quantity;
  protected $voucher_text;

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

  public function setAccountNumber($account_number)
  {
    $this->account_number = $account_number;
  }

  public function setCurrency($currency)
  {
    $this->currency = $currency;
  }

  public function setPrice($price)
  {
    $this->price = $price;
  }

  public function setQuantity($quantity)
  {
    $this->quantity = $quantity;
  }

  public function setVoucherText($voucher_text)
  {
    $this->voucher_text = $voucher_text;
  }
}

