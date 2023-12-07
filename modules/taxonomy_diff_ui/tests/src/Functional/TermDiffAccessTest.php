<?php

namespace Drupal\Tests\taxonomy_diff_ui\Functional;

use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\Tests\taxonomy\Functional\TaxonomyTestBase;

/**
 * Tests the taxonomy term revision access permissions.
 *
 * @group entity diff ui
 */
class TermDiffAccessTest extends TaxonomyTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'taxonomy',
    'block',
    'entity_diff_ui',
    'taxonomy_diff_ui',
  ];

  /**
   * Tests access control functionality for taxonomy revisions.
   */
  public function testTermDiffAccess() {
    $assert_session = $this->assertSession();
    $vocabulary = $this->createVocabulary();

    // Create two terms.
    $published_term = Term::create([
      'vid' => $vocabulary->id(),
      'name' => 'Published term',
      'status' => 1,
    ]);
    $published_term->save();
    $unpublished_term = Term::create([
      'vid' => $vocabulary->id(),
      'name' => 'Unpublished term',
      'status' => 0,
    ]);
    $unpublished_term->save();

    // Start off logged in as admin.
    $this->drupalLogin($this->drupalCreateUser([
      'administer taxonomy',
      'view all taxonomy term revisions',
    ]));

    // Test the 'administer taxonomy' permission.
    $this->drupalGet('taxonomy/term/' . $published_term->id() . '/revisions');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('taxonomy/term/' . $unpublished_term->id() . '/revisions');
    $assert_session->statusCodeEquals(200);

    // Test the 'access content' permission.
    $this->drupalLogin($this->drupalCreateUser(['access content']));
    $this->drupalGet('taxonomy/term/' . $published_term->id() . '/revisions');
    $assert_session->statusCodeEquals(403);
    $this->drupalGet('taxonomy/term/' . $unpublished_term->id() . '/revisions');
    $assert_session->statusCodeEquals(403);
  }

}
