((Drupal, drupalSettings) => {

  Drupal.behaviors.appCoreDeleteMe01 = {
    attach: (context, settings) => {
      // Do something.
    },
  };

  Drupal.appCoreDeleteMe01 = drupalSettings.appCoreDeleteMe01 || {};

  /**
   * @param {Event} event
   */
  Drupal.appCoreDeleteMe01.onFooClick = event => {
    // Do something.
  };

})(Drupal, drupalSettings);
