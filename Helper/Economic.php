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
    protected $orderLine = 1;
    protected $invoiceLine = 1;
    protected $order_items = array();
    protected $invoice_items = array();
    protected $agreementNumber;
    protected $userName;
    protected $password;

    public function __construct($container)
    {
        $this->container = $container;
        $this->em = $container->get('doctrine.orm.default_entity_manager');
        $this->translator = $container->get('translator');

        $this->agreementNumber = $this->container->getParameter('club_account_economic.agreement');
        $this->userName = $this->container->getParameter('club_account_economic.username');
        $this->password = $this->container->getParameter('club_account_economic.password');
    }

    public function setUsername($username)
    {
        $this->userName = $username;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function setAgreementNumber($agreementNumber)
    {
        $this->agreementNumber = $agreementNumber;
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
            'Address' => $user->address,
            'City' => $user->city,
            'Country' => $user->country,
            'PostalCode' => $user->postalCode,
            'DebtorGroupHandle' => $this->getDebtorGroupByNumber($user->group),
            'CurrencyHandle' => $this->getCurrencyByCode($user->currency),
            'TermOfPaymentHandle' => $this->getTermOfPaymentByName($user->term),
            'IsAccessible' => true
        );

        $this->debtor = $this->client->Debtor_CreateFromData(array('data' => $data))->Debtor_CreateFromDataResult;

        return $this->debtor;
    }

    public function addInvoice($entry)
    {
        $this->connect();

        $data = array(
            'CurrencyHandle' => $this->getCurrencyByCode($entry->currency),
            'Date' => $entry->date->format('Y-m-d').'T00:00:00',
            'DebtorHandle' => $entry->debtor,
            'DebtorName' => $entry->name,
            'DebtorAddress' => $entry->address,
            'DebtorCity' => $entry->city,
            'DebtorPostalCode' => $entry->postalCode,
            'DebtorCountry' => $entry->country,
            'DeliveryDate' => $entry->date->format('Y-m-d').'T00:00:00',
            'DueDate' => $entry->date->format('Y-m-d').'T00:00:00',
            'ExchangeRate' => $this->getExchangeRate($entry->currency),
            'GrossAmount' => $entry->grossAmount,
            'Heading' => $entry->heading,
            'IsVatIncluded' => true,
            'Margin' => 0,
            'MarginAsPercent' => 0,
            'NetAmount' => $entry->netAmount,
            'OtherReference' => $entry->otherReference,
            'PublicEntryNumber' => $entry->publicEntryNumber,
            'TermOfPaymentHandle' => $this->getTermOfPaymentByName($entry->term),
            'VatAmount' => $entry->vatAmount,
        );

        $this->invoice = $this->client->CurrentInvoice_CreateFromData(array(
            'data' => $data
        ))->CurrentInvoice_CreateFromDataResult;

        return $this->invoice;
    }

    public function addOrder($entry)
    {
        $this->connect();

        $data = array(
            'Number' => $entry->orderNumber,
            'PublicEntryNumber' => $entry->publicEntryNumber,
            'OtherReference' => $entry->otherReference,
            'DebtorHandle' => $entry->debtor,
            'DebtorName' => $entry->name,
            'TermOfPaymentHandle' => $this->getTermOfPaymentByName($entry->term),
            'Heading' => $entry->heading,
            'Date' => $entry->date->format('Y-m-d').'T00:00:00',
            'DueDate' => $entry->date->format('Y-m-d').'T00:00:00',
            'ExchangeRate' => $this->getExchangeRate($entry->currency),
            'IsVatIncluded' => true,
            'DeliveryDate' => $entry->date->format('Y-m-d').'T00:00:00',
            'IsArchived' => false,
            'CurrencyHandle' => $this->getCurrencyByCode($entry->currency),
            'IsSent' => true,
            'GrossAmount' => $entry->grossAmount,
            'NetAmount' => $entry->netAmount,
            'VatAmount' => $entry->vatAmount,
            'Margin' => 0,
            'MarginAsPercent' => 0,
        );

        $this->order = $this->client->Order_CreateFromData(array(
            'data' => $data
        ))->Order_CreateFromDataResult;

        return $this->order;
    }

    public function addInvoiceItem($entry)
    {
        $data = array(
            'DeliveryDate' => $entry->date->format('Y-m-d').'T00:00:00',
            'Description' => $entry->name,
            'DiscountAsPercent' => 0,
            'MarginAsPercent' => 0,
            'Number' => $this->invoiceLine,
            'InvoiceHandle' => $entry->invoice,
            'ProductHandle' => $this->getProductByNumber($entry->product),
            'Quantity' => $entry->quantity,
            'TotalMargin' => 0,
            'TotalNetAmount' => ($entry->unitPrice*$entry->quantity),
            'UnitHandle' => $this->getUnitByNumber(1),
            'UnitNetPrice' => $entry->unitPrice,
            'UnitCostPrice' => $entry->unitPrice,
        );

        $item = $this->client->CurrentInvoiceLine_CreateFromData(array(
            'data' => $data
        ))->CurrentInvoiceLine_CreateFromDataResult;
        $this->invoice_items[] = $item;

        $this->invoiceLine++;

        return $item;
    }

    public function addOrderItem($entry)
    {
        $data = array(
            'OrderHandle' => $entry->order,
            'Number' => $this->orderLine,
            'DeliveryDate' => $entry->date->format('Y-m-d').'T00:00:00',
            'Quantity' => $entry->quantity,
            'ProductHandle' => $this->getProductByNumber($entry->product),
            'Description' => $entry->name,
            'UnitHandle' => $this->getUnitByNumber(1),
            'UnitNetPrice' => $entry->unitPrice,
            'DiscountAsPercent' => 0,
            'UnitCostPrice' => $entry->unitPrice,
            'TotalNetAmount' => ($entry->unitPrice*$entry->quantity),
            'TotalMargin' => 0,
            'MarginAsPercent' => 0
        );

        $item = $this->client->OrderLine_CreateFromData(array(
            'data' => $data
        ))->OrderLine_CreateFromDataResult;
        $this->order_items[] = $item;

        $this->orderLine++;

        return $item;
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

    public function upgradeOrderToInvoice($orderHandle)
    {
        $param = array(
            'orderHandle' => array(
                'Id' => $orderHandle
            )
        );

        return $this->client->Order_UpgradeToInvoice($param);
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

    protected function getProductByNumber($account)
    {
        $this->connect();
        $r = $this->client->Product_FindByNumber(array(
            'number' => $account
        ));

        if (!isset($r->Product_FindByNumberResult)) {
            return false;
        }

        return $r->Product_FindByNumberResult;
    }

    protected function getTermOfPaymentByName($name)
    {
        $this->connect();
        $r = $this->client->TermOfPayment_FindByName(array(
            'name' => $name
        ));

        if (!isset($r->TermOfPayment_FindByNameResult)) {
            return false;
        }

        return $r->TermOfPayment_FindByNameResult->TermOfPaymentHandle;
    }

    protected function getDebtorGroupByNumber($account)
    {
        $this->connect();
        $r = $this->client->DebtorGroup_FindByNumber(array(
            'number' => $account
        ));

        if (!isset($r->DebtorGroup_FindByNumberResult)) {
            return false;
        }

        return $r->DebtorGroup_FindByNumberResult;
    }

    protected function getUnitByNumber($account)
    {
        $this->connect();
        $r = $this->client->Unit_FindByNumber(array(
            'number' => $account
        ));

        if (!isset($r->Unit_FindByNumberResult)) {
            return false;
        }

        return $r->Unit_FindByNumberResult;
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
        $this->connect();
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

    public function addManualDebtorInvoice(Process $entity, \DateTime $date, $amount, $text, $voucher, $invoice)
    {
        $currency = $this->getCurrencyByCode($entity->getCurrency());
        $contra = $this->getAccountByNumber($entity->getContraAccount());
        $cashbook = $this->getCashBookByName($entity->getCashbook());
        $vat = $this->getVatAccountByVatCode($entity->getVatCode());

        $r = $this->client->CashBookEntry_CreateManualDebtorInvoice(array(
            'cashBookHandle' => $cashbook,
            'contraAccountHandle' => $contra
        ))->CashBookEntry_CreateManualDebtorInvoiceResult;

        $entry = $this->getCashBookEntry($r);

        $param = array();
        $param['Handle'] = $r;
        $param['Type'] = 'ManualDebtorInvoice';
        $param['CashBookHandle'] = $cashbook;
        $param['ContraAccountHandle'] = $contra;
        $param['Date'] = $date->format('c');
        $param['VoucherNumber'] = $voucher;
        $param['DebtorInvoiceNumber'] = $invoice;
        $param['AmountDefaultCurrency'] = $amount;
        $param['Amount'] = $amount;
        $param['CurrencyHandle'] = $currency;
        $param['Text'] = $text;

        return $this->client->CashBookEntry_UpdateFromData(array(
            'data' => $param
        ))->CashBookEntry_UpdateFromDataResult;
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

    public function bookInvoice($id)
    {
        $param = array(
            'currentInvoiceHandle' => array(
                'Id' => $id
            )
        );

        return $this->client->CurrentInvoice_Book($param)
            ->CurrentInvoice_BookResult;
    }

    public function getInvoicePdf($entry)
    {
        $param = array(
            'currentInvoiceHandle' => array(
                'Id' => $entry
            )
        );

        return $this->client->CurrentInvoice_GetPdf($param)
            ->CurrentInvoice_GetPdfResult;
    }

    public function addProcess($entity)
    {
        $process = new Process();

        $entity->setProcess($process);

        return $this;
    }

    public function setReady($entity)
    {
        $entity
            ->getProcess()
            ->setStatus(Process::ORDER_READY)
            ;

        return $this;
    }

    public function setProcessing($entity)
    {
        $entity
            ->getProcess()
            ->setStatus(Process::ORDER_PROCESSING)
            ;

        return $this;
    }

    public function setProcessed($entity)
    {
        $entity
            ->getProcess()
            ->setStatus(Process::ORDER_PROCESSING)
            ;

        return $this;
    }

    public function setError($entity, $message)
    {
        $entity
            ->getProcess()
            ->setStatus(Process::ORDER_ERROR)
            ->setMessage($message)
            ;

        return $this;
    }

    public function addEconomic($order, $currency, $account, $contraAccount, $cashbook, $vat)
    {
        if ($this->container->getParameter('club_account_economic.enabled') == false) {
            return;
        }

        $process = new Process();
        $process->setAccount($account);
        $process->setContraAccount($contraAccount);
        $process->setCashbook($cashbook);
        $process->setCurrency($currency);
        $process->setVatCode($vat);

        $order->setProcess($process);

        return $this;
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

    public function getAllTermOfPayments()
    {
        $this->connect();
        $terms = $this->client->TermOfPayment_GetAll()->TermOfPayment_GetAllResult;

        return $terms;
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
            'agreementNumber' => $this->agreementNumber,
            'userName' => $this->userName,
            'password' => $this->password
        ));

        $this->connected = true;
    }

    public function disconnect()
    {
        if ($this->connected) {
            $this->client->Disconnect();
        }

        $this->connected = false;
    }

    protected function getExchangeRate($currency)
    {
        switch ($currency) {
        case 'SEK':
            $rate = '79.75';
            break;
        case 'NOK':
            $rate = '80.99';
            break;
        case 'EUR':
            $rate = '746.15';
            break;
        case 'DKK':
            $rate = '100';
            break;
        case 'CHF':
            $rate = '688.93';
            break;
        case 'GBP':
            $rate = '1013.51';
            break;
        case 'PLN':
            $rate = '176.13';
            break;
        case 'USD':
            $rate = '657.32';
            break;

        default:
            throw new \Exception('Currency rate is not defined');
        }

        return $rate;
    }
}
