<?php


/**
 * This is file was generated using Drush. DO NOT EDIT.
 */
namespace Drupal\htaccess_insert\Commands;

use Drush\Commands\DrushCommands;
use Drupal\htaccess_insert\HtaccessInsertClass;

/**
 * A Drush commandfile.
 */
class ValidatorCommands extends DrushCommands {
  /**
   * Check htaccess is valid
   *
   * @command htaccess:validator
   * @aliases ht-valid
   * @options arr An option that takes multiple values.
   * @options file, you can use htaccess path alternative,
   * @usage htaccess:validator /var/www/site --file
   *   Check htaccess is valid.
   */
  public function validator($options = ['file' => null]) {
      $result = HtaccessInsertClass::validatorHtaccess($options['file']) ? 'htaccess OK!' : 'htaccess syntax error!';
      $this->output()->writeln($result);
  }
}