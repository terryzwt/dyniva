<?php
## Copy from https://github.com/acquia/lightning/blob/8.x-4.x/src/Composer/ConfigureLegacyProject.php
## some little modify
namespace Davyin\Dyniva\Composer;

use Composer\Json\JsonFile;
use Composer\Script\Event;

/**
 * Configures an instance of drupal/legacy-project to install Lightning.
 */
final class ConfigureLegacyProject {

  /**
   * Executes the script.
   *
   * @param \Composer\Script\Event $event
   *   The script event.
   */
  public static function execute(Event $event) {
    $arguments = $event->getArguments();

    $target = new JsonFile($arguments[0] . '/composer.json');
    $project = $target->read();

    $required = $event->getComposer()->getPackage()->getRequires();
    $components = [
      "drupal/swiftmailer",
      "drupal/key_value",
      "drupal/replication",
      "drupal/workspace",
      "drupal/switch_page_theme",
      "drupal/content_lock",
      "drupal/paragraphs",
      "drupal/eck",
      "drupal/mailsystem",
      "drupal/message",
      "drupal/message_ui",
      "drupal/menu_multilingual",
      "drupal/fontawesome",
      "drupal/fontawesome_iconpicker",
      "drupal/responsive_preview",
      "drupal/calendar",
      "endroid/qr-code",
      "drupal/features",
      "drupal/better_exposed_filters",
      "drupal/chosen",
      "drupal/colorbox",
      "drupal/field_group",
      "drupal/flag",
      "drupal/footable",
      "drupal/menu_breadcrumb",
      "drupal/menu_trail_by_path",
      "drupal/migrate_plus",
      "drupal/migrate_source_csv",
      "drupal/migrate_spreadsheet",
      "drupal/migrate_tools",
      "drupal/select_or_other",
      "drupal/shs",
      "drupal/tour_ui",
      "drupal/views_infinite_scroll",
      "drupal/views_templates",
      "drupal/viewsreference",
      "drupal/multiselect",
      "drupal/field_permissions",
      "drupal/focal_point",
      "drupal/module_filter",
      "drupal/stage_file_proxy",
      "drupal/field_name_prefix_remove",
      "drupal/library",
      "drupal/admin_toolbar",
      "drupal/purge",
      "drupal/purge_purger_http",
      "oyejorge/less.php",
      "drupal/toolbar_visibility",
      "phpoffice/phpspreadsheet",
      "drupal/jsonapi_extras",
      "drupal/site_settings",
      "drupal/devel",
      "drupal/baidu_analytics",
      "drupal/google_analytics",
      "drupal/cas_attributes",
      "drupal/password_policy",
      "drupal/r4032login",
      "drupal/asset_injector",
      "php-http/guzzle6-adapter",
      "woohoolabs/yang",
      "drupal/context",
      "drupal/user_default_page",
      "drupal/search_api_solr",
      "drupal/facets",
      "drupal/search_api_autocomplete",
      "drupal/message_notify",
      "drupal/message_subscribe",
      "drupal/reroute_email",
      "drupal/redis",
      "drupal/block_class",
      "drupal/redirect",
      "drupal/allowed_formats",
      "drupal/confirm_leave",
      "drupal/password_encrypt",
      "drupal/styleguide",
      "drupal/linkit",
      "drupal/relaxed",
      "drupal/production_checklist",
      "drupal/page_load_progress",
      "drupal/locker",
      "drupal/autofill_fields",
      "drupal/ckeditor_uploadimage",
      "drupal/login_emailusername",
      "drupal/private_message",
      "drupal/ckeditor_font",
      "drupal/sms",
      "drupal/colorbutton",
      "drupal/devel_php",
      "overtrue/wechat",
      "drupal/scheduled_updates",
      "drupal/sitemap",
      "drupal/xmlsitemap",
      "drupal/config_ignore",
      "drupal/config_split",
      "drupal/config_installer",
      "drupal/config_pages",
      "drupal/menu_item_extras",
      "drupal/devel_entity_updates",
      "drupal/elasticsearch_connector",
      "drupal/views_entity_form_field",
      "drupal/views_bulk_operations",
      "drupal/matomo",
      "drupal/matomo_reporting_api",
      "drupal/entity_theme_engine",
      "drupal/ace_editor"
    ];
    foreach ($components as $component) {
      $project['require'][$component] = $required[$component]->getPrettyConstraint();
    }
    #unset($project['require']['drupal/core-composer-scaffold']);
    $project['repositories'][] = [
      'type' => 'composer',
      'url' => 'https://asset-packagist.org',
    ];
    $project['extra']['installer-paths']['libraries/{$name}'] = [
      'type:drupal-library',
      'type:bower-asset',
      'type:npm-asset',
    ];
    $project['extra']['installer-types'] = ['bower-asset', 'npm-asset'];
    $project['extra']['patchLevel']['drupal/core'] = '-p2';

    $target->write($project);
  }

}
