<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Hourly_template_model extends CRM_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get invoice item by ID
     * @param  mixed $id
     * @return mixed - array if not passed id, object if id passed
     */

    public function get($id = '')
    {
        $columns = $this->db->list_fields('hourly_template');
        $rateCurrencyColumns = '';
       
        $this->db->select($rateCurrencyColumns.'id as itemid,date_out,
           date_in,time_in,time_out');
        $this->db->from('hourly_template');
        // $this->db->join('tbltaxes t1', 't1.id = tblitems.tax', 'left');
        // $this->db->join('tbltaxes t2', 't2.id = tblitems.tax2', 'left');
        // $this->db->join('tblitems_groups', 'tblitems_groups.id = tblitems.group_id', 'left');
        // $this->db->order_by('description', 'asc');
        if (is_numeric($id)) {
            $this->db->where('hourly_template.id', $id);

            return $this->db->get()->row();
        }

        return $this->db->get()->result_array();
    }

    public function get_grouped()
    {
        $items = array();
        $this->db->order_by('name', 'asc');
        $groups = $this->db->get('tblitems_groups')->result_array();

        array_unshift($groups, array(
            'id' => 0,
            'name' => ''
        ));

        foreach ($groups as $group) {
            $this->db->select('*,tblitems_groups.name as group_name,tblitems.id as id');
            $this->db->where('group_id', $group['id']);
            $this->db->join('tblitems_groups', 'tblitems_groups.id = tblitems.group_id', 'left');
            $this->db->order_by('description', 'asc');
            $_items = $this->db->get('tblitems')->result_array();
            if (count($_items) > 0) {
                $items[$group['id']] = array();
                foreach ($_items as $i) {
                    array_push($items[$group['id']], $i);
                }
            }
        }

        return $items;
    }

    /**
     * Add new invoice item
     * @param array $data Invoice item data
     * @return boolean
     */
    public function add($data)
    {
        unset($data['itemid']);
        $columns = $this->db->list_fields('hourly_template');
        $this->load->dbforge();

        foreach($data as $column => $itemData){
            if(!in_array($column,$columns) ){
                $field = array(
                        $column => array(
                            'type' =>'float',
                            'null'=>true,
                        )
                );
                $this->dbforge->add_column('hourly_template', $field);
            }
        }

        $this->db->insert('hourly_template', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            logActivity('New Invoice Item Added [ID:' . $insert_id . ', ' . $data['salary_grade'] . ']');

            return $insert_id;
        }

        return false;
    }

    /**
     * Update invoiec item
     * @param  array $data Invoice data to update
     * @return boolean
     */
    public function edit($data)
    {
        $itemid = $data['itemid'];
        unset($data['itemid']);

      

        $columns = $this->db->list_fields('hourly_template');
        $this->load->dbforge();

        foreach($data as $column => $itemData){
            if(!in_array($column,$columns) ){
                $field = array(
                        $column => array(
                            'type' =>'decimal(11,'.get_decimal_places().')',
                            'null'=>true,
                        )
                );
                $this->dbforge->add_column('tblitems', $field);
            }
        }

        $this->db->where('id', $itemid);
        $this->db->update('hourly_template', $data);
        if ($this->db->affected_rows() > 0) {
            logActivity('Invoice Item Updated [ID: ' . $itemid . ', ' . $data['date_in'] . ']');
            return true;
        }

        return false;
    }

    public function search($q){

        $this->db->select('rate, id, description as name, long_description as subtext');
        $this->db->like('description',$q);
        $this->db->or_like('long_description',$q);

        $items = $this->db->get('tblitems')->result_array();

        foreach($items as $key=>$item){
            $items[$key]['subtext'] = strip_tags(mb_substr($item['subtext'],0,200)).'...';
            $items[$key]['name'] = '('._format_number($item['rate']).') ' . $item['name'];
        }

        return $items;
    }

    /**
     * Delete invoice item
     * @param  mixed $id
     * @return boolean
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        $this->db->delete('hourly_template');
        if ($this->db->affected_rows() > 0) {
            logActivity('Invoice Item Deleted [ID: ' . $id . ']');
            return true;
        }

        return false;
    }

    public function get_groups()
    {
        $this->db->order_by('firstname', 'asc');

        return $this->db->get('tblstaff')->result_array();
    }

    public function add_group($data)
    {
        $this->db->insert('tblitems_groups', $data);
        logActivity('Items Group Created [Name: ' . $data['name'] . ']');

        return $this->db->insert_id();
    }

    public function edit_group($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update('tblitems_groups', $data);
        if ($this->db->affected_rows() > 0) {
            logActivity('Items Group Updated [Name: ' . $data['name'] . ']');

            return true;
        }

        return false;
    }

    public function delete_group($id)
    {
        $this->db->where('id', $id);
        $group = $this->db->get('tblitems_groups')->row();

        if ($group) {
            $this->db->where('group_id', $id);
            $this->db->update('tblitems', array(
                'group_id' => 0
            ));

            $this->db->where('id', $id);
            $this->db->delete('tblitems_groups');

            logActivity('Item Group Deleted [Name: ' . $group->name . ']');

            return true;
        }

        return false;
    }
}
