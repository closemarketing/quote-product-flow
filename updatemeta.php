<?php

define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

echo 'iniciado proceso.<br />';

$svariations = get_posts('posts_per_page=-1&post_type=variation&meta_key=pbc_imgprodgroup&fields=ids');
if(!empty($svariations)){
   foreach($svariations as $varId){
       $pbc_imgprodgroup = get_post_meta($varId, 'pbc_imgprodgroup', true);
       if(!empty($pbc_imgprodgroup)){
           foreach($pbc_imgprodgroup as $key => $values){
               if(isset($values['pbc_depvarimgprod']) && !is_array($values['pbc_depvarimgprod'])){
                   $pbc_imgprodgroup[$key]['pbc_depvarimgprod'] = array($values['pbc_depvarimgprod']);
               }
           }
       }
       update_post_meta($varId, 'pbc_imgprodgroup', $pbc_imgprodgroup);
   }
}
echo 'finalizado proceso.<br />';
