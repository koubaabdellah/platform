<?php

namespace Oro\Bundle\TranslationBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;

class TranslationListener
{
    const PARAM = 'en_language';

    /** @var LanguageProvider */
    protected $provider;

    public function __construct(LanguageProvider $provider)
    {
        $this->provider = $provider;
    }

    public function onBuildBefore(BuildBefore $event)
    {
        $datagrid = $event->getDatagrid();
        $datagrid->getParameters()->set(self::PARAM, $this->provider->getDefaultLanguage());
    }
}
