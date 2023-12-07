<?php

namespace Drupal\Tests\block_diff_ui\Functional;

use Drupal\Tests\block_content\Functional\BlockContentTestBase;

/**
 * Block revision diff tests.
 *
 * @group entity_diff_ui
 */
class BlockDifRevisionsTest extends BlockContentTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'administer blocks',
    'access block library',
    'administer block types',
    'administer block content',
    'view all block content revisions',
  ];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'block',
    'block_content',
    'entity_diff_ui',
    'block_diff_ui',
  ];

  /**
   * Stores blocks created during the test.
   *
   * @var array
   */
  protected $blocks;

  /**
   * Stores log messages used during the test.
   *
   * @var array
   */
  protected $revisionLogs;

  /**
   * A user with administration permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Sets the test up.
   */
  protected function setUp(): void {
    parent::setUp();

    // Create initial block.
    $block = $this->createBlockContent('initial');

    $blocks = [];
    $logs = [];

    // Get original block.
    $blocks[] = $block->getRevisionId();
    $logs[] = '';

    // Create three revisions.
    $revision_count = 3;
    for ($i = 0; $i < $revision_count; $i++) {
      $block->setNewRevision(TRUE);
      $block->setRevisionLogMessage($this->randomMachineName(32));
      $block->setRevisionUser($this->adminUser);
      $block->setRevisionCreationTime(REQUEST_TIME);
      $logs[] = $block->getRevisionLogMessage();
      $block->save();
      $blocks[] = $block->getRevisionId();
    }

    $this->blocks = $blocks;
    $this->revisionLogs = $logs;
    // Create an admin user for this test.
    $this->adminUser = $this->drupalCreateUser([
      'view all block content revisions',
      'administer block content',
    ]);
  }

  /**
   * Checks block revisions.
   */
  public function testBlockRevisions() {
    $assert = $this->assertSession();
    /** @var string block id. */
    $block = $this->blocks[0];
    $revisions_url = 'block/' . $block . '/revisions';
    // Anonymouse must not have access to revision overview page.
    $this->drupalGet($revisions_url);
    $assert->statusCodeEquals(403);
    // Login as an admin user.
    $this->drupalLogin($this->adminUser);
    // Admin user should be able to access.
    $this->drupalGet($revisions_url);
    $assert->statusCodeEquals(200);
    $assert->buttonExists('Compare selected revisions');
  }

}
