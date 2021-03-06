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
    public function getDoctorPassword($email, $password) {
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

        $surveys = $stmt->fetch_array(MYSQLI_NUM);

     	$stmt->close();

       return $surveys;
    }

    /**
     * Fetching user api key
     * @param String $user_id user id primary key in user table
     */
    public function getApiKeyById($user_id) {
        $stmt = $this->conn->prepare("SELECT api_key FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            // $api_key = $stmt->get_result()->fetch_assoc();
            // TODO
            $stmt->bind_result($api_key);
            $stmt->close();
            return $api_key;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user id by api key
     * @param String $api_key user api key
     */
    public function getUserId($api_key) {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        if ($stmt->execute()) {
            $stmt->bind_result($user_id);
            $stmt->fetch();
            // TODO
            // $user_id = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $user_id;
        } else {
            return NULL;
        }
    }


    /* ------------- `tasks` table method ------------------ */

    /**
     * Creating new task
     * @param String $user_id user id to whom task belongs to
     * @param String $task task text
     */
    public function createTask($user_id, $task) {
        $stmt = $this->conn->prepare("INSERT INTO tasks(task) VALUES(?)");
        $stmt->bind_param("s", $task);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            // task row created
            // now assign the task to user
            $new_task_id = $this->conn->insert_id;
            $res = $this->createUserTask($user_id, $new_task_id);
            if ($res) {
                // task created successfully
                return $new_task_id;
            } else {
                // task failed to create
                return NULL;
            }
        } else {
            // task failed to create
            return NULL;
        }
    }

    /**
     * Fetching single task
     * @param String $task_id id of the task
     */
    public function getTask($task_id, $user_id) {
        $stmt = $this->conn->prepare("SELECT t.id, t.task, t.status, t.created_at from tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($id, $task, $status, $created_at);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["id"] = $id;
            $res["task"] = $task;
            $res["status"] = $status;
            $res["created_at"] = $created_at;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching all user tasks
     * @param String $user_id id of the user
     */
    public function getAllUserTasks($user_id) {
        $stmt = $this->conn->prepare("SELECT t.* FROM tasks t, user_tasks ut WHERE t.id = ut.task_id AND ut.user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $tasks = $stmt->get_result();
        $stmt->close();
        return $tasks;
    }

    /**
     * Updating task
     * @param String $task_id id of the task
     * @param String $task task text
     * @param String $status task status
     */
    public function updateTask($user_id, $task_id, $task, $status) {
        $stmt = $this->conn->prepare("UPDATE tasks t, user_tasks ut set t.task = ?, t.status = ? WHERE t.id = ? AND t.id = ut.task_id AND ut.user_id = ?");
        $stmt->bind_param("siii", $task, $status, $task_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /**
     * Deleting a task
     * @param String $task_id id of the task to delete
     */
    public function deleteTask($user_id, $task_id) {
        $stmt = $this->conn->prepare("DELETE t FROM tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /* ------------- `user_tasks` table method ------------------ */

    /**
     * Function to assign a task to user
     * @param String $user_id id of the user
     * @param String $task_id id of the task
     */
    public function createUserTask($user_id, $task_id) {
        $stmt = $this->conn->prepare("INSERT INTO user_tasks(user_id, task_id) values(?, ?)");
        $stmt->bind_param("ii", $user_id, $task_id);
        $result = $stmt->execute();

        if (false === $result) {
            die('execute() failed: ' . htmlspecialchars($stmt->error));
        }
        $stmt->close();
        return $result;
    }

}

?>
