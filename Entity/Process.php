<?php

namespace Club\Account\EconomicBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Process
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Club\Account\EconomicBundle\Entity\ProcessRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Process
{
    const ORDER_NEW        = 0;
    const ORDER_READY      = 1;
    const ORDER_PROCESSING = 2;
    const ORDER_COMPLETED  = 3;
    const ORDER_ERROR      = 4;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer")
     */
    private $status = self::ORDER_NEW;

    /**
     * @var integer
     *
     * @ORM\Column(name="contraAccount", type="integer", nullable=true)
     */
    private $contraAccount;

    /**
     * @var integer
     *
     * @ORM\Column(name="account", type="integer", nullable=true)
     */
    private $account;

    /**
     * @var string
     *
     * @ORM\Column(name="vat_code", type="string", length=255, nullable=true)
     */
    private $vatCode;

    /**
     * @var string
     *
     * @ORM\Column(name="cashbook", type="string", length=255, nullable=true)
     */
    private $cashbook;

    /**
     * @var string
     *
     * @ORM\Column(name="term_of_payment", type="string", length=255, nullable=true)
     */
    private $termOfPayment;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=255)
     */
    private $currency;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text", nullable=true)
     */
    private $message;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return Process
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Process
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return Process
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->setCreatedAt(new \DateTime());
        $this->setUpdatedAt(new \DateTime());
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->setUpdatedAt(new \DateTime());
    }

    /**
     * Set contraAccount
     *
     * @param integer $contraAccount
     * @return Process
     */
    public function setContraAccount($contraAccount)
    {
        $this->contraAccount = $contraAccount;

        return $this;
    }

    /**
     * Get contraAccount
     *
     * @return integer
     */
    public function getContraAccount()
    {
        return $this->contraAccount;
    }

    /**
     * Set account
     *
     * @param integer $account
     * @return Process
     */
    public function setAccount($account)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * Get account
     *
     * @return integer
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * Set cashbook
     *
     * @param string $cashbook
     * @return Process
     */
    public function setCashbook($cashbook)
    {
        $this->cashbook = $cashbook;

        return $this;
    }

    /**
     * Get cashbook
     *
     * @return string
     */
    public function getCashbook()
    {
        return $this->cashbook;
    }

    /**
     * Set currency
     *
     * @param string $currency
     * @return Process
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set vatCode
     *
     * @param string $vatCode
     * @return Process
     */
    public function setVatCode($vatCode)
    {
        $this->vatCode = $vatCode;

        return $this;
    }

    /**
     * Get vatCode
     *
     * @return string
     */
    public function getVatCode()
    {
        return $this->vatCode;
    }

    /**
     * Set termOfPayment
     *
     * @param string $termOfPayment
     * @return Process
     */
    public function setTermOfPayment($termOfPayment)
    {
        $this->termOfPayment = $termOfPayment;

        return $this;
    }

    /**
     * Get termOfPayment
     *
     * @return string
     */
    public function getTermOfPayment()
    {
        return $this->termOfPayment;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return Process
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
}
