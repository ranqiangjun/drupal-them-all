<?php

namespace Drupal\google_analytics_et\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

/**
 * Class GoogleAnalyticsEventTrackerForm.
 */
class GoogleAnalyticsEventTrackerForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\google_analytics_et\Entity\GoogleAnalyticsEventTracker $entity */
    $entity = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#description' => $this->t("Label for the Google Analytics event tracker."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\google_analytics_et\Entity\GoogleAnalyticsEventTracker::load',
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $form['dom_event'] = [
      '#type' => 'select',
      '#title' => $this->t('User Interaction'),
      '#description' => $this->t('The browser event to track on the selected element(s).'),
      '#options' => $entity->getDomEvents(),
      '#default_value' => $entity->get('dom_event') ?: 'click',
      '#required' => TRUE,
    ];

    $form['element_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Element Selector'),
      '#description' => $this->t("The CSS selector for the target element(s). A single ID selector ('#id-attribute-of-element') is recommended whenever possible."),
      '#default_value' => $entity->get('element_selector') ? : '',
      '#required' => TRUE,
      '#maxlength' => 256,
    ];

    $form['event'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Google Analytics Event'),
    ];

    $form['event']['ga_event_category'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Category'),
      '#description' => $this->t("The event category."),
      '#default_value' => $entity->get('ga_event_category') ? : '',
      '#required' => TRUE,
    ];

    $form['event']['ga_event_action'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Action'),
      '#description' => $this->t("The event action."),
      '#default_value' => $entity->get('ga_event_action') ? : '',
      '#required' => TRUE,
    ];

    $form['event']['ga_event_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t("The event label."),
      '#default_value' => $entity->get('ga_event_label') ? : '',
      '#required' => TRUE,
    ];

    $form['event']['ga_event_value'] = [
      '#type' => 'number',
      '#step' => 1,
      '#title' => $this->t('Value'),
      '#description' => $this->t("The event value."),
      '#default_value' => $entity->get('ga_event_value') ? : '',
      '#required' => TRUE,
    ];

    $form['event']['ga_event_noninteraction'] = [
      '#type' => 'radios',
      '#title' => $this->t('Non-interaction event?'),
      '#description' => $this->t("Whether to treat as a non-interaction event."),
      '#options' => [
        1 => $this->t('Yes'),
        0 => $this->t('No'),
      ],
      '#default_value' => $entity->get('ga_event_noninteraction') ? : 0,
      '#required' => TRUE,
    ];

    $form['visibility'] = [
      '#type' => 'fieldset',
      '#title' => $this->t("Pages"),
    ];

    $form['visibility']['paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Pages'),
      '#default_value' => $entity->get('paths') ?: '',
      '#description' => $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. An example path is /user/* for every user page. <front> is the front page."),
    ];

    $form['visibility']['path_negate'] = [
      '#type' => 'radios',
      '#options' => [
        0 => $this->t('Show for the listed pages'),
        1 => $this->t('Hide for the listed pages'),
      ],
      '#default_value' => $entity->get('path_negate') ?: 0,
    ];

    $node_types = NodeType::loadMultiple();
    $node_options = [];
    /**
     * @var string $key
     * @var \Drupal\node\Entity\NodeType $node_type
     */
    foreach ($node_types as $key => $node_type) {
      $node_options[$key] = $node_type->label();
    }
    $form['visibility']['content_types'] = [
      '#type' => 'select',
      '#title' => $this->t('Content Types'),
      '#description' => $this->t("The content (node) types for which this tracker is effective. If none are selected it will be effective on all node and non-node pages."),
      '#options' => $node_options,
      '#default_value' => $entity->get('content_types') ? : NULL,
      '#multiple' => TRUE,
    ];

    $languages = \Drupal::languageManager()->getLanguages();
    $language_options = [];
    /**
     * @var string $key
     * @var \Drupal\Core\Language\LanguageInterface $language
     */
    foreach ($languages as $key => $language) {
      $language_options[$key] = $language->getName();
    }
    $form['visibility']['languages'] = [
      '#type' => 'select',
      '#title' => $this->t('Languages'),
      '#description' => $this->t('The languages for which this tracker is effective. If none are selected it will be effective for all languages.'),
      '#options' => $language_options,
      '#default_value' => $entity->get('languages') ?: NULL,
      '#multiple' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = $entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Google Analytics event tracker.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Google Analytics event tracker.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirectUrl($entity->toUrl('collection'));
  }

}
