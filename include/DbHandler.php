<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Philippe Heurtaux and Hao Zhang
 */
class DbHandler {

    private $conn;

    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    /* ------------- `Doctor` table method ------------------ */

    /**
     * Creating new doctor
     * @param String $name Doctor full name
     * @param String $email Doctor login email id
     * @param String $password Doctor login password
     */
    public function createDoctor($first_name, $email, $password) {
        $response = array();

        // First check if doctor already existed in db
        if (!$this->isDoctorExists($email)) {

            // insert query
            $stmt = $this->conn->prepare("INSERT INTO doctor(email,password, first_name) values(?, ?, ?)");
            $stmt->bind_param("sss", $email, $password, $first_name);

            $result = $stmt->execute();

            $stmt->close();

            // Check for successful insertion
            if ($result) {
                // Doctor successfully inserted
                return DOCTOR_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create doctor
                return DOCTOR_CREATE_FAILED;
            }
        } else {
            // Doctor with same email already existed in the db
            return DOCTOR_ALREADY_EXISTED;
        }

        return $response;
    }

    /**
     * Checking doctor login
     * @param String $email Doctor login email id
     * @param String $password Doctor login password
     * @return boolean Doctor login status success/fail
     */
    public function checkLogin($email, $password) {
        // fetching user by email
        $stmt = $this->conn->prepare("SELECT password FROM doctor WHERE email = ?");

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($password_bdd);
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            // Found doctor with the email
            // Now verify the password
            $stmt->fetch();
            $stmt->close();

            if ($password_bdd == $password) {
                // Doctor password is correct
                return TRUE;
            } else {
                // Doctor password is incorrect
                return FALSE;
            }
        } else {
            $stmt->close();

            //  No doctor linked with the email
            return FALSE;
        }
    }

    /**
     * Checking for duplicate Doctor by email address
     * @param String $email email to check in db
     * @return boolean
     */
    private function isDoctorExists($email) {
        $stmt = $this->conn->prepare("SELECT first_name from doctor WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Fetching doctor by email
     * @param String $email Doctor email id
     */
    public function getDoctorByEmail($email) {
        $stmt = $this->conn->prepare("SELECT first_name, email  FROM doctor WHERE email = ?");
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            // $doctor = $stmt->get_result()->fetch_assoc();
            $stmt->bind_result($first_name, $email);
            $stmt->fetch();
            $doctor = array();
            $doctor["first_name"] = $first_name;
            $doctor["email"] = $email;
            $stmt->close();
            return $doctor;
        } else {
            return NULL;
        }
    }


	/**
     * Retrieving doctor password
     * @param String $email 
     * @return password
     */
    public function getDoctorPassword($email) {
        // fetching doctor by email
        $stmt = $this->conn->prepare("SELECT password FROM doctor WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($password);
        $stmt->store_result();  
        $stmt->fetch();
        $stmt->close();
		return $password;
            
    }
	
	/**
     * Retrieving doctor surveys token
     * @param String $email 
     * @return surveys
     */
    public function getDoctorSurveysToken($email) {
        // fetching doctor by email
        $stmt = $this->conn->prepare("SELECT token FROM relation_doctor_survey_parameters WHERE email = ?");

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $stmt->bind_result($surveys);

        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Found doctor with the email
            // Now verify the password

            $stmt->fetch();

            $stmt->close();

            if (PassHash::check_password($password_hash, $password)) {
                // Doctor password is correct
                return TRUE;
            } else {
                // Doctor password is incorrect
                return FALSE;
            }
        } else {
            $stmt->close();

            //  No doctor linked with the email
            return FALSE;
        }
    }

   /**
     * Creating new survey parameters
     * @param String $name Doctor full name
     * @param String $email Doctor login email id
     * @param String $password Doctor login password
     */
    public function addSurveyParameters($email, $token, $title, $instruction, $age, $sex, $job, $dial, $circle) {
        $response = array();

        // First check if survey already existed in db
        if (!$this->isSurveyExists($email)) {

            // insert query
            $stmt = $this->conn->prepare("INSERT INTO survey_parameters(token, title, instruction, age, sex, job, dial, circle) values(?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $token, $title, $instruction, $age, $sex, $job, $dial, $circle);
            $result = $stmt->execute();
            $stmt->close();
            
            $stmt_2 = $this->conn->prepare("INSERT INTO relation_doctor_survey_parameters(token, email) values(?, ?)");
            $stmt_2->bind_param("ss", $token, $email);
            $result_2 = $stmt_2->execute();
            $stmt_2->close();

            // Check for successful insertion
            if ($result && $result_2) {
                // Doctor successfully inserted
                return DOCTOR_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create doctor
                return DOCTOR_CREATE_FAILED;
            }
        } else {
            // Doctor with same email already existed in the db
            return DOCTOR_ALREADY_EXISTED;
        }

        return $response;
    }
    
        /**
     * Checking for duplicate survey by token
     * @param String $token token to check in db
     * @return boolean
     */
    private function isSurveyExists($token) {
        $stmt = $this->conn->prepare("SELECT token from survey_parameters WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
}

?>
