<?php

/**
 * Created by PhpStorm.
 * User: rubby
 * Date: 8/16/2017
 * Time: 4:31 PM
 */
class PatientAfterLogin extends CI_Controller
{
    public $token = "";
    public $patient;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('patient_model');
        $this->load->model('doctor_model');
        $this->load->model('schedule_model');

        $this->token = $this->input->post('token');
        //$this->checkTokenSession();
        $this->checkToken();

    }

    public function setSchedule()
    {
        $data['pid'] = $this->patient['pid'];
        $data['date'] = $this->input->post('date');
        $data['note'] = $this->input->post('note');
        $data['history'] = $this->input->post('history');
        if($this->schedule_model->addNewSchedule($data))
        {
            $return_data['success'] = 1;
            $return_data['error'] = "Add new schedule success";
            $return_data['data'] = "Add new schedule success";
            echo json_encode($return_data);
            exit();
        }

        $return_data['success'] = 0;
        $return_data['error'] = "Add new schedule fail";
        $return_data['data'] = "Add new schedule fail";
        echo json_encode($return_data);
        exit();

    }

    public function uploadHistoryAttach(){
        $uploaddir = './assets/uploads/schedule/';
        $path = $_FILES['attach']['name'];
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $uname = time().uniqid(rand());
        $uploadfile = $uploaddir .$uname.'.'.$ext;
        $file_name = $uname.".".$ext;
        if (move_uploaded_file($_FILES['attach']['tmp_name'], $uploadfile)) {
            //$this->sample_item_model->editItemField($item_no,$field,$file_name);
            $temp['img'] = $file_name;
            $data['data'] = $temp;
            $data['success'] = 1;
            echo json_encode($data);
            exit();
        }

        $data['error'] = "Upload fail";
        $data['success'] = 0;
        echo json_encode($data);
        exit();

    }

    private function checkTokenSession(){
        if($this->session->userdata('token')){
            $return_data['success'] = 0;
            $return_data['error'] = 'session is destroied';
            echo json_encode($return_data);
            exit();
        }
    }

    private function checkToken()
    {
        if($this->token == "" || !isset($this->token) || $this->token == null){
            $return_data['success'] = 0;
            $return_data['error'] = 'You have to post token';
            echo json_encode($return_data);
            exit();
        }

        $this->patient = $this->patient_model->getPatientToken($this->token);
        if(!$this->patient)
        {
            $return_data['success'] = 0;
            $return_data['error'] = "You are not logged user. You have to login again";
            echo json_encode($return_data);
            exit();
        }

    }

}