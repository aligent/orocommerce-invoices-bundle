<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Initializer;

use Aligent\FixturesBundle\Initializer\ReferenceInitializerInterface;
use Aligent\InvoiceBundle\Entity\Invoice;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class InvoiceStatusEnumInitializer implements ReferenceInitializerInterface
{
    public function init(ObjectManager $manager, Collection $referenceRepository): void
    {
        $entityName = ExtendHelper::buildEnumValueClassName(Invoice::STATUS_ENUM_CODE);
        /** @var EnumValueRepository $repo - @phpstan-ignore-next-line */
        $repo = $manager->getRepository($entityName);

        /** @var AbstractEnumValue $invoiceStatus */
        foreach ($repo->findAll() as $invoiceStatus) {
            $referenceRepository->set(
                'invoice_status_' . $invoiceStatus->getId(),
                $invoiceStatus
            );
        }
    }
}
