<?php
namespace Drupal\dyniva_migrate\Commands;
use Drupal\Core\Utility\Token;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;
use Drush\Utils\StringUtils;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Yaml\Yaml;

/**
 * For commands that are parts of modules, Drush expects to find commandfiles in
 * __MODULE__/src/Commands, and the namespace is Drupal/__MODULE__/Commands.
 *
 * In addition to a commandfile like this one, you need to add a drush.services.yml
 * in root of your module like this module does.
 */
class MigrateCommands extends DrushCommands {

  protected $token;

  protected $container;

  protected $eventDispatcher;

  protected $moduleHandler;

  public function __construct(Token $token, $container, $eventDispatcher, $moduleHandler) {
    parent::__construct();
    $this->token = $token;
    $this->container = $container;
    $this->eventDispatcher = $eventDispatcher;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   */
  public function getModuleHandler() {
    return $this->moduleHandler;
  }

  /**
   * @return mixed
   */
  public function getEventDispatcher() {
    return $this->eventDispatcher;
  }

  /**
   * @return mixed
   */
  public function getContainer() {
    return $this->container;
  }

  /**
   * @return Token
   */
  public function getToken() {
    return $this->token;
  }

  /**
   * 生成配置文件.
   *
   * @command dyniva_migrate:gen-config
   * @param $entityType
   * @param $bundle
   * @aliases mgc,migrate-gen-config
   * @usage drush migrate-gen-config commerce_product project
   *   生成配置文件.
   */
  public function genConfig($entityType, $bundle) {
    $config = [
      'id' => "{$entityType}_{$bundle}",
      'label' => "$entityType $bundle import",
      'migration_group' => 'dyniva',
      'source' => [
        'plugin' => 'batch',
        'header_row_count' => 1,
        'keys' => ['ID']
      ],
      'destination' => [
        'plugin' => 'entity:'.$entityType,
        'default_bundle' => $bundle
      ],
      'process' => [
        'uid' => [
          'plugin' => 'default_value',
          'default_value' => 1
        ],
        'title' => [
          'plugin' => 'skip_on_empty',
          'method' => 'row',
          'source' => 'Title'
        ],
        'status' => [
          'plugin' => 'default_value',
          'default_value' => 1
        ],
      ]
    ];

    $miss = [];
    $fieldDefinitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($entityType, $bundle);
    foreach($fieldDefinitions as $fieldDefinition) {
      if($fieldDefinition instanceof \Drupal\field\Entity\FieldConfig) {
        switch($fieldDefinition->getType()) {
          case 'entity_reference':
            $process = [];
            $process []= [
              'plugin' => 'skip_on_empty',
              'method' => 'process',
              'source' =>$fieldDefinition->label()
            ];
            $handler = $fieldDefinition->getSetting('handler');
            if(strpos($handler, 'default:') === 0) {
              $_bundle = '?';
              $_entityType = explode(':', $handler)[1];
              $handler_settings = $fieldDefinition->getSetting('handler_settings');
              $_bundle = $handler_settings['target_bundles'];
              if($_bundle) {
                $_bundle = reset($_bundle);
              }
              $process []= [
                'plugin' => 'entity_generate',
                'value_key' => $_entityType == 'taxonomy_term' ? 'name' : 'title',
                'bundle_key' => \Drupal::entityTypeManager()->getDefinition($_entityType)->getKey('bundle'),
                'bundle' => $_bundle,
                'entity_type' => $_entityType,
                'ignore_case' => true
              ];
            } else {
              $process []= [
                'plugin' => 'entity_lookup',
                'value_key' => 'label',
                'bundle_key' => '?',
                'bundle' => '?',
                'entity_type' => '?',
                'ignore_case' => true
              ];
            }

            $config['process'][$fieldDefinition->getName()] = $process;
            break;
          case 'text_with_summary':
            $config['process'][$fieldDefinition->getName().'/format'] = [
              'plugin' => 'default_value',
              'default_value' => 'rich_text'
            ];
            $config['process'][$fieldDefinition->getName().'/value'] = [
              'plugin' => 'clean_style',
              'source' => $fieldDefinition->label()
            ];
            break;
          case 'address':
            $config['process'][$fieldDefinition->getName().'/country_code'] = [
              'plugin' => 'default_value',
              'default_value' => 'CN'
            ];
            $config['process'][$fieldDefinition->getName().'/administrative_area'] = [
              'plugin' => 'address_administrative_area',
              'country_code' => 'CN',
              'source' => 'State'
            ];
            $config['process'][$fieldDefinition->getName().'/locality'] = 'Suburb';
            $config['process'][$fieldDefinition->getName().'/address_line1'] = 'Street address';
            $config['process'][$fieldDefinition->getName().'/postal_code'] = 'Postal code';
            break;
          case 'image':
            $config['process'][$fieldDefinition->getName()] = [[
                'plugin' => 'skip_on_empty',
                'method' => 'process',
                'source' => $fieldDefinition->label()
              ],[
                'plugin' => 'explode',
                'delimiter' => ','
              ],[
                'plugin' => 'media_library',
                'bundle' => 'image'
              ]
            ];
            break;
          case 'file':
            $config['process'][$fieldDefinition->getName()] = [[
                'plugin' => 'skip_on_empty',
                'method' => 'process',
                'source' => $fieldDefinition->label()
              ],[
                'plugin' => 'explode',
                'delimiter' => ','
              ],[
                'plugin' => 'media_library',
                'bundle' => 'document'
              ]
            ];
            break;
          case 'datetime':
            $config['process'][$fieldDefinition->getName()] = [
              'plugin' => 'excel_date',
              'type' => 'string',
              'source' => $fieldDefinition->label()
            ];
            break;
          case 'commerce_price':
            $config['process'][$fieldDefinition->getName().'/currency_code'] = [
              'plugin' => 'default_value',
              'default_value' => 'AUD'
            ];
            $config['process'][$fieldDefinition->getName().'/number'] = $fieldDefinition->label();
            break;
          case 'telephone':
          case 'string_long':
          case 'boolean':
          case 'string':
          case 'decimal':
          case 'integer':
          case 'float':
          case 'list_string':
            $config['process'][$fieldDefinition->getName()] = $fieldDefinition->label();
            break;
          case 'geofield':
            // pass
            break;
          default:
            $miss []= $fieldDefinition->getType();
        }
      }
    }

    $yaml = Yaml::dump($config, 10,2);
    $this->output()->writeln($yaml);
    if($miss) {
      foreach($miss as $type) {
        $this->output()->write($type.' | ');
      }
      $this->output()->writeln( 'is missing.');
    }
  }


  /**
   * 生成Demo文件.
   * @command dyniva_migrate:gen-demo
   * @param $plugin_id
   * @aliases mgd,migrate-gen-demo
   * @usage drush migrate-gen-demo commerce_product_project
   *   生成配置文件.
   */
  public function genDemoFile($plugin_id) {
      $manager = \Drupal::service('plugin.manager.migration');
      /*
       * @var \Drupal\migrate\Plugin\Migration $migration
       */
      $migration = $manager->createInstance($plugin_id);
  }
}
