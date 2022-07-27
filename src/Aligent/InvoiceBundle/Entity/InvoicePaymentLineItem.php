<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Entity;

use Aligent\InvoiceBundle\Model\ExtendInvoicePaymentLineItem;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *      name="aligent_invoice_payment_line_item",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="alg_inv_pay_to_inv_unq", columns={
 *              "invoice_payment_id",
 *              "invoice_id"
 *          })
 *      }
 * )
 * @Config(
 *     defaultValues={
 *          "entity"={
 *              "icon"="fa-file-text-o"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="commerce",
 *              "category"="shopping"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          }
 *     }
 * )
 */
class InvoicePaymentLineItem extends ExtendInvoicePaymentLineItem
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "identity"=true
     *          }
     *      }
     *  )
     */
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Aligent\InvoiceBundle\Entity\InvoicePayment",inversedBy="lineItems")
     * @ORM\JoinColumn(name="invoice_payment_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected ?InvoicePayment $invoicePayment = null;

    /**
     * @ORM\ManyToOne(targetEntity="Aligent\InvoiceBundle\Entity\Invoice")
     * @ORM\JoinColumn(name="invoice_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected ?Invoice $invoice = null;

    /**
     * @ORM\Column(name="amount", type="money", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected float $amount = 0;

    /**
     * @ORM\Column(name="currency", type="string", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected ?string $currency = null;

    protected ?Price $price = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInvoicePayment(): ?InvoicePayment
    {
        return $this->invoicePayment;
    }

    public function setInvoicePayment(InvoicePayment $invoicePayment): InvoicePaymentLineItem
    {
        $this->invoicePayment = $invoicePayment;
        return $this;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(Invoice $invoice): InvoicePaymentLineItem
    {
        $this->invoice = $invoice;
        return $this;
    }

    public function getAmount(): float|int
    {
        return $this->amount;
    }

    public function setAmount(float $amount): InvoicePaymentLineItem
    {
        $this->amount = $amount;
        $this->createPrice();
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): InvoicePaymentLineItem
    {
        $this->currency = $currency;
        $this->createPrice();
        return $this;
    }

    public function setPrice(Price $price = null): InvoicePaymentLineItem
    {
        $this->price = $price;
        $this->updatePrice();
        return $this;
    }

    public function getPrice(): ?Price
    {
        return $this->price;
    }

    /**
     * @ORM\PostLoad
     */
    public function createPrice(): void
    {
        if (null !== $this->currency) {
            $this->price = Price::create((string)$this->amount, $this->currency);
        }
    }

    public function updatePrice(): void
    {
        $this->amount = $this->price?->getValue();
        $this->currency = $this->price?->getCurrency();
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function preSave(): void
    {
        $this->updatePrice();
    }
}
