<?php

declare(strict_types=1);

return [
    'name'        => 'RSS Plus',
    'description' => 'Advanced RSS feed management for Mautic emails',
    'version'     => '1.0.0',
    'author'      => 'Frederik Wouters',
    'icon'        => 'plugins/MauticEmailRssPlusBundle/Assets/rss-icon.png',

    'routes' => [
        'public' => [
            'mautic_rssplus_feeds_list' => [
                'path'       => '/rssplus/feeds/list',
                'controller' => 'MauticPlugin\MauticEmailRssPlusBundle\Controller\ApiController::getFeedsAction',
            ],
            'mautic_rssplus_templates_list' => [
                'path'       => '/rssplus/templates/list',
                'controller' => 'MauticPlugin\MauticEmailRssPlusBundle\Controller\TemplateApiController::getTemplatesAction',
            ],
            'mautic_rssplus_rss_fetch' => [
                'path'       => '/rssplus/rss/fetch/{feedId}',
                'controller' => 'MauticPlugin\MauticEmailRssPlusBundle\Controller\RssController::fetchAction',
                'defaults'   => [
                    'feedId' => 0,
                ],
            ],
        ],
        'main' => [
            'mautic_rssplus_feed_index' => [
                'path'       => '/rssplus/feeds/{page}',
                'controller' => 'MauticPlugin\MauticEmailRssPlusBundle\Controller\FeedController::indexAction',
                'defaults'   => [
                    'page' => 1,
                ],
            ],
            'mautic_rssplus_feed_action' => [
                'path'       => '/rssplus/feeds/{objectAction}/{objectId}',
                'controller' => 'MauticPlugin\MauticEmailRssPlusBundle\Controller\FeedController::executeAction',
                'defaults'   => [
                    'objectId' => 0,
                ],
            ],
            'mautic_rssplus_template_index' => [
                'path'       => '/rssplus/templates/{page}',
                'controller' => 'MauticPlugin\MauticEmailRssPlusBundle\Controller\TemplateController::indexAction',
                'defaults'   => [
                    'page' => 1,
                ],
            ],
            'mautic_rssplus_template_action' => [
                'path'       => '/rssplus/templates/{objectAction}/{objectId}',
                'controller' => 'MauticPlugin\MauticEmailRssPlusBundle\Controller\TemplateController::executeAction',
                'defaults'   => [
                    'objectId' => 0,
                ],
            ],
        ],
    ],

    'menu' => [
        'admin' => [
            'mautic.rssplus.menu.root' => [
                'id'        => 'mautic_rssplus_root',
                'iconClass' => 'ri-rss-line',
                'priority'  => 1,
                'checks'    => [
                    'integration' => [
                        'EmailRssPlus' => [
                            'enabled' => true,
                        ],
                    ],
                ],
            ],
            'mautic.rssplus.menu.feeds' => [
                'route'     => 'mautic_rssplus_feed_index',
                'parent'    => 'mautic.rssplus.menu.root',
                'iconClass' => 'ri-rss-line',
                'priority'  => 1,
                'checks'    => [
                    'integration' => [
                        'EmailRssPlus' => [
                            'enabled' => true,
                        ],
                    ],
                ],
            ],
            'mautic.rssplus.menu.templates' => [
                'route'     => 'mautic_rssplus_template_index',
                'parent'    => 'mautic.rssplus.menu.root',
                'iconClass' => 'ri-file-text-line',
                'priority'  => 2,
                'checks'    => [
                    'integration' => [
                        'EmailRssPlus' => [
                            'enabled' => true,
                        ],
                    ],
                ],
            ],
        ],
        'main' => [
            'mautic.rssplus.menu.root' => [
                'id'        => 'mautic_rssplus_root',
                'iconClass' => 'ri-rss-fill',
                'priority'  => 60,
                'children'  => [
                    'mautic.rssplus.menu.feeds' => [
                        'route'    => 'mautic_rssplus_feed_index',
                        'iconClass' => 'fa-list',
                    ],
                    'mautic.rssplus.menu.templates' => [
                        'route'    => 'mautic_rssplus_template_index',
                        'iconClass' => 'fa-file-text',
                    ],
                ],
            ],
        ],
    ],

    'services' => [
        'events' => [
            'mautic.rssplus.asset.subscriber' => [
                'class' => MauticPlugin\MauticEmailRssPlusBundle\EventListener\AssetSubscriber::class,
                'tags' => [
                    'kernel.event_subscriber',
                ],
            ],
        ],
        'integrations' => [
            'mautic.integration.emailrssplus' => [
                'class' => MauticPlugin\MauticEmailRssPlusBundle\Integration\EmailRssPlusIntegration::class,
                'arguments' => [
                    'event_dispatcher',                              // 1
                    'mautic.helper.cache_storage',                   // 2
                    'doctrine.orm.entity_manager',                   // 3
                    'request_stack',                                 // 4
                    'router',                                        // 5
                    'translator',                                    // 6
                    'logger',                                        // 7
                    'mautic.helper.encryption',                      // 8
                    'mautic.lead.model.lead',                        // 9
                    'mautic.lead.model.company',                     // 10
                    'mautic.helper.paths',                           // 11
                    'mautic.core.model.notification',                // 12
                    'mautic.lead.model.field',                       // 13
                    'mautic.plugin.model.integration_entity',        // 14
                    'mautic.lead.model.dnc',                         // 15
                    'mautic.lead.field.fields_with_unique_identifier' // 16
                ],
            ],
        ],
        'models' => [
            'mautic.rssplus.model.feed' => [
                'class'     => MauticPlugin\MauticEmailRssPlusBundle\Model\FeedModel::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.security',
                    'event_dispatcher',
                    'router',
                    'translator',
                    'mautic.helper.user',
                    'monolog.logger.mautic',
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.rssplus.model.template' => [
                'class'     => MauticPlugin\MauticEmailRssPlusBundle\Model\TemplateModel::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.security',
                    'event_dispatcher',
                    'router',
                    'translator',
                    'mautic.helper.user',
                    'monolog.logger.mautic',
                    'mautic.helper.core_parameters',
                ],
            ],
        ],
        'forms' => [
            'mautic.rssplus.form.type.feed' => [
                'class' => MauticPlugin\MauticEmailRssPlusBundle\Form\Type\FeedType::class,
                'tags'  => [
                    'form.type',
                ],
            ],
            'mautic.rssplus.form.type.template' => [
                'class' => MauticPlugin\MauticEmailRssPlusBundle\Form\Type\TemplateType::class,
                'tags'  => [
                    'form.type',
                ],
            ],
        ],
    ],

    'parameters' => [
        'rssplus_enabled' => true,
    ],
];
