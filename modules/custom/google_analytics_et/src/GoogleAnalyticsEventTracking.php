<?php

namespace Drupal\google_analytics_et;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\google_analytics_et\Entity\GoogleAnalyticsEventTrackerInterface;

class GoogleAnalyticsEventTracking  {

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  public function __construct(
    RendererInterface $renderer,
    EntityTypeManagerInterface $entity_type_manager,
    CurrentPathStack $current_path,
    CurrentRouteMatch $current_route_match,
    AliasManagerInterface $alias_manager,
    PathMatcherInterface $path_matcher,
    LanguageManagerInterface $language_manager) {
    $this->renderer = $renderer;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentPath = $current_path;
    $this->currentRouteMatch = $current_route_match;
    $this->aliasManager = $alias_manager;
    $this->pathMatcher = $path_matcher;
    $this->languageManager = $language_manager;
  }

  /**
   * Adds our JS library and drupalSettings to provided page attachments.
   *
   * @param $attachments
   */
  public function addAttachments(&$attachments) {
    if ($this::isGaAttached($attachments)) {
      $et_attached = FALSE;
      /** @var \Drupal\Core\Render\RendererInterface $renderer */
      $renderer = $this->renderer;
      $trackers = $this->entityTypeManager->getStorage('google_analytics_event_tracker')->loadMultiple();
      /** @var \Drupal\google_analytics_et\Entity\GoogleAnalyticsEventTrackerInterface $tracker */
      foreach ($trackers as $tracker) {
        if ($this->isTrackerActive($tracker)) {
          // Attach the main library.
          if (!$et_attached) {
            $attachments['#attached']['library'][] = 'google_analytics_et/google_analytics_et';
          }
          $et_attached = TRUE;
          if (empty($attachments['#attached']['drupalSettings'])) {
            $attachments['#attached']['drupalSettings'] = [];
          }
          $attachments['#attached']['drupalSettings']['googleAnalyticsEt'][] = $tracker->getJsSettings();
          $renderer->addCacheableDependency($attachments, $tracker);
        }
      }
    }
  }

  /**
   * Evaluates whether the provided event tracker is active in current context.
   *
   * @param \Drupal\google_analytics_et\Entity\GoogleAnalyticsEventTrackerInterface $tracker
   * @return bool
   */
  public function isTrackerActive(GoogleAnalyticsEventTrackerInterface $tracker) {
    return $this->pathMatch($tracker) && $this->languageMatch($tracker) && $this->contentTypeMatch($tracker);
  }

  /**
   * Evaluates whether the tracker is effective for the current path.
   *
   * @param \Drupal\google_analytics_et\Entity\GoogleAnalyticsEventTrackerInterface $tracker
   * @return bool
   */
  protected function pathMatch(GoogleAnalyticsEventTrackerInterface $tracker) {
    if (!$tracker->status()) {
      return FALSE;
    }

    $paths = rtrim($tracker->get('paths'));
    // If no paths provided this tracker will match all paths.
    if (empty($paths)) {
      return TRUE;
    }

    $path = $this->currentPath->getPath();
    $path_alias = $this->aliasManager->getAliasByPath($path);
    $path_alias = Unicode::strtolower($path_alias);
    $path_match = $this->pathMatcher->matchPath($path_alias, $paths) || $this->pathMatcher->matchPath($path, $paths);
    // When $tracker->path_negate has a value of 1, the asset is
    // added on all pages except those listed in $tracker->paths.
    // When set to 0, it is added only on those pages listed in
    // $tracker->paths.
    return ($tracker->get('path_negate') xor $path_match);
  }

  /**
   * Evaluates whether the tracker is effective for current interface language.
   *
   * @param \Drupal\google_analytics_et\Entity\GoogleAnalyticsEventTrackerInterface $tracker
   * @return bool
   */
  protected function languageMatch(GoogleAnalyticsEventTrackerInterface $tracker) {
    $languages = $tracker->get('languages');
    if (empty($languages)) {
      return TRUE;
    }

    foreach ($languages as $id => $language) {
      if ($id == $this->languageManager->getCurrentLanguage()->getId()) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Evaluates whether the tracker is effective for the current content type.
   *
   * @param \Drupal\google_analytics_et\Entity\GoogleAnalyticsEventTrackerInterface $tracker
   * @return bool
   */
  protected function contentTypeMatch(GoogleAnalyticsEventTrackerInterface $tracker) {
    $content_types = $tracker->get('content_types');
    if (empty($content_types)) {
      return TRUE;
    }
    /** @var \Drupal\node\NodeInterface $node */
    if ($node = $this->currentRouteMatch->getParameter('node')) {
      foreach ($content_types as $key => $content_type) {
        if ($content_type == $node->getType()) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Evaluates whether Google Analytics itself has been attached to the page.
   *
   * @param $attachments
   * @return bool
   */
  public static function isGaAttached($attachments) {
    if (!empty($attachments['#attached']['html_head'])) {
      foreach ($attachments['#attached']['html_head'] as $attachment) {
        if (!empty($attachment[1]) && $attachment[1] == 'google_analytics_tracking_script') {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}
