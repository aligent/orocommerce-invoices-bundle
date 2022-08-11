<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Tests\Unit\Entity\Stub;

use Aligent\InvoiceBundle\Entity\Invoice as BaseInvoice;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;

class Invoice extends BaseInvoice
{
    private ?AbstractEnumValue $status = null;

    public function __construct()
    {
        $this->status = null;
        parent::__construct();
    }

    public function getStatus(): ?AbstractEnumValue
    {
        return $this->status;
    }

    public function setStatus(?AbstractEnumValue $status): Invoice
    {
        $this->status = $status;
        return $this;
    }
}
