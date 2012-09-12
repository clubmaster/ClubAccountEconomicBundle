<?php

namespace Club\Account\EconomicBundle\Listener;

class EconomicLog
{
  protected $container;

  public function __construct($container)
  {
    $this->container = $container;
  }

  public function onOrderPaid(\Club\ShopBundle\Event\FilterPurchaseLogEvent $event)
  {
    $purchase = $event->getPurchaseLog();

    $c = new \Club\Account\EconomicBundle\Model\Config();
    $c->url = $this->container->getParameter('club_account_economic.economic_url');
    $c->agreementNumber = $this->container->getParameter('club_account_economic.agreement');
    $c->userName = $this->container->getParameter('club_account_economic.username');
    $c->password = $this->container->getParameter('club_account_economic.password');
    $c->cashbook = $this->container->getParameter('club_account_economic.cashbook');
    $c->currency = $this->container->getParameter('club_account_economic.currency');
    $c->contraAccount = $this->container->getParameter('club_account_economic.contraAccount');

    $economic = $this->container->get('club_account_economic.economic');
    $economic->setConfig($c);

    foreach ($purchase->getOrder()->getOrderProducts() as $prod) {
      $economic->addFinanceVoucher($prod);
    }
  }
}
