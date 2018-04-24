<?php

namespace Drupal\Tests\google_analytics_et\FunctionalJavascript;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\google_analytics_et\Entity\GoogleAnalyticsEventTracker;
use Drupal\Tests\ConfigTestTrait;

/**
 * Google Analytics Event Tracking JavaScript tests.
 *
 * @group google_analytics_et
 */
class GoogleAnalyticsEventTrackingJsTest extends JavascriptTestBase {

  use ConfigTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'google_analytics',
    'google_analytics_et',
    'google_analytics_et_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->container->get('theme_installer')->install(['bartik']);
    $this->container->get('config.factory')->getEditable('system.theme')->set('default', 'bartik')->save();
    // Set a fake account for GA so the script is added to the page.
    $this->config('google_analytics.settings')->set('account', 'UA-123456-1');
    // Create a test tracker config.
    GoogleAnalyticsEventTracker::create([
      'label' => 'test tracker',
      'id' => 'test_tracker',
      'element_selector' => '#edit-test-radios-two',
      'dom_event' => 'click',
      'ga_event_category' => 'test category',
      'ga_event_action' => 'test action',
      'ga_event_label' => 'test label',
      'ga_event_value' => 666,
      'ga_event_noninteraction' => 0,
    ]);
  }

  /**
   * Ensure a tracker config adds a click event to an element.
   */
  public function testClickTrackerConfig() {
    $web_assert = $this->assertSession();
    $this->drupalGet('<front>');
    $drupal_settings = $this->getDrupalSettings();
    $this->assertArrayNotHasKey('googleAnalyticsEt', $drupal_settings, 'No google analytics event trackers configured for front page.');
    $web_assert->elementNotExists('xpath', "//[@data-google-analytics-et-processed = 'true']");

    $this->drupalGet('/google_analytics_et_test/test');
    $drupal_settings = $this->getDrupalSettings();
    $this->assertArrayHasKey('googleAnalyticsEt', $drupal_settings, 'Google analytics event trackers configured for test page.');
    $web_assert->elementExists('xpath', "//[@data-google-analytics-et-processed = 'true']");
  }


}
