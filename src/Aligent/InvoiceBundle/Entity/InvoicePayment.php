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

use Aligent\InvoiceBundle\Model\ExtendInvoicePayment;
use Brick\Math\BigDecimal;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CurrencyBundle\Entity\CurrencyAwareInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\Ownership\AuditableFrontendCustomerUserAwareTrait;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(
 *      name="aligent_invoice_payment",
 * )
 * @Config(
 *     defaultValues={
 *          "entity"={
 *              "icon"="fa-money"
 *          },
 *          "ownership"={
 *              "frontend_owner_type"="FRONTEND_USER",
 *              "frontend_owner_field_name"="customerUser",
 *              "frontend_owner_column_name"="customer_user_id",
 *              "frontend_customer_field_name"="customer",
 *              "frontend_customer_column_name"="customer_id"
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
class InvoicePayment extends ExtendInvoicePayment implements
    DatesAwareInterface,
    CurrencyAwareInterface,
    CustomerOwnerAwareInterface
{
    use AuditableFrontendCustomerUserAwareTrait;
    use DatesAwareTrait;

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
     * @var bool
     * @ORM\Column(name="active", type="boolean")
     */
    protected bool $active = false;

    /**
     * @ORM\Column(name="payment_method", type="string", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected ?string $paymentMethod = null;

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
     * @ORM\Column(name="total", type="money", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected float $total = 0;

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

    /**
     * @ORM\OneToMany(targetEntity="InvoicePaymentLineItem",
     *      mappedBy="invoicePayment", cascade={"ALL"}, orphanRemoval=true
     * )
     * @ORM\OrderBy({"invoice" = "ASC"})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     * @var Collection<int,InvoicePaymentLineItem>
     */
    protected Collection $lineItems;

    public function __construct()
    {
        $this->lineItems = new ArrayCollection();
        parent::__construct();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): InvoicePayment
    {
        $this->active = $active;
        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): InvoicePayment
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getAmount(): float|int
    {
        return $this->amount;
    }

    public function setAmount(float $amount): InvoicePayment
    {
        $this->amount = $amount;
        return $this;
    }

    public function getTotal(): float|int
    {
        return $this->total;
    }

    public function setTotal(float $total): InvoicePayment
    {
        $this->total = $total;
        $this->createPrice();
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency($currency): InvoicePayment
    {
        $this->currency = $currency;
        $this->createPrice();
        return $this;
    }

    public function setPrice(Price $price = null): InvoicePayment
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
            $this->price = Price::create((string)$this->total, $this->currency);
        }
    }

    public function updatePrice(): void
    {
        $this->total = $this->price?->getValue();
        $this->currency = $this->price?->getCurrency();
    }


    public function hasLineItem(InvoicePaymentLineItem $lineItem): bool
    {
        return $this->lineItems->contains($lineItem);
    }

    public function addLineItem(InvoicePaymentLineItem $lineItem): InvoicePayment
    {
        if (!$this->hasLineItem($lineItem)) {
            $this->lineItems[] = $lineItem;
            $lineItem->setInvoicePayment($this);
        }
        $this->recalculateAmount();
        return $this;
    }

    public function removeLineItem(InvoicePaymentLineItem $lineItem): InvoicePayment
    {
        if ($this->hasLineItem($lineItem)) {
            $this->lineItems->removeElement($lineItem);
        }
        $this->recalculateAmount();
        return $this;
    }

    /**
     * @param Collection<int, InvoicePaymentLineItem> $lineItems
     * @return InvoicePayment
     */
    public function setLineItems(Collection $lineItems): InvoicePayment
    {
        foreach ($lineItems as $lineItem) {
            $lineItem->setInvoicePayment($this);
        }

        $this->lineItems = $lineItems;
        $this->recalculateAmount();
        return $this;
    }

    /**
     * @return Collection<int, InvoicePaymentLineItem>
     */
    public function getLineItems(): Collection
    {
        return $this->lineItems;
    }

    /**
     * @return ArrayCollection<int, Invoice>
     */
    public function getInvoices(): ArrayCollection
    {
        $invoices = new ArrayCollection();
        foreach ($this->getLineItems() as $lineItem) {
            $invoices->add($lineItem->getInvoice());
        }
        return $invoices;
    }

    /**
     * Does this InvoicePayment contain a line item with the provided $invoice?
     */
    public function hasInvoice(Invoice $invoice): bool
    {
        return $this->getLineItems()->exists(function ($key, $element) use ($invoice) {
            return $element->getInvoice()->getId() === $invoice->getId();
        });
    }

    public function removeLineItemByInvoice(Invoice $invoice): bool
    {
        $lineItems = $this->getLineItems()->filter(function (InvoicePaymentLineItem $lineItem) use ($invoice) {
            return $lineItem->getInvoice()->getId() === $invoice->getId();
        });

        if ($lineItems->count() !== 1) {
            return false;
        }

        $this->removeLineItem($lineItems->current());

        return true;
    }

    /**
     * Recalculate the Payment Amount (Subtotal) based on the Line Item Amounts.
     * Required when adding/updating/removing Payment Line Items.
     * (Automatically called by methods in this class, or can be called manually)
     */
    public function recalculateAmount(): float|int
    {
        $amount = BigDecimal::zero()->toScale(2);
        foreach ($this->getLineItems() as $lineItem) {
            $amount = $amount->plus(BigDecimal::of($lineItem->getAmount()));
            if (!$this->getCurrency()) {
                $this->setCurrency($lineItem->getCurrency());
            }
        }

        $this->setAmount($amount->toFloat());

        return $this->getAmount();
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function preSave(): void
    {
        $this->updatePrice();
    }

    public function getEntityIdentifier(): ?int
    {
        return $this->id;
    }
}
