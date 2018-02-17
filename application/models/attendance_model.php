 <?php
defined('BASEPATH') or exit('No direct script access allowed');
class attendance_model extends CRM_Model
{
    private $piping = false;

    public function __construct()
    {
        parent::__construct();
    }
    public function add_attendance($data)
    {
        $this->db->insert('attendance', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            logActivity('New Ticket Service Added [ID: ' . $insert_id . '.' . $data['name'] . ']');
        }

        return $insert_id;
    }
}