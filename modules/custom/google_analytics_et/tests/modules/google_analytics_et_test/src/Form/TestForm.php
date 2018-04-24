<?php

namespace Drupal\google_analytics_et_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TestForm.
 */
class TestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['test_radios'] = [
      '#type' => 'radios',
      '#title' => $this->t('Test Radios'),
      '#description' => $this->t('Test radio buttons'),
      '#options' => ['one' => $this->t('one'), 'two' => $this->t('two'), 'three' => $this->t('three'), 'four' => $this->t('four')],
      '#default_value' => 'three',
    ];
    $form['test_select'] = [
      '#type' => 'select',
      '#title' => $this->t('Test Select'),
      '#description' => $this->t('Test select'),
      '#options' => ['blue' => $this->t('blue'), 'red' => $this->t('red'), 'green' => $this->t('green'), 'yellow' => $this->t('yellow')],
      '#size' => 5,
      '#default_value' => 'green',
    ];
    $form['test_button'] = [
      '#type' => 'button',
      '#title' => $this->t('Test Button'),
      '#description' => $this->t('Test button'),
      '#value' => $this->t('Click me'),
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
  }

}
