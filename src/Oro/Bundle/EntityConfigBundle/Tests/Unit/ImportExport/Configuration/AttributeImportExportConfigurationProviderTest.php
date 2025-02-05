<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\ImportExport\Configuration;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\ImportExport\Configuration\AttributeImportExportConfigurationProvider;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;

class AttributeImportExportConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGet()
    {
        self::assertEquals(
            new ImportExportConfiguration([
                ImportExportConfiguration::FIELD_ENTITY_CLASS => FieldConfigModel::class,
                ImportExportConfiguration::FIELD_IMPORT_JOB_NAME => 'attribute_import_from_csv',
                ImportExportConfiguration::FIELD_IMPORT_VALIDATION_JOB_NAME => 'attribute_import_validation_from_csv',
                ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS =>
                    'oro_entity_config_entity_field.add_or_replace',
                ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_JOB_NAME => 'entity_export_template_to_csv',
                ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS =>
                    'oro_entity_config_attribute.export_template',
            ]),
            (new AttributeImportExportConfigurationProvider())->get()
        );
    }
}
