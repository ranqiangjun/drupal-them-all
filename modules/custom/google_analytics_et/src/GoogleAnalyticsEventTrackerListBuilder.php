<?php

namespace Drupal\google_analytics_et;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Entity\NodeType;

/**
 * Provides a listing of Google Analytics event tracker entities.
 */
class GoogleAnalyticsEventTrackerListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Tracker');
    $header['id'] = $this->t('Machine name');
    $header['paths'] = $this->t('Effective On');
    $header['event'] = $this->t('User Interaction');
    $header['selector'] = $this->t('Element Selector');
    $header['category'] = $this->t('Category');
    $header['action'] = $this->t('Action');
    $header['ga_label'] = $this->t('Event Label');
    $header['value'] = $this->t('Value');
    $header['noninteraction'] = $this->t('Non-interaction?');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $paths = $entity->get('paths');
    $effective = $this->t('All Pages');
    if (!empty($paths)) {
      $effective = $entity->get('path_negate') ? $this->t('All pages except:') : $this->t('Only pages:');
      $effective .= "\r" . $paths;
    }

    if ($node_types = $entity->get('content_types')) {
      $effective .= "\r" . $this->t('Only content types:');
      foreach ($node_types as $type) {
        if ($node_type = NodeType::load($type)) {
          $effective .= "\r" . $node_type->label();
        }
      }
    }
    else {
      $effective .= "\r" . $this->t('All content types');
    }

    if ($languages = $entity->get('languages')) {
      $effective .= "\r" . $this->t('Only languages:');
      foreach ($languages as $lang) {
        if ($language = \Drupal::languageManager()->getLanguage($lang)) {
          $effective .= "\r" . $language->getName();
        }
      }
    }
    else {
      $effective .= "\r" . $this->t('All languages');
    }

    $row['paths']['data'] = [
      '#markup' => nl2br(htmlentities($effective)),
    ];
    $row['event'] = $entity->get('dom_event');
    $row['selector'] = $entity->get('element_selector');
    $row['category'] = $entity->get('ga_event_category');
    $row['action'] = $entity->get('ga_event_action');
    $row['ga_label'] = $entity->get('ga_event_label');
    $row['value'] = $entity->get('ga_event_value');
    $row['bounce'] = $entity->get('ga_event_noninteraction') ? $this->t('Yes') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

}
