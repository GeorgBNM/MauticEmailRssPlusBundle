<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailRssPlusBundle\Integration;

use Mautic\CoreBundle\Helper\CacheStorageHelper; // QUESTO DEVE ESSERE CORRETTO
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;

use Doctrine\ORM\EntityManager;

class EmailRssPlusIntegration extends AbstractIntegration
{
    public function __construct(
        \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher,
        \Mautic\CoreBundle\Helper\CacheStorageHelper $cacheStorageHelper,
        \Doctrine\ORM\EntityManager $em,
        \Symfony\Component\HttpFoundation\RequestStack $requestStack,
        \Symfony\Component\Routing\RouterInterface $router,
        \Symfony\Contracts\Translation\TranslatorInterface $translator,
        \Psr\Log\LoggerInterface $logger,
        \Mautic\CoreBundle\Helper\EncryptionHelper $encryptionHelper,
        \Mautic\LeadBundle\Model\LeadModel $leadModel,
        \Mautic\LeadBundle\Model\CompanyModel $companyModel,
        \Mautic\CoreBundle\Helper\PathsHelper $pathsHelper,
        \Mautic\CoreBundle\Model\NotificationModel $notificationModel,
        \Mautic\LeadBundle\Model\FieldModel $fieldModel,
        \Mautic\PluginBundle\Model\IntegrationEntityModel $integrationEntityModel,
        \Mautic\LeadBundle\Model\DoNotContact $doNotContact,
        \Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier $fieldsWithUniqueIdentifier
    ) {
        parent::__construct(
            $dispatcher,
            $cacheStorageHelper,
            $em,
            $requestStack,
            $router,
            $translator,
            $logger,
            $encryptionHelper,
            $leadModel,
            $companyModel,
            $pathsHelper,
            $notificationModel,
            $fieldModel,
            $integrationEntityModel,
            $doNotContact,
            $fieldsWithUniqueIdentifier
        );
    }

    public function getName(): string
    {
        return 'EmailRssPlus';
    }

    public function getDisplayName(): string
    {
        return 'RSS Plus';
    }

    public function getAuthenticationType(): string
    {
        return 'none';
    }

    public function getIcon(): string
    {
        return 'plugins/MauticEmailRssPlusBundle/Assets/rss-icon.png';
    }

    public function getRequiredKeyFields(): array
    {
        return [];
    }

    /**
     * @param FormBuilder|Form $builder
     * @param array            $data
     * @param string           $formArea
     */
    public function appendToForm(&$builder, $data, $formArea): void
    {
        if ('features' === $formArea) {
            $builder->add(
                'enabled',
                ChoiceType::class,
                [
                    'label' => 'mautic.plugin.emailrssplus.enabled',
                    'choices' => [
                        'mautic.core.form.yes' => true,
                        'mautic.core.form.no' => false,
                    ],
                    'data' => $data['enabled'] ?? false,
                    'attr' => [
                        'class' => 'form-control',
                        'tooltip' => 'mautic.plugin.emailrssplus.enabled.tooltip',
                    ],
                    'required' => false,
                    'expanded' => true,
                    'multiple' => false,
                    'label_attr' => ['class' => 'control-label'],
                ]
            );
        }
    }

    public function isConfigured(): bool
    {
        $featureSettings = $this->settings->getFeatureSettings();
        return !empty($featureSettings['enabled']);
    }
}