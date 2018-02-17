<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Timesheet extends Admin_controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('invoice_items_model');
        $this->load->model('attendance_model');
    }

    /* List all available items */
    public function index()
    {
        if (!has_permission('items', '', 'view')) {
            access_denied('Invoice Items');
        }

        $this->load->model('taxes_model');
        $data['taxes']        = $this->taxes_model->get();
        $data['items_groups'] = $this->attendance_model->get_groups();

        $this->load->model('currencies_model');
        $data['currencies'] = $this->currencies_model->get();

        $data['base_currency'] = $this->currencies_model->get_base_currency();

        $data['title'] = _l('timesheet');
        $this->load->view('admin/timesheet/attendance', $data);
    }

    public function import()
    {
        if (!has_permission('customers', '', 'create')) {
            access_denied('customers');
        }

        $country_fields = array('country', 'billing_country', 'shipping_country');
        $simulate_data  = array();
        $total_imported = 0;
        if ($this->input->post()) {

            // Used when checking existing company to merge contact
            
            $contactFields = $this->db->list_fields('tblcontacts');

            if (isset($_FILES['file_csv']['name']) && $_FILES['file_csv']['name'] != '') {
                // Get the temp file path
                $tmpFilePath = $_FILES['file_csv']['tmp_name'];
                // Make sure we have a filepath
                if (!empty($tmpFilePath) && $tmpFilePath != '') {
                    // Setup our new file path
                    $newFilePath = TEMP_FOLDER . $_FILES['file_csv']['name'];
                    if (!file_exists(TEMP_FOLDER)) {
                        mkdir(TEMP_FOLDER, 777);
                    }
                    if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                        $import_result = true;
                        $fd            = fopen($newFilePath, 'r');
                        $rows          = array();
                        while ($row = fgetcsv($fd)) {
                            $rows[] = $row;
                        }

                        $data['total_rows_post'] = count($rows);
                        fclose($fd);
                        if (count($rows) <= 1) {
                            set_alert('warning', 'Not enought rows for importing');
                            redirect(admin_url('clients/import'));
                        }
                        unset($rows[0]);
                        if ($this->input->post('simulate')) {
                            if (count($rows) > 500) {
                                set_alert('warning', 'Recommended splitting the CSV file into smaller files. Our recomendation is 500 row, your CSV file has ' . count($rows));
                            }
                        }
                        $client_contacts_fields = $this->db->list_fields('tblcontacts');
                        $i                      = 0;
                        foreach ($client_contacts_fields as $cf) {
                            if ($cf == 'phonenumber') {
                                $client_contacts_fields[$i] = 'contact_phonenumber';
                            }
                            $i++;
                        }
                        $db_temp_fields = $this->db->list_fields('tblclients');
                        $db_temp_fields = array_merge($client_contacts_fields, $db_temp_fields);
                        $db_fields      = array();
                        foreach ($db_temp_fields as $field) {
                            if (in_array($field, $this->not_importable_clients_fields)) {
                                continue;
                            }
                            $db_fields[] = $field;
                        }
                        $custom_fields = get_custom_fields('customers');
                        $_row_simulate = 0;

                        $required = array(
                            'firstname',
                            'lastname',
                            'email',
                        );

                        if (get_option('company_is_required') == 1) {
                            array_push($required, 'company');
                        }

                        foreach ($rows as $row) {
                            // do for db fields
                            $insert    = array();
                            $duplicate = false;
                            for ($i = 0; $i < count($db_fields); $i++) {
                                if (!isset($row[$i])) {
                                    continue;
                                }
                                if ($db_fields[$i] == 'email') {
                                    $email_exists = total_rows('tblcontacts', array(
                                        'email' => $row[$i],
                                    ));
                                    // don't insert duplicate emails
                                    if ($email_exists > 0) {
                                        $duplicate = true;
                                    }
                                }
                                // Avoid errors on required fields;
                                if (in_array($db_fields[$i], $required) && $row[$i] == '' && $db_fields[$i] != 'company') {
                                    $row[$i] = '/';
                                } elseif (in_array($db_fields[$i], $country_fields)) {
                                    if ($row[$i] != '') {
                                        if (!is_numeric($row[$i])) {
                                            $this->db->where('iso2', $row[$i]);
                                            $this->db->or_where('short_name', $row[$i]);
                                            $this->db->or_where('long_name', $row[$i]);
                                            $country = $this->db->get('tblcountries')->row();
                                            if ($country) {
                                                $row[$i] = $country->country_id;
                                            } else {
                                                $row[$i] = 0;
                                            }
                                        }
                                    } else {
                                        $row[$i] = 0;
                                    }
                                }
                                $insert[$db_fields[$i]] = $row[$i];
                            }

                            if ($duplicate == true) {
                                continue;
                            }
                            if (count($insert) > 0) {
                                $total_imported++;
                                $insert['datecreated'] = date('Y-m-d H:i:s');
                                if ($this->input->post('default_pass_all')) {
                                    $insert['password'] = $this->input->post('default_pass_all',false);
                                }
                                if (!$this->input->post('simulate')) {
                                    $insert['donotsendwelcomeemail'] = true;
                                    foreach ($insert as $key =>$val) {
                                        $insert[$key] = trim($val);
                                    }

                                    if (isset($insert['company']) && $insert['company'] != '' && $insert['company'] != '/') {
                                        if (total_rows('tblclients', array('company'=>$insert['company'])) === 1) {
                                            $this->db->where('company', $insert['company']);
                                            $existingCompany = $this->db->get('tblclients')->row();
                                            $tmpInsert = array();

                                            foreach ($insert as $key=>$val) {
                                                foreach ($contactFields as $tmpContactField) {
                                                    if (isset($insert[$tmpContactField])) {
                                                        $tmpInsert[$tmpContactField] = $insert[$tmpContactField];
                                                    }
                                                }
                                            }
                                            $tmpInsert['donotsendwelcomeemail'] = true;
                                            if (isset($insert['contact_phonenumber'])) {
                                                $tmpInsert['phonenumber'] = $insert['contact_phonenumber'];
                                            }

                                            $contactid = $this->clients_model->add_contact($tmpInsert, $existingCompany->userid, true);

                                            continue;
                                        }
                                    }
                                    $insert['is_primary'] = 1;

                                    $clientid                        = $this->clients_model->add($insert, true);
                                    if ($clientid) {
                                        if ($this->input->post('groups_in[]')) {
                                            $groups_in = $this->input->post('groups_in[]');
                                            foreach ($groups_in as $group) {
                                                $this->db->insert('tblcustomergroups_in', array(
                                                    'customer_id' => $clientid,
                                                    'groupid' => $group,
                                                ));
                                            }
                                        }
                                        if (!has_permission('customers', '', 'view')) {
                                            $assign['customer_admins']   = array();
                                            $assign['customer_admins'][] = get_staff_user_id();
                                            $this->clients_model->assign_admins($assign, $clientid);
                                        }
                                    }
                                } else {
                                    foreach ($country_fields as $country_field) {
                                        if (array_key_exists($country_field, $insert)) {
                                            if ($insert[$country_field] != 0) {
                                                $c = get_country($insert[$country_field]);
                                                if ($c) {
                                                    $insert[$country_field] = $c->short_name;
                                                }
                                            } elseif ($insert[$country_field] == 0) {
                                                $insert[$country_field] = '';
                                            }
                                        }
                                    }
                                    $simulate_data[$_row_simulate] = $insert;
                                    $clientid                      = true;
                                }
                                if ($clientid) {
                                    $insert = array();
                                    foreach ($custom_fields as $field) {
                                        if (!$this->input->post('simulate')) {
                                            if ($row[$i] != '') {
                                                $this->db->insert('tblcustomfieldsvalues', array(
                                                    'relid' => $clientid,
                                                    'fieldid' => $field['id'],
                                                    'value' => $row[$i],
                                                    'fieldto' => 'customers',
                                                ));
                                            }
                                        } else {
                                            $simulate_data[$_row_simulate][$field['name']] = $row[$i];
                                        }
                                        $i++;
                                    }
                                }
                            }
                            $_row_simulate++;
                            if ($this->input->post('simulate') && $_row_simulate >= 100) {
                                break;
                            }
                        }
                        unlink($newFilePath);
                    }
                } else {
                    set_alert('warning', _l('import_upload_failed'));
                }
            }
        }
        if (count($simulate_data) > 0) {
            $data['simulate'] = $simulate_data;
        }
        if (isset($import_result)) {
            set_alert('success', _l('import_total_imported', $total_imported));
        }
        $data['groups']         = $this->clients_model->get_groups();
        $data['not_importable'] = $this->not_importable_clients_fields;
        $data['title']          = _l('import');
        $data['bodyclass'] = 'dynamic-create-groups';
        $this->load->view('admin/timesheet/import', $data);
    }

    public function table(){
        if (!has_permission('items', '', 'view')) {
            ajax_access_denied();
        }
        $this->perfex_base->get_table_data('attendance');
    }
    /* Edit or update items / ajax request /*/
    public function manage()
    {
        if (has_permission('items', '', 'view')) {
            if ($this->input->post()) {
                $data = $this->input->post();
                if ($data['itemid'] == '') {
                    if (!has_permission('items', '', 'create')) {
                        header('HTTP/1.0 400 Bad error');
                        echo _l('access_denied');
                        die;
                    }
                    $id      = $this->attendance_model->add($data);
                    $success = false;
                    $message = '';
                    if ($id) {
                        $success = true;
                        $message = _l('added_successfully', _l('invoice_item'));
                    }
                    echo json_encode(array(
                        'success' => $success,
                        'message' => $message,
                        'item' => $this->attendance_model->get($id)
                    ));
                } else {
                    if (!has_permission('items', '', 'edit')) {
                        header('HTTP/1.0 400 Bad error');
                        echo _l('access_denied');
                        die;
                    }
                    $success = $this->attendance_model->edit($data);
                    $message = '';
                    if ($success) {
                        $message = _l('updated_successfully', _l('invoice_item'));
                    }
                    echo json_encode(array(
                        'success' => $success,
                        'message' => $message
                    ));
                }
            }
        }
    }

    public function add_group()
    {
        if ($this->input->post() && has_permission('items', '', 'create')) {
            $this->invoice_items_model->add_group($this->input->post());
            set_alert('success', _l('added_successfully', _l('item_group')));
        }
    }

    public function update_group($id)
    {
        if ($this->input->post() && has_permission('items', '', 'edit')) {
            $this->invoice_items_model->edit_group($this->input->post(), $id);
            set_alert('success', _l('updated_successfully', _l('item_group')));
        }
    }

    public function delete_group($id)
    {
        if (has_permission('items', '', 'delete')) {
            if ($this->invoice_items_model->delete_group($id)) {
                set_alert('success', _l('deleted', _l('item_group')));
            }
        }
        redirect(admin_url('timesheet?groups_modal=true'));
    }

    /* Delete item*/
    public function delete($id)
    {
        if (!has_permission('items', '', 'delete')) {
            access_denied('Invoice Items');
        }

        if (!$id) {
            redirect(admin_url('timesheet'));
        }

        $response = $this->attendance_model->delete($id);
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('invoice_item_lowercase')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('invoice_item')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('invoice_item_lowercase')));
        }
        redirect(admin_url('timesheet'));
    }

    public function search(){
        if($this->input->post() && $this->input->is_ajax_request()){
            echo json_encode($this->invoice_items_model->search($this->input->post('q')));
        }
    }

    /* Get item by id / ajax */
    public function get_item_by_id($id)
    {
        if ($this->input->is_ajax_request()) {
            $item                   = $this->attendance_model->get($id);
            //$item->long_description = nl2br($item->long_description);
            echo json_encode($item);
        }
    }
}
