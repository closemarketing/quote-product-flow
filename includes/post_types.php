<?php
/**
 * Post Types needed to make work the configurator
 *
 * Register all post types. Phases and variations.
 *
 * @author   closemarketing
 * @category Post Types
 * @package  Product Budget Configurator
 * @version  1.0
 */

 /**
  * Phases
  **/

 add_action('init', 'pbc_cpt_phases');
 function pbc_cpt_phases()
 {
   $nametype = 'Producto';

   $labels = array(
     'name' => $nametype.'s',
     'singular_name' => $nametype,
     'add_new' => 'Añadir '.$nametype,
     'add_new_item' => 'Añadir Nuevo '.$nametype,
     'edit_item' => 'Editar '.$nametype,
     'new_item' => 'Nuevo '.$nametype,
     'view_item' => 'Ver '.$nametype,
     'search_items' => 'Buscar '.$nametype.'s',
     'not_found' =>  'No hemos encontrado ningún '.$nametype,
     'not_found_in_trash' => 'No hemos encontrado ningún '.$nametype.' en la papelera',
   );
   $args = array(
     'labels' => $labels,
     'public' => true,
     'publicly_queryable' => true,
     'show_ui' => true,
     'query_var' => true,
      'rewrite' => array(
                'slug' => 'ladrillos-ceramicas',
                'with_front' => 'true'
                ),
      'has_archive' => false,
     'capability_type' => 'page',
     'hierarchical' => true,
     'menu_position' => 5,
     'supports' => array('title','editor','thumbnail','excerpt','page-attributes'),
     'menu_icon' => 'dashicons-tagcloud'
   );
   register_post_type('phases',$args);

 }
