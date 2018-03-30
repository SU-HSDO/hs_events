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

    $this->container->get('theme_installer')->install(['bartik', 'seven']);

    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    $config = $config_factory->getEditable('system.theme');
    $config->set('admin', 'seven');
    $config->set('default', 'bartik');
    $config->save();
    $config = $config_factory->getEditable('node.settings');
    $config->set('use_admin_theme', TRUE);
    $config->save();

    $this->drupalPlaceBlock('local_tasks_block', [
      'region' => 'content',
      'weight' => -20,
    ]);
    $this->drupalPlaceBlock('page_title_block', [
      'region' => 'content',
      'weight' => -50,
    ]);

    $this->testfileData = file_get_contents(__DIR__ . '/avatar.png');
    $this->tmpFile = tempnam($this->filesDir, $this->testfilePrefix);
    $this->tmpFile = tempnam('', $this->testfilePrefix);
    file_put_contents($this->tmpFile, $this->testfileData);
  }

  /**
   * Log in with appropriate permissions.
   */
  protected function logIn() {
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
      'access media overview',
      'administer media',
      'administer eck entities',
      'bypass eck entity access',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Validates the module.
   */
  public function testHsEvents() {
    $this->logIn();
    $test_image = $this->createImageMedia();
    $this->createVideoMedia();
    /** @var \Drupal\Tests\WebAssert $assert_session */
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('node/add/stanford_event');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('Create Event');
    $page->checkField('Show End Date');

    $field_tabs = [
      'Event Details' => [
        'text' => [
          'title[0][value]' => 'Test Event',
          'body[0][summary]' => NULL,
          'body[0][value]' => 'Body Value',
          'field_s_event_link[0][uri]' => 'http://google.com',
          'field_s_event_link[0][title]' => 'Google',
        ],
        'select' => [
          'field_s_event_date[0][value][month]' => date('n'),
          'field_s_event_date[0][value][day]' => date('j'),
          'field_s_event_date[0][value][year]' => date('Y'),
          'field_s_event_date[0][value][hour]' => date('g'),
          'field_s_event_date[0][value][minute]' => 15,
          'field_s_event_date[0][value][ampm]' => date('a'),
          'field_s_event_date[0][end_value][month]' => date('n'),
          'field_s_event_date[0][end_value][day]' => date('j'),
          'field_s_event_date[0][end_value][year]' => date('Y') + 1,
          'field_s_event_date[0][end_value][hour]' => date('g'),
          'field_s_event_date[0][end_value][minute]' => 15,
          'field_s_event_date[0][end_value][ampm]' => date('a'),
        ],
      ],
      'Supplement Info' => [
        'text' => [
          'field_s_event_location[0][value]' => 'The White House',
          'field_s_event_map_link[0][uri]' => 'http://maps.google.com',
          'field_s_event_map_link[0][title]' => 'Google Maps',
          'field_s_event_sponsor[0][value]' => 'Stanford University',
          'field_s_event_contact_email[0][value]' => 'test@test.com',
          'field_s_event_contact_phone[0][value]' => '123-456-7890',
          'field_s_event_admission[0][value]' => 'Free',
        ],
        'autocomplete' => [
          'field_s_event_category[0][target_id]' => 'Test Category',
          'field_s_event_audience[0][target_id]' => 'General Public',
        ],
      ],
    ];

    // Check for each field on the node form.
    foreach ($field_tabs as $tab => $field_types) {
      $page->clickLink($tab);

      foreach ($field_types as $type => $fields) {
        // Check for the fields and populate them.
        foreach ($fields as $name => $value) {
          $this->assertTrue($assert_session->fieldExists($name));

          if (!is_null($value)) {

            switch ($type) {
              case 'select':
                $page->selectFieldOption($name, $value);
                break;
              default:
                $this->getSession()->getPage()->fillField($name, $value);
                break;
            }
          }
        }
      }
    }
    $this->createScreenshot('/var/www/mrc/blt/docroot/sites/simpletest/screenshot/shot' . __LINE__ . '.jpg');
    $page->clickLink('Event Details');
    $page->find('css', '.field--name-field-s-event-image summary')
      ->click();
    $this->getSession()->switchToIFrame('entity_browser_iframe_image_browser');

    $this->assertSession()
      ->waitForId('views-exposed-form-media-entity-browser-image-browser');
    $this->assertSession()->pageTextContains('Image Browser');
    $this->assertSession()->fieldExists('name');
    $this->getSession()->getPage()->findLink('Embed a File')->click();
    $this->assertSession()->waitForField('upload[uploaded_files]');
    $this->assertSession()->pageTextContains('Select Files');
    $this->getSession()->getPage()->findLink('Media Library')->click();
    $this->assertSession()->waitForField('Name');

    $this->getSession()
      ->getPage()
      ->find('css', 'img[src*="/' . $test_image . '"]')
      ->click();

    $this->assertSession()
      ->waitForElementVisible('css', 'input[name="use_selected"]')->click();

    $this->getSession()->switchToIFrame();
    $this->assertSession()
      ->waitForElementVisible('css', '.field--name-field-s-event-image input[name="remove"]');

    $page->pressButton('Add new Speaker');
    $this->assertSession()
      ->waitForField('field_s_event_speaker[form][inline_entity_form][title][0][value]');
    $page->fillField('field_s_event_speaker[form][inline_entity_form][title][0][value]', $this->randomString());

    $this->getSession()->getPage()->clickLink('Post Event Details');

    // Switch into video browser.
    $this->getSession()->switchToIFrame('entity_browser_iframe_video_browser');
    $this->getSession()->getPage()->checkField('Select this item');
    $this->getSession()->getPage()->pressButton('Continue');
    $this->assertSession()
      ->waitForElementVisible('css', 'input[name="use_selected"]')->click();

    $this->getSession()->switchToIFrame();
    $this->assertSession()
      ->waitForElement('css', '.field--name-field-s-event-video input[name="remove"]');

    $this->getSession()->getPage()->pressButton('Save');
    // Valdates the path auto works.
    $assert_session->addressEquals('/events/test-event');
    $this->createScreenshot('/var/www/mrc/blt/docroot/sites/simpletest/screenshot/shot' . __LINE__ . '.jpg');
  }

  /**
   * Upload and create a media item to be used on events.
   *
   * @return string
   *   Name of the media item.
   */
  protected function createImageMedia() {
    $test_filename = $this->randomMachineName() . '.png';
    $test_filepath = 'public://' . $test_filename;
    file_put_contents($test_filepath, file_get_contents(__DIR__ . '/avatar.png'));
    $source_field_id = 'field_media_image';
    $this->drupalGet("media/add/image");

    $this->getSession()->getPage()->fillField('Name', $test_filename);
    $real_path = \Drupal::service('file_system')->realpath($test_filepath);
    $this->getSession()->getPage()
      ->attachFileToField("files[{$source_field_id}_0]", $real_path);

    $result = $this->assertSession()->waitForButton('Remove');
    $this->assertNotEmpty($result);
    $this->getSession()->getPage()->pressButton('Save');

    return $test_filename;
  }

  /**
   * Create a media video item to be used on events.
   *
   * @return string
   *   Name of the media item.
   */
  protected function createVideoMedia() {
    $name = $this->randomMachineName();
    $video_url = 'https://www.youtube.com/watch?v=uLcS7uIlqPo';
    $source_field_id = 'field_media_video_embed_field';
    $this->drupalGet("media/add/video");
    $this->getSession()->getPage()->fillField('Name', $name);
    $this->getSession()
      ->getPage()
      ->fillField("{$source_field_id}[0][value]", $video_url);
    $this->getSession()->getPage()->pressButton('Save');
    return $name;
  }

}
