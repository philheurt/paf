<?php
define('ERROR_LOG_FILE', 'error.log');
require_once '../include/DbHandler.php';
require_once '../include/PassHash.php';
require '.././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

// User id from db - Global Variable
$user_id = NULL;



/**
 * Doctor Registration
 * url - /register
 * method - POST
 * params - name, email, password
 */
$app->post('/register', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('first_name', 'email', 'password'));

            $response = array();

            // reading post params
            $first_name = $app->request->post('first_name');
            $email = $app->request->post('email');
            $password = $app->request->post('password');

            // validating email address
            validateEmail($email);

            $db = new DbHandler();
            $res = $db->createDoctor($first_name, $email, $password);

            if ($res == DOCTOR_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "You are successfully registered";
            } else if ($res == DOCTOR_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registering";
            } else if ($res == DOCTOR_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this email is already existing";
            }
            // echo json response
            echoResponse(201, $response);
        });

/**
 * Doctor Login
 * url - /login
 * method - POST
 * params - email, password
 */
$app->post('/login', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('email', 'password'));

            // reading post params
            $email = $app->request()->post('email');
            $password = $app->request()->post('password');
            $response = array();

            $db = new DbHandler();
            // check for correct email and password
            if ($db->checkLogin($email, $password)) {
                // get the doctor by email
                $doctor = $db->getDoctorByEmail($email);

                if ($doctor != NULL) {
                    $response["error"] = false;
                    $response['message'] = "You've been successfully identified";
                    $response['first_name'] = $doctor['first_name'];
                } else {
                    // unknown error occurred
                    $response['error'] = true;
                    $response['message'] = "An error occurred. Please try again";
                }
            } else {
                // doctor credentials are wrong
                $response['error'] = true;
                $response['message'] = 'Login failed. Incorrect credentials';
            }

            echoResponse(200, $response);
        });

/**
 * Doctor Retrieve password
 * url - /retrieve_password
 * method - POST
 * params - email
 */
$app->post('/retrieve_password', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('email'));

            // reading post params
            $email = $app->request()->post('email');
            $response = array();

            $db = new DbHandler();

                // get the doctor's password
                $doctor = $db->getDoctorPassword($email);

                if ($doctor != NULL) {
                    $response["error"] = false;
                    $response['message'] = "Don't forget your password next time";
                    $response['password'] = $doctor;
                } else {
                    // unknown error occurred
                    $response['error'] = true;
                    $response['message'] = "An error occurred. Please try again";
                }
        
            echoResponse(200, $response);
        });
        

/**
 * Doctor retrieve surveys
 * url - /retrieve_surveys
 * method - POST
 * params - email, password
 */
$app->post('/retrieve_surveys', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('email', 'password'));

            // reading post params
            $email = $app->request()->post('email');
            $password = $app->request()->post('password');
            $response = array();

            $db = new DbHandler();
            // check for correct email and password
            if ($db->checkLogin($email, $password)) {
                // get the doctor by email
                $surveys_token = $db->getDoctorSurveysToken($email);
				$surveys = $db->getDoctorSurveys($surveys_token);
               
                    $response["error"] = false;
                    $response['message'] = "Here are your previous surveys";                   
                    $response['surveys'] = $surveys;
                
            } else {
                // doctor credentials are wrong
                $response['error'] = true;
                $response['message'] = 'Login failed. Incorrect credentials';
            }

            echoResponse(200, $response);
        });

/**
 * Doctor add survey
 * url - /register
 * method - POST
 * params - email, token, title, instruction, age sex, job, dial, circle
 */
$app->post('/add_survey', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('email', 'token','title','instruction', 'age', 'sex', 'job', 'dial', 'circle'));

            $response = array();

            // reading post params
            $email = $app->request->post('email');
            $token = $app->request->post('token');
            $title = $app->request->post('title');
            $instruction = $app->request->post('instruction');
            $age = $app->request->post('age');
            $sex = $app->request->post('sex');
            $job = $app->request->post('job');
            $dial = $app->request->post('dial');
            $circle = $app->request->post('circle');

            // validating email address
            validateEmail($email);

            $db = new DbHandler();
            $res = $db->addSurveyParameters($email, $token, $title, $instruction, $age, $sex, $job, $dial, $circle);
			// I use here the same return codes of the registering of doctors...
            if ($res == DOCTOR_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = " Survey successfully saved, thank you";
            } else if ($res == DOCTOR_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while saving";
            } else if ($res == DOCTOR_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this survey already exists";
            }
            // echo json response
            echoResponse(201, $response);
        });

/**
 * Doctor Adding Patient
 * url - /add_patients
 * method - POST
 * params - name, email, password
 */
$app->post('/add_patients', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('token', 'emails'));

            $response = array();

            // reading post params
            $token = $app->request->post('token');
            $patients_json = $app->request->post('emails');
			$patients = json_decode($patients_json);
			
            $db = new DbHandler();
            $res = $db->addPatients($token, $patients);

            if ($res == DOCTOR_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "Patients successfully added";
            } else if ($res == DOCTOR_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while adding patient";
            } else if ($res == DOCTOR_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, but you can't add two times the same patient for one study";
            } 
            // echo json response 
            echoResponse(201, $response);
        });
/**
 * Patient Retrieve survey
 * url - /get_survey
 * method - POST
 * params - email, token
 */
$app->post('/get_survey', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('email','token'));

            // reading post params
            $email = $app->request()->post('email');
            $token = $app->request()->post('token');
            $response = array();

            $db = new DbHandler();
			if ($db->checkPatientSurvey($email,$token)) {
                // get survey params
                $survey = $db->getSurveyParameters($token);

                    $response["error"] = false;
                    $response['message'] = "Here are the survey parameters";
                    $response['survey'] = $survey;
                } else {
                    // unknown error occurred
                    $response['error'] = true;
                    $response['message'] = "An error occurred with your authentification. Please try again";
                }
        
            echoResponse(200, $response);
        });
/**
 * Doctor Adding Patient
 * url - /add_patients
 * method - POST
 * params - name, email, password
 */
$app->post('/save_survey', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('email', 'token','group','node','link'));

            $response = array();

            // reading post params
            $email = $app->request->post('email');
            $token = $app->request->post('token');
            $group_json = $app->request->post('group');
            $node_json = $app->request->post('node');
            $link_json = $app->request->post('link');
			$group = json_decode($group_json, true);
			$node = json_decode($node_json, true);
			$link = json_decode($link_json, true);
			
            $db = new DbHandler();
            $res = $db->saveSurvey($email, $token, $group, $node, $link);

            if ($res == DOCTOR_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = " Survey successfully saved";
            } else if ($res == DOCTOR_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while saving the survey";
            } else if ($res == DOCTOR_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, but you can't add two times the same patient for one study";
            } 
            // echo json response 
            echoResponse(201, $response);
        });      

/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoResponse(400, $response);
        $app->stop();
    }
}

/**
 * Validating email address
 */
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid';
        echoResponse(400, $response);
        $app->stop();
    }
}

/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoResponse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');

    echo json_encode($response);
}

$app->run();
?>