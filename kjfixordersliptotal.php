<?php
/**
 * Copyright since 2019 Kaudaj
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@kaudaj.com so we can send you a copy immediately.
 *
 * @author    Kaudaj <info@kaudaj.com>
 * @copyright Since 2019 Kaudaj
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

declare(strict_types=1);

if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require_once dirname(__FILE__) . '/vendor/autoload.php';
}

use Kaudaj\Module\FixOrderSlipTotal\Form\Settings\GeneralConfiguration;
use Kaudaj\Module\FixOrderSlipTotal\Form\Settings\GeneralType;
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class KJFixOrderSlipTotal extends Module
{
    /**
     * @var array<string, string> Configuration values to install/uninstall
     */
    public $configurationValues = [];

    /**
     * @var string[] Hooks to register/unregister
     */
    public const HOOKS = [
        'exampleHook',
    ];

    /**
     * @var Configuration<string, mixed> Configuration
     */
    private $configuration;

    public function __construct()
    {
        $this->name = 'kjfixordersliptotal';
        $this->tab = 'others';
        $this->version = '1.0.0';
        $this->author = 'Kaudaj';
        $this->ps_versions_compliancy = ['min' => '1.7.8.0', 'max' => _PS_VERSION_];

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Fix Order Slip Total', [], 'Modules.Kjfixordersliptotal.Admin');
        $this->description = $this->trans(<<<EOF
        Boost module development by providing a solid bedrock.
EOF
            ,
            [],
            'Modules.Kjfixordersliptotal.Admin'
        );

        $this->tabs = [
            [
                'name' => 'Fix Order Slip Total Settings',
                'class_name' => 'KJFixOrderSlipTotalSettings',
                'route_name' => 'kj_fix_order_slip_total_settings',
                'parent_class_name' => 'CONFIGURE',
                'visible' => false,
                'wording' => 'Fix Order Slip Total Settings',
                'wording_domain' => 'Modules.Kjfixordersliptotal.Admin',
            ],
        ];

        $this->configuration = new Configuration();

        $this->configurationValues = [
            GeneralConfiguration::getConfigurationKey(GeneralType::FIELD_EXAMPLE_SETTING) => 'default_value',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function install(): bool
    {
        return parent::install()
            && $this->installConfiguration()
            && $this->registerHook(self::HOOKS)
        ;
    }

    /**
     * Install configuration values
     */
    private function installConfiguration(): bool
    {
        try {
            foreach ($this->configurationValues as $key => $defaultValue) {
                $this->configuration->set($key, $defaultValue);
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(): bool
    {
        return parent::uninstall()
            && $this->uninstallConfiguration()
        ;
    }

    /**
     * Uninstall configuration values
     */
    private function uninstallConfiguration(): bool
    {
        try {
            foreach (array_keys($this->configurationValues) as $key) {
                $this->configuration->remove($key);
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Get module configuration page content
     */
    public function getContent(): void
    {
        $container = SymfonyContainer::getInstance();

        if ($container != null) {
            /** @var UrlGeneratorInterface */
            $router = $container->get('router');

            Tools::redirectAdmin($router->generate('kj_fix_order_slip_total_settings'));
        }
    }

    /**
     * Example hook
     *
     * @param array<string, mixed> $params Hook parameters
     */
    public function hookExampleHook(array $params): void
    {
        /* Do anything */
    }
}
