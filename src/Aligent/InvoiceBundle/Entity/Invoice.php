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

use Aligent\InvoiceBundle\Model\ExtendInvoice;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Component\Math\BigDecimal;

/**
 * @ORM\Entity(repositoryClass="Aligent\InvoiceBundle\Entity\Repository\InvoiceRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(
 *      name="aligent_invoice",
 *      indexes={
 *          @ORM\Index(name="invoice_no_idx", columns={"invoice_no"}),
 *          @ORM\Index(name="issue_date_idx", columns={"issue_date"}),
 *          @ORM\Index(name="due_date_idx", columns={"due_date"}),
 *      }
 * )
 * @Config(
 *     routeName="aligent_invoice_index",
 *     routeView="aligent_invoice_view",
 *     defaultValues={
 *          "entity"={
 *              "icon"="fa-file-text-o"
 *          },
 *          "ownership"={
 *              "frontend_owner_type"="FRONTEND_CUSTOMER",
 *              "frontend_owner_field_name"="customer",
 *              "frontend_owner_column_name"="customer_id",
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
class Invoice extends ExtendInvoice implements
    DatesAwareInterface
{
    use DatesAwareTrait;

    const STATUS_ENUM_CODE = 'invoice_status';

    const STATUS_OPEN = 'open';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_OVERDUE = 'overdue';

    const STATUSES = [
        self::STATUS_OPEN => 'Open',
        self::STATUS_PAID => 'Paid',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_OVERDUE => 'Overdue',
    ];

    /**
     * Which statuses are considered 'unpaid'
     */
    const UNPAID_STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_OVERDUE,
    ];

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "identity"=false
     *          }
     *      }
     *  )
     */
    protected ?int $id = null;

    /**
     * @ORM\Column(name="invoice_no", type="string", length=50, nullable=false, unique=true)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "identity"=true
     *          },
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     *  )
     */
    protected ?string $invoiceNo = null;

    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\Customer")
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected ?Customer $customer = null;

    /**
     * @ORM\Column(name="issue_date", type="date", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected ?\DateTime $issueDate = null;

    /**
     * @ORM\Column(name="due_date", type="date", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected ?\DateTime $dueDate = null;

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

    /**
     * @ORM\Column(name="tax_total", type="money", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected float $totalTax = 0;

    /**
     * @ORM\Column(name="amount_paid", type="money", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected float $amountPaid = 0;

    /**
     * @ORM\OneToMany(targetEntity="Aligent\InvoiceBundle\Entity\InvoiceLineItem",
     *      mappedBy="invoice", cascade={"ALL"}, orphanRemoval=true
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     * @var Collection<int,InvoiceLineItem>
     */
    protected Collection $lineItems;

    public function __construct()
    {
        $this->customer = null;
        $this->lineItems = new ArrayCollection();
        parent::__construct();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInvoiceNo(): ?string
    {
        return $this->invoiceNo;
    }

    public function setInvoiceNo(?string $invoiceNo): Invoice
    {
        $this->invoiceNo = $invoiceNo;
        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): Invoice
    {
        $this->customer = $customer;
        return $this;
    }

    public function getIssueDate(): ?\DateTime
    {
        return $this->issueDate;
    }

    public function setIssueDate(?\DateTime $issueDate): Invoice
    {
        $this->issueDate = $issueDate;
        return $this;
    }

    public function getDueDate(): ?\DateTime
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTime $dueDate): Invoice
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    public function getAmount(): float|int
    {
        return $this->amount;
    }

    public function setAmount(float $amount): Invoice
    {
        $this->amount = $amount;
        $this->createPrice();
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): Invoice
    {
        $this->currency = $currency;
        $this->createPrice();
        return $this;
    }

    public function setPrice(Price $price = null): Invoice
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

    public function getTotalTax(): float|int
    {
        return $this->totalTax;
    }

    public function setTotalTax(float $totalTax): Invoice
    {
        $this->totalTax = $totalTax;
        return $this;
    }

    public function getAmountPaid(): float|int
    {
        return $this->amountPaid;
    }

    public function setAmountPaid(float $amountPaid): Invoice
    {
        $this->amountPaid = $amountPaid;
        return $this;
    }

    public function getBalance(): float
    {
        return BigDecimal::of($this->getAmount())
            ->minus($this->getAmountPaid())
            ->toFloat();
    }

    /**
     * Is this Invoice Balance Paid?
     * (Used to determine whether to update Status)
     */
    public function isBalancePaid(): bool
    {
        return ($this->getBalance() <= 0);
    }

    public function hasLineItem(InvoiceLineItem $lineItem): bool
    {
        return $this->lineItems->contains($lineItem);
    }

    public function addLineItem(InvoiceLineItem $lineItem): Invoice
    {
        if (!$this->hasLineItem($lineItem)) {
            $this->lineItems[] = $lineItem;
            $lineItem->setInvoice($this);
        }

        return $this;
    }

    public function removeLineItem(InvoiceLineItem $lineItem): Invoice
    {
        if ($this->hasLineItem($lineItem)) {
            $this->lineItems->removeElement($lineItem);
        }

        return $this;
    }

    /**
     * @param Collection<int, InvoiceLineItem> $lineItems
     * @return Invoice
     */
    public function setLineItems(Collection $lineItems): Invoice
    {
        foreach ($lineItems as $lineItem) {
            $lineItem->setInvoice($this);
        }

        $this->lineItems = $lineItems;

        return $this;
    }

    /**
     * @return Collection<int, InvoiceLineItem>
     */
    public function getLineItems(): Collection
    {
        return $this->lineItems;
    }

    /**
     * @return array<string,string>
     */
    public static function getStatuses(): array
    {
        return self::STATUSES;
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

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
        }
    }
}
