<?php

use Symfony\Component\DependencyInjection\Compiler\PassConfig;

require_once 'phpstorm.civix.php';
// phpcs:disable
use CRM_Phpstorm_ExtensionUtil as E;
// phpcs:enable

/**
 * Determine the folder where we will store PhpStorm metadata files.
 *
 *  Not 100% sure which is best. These options trade-off in edge-cases of writability and readability:
 *  - '[civicrm.files]/.phpstorm.meta.php'
 *  - '[civicrm.compile]/.phpstorm.meta.php'
 *  - '[civicrm.root]/.phpstorm.meta.php'
 *
 * @return string
 */
function phpstorm_metadata_dir(): string {
  return \Civi::paths()->getPath('[civicrm.files]/.phpstorm.meta.php');
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function phpstorm_civicrm_config(&$config): void {
 _phpstorm_civix_civicrm_config($config);
}

function phpstorm_civicrm_container(\Symfony\Component\DependencyInjection\ContainerBuilder $container) {
  $container->addCompilerPass(new \Civi\PhpStorm\PhpStormCompilePass(), PassConfig::TYPE_AFTER_REMOVING, 2000);
}

function phpstorm_civicrm_uninstall() {
  $dir = phpstorm_metadata_dir();
  if (file_exists($dir)) {
    CRM_Utils_File::cleanDir($dir, TRUE);
  }
}