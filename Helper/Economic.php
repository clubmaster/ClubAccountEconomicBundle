<?php

namespace Club\Account\EconomicBundle\Helper;

use Club\Account\EconomicBundle\Entity\Process;

class Economic
{
    protected $container;
    protected $em;

    protected $client;
    protected $config;
    protected $connected = false;

    public function __construct($container)
    {
        $this->container = $container;
        $this->em = $container->get('doctrine.orm.default_entity_manager');
    }

    protected function findDebtor(\Club\UserBundle\Entity\User $user)
    {
        $this->connect();

        $this->debtor = $this->client->Debtor_FindByNumber(array('number' => $user->getMemberNumber()));
        if (count($this->debtor)) return $this->debtor->Debtor_FindByNumberResult;

        return $this->debtor;
    }

    public function updateDebtor($number, $name, $email, $phone)
    {
        $data = array(
            'Handle' => array('Number' => $number),
            'VatZone' => 'HomeCountry',
            'Name' => $name,
            'Email' => $email,
            'TelephoneAndFaxNumber' => $phone,
            'DebtorGroupHandle' => array('Number' => 1),
            'CurrencyHandle' => array('Code' => 'DKK'),
            'TermOfPaymentHandle' => array('Id' => 1),
            'IsAccessible' => true,
            'LayoutHandle' => array('Id' => 16),
        );

        $this->debtor = $this->client->Debtor_UpdateFromData(array('data' => $data))->Debtor_UpdateFromDataResult;
    }

    public function addDebtor($user)
    {
        $this->connect();

        $data = array(
            'VatZone' => 'HomeCountry',
            'Number' => $user->number,
            'Name' => $user->name,
            'Email' => $user->email,
            'DebtorGroupHandle' => array('Number' => 1),
            'CurrencyHandle' => array('Code' => 'DKK'),
            'TermOfPaymentHandle' => array('Id' => 1),
            'IsAccessible' => true
        );

        $this->debtor = $this->client->Debtor_CreateFromData(array('data' => $data))->Debtor_CreateFromDataResult;

        return $this->debtor;
    }

    public function addOrder($order_number, $debtor_number, $name)
    {
        $data = array(
            'Number' => $order_number,
            'DebtorHandle' => array('Number' => $debtor_number),
            'DebtorName' => $name,
            'TermOfPaymentHandle' => array('Id' => 1),
            'Date' => date('Y-m-d').'T00:00:00',
            'DueDate' => date('Y-m-d').'T00:00:00',
            'ExchangeRate' => 1,
            'IsVatIncluded' => true,
            'DeliveryDate' => date('Y-m-d').'T00:00:00',
            'IsArchived' => false,
            'CurrencyHandle' => array('Code' => 'DKK'),
            'IsSent' => false,
            'NetAmount' => 0,
            'VatAmount' => 0,
            'GrossAmount' => 0,
            'Margin' => 0,
            'MarginAsPercent' => 0,
        );

        $this->order = $this->client->Order_CreateFromData(array(
            'data' => $data
        ))->Order_CreateFromDataResult;
    }

    public function addOrderItem($item_number, $quantity, $name, $unit_price)
    {
        $data = array(
            'OrderHandle' => $this->order,
            'Number' => $item_number,
            'DeliveryDate' => date('Y-m-d').'T00:00:00',
            'Quantity' => $quantity,
            'ProductHandle' => array('Number' => 120),
            'Description' => $name,
            'UnitHandle' => array('Number' => 1),
            'UnitNetPrice' => $unit_price,
            'DiscountAsPercent' => 0,
            'UnitCostPrice' => 0,
            'TotalNetAmount' => ($unit_price*$quantity),
            'TotalMargin' => 0,
            'MarginAsPercent' => 0
        );

        $item = $this->client->OrderLine_CreateFromData(array(
            'data' => $data
        ))->OrderLine_CreateFromDataResult;
        $this->order_items[] = $item;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function findOrder($number)
    {
        $this->order = $this->client->Order_FindByNumber(array('number' => $number));

        return $this->order;
    }

    public function getOrderNumber()
    {
        return $this->client->Order_GetNumber(array('orderHandle' => $this->order))->Order_GetNumberResult;
    }

    public function upgradeOrder()
    {
        $item = $this->client->OrderLine_CreateFromData(array(
            'data' => $data
        ))->OrderLine_CreateFromDataResult;

    }

    public function __destruct()
    {
        $this->disconnect();
    }

    protected function getAccount($account)
    {
        return $this->getAccountByNumber($account);
    }

    public function getDebtorByNumber($number)
    {
        $this->connect();
        $r = $this->client->Debtor_FindByNumber(array(
            'number' => $number
        ));

        if (!isset($r->Debtor_FindByNumberResult)) {
            return false;
        }

        return $r->Debtor_FindByNumberResult;
    }

    protected function getAccountByNumber($account)
    {
        $this->connect();
        $r = $this->client->Account_FindByNumber(array(
            'number' => $account
        ));

        if (!isset($r->Account_FindByNumberResult)) {
            return false;
        }

        return $r->Account_FindByNumberResult;
    }

    protected function getAccountByName($account)
    {
        $this->connect();
        $r = $this->client->Account_FindByName(array(
            'name' => $account
        ));

        if (!isset($r->Account_FindByNameResult)) {
            return false;
        }

        return $r->Account_FindByNameResult;
    }

    protected function getVatAccountByVatCode($code)
    {
        $this->connect();
        $r = $this->client->VatAccount_FindByVatCode(array(
            'vatCode' => $code
        ));

        if (!isset($r->VatAccount_FindByVatCodeResult)) {
            return false;
        }

        return $r->VatAccount_FindByVatCodeResult;
    }

    protected function getCurrencyByCode($currency)
    {
        return $this->client->Currency_FindByCode(array(
            'code' => $currency
        ))->Currency_FindByCodeResult;
    }

    public function addCashBook($product)
    {
        $this->connect();

        if (isset($product->type)) {
            switch ($product->type) {
            case 'coupon':
                $account = $this->getAccount($this->container->getParameter('club_shop.coupon_account_number'));
                break;
            case 'guest_booking':
                $account = $this->getAccount($this->container->getParameter('club_shop.guest_account_number'));
                break;
            }
        }

        $r = $this->client->CashBookEntry_Create(array(
            'type' => $product->type,
            'cashBookHandle' => $this->getCashBookByName($product->cashbook),
            'accountHandle' => $this->getAccountByNumber($product->account),
            'contraAccountHandle' => $this->getAccountByNumber($product->contraAccount)
        ))->CashBookEntry_CreateResult;

        $entry = $this->getCashBookEntry($r);
        $d = new \DateTime();

        $r = $this->client->CashBookEntry_UpdateFromData(array(
            'data' => array(
                'Handle' => $r,
                'Type' => $product->type,
                'CashBookHandle' => $this->getCashBookByName($product->cashbook),
                'AccountHandle' => $this->getAccountByNumber($product->account),
                'ContraAccountHandle' => $this->getAccountByNumber($product->contraAccount),
                'Date' => $d->format('c'),
                'VoucherNumber' => $entry->VoucherNumber,
                'AmountDefaultCurrency' => $product->price*-1,
                'Amount' => $product->price*-1,
                'CurrencyHandle' => $this->getCurrencyByCode($product->currency),
                'VatAccountHandle' => $this->getVatAccountByVatCode($product->vatAccount),
                'Text' => $product->voucherText
            )));

        return $r->CashBookEntry_UpdateFromDataResult;
    }

    public function addDebtorPayment(\Club\ShopBundle\Entity\PurchaseLog $purchase_log)
    {
        $user = $this->findDebtor($purchase_log->getOrder()->getUser());
        if (!count($user)) $user = $this->addDebtor($user);

        $contra_account = $this->getAccount($this->config->contraAccount);
        $cashbook = $this->getCashBookByName($this->config->cashbook);
        $currency = $this->getCurrencyByCode($this->config->currency);

        $r = $this->client->CashBookEntry_Create(array(
            'type' => 'DebtorPayment',
            'cashBookHandle' => $cashbook,
            'debtorHandle' => $user,
            'contraAccountHandle' => $contra_account,
        ))->CashBookEntry_CreateResult;

        $entry = $this->getCashBookEntry($r);
        $d = new \DateTime();

        return $this->client->CashBookEntry_UpdateFromData(array(
            'data' => array(
                'Handle' => $r,
                'Type' => 'DebtorPayment',
                'CashBookHandle' => $cashbook,
                'DebtorHandle' => $user,
                'ContraAccountHandle' => $contra_account,
                'Date' => $d->format('c'),
                'VoucherNumber' => $entry->VoucherNumber,
                'AmountDefaultCurrency' => $purchase_log->getAmount()/100,
                'Amount' => $purchase_log->getAmount()/100,
                'CurrencyHandle' => $currency,
                'Text' => $this->translator->trans('Payment from %user%, order %order%', array(
                    '%user%' => $purchase_log->getOrder()->getUser()->getName(),
                    '%order%' => $purchase_log->getOrder()->getOrderNumber()
                ))
            )))->CashBookEntry_UpdateFromDataResult;
    }

    public function getCashBookEntry($entry)
    {
        return $this->client->CashBookEntry_GetData(array(
            'entityHandle' => $entry
        ))->CashBookEntry_GetDataResult;
    }

    public function addEconomic($order, $currency, $account, $contraAccount, $cashbook)
    {
        if ($this->container->getParameter('club_account_economic.enabled') == false) {
            return;
        }

        $process = new Process();
        $process->setAccount($account);
        $process->setContraAccount($contraAccount);
        $process->setCashbook($cashbook);
        $process->setCurrency($currency);

        $order->setProcess($process);
        $this->em->persist($process);
    }

    protected function getAllCurrencies()
    {
        $this->connect();
        $currencies = $this->client->Currency_GetAll()->Currency_GetAllResult;

        return $currencies;
    }

    public function getAllVatCodes()
    {
        $this->connect();
        $vatCodes = $this->client->VatAccount_GetAll()->VatAccount_GetAllResult;

        return $vatCodes;
    }

    public function getCashBooks()
    {
        $this->connect();
        $cashbooks = $this->client->CashBook_GetAll();

        return $cashbooks;
    }

    protected function getCashBookByName($name)
    {
        $cashbook = $this->client->CashBook_FindByName(array(
            'name' => $name
        ));

        if (!isset($cashbook->CashBook_FindByNameResult)) {
            return false;
        }

        return $cashbook->CashBook_FindByNameResult;
    }

    protected function connect()
    {
        if ($this->connected) return true;

        $this->client = new \SoapClient(
            $this->container->getParameter('club_account_economic.economic_url'),
            array("trace" => 1, "exceptions" => 1)
        );

        $this->client->Connect(array(
            'agreementNumber' => $this->container->getParameter('club_account_economic.agreement'),
            'userName' => $this->container->getParameter('club_account_economic.username'),
            'password' => $this->container->getParameter('club_account_economic.password')
        ));

        $this->connected = true;
    }

    protected function disconnect()
    {
        if ($this->connected) {
            $this->client->Disconnect();
        }

        $this->connected = false;
    }
}
