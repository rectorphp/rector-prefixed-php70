<?php

declare (strict_types=1);
namespace RectorPrefix20210519;

use Ssch\TYPO3Rector\Rector\v7\v0\RemoveDivider2TabsConfigurationRector;
use Ssch\TYPO3Rector\Rector\v7\v4\DropAdditionalPaletteRector;
use Ssch\TYPO3Rector\Rector\v7\v4\MoveLanguageFilesFromRemovedCmsExtensionRector;
use Ssch\TYPO3Rector\Rector\v7\v5\RemoveIconsInOptionTagsRector;
use Ssch\TYPO3Rector\Rector\v7\v5\UseExtPrefixForTcaIconFileRector;
use Ssch\TYPO3Rector\Rector\v7\v6\AddRenderTypeToSelectFieldRector;
use Ssch\TYPO3Rector\Rector\v7\v6\MigrateT3editorWizardToRenderTypeT3editorRector;
use Ssch\TYPO3Rector\Rector\v7\v6\RemoveIconOptionForRenderTypeSelectRector;
use Ssch\TYPO3Rector\Rector\v8\v4\SubstituteOldWizardIconsRector;
use RectorPrefix20210519\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
return static function (\RectorPrefix20210519\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator) {
    $containerConfigurator->import(__DIR__ . '/../config.php');
    $services = $containerConfigurator->services();
    $services->set(\Ssch\TYPO3Rector\Rector\v7\v0\RemoveDivider2TabsConfigurationRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v7\v4\MoveLanguageFilesFromRemovedCmsExtensionRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v7\v4\DropAdditionalPaletteRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v7\v5\RemoveIconsInOptionTagsRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v7\v5\UseExtPrefixForTcaIconFileRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v7\v6\MigrateT3editorWizardToRenderTypeT3editorRector::class);
    $services->set('substitute_old_wizard_icons_version_76')->class(\Ssch\TYPO3Rector\Rector\v8\v4\SubstituteOldWizardIconsRector::class)->call('configure', [[\Ssch\TYPO3Rector\Rector\v8\v4\SubstituteOldWizardIconsRector::OLD_TO_NEW_FILE_LOCATIONS => ['add.gif' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_add.gif', 'link_popup.gif' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_link.gif', 'wizard_rte2.gif' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_rte.gif', 'wizard_table.gif' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_table.gif', 'edit2.gif' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_edit.gif', 'list.gif' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_list.gif', 'wizard_forms.gif' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_forms.gif']]]);
    $services->set(\Ssch\TYPO3Rector\Rector\v7\v6\AddRenderTypeToSelectFieldRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v7\v6\RemoveIconOptionForRenderTypeSelectRector::class);
};
