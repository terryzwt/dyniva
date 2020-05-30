<?php
namespace Drupal\dyniva_content_moderation\Plugin\IPEAccess;

use Drupal\dyniva_core\Plugin\ManagedEntityPluginBase;
use Drupal\dyniva_core\Entity\ManagedEntity;
use Drupal\Core\Entity\EntityInterface;
use Drupal\panels_ipe\Plugin\IPEAccessBase;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;
use Drupal\node\Entity\Node;
use Drupal\Core\Access\AccessResult;

/**
 * IPEAccess Plugin.
 * 
 * @IPEAccess(
 *  id = "ccms_node_access",
 *  label = @Translation("Ccms node access"),
 *  weight = 1
 * )
 *
 */
class NodeIpeAccess extends IPEAccessBase{
  /**
   * @inheritdoc
   */
  public function applies(PanelsDisplayVariant $display){
    $contexts = $display->getContexts();
    return isset($contexts['@panelizer.entity_context:entity']);
  }
  
  /**
   * @inheritdoc
   */
  public function access(PanelsDisplayVariant $display){
//     $contexts = $display->getContexts();
    
//     $entity = $contexts['@panelizer.entity_context:entity']->getContextValue();
//     if($entity instanceof Node){
//       if(!empty($entity->moderation_state->value)){
//         if($entity->moderation_state->value == 'draft'){
//           return TRUE;
//         }
//       }else{
//         return TRUE;
//       }
//     }
    return TRUE;
  }
}
