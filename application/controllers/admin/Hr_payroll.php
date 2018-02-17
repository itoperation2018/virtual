<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Hr_payroll extends Admin_controller
{
  public function __construct()
    {
        parent::__construct();
        $this->load->model('Salary_template_model');
    }

     public function index()
    {
       
        $this->load->view('admin/payroll/payroll_summary', $data);
    }



    public function manage_salary()
    {
        $data['title'] = _l('manage_salary');
        $this->load->view('admin/timesheet/attendance', $data);
    }

    public function generate_payslip()
    {
        $data['title'] = _l('manage_salary');
        $this->load->view('admin/payroll/generate_payslip', $data);
    }

     public function employee_salary()
    {
        $data['title'] = _l('employee_salary');
        $this->load->view('admin/payroll/employee_salary');
    }

    public function make_payment()
    {
        $data['title'] = _l('make_payment');
        $this->load->view('admin/payroll/make_payment', $data);
    }

    public function payroll_summary()
    {
        $data['title'] = _l('payroll_summary');
        $this->load->view('admin/payroll/payroll_summary', $data);
    }


     public function advance_salary()
    {
        $data['title'] = _l('advance_salary');
        $this->load->view('admin/payroll/advance_salary', $data);
    }

      public function provident_fund()
    {
        $data['title'] = _l('provident_fund');
        $this->load->view('admin/payroll/provident_fund', $data);
    }
      public function overtime()
    {
        $data['title'] = _l('overtime');
        $this->load->view('admin/payroll/overtime', $data);
    }


    public function table(){
        if (!has_permission('items', '', 'view')) {
            ajax_access_denied();
        }
        $this->perfex_base->get_table_data('salary_template');
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
                    $id      = $this->Salary_template_model->add($data);
                    $success = false;
                    $message = '';
                    if ($id) {
                        $success = true;
                        $message = _l('added_successfully', _l('invoice_item'));
                    }
                    echo json_encode(array(
                        'success' => $success,
                        'message' => $message,
                        'item' => $this->Salary_template_model->get($id)
                    ));
                } else {
                    if (!has_permission('items', '', 'edit')) {
                        header('HTTP/1.0 400 Bad error');
                        echo _l('access_denied');
                        die;
                    }
                    $success = $this->Salary_template_model->edit($data);
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

        $response = $this->Salary_template_model->delete($id);
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
            $item                   = $this->Salary_template_model->get($id);
            //$item->long_description = nl2br($item->long_description);
            echo json_encode($item);
        }
    }
}
