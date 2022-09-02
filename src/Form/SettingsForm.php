<?php

namespace Drupal\htaccess_insert\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\htaccess_insert\HtaccessInsertClass;

/**
 * Configure htaccess insert settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'htaccess_insert_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['htaccess_insert.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    \Drupal::messenger()->addMessage('Warning, if you make use of this functionality you may affect the proper functioning of the site.', 'warning');
    $form['base'] = [
      '#type'           => 'textfield',
      '#title'          => $this->t('.htaccess base'),
      '#default_value'  => $this->config('htaccess_insert.settings')->get('base'),
      '#description'    => $this->t('Enter the path of the base htaccess, the path to enter must be absolute Ej. @path/.htaccess, in case the field is empty the .htaccess is taken from the root of the project.', ['@path' => DRUPAL_ROOT]),
    ];
    $form['additional'] = [
      '#type'           => 'textarea',
      '#title'          => $this->t('Additional data to .htaccess'),
      '#default_value'  => $this->config('htaccess_insert.settings')->get('additional'),
      '#rows'           => 20,
      '#resizable'      => TRUE,
      '#description'    => $this->t('The entire value of this field will be inserted at the end of the .htaccess file and must contain valid .htaccess syntax.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check htaccess base to validate.
    if( $form_state->getValue('base') != '' && file_exists($form_state->getValue('base')) ) {
      $form_state->setErrorByName('base', $this->t('The route file not exist.'));
    }

    // Check htaccess syntax for additional instructions.
    $filePath = HtaccessInsertClass::createNewHtaccess($form_state->getValue('base'), $form_state->getValue('additional'));
    if( !is_null($filePath) &&  HtaccessInsertClass::validatorHtaccess($filePath)){
        $form_state->setErrorByName('additional', $this->t('The additional data for built htaccess has a syntax error.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
      if(HtaccessInsertClass::updateHtaccess(false)){
          $this->config('htaccess_insert.settings')->set('base', $form_state->getValue('base'))->save();
          $this->config('htaccess_insert.settings')->set('additional', $form_state->getValue('additional'))->save();
          parent::submitForm($form, $form_state);
      }
  }

}
