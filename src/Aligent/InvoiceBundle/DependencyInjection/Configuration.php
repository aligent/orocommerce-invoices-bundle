<?php
/**
 * @category  Aligent
 * @author    Bruno Pasqualini <bruno.pasqualini@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @link      http://www.aligent.com.au/
 */

namespace Aligent\InvoiceBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const ROOT_NODE = AligentInvoiceExtension::ALIAS;

    const INVOICE_ENABLED = 'invoices_enabled';
    const INVOICE_PAYMENT_METHODS = 'invoices_payment_methods';
    const INVOICE_PAYMENT_MESSAGE_ENABLED = 'invoices_payment_message_enabled';
    const INVOICE_PAYMENT_MESSAGE_TEXT = 'invoices_payment_message_text';

    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                self::INVOICE_ENABLED => [
                    'value' => true,
                    'type' => 'boolean',
                ],
                self::INVOICE_PAYMENT_METHODS => [
                    'value' => [],
                    'type' => 'array',
                ],
                self::INVOICE_PAYMENT_MESSAGE_ENABLED => [
                    'type' => 'boolean',
                    'value' => false,
                ],
                self::INVOICE_PAYMENT_MESSAGE_TEXT => [
                    'type' => 'text',
                    'value' => '',
                ],
            ]
        );

        return $treeBuilder;
    }

    public static function getConfigKeyByName(string $key): string
    {
        return implode(ConfigManager::SECTION_MODEL_SEPARATOR, [AligentInvoiceExtension::ALIAS, $key]);
    }
}
