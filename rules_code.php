<?php
function reduce_design_office_quota_create_project($node) {
  $area = $node->field_text19_1_value['und'][0]['value'];
  $target_id = $node->og_group_ref2['und'][0]['target_id'];
  if (isset($target_id)) {
    $design_office = node_load($target_id);
    $old_area = $design_office->field_integer1_1_value['und'][0]['value'];
    $design_office->field_integer1_1_value['und'][0]['value'] = $old_area - $area;
    node_save($design_office);
  }
}


function reduce_design_office_quota_edit_project($node, $node_unchanged) {
  $old_area = $node_unchanged->field_text19_1_value['und'][0]['value'];
  $area = $node->field_text19_1_value['und'][0]['value'];
  if ($old_area != $area) {
    $target_id = $node->og_group_ref2['und'][0]['target_id'];
    
    if (isset($target_id)) {
      $design_office = node_load($target_id);
      $office_area = $design_office->field_integer1_1_value['und'][0]['value'];
      $design_office->field_integer1_1_value['und'][0]['value'] = $office_area + $old_area - $area;
      node_save($design_office);
    }
  }
}
//__________________________________ map event
function reduce_design_office_quota($event, $node, $node_unchanged) {
  //event can be 'create' or 'edit'
  $municipility_status = $node->field_municipility_status['und'][0]['value'];
  if ($municipility_status == 'verified') {
    $old_reduced_quota = 0;
    if ($event == 'create') {
      $project_id = $node->og_group_ref['und'][0]['target_id'];
//      dpm('befor load project');
      $project = node_load($project_id);
//      dpm('after load project');
      $old_reduced_quota = $project->field_text19_1_value['und'][0]['value'];
//      dpm($old_reduced_quota,'old reduce');
    }
    else {
//      dpm($event,'eeeeeeeeeeeeeeeeeeeeeeeeee');
      $old_reduced_quota = $node_unchanged->field_integer1_1_value['und'][0]['value'];
    }
//    dpm($node,'in edit map structure');
    $total_area = $node->field_integer1_1_value['und'][0]['value'];
    $design_office_id = $project->og_group_ref2['und'][0]['target_id'];
    $design_office = node_load($design_office_id);
    $current_quota = $design_office->field_integer1_1_value['und'][0]['value'];
    $design_office->field_integer1_1_value['und'][0]['value'] = ($current_quota + $old_reduced_quota) - $total_area;
    node_save($design_office);
  }
}
//__________________________________________map event
function set_structure_group($node, $unchanged_node) {

  $municipility_status = $node->field_municipility_status['und'][0]['value'];
  if ($municipility_status == 'verified') {
    $total_area = $node->field_integer1_1_value['und'][0]['value'];
    $number_of_floor = _get_number_of_floor($node->field_nosazi_karbari_mojood);
    $structure_type = $node->field_nosazi_saze_type['und'][0]['tid'];

    $tid = 19; // the configuration term_id
    $term = taxonomy_term_load($tid);

    $result_value = $term->field_nosazi_structure_group['und'][0]['tid']; // set the defaul structure group for unknown configuration
    foreach ($term->field_nosazi_settings['und'] as $key => $value) {
      $item_id = $value['value'];
      $item = entity_load_single('field_collection_item', $item_id);
      $saze_type_cfg = $item->field_nosazi_saze_type['und'][0]['tid'];
      $zirbana_from = $item->field_integer1_1_value['und'][0]['value'];
      $zirbana_to = $item->field_integer2_1_value['und'][0]['value'];
      $number_of_floor_cfg = $item->field_integer3_1_value['und'][0]['value'];
      $structure_group_cfg = $item->field_nosazi_structure_group['und'][0]['tid'];
//  dpm($zirbana,'zirbana');
//  dpm($zirbana_from,'zirbana_from');
//  dpm($zirbana_to,'zirbana_to');
      if ($total_area >= $zirbana_from && $total_area < $zirbana_to) {
//        dpm($saze_type, '$saze_type');
//    dpm($saze_type_cfg,'$saze_type_cfg');
        if ($structure_type == $saze_type_cfg) {
//          dpm($number_of_floor, '$number_of_floor');
//      dpm($number_of_floor_cfg,'$number_of_floor_cfg');
          if ($number_of_floor == $number_of_floor_cfg) {
            $result_value = $structure_group_cfg;
            break;
          }
        }
      }
    }
//    dpm($result_value,'structure group');
    $og_id = $node->og_group_ref['und'][0]['target_id'];
//    dpm($og_id,'og_id');
    //get project object to update structure group field
    $og_node = node_load($og_id);
    $og_node->field_nosazi_structure_group['und'][0]['tid'] = $result_value;
    node_save($og_node);
  }
}

function _get_number_of_floor($field) {
  if (!isset($field['und'])) {
    return 0;
  }
  $has_half_floor = false;
  $cnt = 0;
  foreach ($field['und'] as $item) {
    $row_id = $item['value'];
    $row = entity_load_single('field_collection_item', $row_id);
    $roof_type = $row->field_roof_type['und'][0]['value'];
    if ($roof_type == 'half_floor ') {
      $has_half_floor = TRUE;
    }
    $cnt++;
  }
  if ($has_half_floor) {
    return $cnt - 1;
  }
  return $cnt;
}

?>
