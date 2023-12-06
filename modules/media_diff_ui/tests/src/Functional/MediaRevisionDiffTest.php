<?php

namespace Drupal\Tests\media_diff_ui\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Tests\media\Functional\MediaFunctionalTestBase;
use Drupal\media\MediaInterface;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests the revisionability of media entities.
 *
 * @group media
 */
class MediaRevisionDiffTest extends MediaFunctionalTestBase {

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
    'system',
    'node',
    'field_ui',
    'views_ui',
    'media',
    'media_test_source',
    'entity_diff_ui',
    'media_diff_ui',
  ];

  /**
   * Checks media revision operations.
   */
  public function testRevisions() {
    $assert = $this->assertSession();

    /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $media_storage */
    $media_storage = $this->container->get('entity_type.manager')->getStorage('media');

    // Create a media type and media item.
    $media_type = $this->createMediaType('test');
    $media = $media_storage->create([
      'bundle' => $media_type->id(),
      'name' => 'Unnamed',
    ]);
    $media->save();

    // Anonymouse must not have access to revision overview page.
    $this->drupalGet('media/' . $media->id() . '/revisions');
    $assert->statusCodeEquals(403);

    // Create some revisions.
    $media_revisions = [];
    $media_revisions[] = clone $media;
    $revision_count = 3;
    for ($i = 0; $i < $revision_count; $i++) {
      $media->revision_log = $this->randomMachineName(32);
      $media = $this->createMediaRevision($media);
      $media_revisions[] = clone $media;
    }

    // Get the last revision for simple checks.
    /** @var \Drupal\media\MediaInterface $media */
    $media = end($media_revisions);

    // Test permissions.
    $this->drupalLogin($this->nonAdminUser);
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load(RoleInterface::AUTHENTICATED_ID);

    // Test 'view all media revisions' permission ('view media' permission is
    // needed as well).
    user_role_revoke_permissions($role->id(), [
      'view media',
      'view all media revisions',
    ]);
    $this->drupalGet('media/' . $media->id() . '/revisions/' . $media->getRevisionId() . '/view');
    $assert->statusCodeEquals(403);
    $this->grantPermissions($role, ['view media', 'view all media revisions']);
    $this->drupalGet('media/' . $media->id() . '/revisions/' . $media->getRevisionId() . '/view');
    $assert->statusCodeEquals(200);

    // Confirm the revision page shows the correct title.
    $assert->pageTextContains($media->getName());

    // Confirm that the last revision is the default revision.
    $this->assertTrue($media->isDefaultRevision(), 'Last revision is the default.');
  }

  /**
   * Creates a new revision for a given media item.
   *
   * @param \Drupal\media\MediaInterface $media
   *   A media object.
   *
   * @return \Drupal\media\MediaInterface
   *   A media object with up to date revision information.
   */
  protected function createMediaRevision(MediaInterface $media) {
    $media->setName($this->randomMachineName());
    $media->setNewRevision();
    $media->save();
    return $media;
  }

  /**
   * Asserts that an entity has a certain number of revisions.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity in question.
   * @param int $expected_revisions
   *   The expected number of revisions.
   *
   * @internal
   */
  protected function assertRevisionCount(EntityInterface $entity, int $expected_revisions): void {
    $entity_type = $entity->getEntityType();

    $count = $this->container
      ->get('entity_type.manager')
      ->getStorage($entity_type->id())
      ->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->allRevisions()
      ->condition($entity_type->getKey('id'), $entity->id())
      ->execute();

    $this->assertSame($expected_revisions, (int) $count);
  }

}
