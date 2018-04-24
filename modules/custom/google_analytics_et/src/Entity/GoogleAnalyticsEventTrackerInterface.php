<?php

namespace Drupal\google_analytics_et\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Google Analytics event tracker entities.
 */
interface GoogleAnalyticsEventTrackerInterface extends ConfigEntityInterface {

  /**
   * Returns whether the tracker is effective in the current page context.
   *
   * @return boolean
   */
  public function isActive();

  /**
   * Returns an array with the drupalSettings for this tracker.
   */
  public function getJsSettings();

  /**
   * Returns the list of DOM events supported by this tracker.
   *
   * @return array
   */
  public function getDomEvents();

}
