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

use Aligent\InvoiceBundle\Model\ExtendInvoiceLineItem;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(
 *      name="aligent_invoice_line_item",
 * )
 * @Config(
 *     defaultValues={
 *          "entity"={
 *              "icon"="fa-check-square-o"
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
class InvoiceLineItem extends ExtendInvoiceLineItem
{
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
     * @ORM\ManyToOne(targetEntity="Aligent\InvoiceBundle\Entity\Invoice",inversedBy="lineItems")
     * @ORM\JoinColumn(name="invoice_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected ?Invoice $invoice = null;

    /**
     * @var float
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
     * @ORM\Column(name="summary", type="string", length=255, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     *  )
     */
    protected ?string $summary = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(Invoice $invoice): InvoiceLineItem
    {
        $this->invoice = $invoice;
        return $this;
    }

    public function getAmount(): float|int
    {
        return $this->amount;
    }

    public function setAmount(float|int $amount): InvoiceLineItem
    {
        $this->amount = $amount;
        $this->createPrice();
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): InvoiceLineItem
    {
        $this->currency = $currency;
        $this->createPrice();
        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): InvoiceLineItem
    {
        $this->summary = $summary;
        return $this;
    }

    public function setPrice(Price $price = null): InvoiceLineItem
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

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function preSave(): void
    {
        $this->updatePrice();
    }

    public function updatePrice(): void
    {
        $this->amount = $this->price?->getValue();
        $this->currency = $this->price?->getCurrency();
    }
}
