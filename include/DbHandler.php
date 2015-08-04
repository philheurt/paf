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
            $stmt = $this->conn->prepare("INSERT INTO `doctor`(`email`,`password`, `first_name`) values(?, ?, ?)");
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
        $stmt = $this->conn->prepare("SELECT `password` FROM `doctor` WHERE `email` = ?");

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
        $stmt = $this->conn->prepare("SELECT `first_name` from `doctor` WHERE `email` = ?");
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
        $stmt = $this->conn->prepare("SELECT `first_name`, `email`  FROM `doctor` WHERE `email` = ?");
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
        $stmt = $this->conn->prepare("SELECT `password` FROM `doctor` WHERE `email` = ?");
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
        // 
        $surveys = array();
        $stmt = $this->conn->prepare("SELECT `token` FROM `relation_doctor_survey_parameters` WHERE `email` = ?");

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $stmt->bind_result($survey);

        $stmt->store_result();

        while($stmt->fetch())
        {
        	array_push($surveys,$survey);
		}
        $stmt->close();

    	return $surveys;        
    }

/**
     * Retrieving doctor surveys 
     * @param  $surveys
     * @return surveys
     */
    public function getDoctorSurveys($surveys_token) {
        // 
        $surveys = array();
        foreach($surveys_token as $value)
        {
        	$stmt = $this->conn->prepare("SELECT `token`, `title`, `instruction`, `age`, `sex`, `job`, `dial`, `circle` FROM `survey_parameters` WHERE `token` = ?");
        	$stmt->bind_param("s", $value);
        	$stmt->execute();
        	$stmt->bind_result($token, $title, $instruction, $age, $sex, $job, $dial, $circle);
        	$stmt->store_result(); 
        	$stmt->fetch(); 
        	$survey = array();
        	$survey["token"] = $token;
        	$survey["title"] = $title;
        	$survey["instruction"] = $instruction;
        	$survey["age"] = $age;
        	$survey["sex"] = $sex;
        	$survey["job"] = $job;
        	$survey["dial"] = $dial;
        	$survey["circle"] = $circle;   	
        	array_push($surveys,$survey);
      	 	$stmt->close();
		}
		unset($value);
    	return $surveys;        
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
        if (!$this->isSurveyExists($token)) {

            // insert query
            $stmt = $this->conn->prepare("INSERT INTO `survey_parameters`(`token`, `title`, `instruction`, `age`, `sex`, `job`, `dial`, `circle`) values(?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $token, $title, $instruction, $age, $sex, $job, $dial, $circle);
            $result = $stmt->execute();
            $stmt->close();
            
            $stmt_2 = $this->conn->prepare("INSERT INTO `relation_doctor_survey_parameters`(`token`, `email`) values(?, ?)");
            $stmt_2->bind_param("ss", $token, $email);
            $result_2 = $stmt_2->execute();
            $stmt_2->close();

            // Check for successful insertion
            if ($result && $result_2) {
                // Doctor successfully inserted
                return DOCTOR_CREATED_SUCCESSFULLY;
            }else {
                // Failed to create doctor
                return DOCTOR_CREATE_FAILED;
            }
        }else {
            // Survey with same token already existed in the db
            return DOCTOR_ALREADY_EXISTED;
        }

        return $response;
    }
    
    /**
     * Creating new survey parameters
     * @param String $name Doctor full name
     * @param String $email Doctor login email id
     * @param String $password Doctor login password
     */
    public function addPatients($token, $patients) {
        $response = array();

        // First check if survey already existed in db
       // if (!$this->isSurveyExists($email)) {

            foreach( $patients as $patient)
            {
            if($stmt = $this->conn->prepare("INSERT INTO `patient`(`email`) values(?)")){
            $stmt->bind_param("s", $patient);
            $result = $stmt->execute();
            $stmt->close();
            
            	if($stmt_2 = $this->conn->prepare("INSERT INTO `survey_done`(`token_parameters`, `email_patient`) values(?, ?)")){
            	$stmt_2->bind_param("ss", $token, $patient);
            	$result_2 = $stmt_2->execute();
            	$stmt_2->close();            
				}else{  return DOCTOR_CREATE_FAILED;}
			}else{  return DOCTOR_CREATE_FAILED; }
			}
        	return DOCTOR_CREATED_SUCCESSFULLY;

    }
    
        /**
     * Checking for duplicate survey by token
     * @param String $token token to check in db
     * @return boolean
     */
    private function isSurveyExists($token) {
        $stmt = $this->conn->prepare("SELECT `token` from `survey_parameters` WHERE `token` = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->store_result();
        $stmt->fetch(); 
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
    
    /**
     * Retrieving patient survey
     * @param  $surveys
     * @return surveys
     */
    public function getSurveyParameters($token) {
        	$stmt = $this->conn->prepare("SELECT `token`, `title`, `instruction`, `age`, `sex`, `job`, `dial`, `circle` FROM `survey_parameters` WHERE `token` = ?");
        	$stmt->bind_param("s", $token);
        	$stmt->execute();
        	$stmt->bind_result($token, $title, $instruction, $age, $sex, $job, $dial, $circle);
        	$stmt->store_result(); 
        	$stmt->fetch(); 
        	$survey = array();
        	$survey["token"] = $token;
        	$survey["title"] = $title;
        	$survey["instruction"] = $instruction;
        	$survey["age"] = $age;
        	$survey["sex"] = $sex;
        	$survey["job"] = $job;
        	$survey["dial"] = $dial;
        	$survey["circle"] = $circle;   	
      	 	$stmt->close();
    	return $survey;        
    }
    
    /**
     * Checking patient login
     * @param String $email Doctor login email id
     * @param String $password Doctor login password
     * @return boolean Doctor login status success/fail
     */
    public function checkPatientSurvey($email, $token) {
        // fetching user by email
        $stmt = $this->conn->prepare("SELECT `id` FROM `survey_done` WHERE `email_patient` = ? and `token_parameters` = ? ");

        $stmt->bind_param("ss", $email, $token);
        $stmt->execute();
        $stmt->bind_result($id);
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            // Found patient with the email linked to the survey        
            $stmt->fetch();
            $stmt->close();
			return TRUE;    
        } else {
            $stmt->close();
            //  No patient linked with the token
            return FALSE;
        }
    }
  
     /**
     * Select id survey done
     * @param String $email Doctor login email id
     * @param String $password Doctor login password
     * @return boolean Doctor login status success/fail
     */
    public function selectIdSurveyDone($email, $token) {
        // fetching user by email
        $stmt = $this->conn->prepare("SELECT `id` FROM `survey_done` WHERE `email_patient` = ? and `token_parameters` = ? ");
        $stmt->bind_param("ss", $email, $token);
        $stmt->execute();
        $stmt->bind_result($id);
        $stmt->store_result();
        $stmt->fetch();
        $stmt->close();
		return $id;       
    }
    
    /**
     * Select id survey done
     * @param String $email Doctor login email id
     * @param String $password Doctor login password
     * @return boolean Doctor login status success/fail
     */
    public function selectIdNode($email, $token, $id_app) {
        // fetching user by email
        if($stmt = $this->conn->prepare("SELECT `id_node` FROM `node` INNER JOIN  `relation_node_survey_done` ON `node`.`id` = `relation_node_survey_done`.`id_node` WHERE (`node`.`id_app` = ? AND `relation_node_survey_done`.`id_survey_done` = ?) ")){
        $id_survey_done = $this->selectIdSurveyDone($email, $token);
        $stmt->bind_param("ss", $id_app, $id_survey_done);
        $stmt->execute();
        $stmt->bind_result($id_node);
        $stmt->store_result();
        $stmt->fetch();
        $stmt->close();
        }else{ die("Errormessage: ". $this->conn->error);}
		return $id_node;       
    }  
        
/**
     * Save survey
     * @param String $name Doctor full name
     * @param String $email Doctor login email id
     * @param String $password Doctor login password
     */
    public function saveSurvey($email, $token, $groups, $nodes, $links) {
        $response = array();
        $id_survey_done = $this->selectIdSurveyDone($email, $token);
			foreach( $groups as $group)
			{
				if($stmt = $this->conn->prepare("INSERT INTO `group`(`name`,`color`) values(?,?)"))
				{
            	$stmt->bind_param("ss", $group['name'], $group['color']);            
           		$result = $stmt->execute();
           		$id_group = $this->conn->insert_id;
            	$stmt->close();
            	}else{  die("Errormessage: ". $this->conn->error); }
            	
            	if($stmt_2 = $this->conn->prepare("INSERT INTO `relation_group_survey_done`(`id_survey_done`, `id_group`) values(?,?)")){
            	$stmt_2->bind_param("ss", $id_survey_done, $id_group);
            	$result_2 = $stmt_2->execute();
            	$stmt_2->close();
            	}else{ die("Errormessage: ". $this->conn->error); }
			}
			
			foreach( $nodes as $node)
			{			
				if($stmt_3 = $this->conn->prepare("INSERT INTO `node`(`first_name`, `age`, `sex`, `job`, `dial`, `circle`, `position_x`, `position_y`, `id_app`, `group_name`) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")){
            	$stmt_3->bind_param("siisiiiiis", $node['first_name'], $node['age'], $node['sex'], $node['job'], $node['dial'], $node['circle'], $node['position_x'], $node['position_y'], $node['id_app'], $node['group_name']);
           		$result_3 = $stmt_3->execute();
           		$id_node = $this->conn->insert_id;
            	$stmt_3->close();
            	}else{ die("Errormessage: ". $this->conn->error);}
            
            	
            	if($stmt_4 = $this->conn->prepare("INSERT INTO `relation_node_survey_done`(`id_survey_done`, `id_node`) values(?, ?)")){
            	$stmt_4->bind_param("ii", $id_survey_done, $id_node);
            	$result_4 = $stmt_4->execute();
            	$stmt_4->close(); 
            	}else{ die("Errormessage: ". $this->conn->error);}
            	
			}
			
			foreach( $links as $link)
			{
				$stmt_5 = $this->conn->prepare("INSERT INTO `link`(`id`) values (NULL)");
            	$result_5 = $stmt_5->execute();
           		$id_link = $this->conn->insert_id;
            	$stmt_5->close();
            	
            	$id_node_1 = $this->selectIdNode($email, $token, $link['id_1']);
            	$stmt_6 = $this->conn->prepare("INSERT INTO `relation_link_node`(`link_id`, `node_id`) values(?, ?)");
            	$stmt_6->bind_param("ii", $id_link, $id_node_1);
            	$result_6 = $stmt_6->execute();
            	$stmt_6->close();            	
            	
            	$id_node_2 = $this->selectIdNode($email, $token, $link['id_2']);
            	$stmt_7 = $this->conn->prepare("INSERT INTO `relation_link_node`(`link_id`, `node_id`) values(?, ?)");
            	$stmt_7->bind_param("ii", $id_link, $id_node_2);
            	$result_7 = $stmt_7->execute();
            	$stmt_7->close();           	
			}

           
            // Check for successful insertion
            if ($result_3 && $result_4) {
                // Doctor successfully inserted
                return DOCTOR_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create doctor
                return DOCTOR_CREATE_FAILED;
            }

        return $response;
    }
        
      
    /**
     * Select id survey done
     * @param String $email Doctor login email id
     * @param String $password Doctor login password
     * @return boolean Doctor login status success/fail
     */
    public function selectIdGroup($email, $token) {
        // fetching user by email
        $stmt = $this->conn->prepare("SELECT `id_group` FROM `relation_group_survey_done` WHERE `id_survey_done` = ? ");

        $stmt->bind_param("s", selectIdSurveyDone($email, $token));
        $stmt->execute();
        $stmt->bind_result($id_group);
        $stmt->store_result();
        $stmt->fetch();
        $stmt->close();
		return $id_group;       
    }
}

?>
