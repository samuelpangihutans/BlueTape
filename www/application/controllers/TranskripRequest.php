<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class TranskripRequest extends CI_Controller {

    public function __construct() {
        parent::__construct();
        try {
            $this->Auth_model->checkModuleAllowed(get_class());
        } catch (Exception $ex) {
            $this->session->set_flashdata('error', $ex->getMessage());
            header('Location: /');
        }
        $this->load->library('bluetape');
        $this->load->model('Transkrip_model');
        $this->load->database();
    }

    public function index() {
        // Retrieve logged in user data
        $userInfo = $this->Auth_model->getUserInfo();
        // Retrieve requests for this user
        $requests = $this->Transkrip_model->requestsBy($userInfo['email']);
        $forbiddenTypes = $this->Transkrip_model->requestTypesForbidden($requests);
        foreach ($requests as &$request) {
            if ($request->answer === NULL) {
                $request->status = 'TUNGGU';
                $request->labelClass = 'secondary';
            } else if ($request->answer === 'printed') {
                $request->status = 'TERCETAK';
                $request->labelClass = 'success';
            } else if ($request->answer === 'rejected') {
                $request->status = 'DITOLAK';
                $request->labelClass = 'alert';
            }
            $request->requestDateString = $this->bluetape->dbDateTimeToReadableDate($request->requestDateTime);
            $request->requestByName = $this->bluetape->getName($request->requestByEmail);
            $request->answeredDateString = $this->bluetape->dbDateTimeToReadableDate($request->answeredDateTime);
        }
        unset($request);

        $this->load->view('TranskripRequest/main', array(
            'currentModule' => get_class(),
            'requestByEmail' => $userInfo['email'],
            'requestByNPM' => $this->bluetape->getNPM($userInfo['email'], '-'),
            'requestByName' => $userInfo['name'],
            'requests' => $requests,
            'forbiddenTypes' => $forbiddenTypes
        ));
    }

    public function add() {
        try {
           #request methodnya dihilangkan
           # if ($this->input->server('REQUEST_METHOD') == 'POST'){
                date_default_timezone_set("Asia/Jakarta");
                $userInfo = $this->Auth_model->getUserInfo();
                $requests = $this->Transkrip_model->requestsBy($userInfo['email']);
                $forbiddenTypes = $this->Transkrip_model->requestTypesForbidden($requests);
                if (is_string($forbiddenTypes)) {
                    throw new Exception($forbiddenTypes);
                }
                $requestType = htmlspecialchars($this->input->post('requestType'));
                if (in_array($requestType, $forbiddenTypes)) {
                    throw new Exception("Tidak bisa, karena transkrip $requestType sudah pernah dicetak di semester ini.");
                }

                #KODE ASLI : TERLALU SECURE KARNA DARI CI (digunakan ketika CSRF)
                $this->db->insert('Transkrip', array(
                     'requestByEmail' => $userInfo['email'],
                     'requestDateTime' => strftime('%Y-%m-%d %H:%M:%S'),
                     'requestType' => $requestType,
                     'requestUsage' => htmlspecialchars($this->input->post('requestUsage'))
                 ));
                
                 #--------------------------------------------------------------------------------------------------
                
                 /**
                  * 1.SQL INJECTION  
                  * Code Asli sudah Secure karena menggunakan framework CI , sehingga tidak bisa memanipulsai query
                  * Menempelkan Query update pada Input Form : 
                 */   

                //  $con=mysqli_connect('localhost','root','','bluetape');

                //  $requestByEmail = $userInfo['email'];
                //  $requestDateTime = strftime('%Y-%m-%d %H:%M:%S');
                //  $requestUsage= $this->input->post('requestUsage');


                //  $query='INSERT INTO Transkrip (requestByEmail,requestDateTime,RequestType,RequestUsage) values("'.$requestByEmail.'","'.$requestDateTime.'","'.$requestType.'","'.$requestUsage.'")';
                //  mysqli_multi_query($con,$query);

                 #input pada Form "Keterangan" = ");UPDATE Transkrip SET answer="printed";
             

                 #--------------------------------------------------------------------------------------------------

                 /** 
                  * 2.SCRIPT INJECTION  : 
                  * Code asli menggunakan html special Char : perlindungan terhadap script injection
                  * Htmlspecialchars digunakan ketika anda akan menuliskan kode HTML pada file berekstensi HTML namun karakter-karakter special tetap ingin dipertahankan pada saat tampil dibrowser.
                 */
                
                //  $this->db->insert('Transkrip', array(
                //      'requestByEmail' => $userInfo['email'],
                //      'requestDateTime' => strftime('%Y-%m-%d %H:%M:%S'),
                //       'requestType' => $requestType,
                //      'requestUsage' => $this->input->post('requestUsage')
                //  ));

                 #Input pada Form "keperluan" : <script>window.location.href="https://www.youtube.com/watch?time_continue=8&v=dQw4w9WgXcQ"</script>

                
                 #--------------------------------------------------------------------------------------------------

                 /**
                  * 3.CSRF : 
                  * edit pada application/config/config.php ubah csrf proetction menjadi fals : -> UNTUK MEMBUAT CELAH
                  * edit application/view/transkripRequest/main.php hapus  <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>" /> (baris 16 pada notepad++)
                  */

                 
                $this->session->set_flashdata('info', 'Permintaan cetak transkrip sudah dikirim. Silahkan cek statusnya secara berkala di situs ini.');

                $this->load->model('Email_model');
                $recipients = $this->config->item('roles')['tu.ftis'];
                if (is_array($recipients)) {
                    foreach ($recipients as $email) {
                        $requestByName = $this->bluetape->getName($userInfo['email']);
                        $subject = "Permohonan Transkrip dari $requestByName";
                        $message = $this->load->view('TranskripRequest/email', array(
                            'name' => $this->bluetape->getName($email),
                            'requestByName' => $requestByName
                        ), TRUE);
                        $this->Email_model->send_email($email, $subject, $message);
                    }
                }
            #} else {
                #throw new Exception("Can't call method from GET request!");
           # }
        } catch (Exception $e) {
            $this->session->set_flashdata('error', $e->getMessage());
        }
        header('Location: /TranskripRequest');
    }

}
