<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\InvoiceBundle\Twig;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Twig\DateTimeExtension as BaseDateTimeExtension;
use Psr\Container\ContainerExceptionInterface;
use Twig\TwigFilter;

class DateTimeExtension extends BaseDateTimeExtension
{
    const FUTURE_DATE_FORMAT = 'In %s';
    const PAST_DATE_FORMAT = '%s ago';

    protected ?\DateTimeZone $timezone = null;

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('aligent_datetime_since', [$this, 'sinceDateTime']),
            new TwigFilter('aligent_datetime_is_past', [$this, 'dateTimeIsPast']),
        ];
    }

    /**
     * Determine how long since the provided DateTime.
     * Uses Carbon's diffForHumans function.
     * Examples include:
     *      - '3 seconds ago'
     *      - '2 hours ago'
     *      - '3 days ago'
     *      - 'In 3 days'
     *      - 'In 1 month'
     *
     * @param string|\DateTime $date
     * @param array<string,mixed> $options Currently unused
     * @return string|null
     */
    public function sinceDateTime(mixed $date, array $options = []): ?string
    {
        if (!$date) {
            return null;
        }

        $instance = $this->getCarbonInstanceFromDate($date);

        $diffString = $instance->diffForHumans([
            'syntax' => CarbonInterface::DIFF_ABSOLUTE
        ]);

        if ($instance->isFuture()) {
            // Is in future
            return sprintf(self::FUTURE_DATE_FORMAT, $diffString);
        }

        // Is in past
        return sprintf(self::PAST_DATE_FORMAT, $diffString);
    }

    /**
     * Determine whether the provided DateTime is in the past
     * @param \DateTime|string $date
     * @param array<string,mixed> $options Currently unused
     * @return bool|null
     */
    public function dateTimeIsPast(mixed $date, array $options = []): ?bool
    {
        if (!$date) {
            return null;
        }

        $instance = $this->getCarbonInstanceFromDate($date);
        return $instance->isPast();
    }

    /**
     * Convert a date (DateTime or string) to a Carbon Instance
     * @param \DateTime|string $date
     * @return Carbon
     */
    protected function getCarbonInstanceFromDate(mixed $date): Carbon
    {
        if ($date instanceof \DateTime) {
            return Carbon::instance($date)->shiftTimezone($this->getConfiguredTimeZone());
        }

        // String
        return Carbon::parse($date);
    }

    protected function getConfiguredTimeZone(): ?\DateTimeZone
    {
        if ($this->timezone === null) {
            try {
                $configManager = $this->getConfigManager();
                $this->timezone = new \DateTimeZone($configManager->get('oro_locale.timezone'));
            } catch (ContainerExceptionInterface $e) {
                // Not worrying with exception logging here as it's pretty unlikely
                // that configManager will be unavailable
            }
        }

        return $this->timezone;
    }

    /**
     * @throws ContainerExceptionInterface
     */
    protected function getConfigManager(): ConfigManager
    {
        return $this->container->get('oro_config.global');
    }

    /**
     * @return string[]
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(
            [
                'oro_config.global',
            ],
            parent::getSubscribedServices(),
        );
    }
}
