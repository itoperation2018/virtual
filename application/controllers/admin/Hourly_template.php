<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Hourly_template extends Admin_controller
{
  public function __construct()
    {
        parent::__construct();
        $this->load->model('hourly_template_model');
    }

	 public function index()
    {
       
        $this->load->view('admin/payroll/hourly_template', $data);
    }

    public function table(){
        if (!has_permission('items', '', 'view')) {
            ajax_access_denied();
        }
        $this->perfex_base->get_table_data('hourly_template');
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
                    $id      = $this->hourly_template_model->add($data);
                    $success = false;
                    $message = '';
                    if ($id) {
                        $success = true;
                        $message = _l('added_successfully', _l('invoice_item'));
                    }
                    echo json_encode(array(
                        'success' => $success,
                        'message' => $message,
                        'item' => $this->hourly_template_model->get($id)
                    ));
                } else {
                    if (!has_permission('items', '', 'edit')) {
                        header('HTTP/1.0 400 Bad error');
                        echo _l('access_denied');
                        die;
                    }
                    $success = $this->hourly_template_model->edit($data);
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

        $response = $this->hourly_template_model->delete($id);
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
            $item                   = $this->hourly_template_model->get($id);
            //$item->long_description = nl2br($item->long_description);
            echo json_encode($item);
        }
    }
}
