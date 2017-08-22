<?php

/**
 * Created by PhpStorm.
 * User: rubby
 * Date: 8/16/2017
 * Time: 4:31 PM
 */
use \Stripe\Stripe;
class Patientafterlogin extends CI_Controller
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
        $path = $_FILES['img']['name'];
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $uname = time().uniqid(rand());
        $uploadfile = $uploaddir .$uname.'.'.$ext;
        $file_name = $uname.".".$ext;
        if (move_uploaded_file($_FILES['img']['tmp_name'], $uploadfile)) {
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

    public function setScheduleIOS(){
        $data['pid'] = $this->patient['pid'];
        $data['date'] = $this->input->post('date');
        $data['note'] = $this->input->post('note');

        $upload_count = $this->input->post('attach_count');
        if($upload_count <= 0 || !isset($upload_count))
        {
            $return_data['success'] = 0;
            $return_data['error'] = "There is not any attachments, please upload some files";
            echo json_encode($return_data);
            exit();
        }
        $data['history'] = "";
        for ($i = 0;$i<$upload_count;$i++)
        {
            $uploaddir = './assets/uploads/schedule/';
            $path = $_FILES['img_'.$i]['name'];
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            $uname = time().uniqid(rand());
            $uploadfile = $uploaddir .$uname.'.'.$ext;
            $file_name = $uname.".".$ext;
            if (move_uploaded_file($_FILES['img_'.$i]['tmp_name'], $uploadfile)) {
                $data['history'] = $data['history'].$file_name.",";
            }
        }

        if ($data['history'] == "")
        {
            $return_data['success'] = 0;
            $return_data['error'] = "file upload happens error";
            echo json_encode($return_data);
            exit();
        }

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
        echo json_encode($return_data);
        exit();

    }

    public function getIdDoctor()
    {
       if($this->patient['did'] == "" || $this->patient['did'] == null)
       {
           $return_data['success'] = 0;
           $return_data['error'] = "You are not alloced to any Doctor";
           echo json_encode($return_data);
           exit();
       }
        $doctor = $this->doctor_model->getDoctorId($this->patient['did']);

       if(!$doctor)
       {
           $return_data['success'] = 0;
           $return_data['error'] = "This doctor is remove in database.";
           echo json_encode($return_data);
           exit();
       }

       $return_data['success'] = 1;
       if($doctor['img'] == "" || $doctor['img'] == null)
       {
           $doctor['img'] = base_url()."assets/uploads/doctor/no-img.png";
       }
       else{
           $doctor['img'] = base_url()."assets/uploads/doctor/".$doctor['img'];
       }
       $return_data['data'] = $doctor;
       echo json_encode($return_data);
       exit();
    }

    public function reqCall()
    {
        if($this->patient['did'] == "" || $this->patient['did'] == null)
        {
            $return_data['success'] = 0;
            $return_data['error'] = "You are not alloced to any Doctor";
            echo json_encode($return_data);
            exit();
        }

        $current_schedule = $this->schedule_model->getScheduleCurrent($this->patient['pid']);
        if(!$current_schedule){
            $return_data['success'] = 0;
            $return_data['error'] = "You have not schedule,please add new schedule";
            echo json_encode($return_data);
            exit();
        }

        $this->load->helper('opentok');
        $opentok = createNewOpentokSession();

        if(!$opentok)
        {
            $data["success"] = 0;
            $data["error"] = "Opentok Session creation is failed!";
            echo json_encode($data);
            exit();
        }

        if(!$this->schedule_model->updateSchedule($current_schedule['id'],$opentok))
        {
            $return_data['success'] = 0;
            $return_data['error'] = "Opentok session is not added to database";
            echo json_encode($return_data);
            exit();
        }
        $return_data['success'] = 1;
        $return_data['data'] = $opentok;
        echo json_encode($return_data);

        $doctor = $this->doctor_model->getDoctorId($this->patient['did']);
        $this->sendNotification($doctor);
        exit();


    }

    public function checkOut()
    {

        $stripe_token = $this->input->post('stripeToken');//$_POST['stripeToken'];
        $amount = $this->input->post('amount');//$_POST['amount'];
        $stripe_charge="";
        Stripe::setApiKey("sk_test_B9TdZJeFIk4d01GXdy0vVM9q");
        try{
            $stripe_charge = \Stripe\Charge::create(array(
                "amount" => $amount,
                "currency" => "usd",
                "card" => $stripe_token,
                "description" => "test"
            ));
        }catch (Exception $e)
        {
            //var_dump($e);
            $return_data['success'] = 0;
            $return_data['error'] = "Payment happens error";
            echo json_encode($return_data);
            exit();
        }
        $return_data['success'] = 1;
        $return_data['data'] = "payement successed";
        echo json_encode($return_data);
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

    public function sendNotification($doctor)
    {
        $options = array(
            'cluster' => 'us2',
            'encrypted' => true
        );
        $pusher = new Pusher\Pusher(
            '4d19d5b3edbd8e1743b4',
            'e568f9f7c0b1af9ab21d',
            '387748',
            $options
        );

        $data['message'] = 'Hello! '.$doctor["fname"]." ".$doctor["lname"]." is requesting call!";
        $data['doctor'] = $doctor;
        $pusher->trigger('my-channel', 'my-event', $data);
    }
}