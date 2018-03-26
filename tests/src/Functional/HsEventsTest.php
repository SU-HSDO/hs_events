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
  protected static $modules = ['hs_events', 'toolbar', 'block'];

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

  }

}
