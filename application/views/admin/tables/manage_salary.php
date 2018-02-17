<?php

defined('BASEPATH') OR exit('No direct script access allowed');

$aColumns     = array(
    'salary_grade',
    'basic_salary',
    'overtime_rate',
    'house_rent_allowance',
    'medicle_allowance',
    'provident_fund',
    'tax_deduction',
    );

// // Get date from array and create DateTime Object
// $date = new DateTime($aColumns['date_in']);

// // Change date format and output
// $aColumns['date_in'] = $date->format('Y-m-d');

$sIndexColumn = "id";

$sTable       = 'salary_template';

// $join         = array(
//     'LEFT JOIN tbltaxes t1 ON t1.id = tblitems.tax',
//     'LEFT JOIN tbltaxes t2 ON t2.id = tblitems.tax2',
//     'LEFT JOIN tblitems_groups ON tblitems_groups.id = tblitems.group_id'
//     );

$additionalSelect = array(
    'salary_template.id',
    );

$result =  data_tables_init($aColumns, $sIndexColumn, $sTable, $join, array(), $additionalSelect);

$output           = $result['output'];
$rResult          = $result['rResult'];

foreach ($rResult as $aRow) {

    $row = array();

    for ($i = 0; $i < count($aColumns); $i++) {
    
       $_data = $aRow[$aColumns[$i]];

       if($aColumns[$i] == 'date_in'){
            
            $date = DateTime::createFromFormat('Y-m-d H:i:s', $_data);
            $_data = $date->format('Y-m-d');

            $_data = '<a href="#" data-toggle="modal" data-target="#add_attend_modal" data-id="'.$aRow['id'].'">'.$_data.'</a>';    
        }

         if($aColumns[$i] == 'date_out'){
            
            $date = DateTime::createFromFormat('Y-m-d H:i:s', $_data);
            $_data = $date->format('Y-m-d');
       
        }

         if($aColumns[$i] == 'time_in'){
            
          $date = DateTime::createFromFormat('Y-m-d H:i:s', $_data);
            $_data = $date->format('H:i:s a');
       
        }

         if($aColumns[$i] == 'time_out'){
            
            $date = DateTime::createFromFormat('Y-m-d H:i:s', $_data);
            $_data = $date->format('H:i:s a');
       
        }

        $row[] = $_data;
    }

    $options = '';
    if(has_permission('items','','edit')){
        $options .= icon_btn('#' . $aRow['id'], 'pencil-square-o', 'btn-default', array(
            'data-toggle' => 'modal',
            'data-target' => '#salary_template_modal',
            'data-id' => $aRow['id'],
            ));
    }
    if(has_permission('items','','delete')){
       $options .= icon_btn('payroll/delete/' . $aRow['id'], 'remove', 'btn-danger _delete');
   }
   $row[] = $options;

   $output['aaData'][] = $row;
}
