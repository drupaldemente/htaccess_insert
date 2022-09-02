<?php


/**
 * This is file was generated using Drush. DO NOT EDIT.
 */
namespace Drupal\htaccess_insert\Commands;

use Drupal\htaccess_insert\HtaccessInsertClass;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 */
class InsertCommands extends DrushCommands {
  /**
   * Insert instructions saved in htaccess_insert
   *
   * @command htaccess:execute
   * @aliases ht-exec
   * @usage htaccess:execute
   *   Insert instructions saved in htaccess_insert.
   */
  public function insert() {
      $result = HtaccessInsertClass::updateHtaccess(true) ? '.htaccess updated!' : '.htaccess update failed !';
      $this->output()->writeln($result);
  }
}