/**
 * @file
 * Fires Google Analytics events based on user configuration settings.
 */

(function (Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.googleAnaltyicsEt = {
    attach: function (context, settings) {
      // Bail if the ga function isn't defined.
      if (typeof ga == 'undefined') {
        return;
      }
      var trackers = settings.googleAnalyticsEt;
      // Iterate over our tracker settings.
      for (var i = 0; i < trackers.length; i++) {
        var elements = context.querySelectorAll(trackers[i].selector);
        for (var j = 0; j < elements.length; j++) {
          if (!elements[j].hasAttribute('data-google-analytics-et-processed')) {
            elements[j].addEventListener(trackers[i].event, (function(setting, element) {
              return function(e) {
                Drupal.googleAnalyticsEt(setting, element);
              };
            }) (trackers[i], elements[j]), false);
            elements[j].setAttribute('data-google-analytics-et-processed', 'true');
          }
        }
      }
    }
  };

  Drupal.googleAnalyticsEt = function (tracker, element) {
    ga('send', {
      'hitType': 'event',
      'eventCategory': Drupal.googleAnalyticsEtTokenReplace(tracker.category, element),
      'eventAction': Drupal.googleAnalyticsEtTokenReplace(tracker.action, element),
      'eventLabel': Drupal.googleAnalyticsEtTokenReplace(tracker.label, element),
      'eventValue': Number(tracker.value),
      'nonInteraction': Boolean(tracker.noninteraction)
    });
  };

  Drupal.googleAnalyticsEtTokenReplace = function(str, element) {
    var elem_text = element.innerText || element.textContent;
    var elem_href = element.getAttribute('href') || '';
    var current_page = window.location.href;
    return str.replace('!text', elem_text).replace('!href', elem_href).replace('!currentPage', current_page);
  }

})(Drupal, drupalSettings);
