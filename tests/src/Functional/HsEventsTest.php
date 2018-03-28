<?php

namespace Drupal\Tests\hs_events\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Class HsEventsTest.
 *
 * @package Drupal\Tests\hs_events\Functional
 *
 * @group hs_events
 */
class HsEventsTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['block', 'hs_events', 'field_ui'];

  /**
   * Disable strict config testing since entity_browser throws issues.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->container->get('theme_installer')->install(['bartik', 'seven']);
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Validates the module.
   */
  public function testHsEvents() {
    $this->drupalGet('node/add');
    $this->assertSession()->statusCodeEquals(403);

    $account = $this->drupalCreateUser([
      'administer nodes',
      'administer content types',
      'bypass node access',
    ]);
    $this->drupalLogin($account);

    $this->drupalGet('node/add/stanford_event');
    $this->assertSession()->statusCodeEquals(404);
  }

}
