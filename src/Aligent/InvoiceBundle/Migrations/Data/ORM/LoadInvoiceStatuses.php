<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Migrations\Data\ORM;

use Aligent\InvoiceBundle\Entity\Invoice;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

class LoadInvoiceStatuses extends AbstractEnumFixture implements
    VersionedFixtureInterface
{
    protected ObjectManager $manager;

    /**
     * Overwrite parent load() method as AbstractEnumFixture
     * does not support skipping/updating existing enum values
     * (or running more than once).
     */
    public function load(ObjectManager $manager): void
    {
        $className = ExtendHelper::buildEnumValueClassName($this->getEnumCode());
        if (!class_exists($className)) {
            return;
        }

        /** @var EnumValueRepository $enumRepo */
        $enumRepo = $manager->getRepository($className);

        /**
         * Build lookup table of existing enum values
         */
        /** @var AbstractEnumValue[] $existing */
        $existing = [];
        foreach ($enumRepo->findAll() as $item) {
            $existing[$item->getId()] = $item;
        }

        $priority = 1;
        foreach ($this->getData() as $id => $name) {
            $isDefault = $id === $this->getDefaultValue();
            if (isset($existing[$id])) {
                // Already exists, update Priority
                $enumOption = $existing[$id];
                $enumOption->setPriority($priority);
            } else {
                // Create new Enum Value
                $enumOption = $enumRepo->createEnumValue($name, $priority, $isDefault, $id);
            }

            $manager->persist($enumOption);
            $priority++;
        }

        $manager->flush();
    }

    /**
     * @return array<string,string>
     */
    protected function getData(): array
    {
        return Invoice::STATUSES;
    }

    protected function getEnumCode(): string
    {
        return Invoice::STATUS_ENUM_CODE;
    }

    public function getVersion(): string
    {
        return '1.0.1';
    }
}
