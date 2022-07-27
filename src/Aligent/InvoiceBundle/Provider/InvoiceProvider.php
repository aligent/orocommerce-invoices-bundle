<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Provider;

use Aligent\InvoiceBundle\Entity\Invoice;
use Aligent\InvoiceBundle\Entity\Repository\InvoiceRepository;
use Carbon\Carbon;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class InvoiceProvider
{
    protected ManagerRegistry $registry;
    protected LocaleSettings $localeSettings;

    public function __construct(
        ManagerRegistry $registry,
        LocaleSettings $localeSettings
    ) {
        $this->registry = $registry;
        $this->localeSettings = $localeSettings;
    }

    /**
     * @return array<Invoice>
     */
    public function getOverdueInvoices(): array
    {
        /** @var InvoiceRepository $repo */
        $repo = $this->getInvoiceRepository();

        // convert date to one time zone
        $timeZone = new \DateTimeZone($this->localeSettings->getTimeZone());
        $today = Carbon::today()->tz($timeZone);

        return $repo->getOverdueInvoices($today);
    }

    /**
     * @return ObjectRepository<Invoice>
     */
    protected function getInvoiceRepository(): ObjectRepository
    {
        return $this->registry->getRepository(Invoice::class);
    }
}
