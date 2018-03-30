<?php

namespace Drupal\Tests\hs_events\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

class HsEventsJavascriptTest extends JavascriptTestBase {

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

    if ($this->htmlOutputEnabled) {
      $files = glob($this->htmlOutputDirectory . '/*'); // get all file names
      foreach ($files as $file) { // iterate files
        if (is_file($file)) {
          unlink($file);
        }
      }
    }

    $this->container->get('theme_installer')->install(['bartik', 'seven']);
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Validates the module.
   */
  public function testHsEvents() {
    /** @var \Drupal\Tests\WebAssert $assert_session */
    $assert_session = $this->assertSession();
    $this->drupalGet('node/add/stanford_event');
    $assert_session->statusCodeEquals(403);

    $account = $this->drupalCreateUser([
      'administer nodes',
      'administer content types',
      'bypass node access',
      'access image_browser entity browser pages',
      'access file_browser entity browser pages',
      'access video_browser entity browser pages',
      'access media_browser entity browser pages',
      'dropzone upload files',
    ]);
    $this->drupalLogin($account);

    $this->drupalGet('node/add/stanford_event');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('Create Event');

    $fields = [
      'title[0][value]' => 'Test Event',
      //      'field_s_event_date[0][value][month]' => date('n'),
      //      'field_s_event_date[0][value][day]' => date('j'),
      //      'field_s_event_date[0][value][year]' => date('Y'),
      //      'field_s_event_date[0][value][hour]' => date('g'),
      //      'field_s_event_date[0][value][minute]' => 15,
      //      'field_s_event_date[0][value][ampm]' => date('a'),
      //      'field_s_event_date[0][end_value][month]' => date('n'),
      //      'field_s_event_date[0][end_value][day]' => date('j'),
      //      'field_s_event_date[0][end_value][year]' => date('Y'),
      //      'field_s_event_date[0][end_value][hour]' => date('g'),
      //      'field_s_event_date[0][end_value][minute]' => 15,
      //      'field_s_event_date[0][end_value][ampm]' => date('a'),
      //      'body[0][summary]' => NULL,
      'body[0][value]' => 'Body Value',
      //      'field_s_event_link[0][uri]' => 'http://google.com',
      //      'field_s_event_link[0][title]' => 'Google',
      //      'field_s_event_type' => NULL,
      //      'field_s_event_audience[0][target_id]' => 'General Public',
      //      'field_s_event_location[0][value]' => 'The White House',
      //      'field_s_event_map_link[0][uri]' => 'http://maps.google.com',
      //      'field_s_event_map_link[0][title]' => 'Google Maps',
      //      'field_s_event_category[0][target_id]' => 'Test Category',
      //      'field_s_event_sponsor[0][value]' => 'Stanford University',
      //      'field_s_event_contact_email[0][value]' => 'test@test.com',
      //      'field_s_event_contact_phone[0][value]' => '123-456-7890',
      //      'field_s_event_admission[0][value]' => 'Free',
    ];
    foreach ($fields as $name => $value) {
      $this->addToAssertionCount(1);
      $assert_session->fieldExists($name);

      if (!is_null($value)) {
        $this->getSession()->getPage()->fillField($name, $value);
      }
    }
    $assert_session->pageTextContains('Create Event');

    $this->getSession()
      ->getPage()
      ->find('css', '.field--name-field-s-event-image summary')
      ->click();
    $this->getSession()->switchToIFrame('entity_browser_iframe_image_browser');

    $this->assertSession()
      ->waitForId('views-exposed-form-media-entity-browser-image-browser');
    $this->assertSession()->pageTextContains('Image Browser');
    $this->assertSession()->fieldExists('name');
    $this->getSession()->getPage()->findLink('Embed a File')->click();
    $this->assertSession()->waitForField('upload[uploaded_files]');
    $this->createScreenshot('/var/www/mrc/blt/docroot/sites/simpletest/screenshot/shot.jpg');
    $this->assertTrue(TRUE);

    $this->getSession()
      ->getPage()
      ->attachFileToField('File upload', __DIR__ . '/avatar.png');

    $this->getSession()->getPage()->findField('Add to Library')->click();
    $this->getSession()->wait(10000);
    $this->getSession()->switchToIFrame();

    $this->getSession()->getPage()->pressButton('Save');


    // Valdates the path auto works.
    $assert_session->addressEquals('/events/test-event');
  }

}
