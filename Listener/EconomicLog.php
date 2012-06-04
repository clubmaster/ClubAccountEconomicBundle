<?php

namespace Club\Account\EconomicBundle\Listener;

class EconomicLog
{
  protected $container;

  public function __construct($container)
  {
    $this->container = $container;
  }

  public function onPurchaseCreate(\Club\ShopBundle\Event\FilterPurchaseLogEvent $event)
  {
    $purchase = $event->getPurchaseLog();

    $economic = $this->container->get('club_account_economic.economic');
    $economic->setConfig(array(
      'url' => $this->container->getParameter('club_account_economic.economic_url'),
      'agreementNumber' => $this->container->getParameter('club_account_economic.agreement'),
      'userName' => $this->container->getParameter('club_account_economic.username'),
      'password' => $this->container->getParameter('club_account_economic.password')
    ));

    foreach ($purchase->getOrder()->getOrderProducts() as $prod) {
      $economic->addFinanceVoucher($prod);
    }
  }
}
