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

namespace Kaudaj\Module\FixOrderSlipTotal\Form\Settings;

use PrestaShopBundle\Form\Admin\Type\MultistoreConfigurationType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class GeneralType extends TranslatorAwareType
{
    public const FIELD_EXAMPLE_SETTING = 'example_setting';

    /**
     * {@inheritdoc}
     *
     * @param FormBuilderInterface<string, mixed> $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(self::FIELD_EXAMPLE_SETTING, TextType::class, [
                'label' => $this->trans('Example setting', 'Modules.Kjfixordersliptotal.Admin'),
                'help' => $this->trans('Help user to fill this field.', 'Modules.Kjfixordersliptotal.Admin'),
                'required' => false,
                'multistore_configuration_key' => GeneralConfiguration::getConfigurationKey(self::FIELD_EXAMPLE_SETTING),
            ]);
    }

    /**
     * {@inheritdoc}
     *
     * @see MultistoreConfigurationTypeExtension
     */
    public function getParent(): string
    {
        return MultistoreConfigurationType::class;
    }
}
