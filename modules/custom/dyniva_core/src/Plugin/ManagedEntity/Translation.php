<?php

namespace Drupal\dyniva_core\Plugin\ManagedEntity;

use Drupal\dyniva_core\Plugin\ManagedEntityPluginBase;
use Drupal\dyniva_core\Entity\ManagedEntity;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\content_translation\ContentTranslationManager;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Language\LanguageInterface;

/**
 * ManagedEntity Plugin.
 *
 * @ManagedEntityPlugin(
 *  id = "translation",
 *  label = @Translation("Translation"),
 *  weight = 0
 * )
 */
class Translation extends ManagedEntityPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildPage(ManagedEntity $managedEntity, EntityInterface $entity) {
    $entity_type_id = $entity->getEntityTypeId();
    $account = \Drupal::currentUser();
    $handler = \Drupal::entityTypeManager()->getHandler($entity_type_id, 'translation');
    $manager = \Drupal::service('content_translation.manager');
    $entity_type = $entity->getEntityType();
    $use_latest_revisions = $entity_type->isRevisionable() && ContentTranslationManager::isPendingRevisionSupportEnabled($entity_type_id, $entity->bundle());

    // Start collecting the cacheability metadata, starting with the entity and
    // later merge in the access result cacheability metadata.
    $cacheability = CacheableMetadata::createFromObject($entity);

    $languages = \Drupal::languageManager()->getLanguages();
    $original = $entity->getUntranslated()->language()->getId();
    $translations = $entity->getTranslationLanguages();
    $field_ui = \Drupal::moduleHandler()->moduleExists('field_ui') && $account->hasPermission('administer ' . $entity_type_id . ' fields');

    $rows = [];
    $show_source_column = FALSE;
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
    $default_revision = $storage->load($entity->id());

    if (\Drupal::languageManager()->isMultilingual()) {
      // Determine whether the current entity is translatable.
      $translatable = FALSE;
      foreach (\Drupal::entityManager()->getFieldDefinitions($entity_type_id, $entity->bundle()) as $instance) {
        if ($instance->isTranslatable()) {
          $translatable = TRUE;
          break;
        }
      }

      // Show source-language column if there are non-original source langcodes.
      $additional_source_langcodes = array_filter(array_keys($translations), function ($langcode) use ($entity, $original, $manager) {
        $source = $manager->getTranslationMetadata($entity->getTranslation($langcode))->getSource();
        return $source != $original && $source != LanguageInterface::LANGCODE_NOT_SPECIFIED;
      });
      $show_source_column = !empty($additional_source_langcodes);

      foreach ($languages as $language) {
        $language_name = $language->getName();
        $langcode = $language->getId();

        // If the entity type is revisionable, we may have pending revisions
        // with translations not available yet in the default revision. Thus we
        // need to load the latest translation-affecting revision for each
        // language to be sure we are listing all available translations.
        if ($use_latest_revisions) {
          $entity = $default_revision;
          $latest_revision_id = $storage->getLatestTranslationAffectedRevisionId($entity->id(), $langcode);
          if ($latest_revision_id) {
            /** @var \Drupal\Core\Entity\ContentEntityInterface $latest_revision */
            $latest_revision = $storage->loadRevision($latest_revision_id);
            // Make sure we do not list removed translations, i.e. translations
            // that have been part of a default revision but no longer are.
            if (!$latest_revision->wasDefaultRevision() || $default_revision->hasTranslation($langcode)) {
              $entity = $latest_revision;
            }
          }
          $translations = $entity->getTranslationLanguages();
        }

        $current_url = Url::fromRoute('<current>');
        $destination = $current_url->toString();

        $add_url = new Url(
          "dyniva_core.managed_entity.{$managedEntity->id()}.translation_add",
          [
            'source' => $original,
            'target' => $language->getId(),
            'managed_entity_id' => $entity->id(),
          ],
          [
            'language' => $language,
            'query' => ['destination' => $destination],
          ]
          );
        $edit_url = new Url(
          "dyniva_core.managed_entity.{$managedEntity->id()}.translation_edit",
          [
            'language' => $language->getId(),
            'managed_entity_id' => $entity->id(),
          ],
          [
            'language' => $language,
            'query' => ['destination' => $destination],
          ]
          );
        $delete_url = new Url(
          "dyniva_core.managed_entity.{$managedEntity->id()}.translation_delete",
          [
            'language' => $language->getId(),
            'managed_entity_id' => $entity->id(),
          ],
          [
            'language' => $language,
            'query' => ['destination' => $destination],
          ]
          );
        $operations = [
          'data' => [
            '#type' => 'operations',
            '#links' => [],
          ],
        ];

        $links = &$operations['data']['#links'];
        if (array_key_exists($langcode, $translations)) {
          // Existing translation in the translation set: display status.
          $translation = $entity->getTranslation($langcode);
          $metadata = $manager->getTranslationMetadata($translation);
          $source = $metadata->getSource() ?: LanguageInterface::LANGCODE_NOT_SPECIFIED;
          $is_original = $langcode == $original;
          $label = $entity->getTranslation($langcode)->label();
          $link = isset($links->links[$langcode]['url']) ? $links->links[$langcode] : ['url' => $entity->urlInfo()];
          if (!empty($link['url'])) {
            $link['url']->setOption('language', $language);
            $row_title = \Drupal::l($label, $link['url']);
          }

          if (empty($link['url'])) {
            $row_title = $is_original ? $label : t('n/a');
          }

          // If the user is allowed to edit the entity we point the edit link to
          // the entity form, otherwise if we are not dealing with the original
          // language we point the link to the translation form.
          $update_access = $entity->access('update', NULL, TRUE);
          $translation_access = $handler->getTranslationAccess($entity, 'update');
          $cacheability = $cacheability
            ->merge(CacheableMetadata::createFromObject($update_access))
            ->merge(CacheableMetadata::createFromObject($translation_access));
          if ($update_access->isAllowed() && $is_original) {
            $links['edit']['url'] = Url::fromRoute("dyniva_core.managed_entity.{$managedEntity->id()}.edit_page", ['managed_entity_id' => $entity->id()]);
            $links['edit']['language'] = $language;
          }
          elseif (!$is_original && $translation_access->isAllowed()) {
            $links['edit']['url'] = $edit_url;
          }
          if (isset($links['edit'])) {
            $links['edit']['title'] = t('Edit');
          }
          $status = [
            'data' => [
              '#type' => 'inline_template',
              '#template' => '<span class="status">{% if status %}{{ "Published"|t }}{% else %}{{ "Not published"|t }}{% endif %}</span>{% if outdated %} <span class="marker">{{ "outdated"|t }}</span>{% endif %}',
              '#context' => [
                'status' => $metadata->isPublished(),
                'outdated' => $metadata->isOutdated(),
              ],
            ],
          ];

          if ($is_original) {
            $language_name = t('<strong>@language_name (Original language)</strong>', ['@language_name' => $language_name]);
            $source_name = t('n/a');
          }
          else {
            /** @var \Drupal\Core\Access\AccessResultInterface $delete_route_access */
            $delete_route_access = \Drupal::service('content_translation.delete_access')->checkAccess($translation);
            $cacheability->addCacheableDependency($delete_route_access);

            if ($delete_route_access->isAllowed()) {
              $source_name = isset($languages[$source]) ? $languages[$source]->getName() : t('n/a');
              $delete_access = $entity->access('delete', NULL, TRUE);
              $translation_access = $handler->getTranslationAccess($entity, 'delete');
              $cacheability
                ->addCacheableDependency($delete_access)
                ->addCacheableDependency($translation_access);

              $links['delete'] = [
                'title' => t('Delete'),
                'url' => $delete_url,
              ];
            }
          }
        }
        else {
          // No such translation in the set yet: help user to create it.
          $row_title = $source_name = t('n/a');
          $source = $entity->language()->getId();

          $create_translation_access = $handler->getTranslationAccess($entity, 'create');
          $cacheability = $cacheability
            ->merge(CacheableMetadata::createFromObject($create_translation_access));
          if ($source != $langcode && $create_translation_access->isAllowed()) {
            if ($translatable) {
              $links['add'] = [
                'title' => t('Add'),
                'url' => $add_url,
              ];
            }
            elseif ($field_ui) {
              $url = new Url('language.content_settings_page');

              // Link directly to the fields tab to make it easier to find the
              // setting to enable translation on fields.
              $links['nofields'] = [
                'title' => t('No translatable fields'),
                'url' => $url,
              ];
            }
          }

          $status = t('Not translated');
        }
        if ($show_source_column) {
          $rows[] = [
            $language_name,
            $row_title,
            $source_name,
            $status,
            $operations,
          ];
        }
        else {
          $rows[] = [$language_name, $row_title, $status, $operations];
        }
      }
    }
    if ($show_source_column) {
      $header = [
        t('Language'),
        t('Translation'),
        t('Source language'),
        t('Status'),
        t('Operations'),
      ];
    }
    else {
      $header = [
        t('Language'),
        t('Translation'),
        t('Status'),
        t('Operations'),
      ];
    }

    $build['#title'] = t('Translations of %label', ['%label' => $entity->label()]);

    // Add metadata to the build render array to let other modules know about
    // which entity this is.
    $build['#entity'] = $entity;
    $cacheability
      ->addCacheTags($entity->getCacheTags())
      ->applyTo($build);

    $build['content_translation_overview'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getPageTitle(ManagedEntity $managedEntity, EntityInterface $entity) {
    return $entity->label() . ' ' . $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function isMenuTask(ManagedEntity $managedEntity) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isMenuAction(ManagedEntity $managedEntity) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function applyOperationLinks(ManagedEntity $managedEntity, EntityInterface $entity, &$operations) {
    $url = Url::fromRoute("dyniva_core.managed_entity.{$managedEntity->id()}.{$this->getPluginId()}_page", ['managed_entity_id' => $entity->id()], []);
    $access = \Drupal::accessManager()->checkNamedRoute($url->getRouteName(), $url->getRouteParameters());
    if ($access) {
      $operations[$this->getPluginId()] = [
        'title' => t('Translate'),
        'url' => $url,
        'weight' => 3,
        'attributes' => ['target' => '_blank'],
      ];
    }

    unset($operations['translate']);
  }

  /**
   * {@inheritdoc}
   */
  public function getPageRequirements(ManagedEntity $managedEntity) {
    return ['_custom_access' => "Drupal\dyniva_core\CcmsCoreEntityModerateAccess::entityAccess"];
  }

}
