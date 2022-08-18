<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');

header('Access-Control-Allow-Methods: GET, POST');

header("Access-Control-Allow-Headers: X-Requested-With");

// Response Reusable Function
function respond($code, $response)
{
    header("Content-Type:application/json");
    http_response_code($code);
    echo (is_array($response) ? json_encode($response) : $response);
    exit(0);
}

// Establish Connection with DataBase
function getDb()
{
    $con = mysqli_connect("localhost", "mavenjan21", "genius4good", "ccl_learning");
    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
        exit(0);
    }
    return $con;
}

// Get current domain name the api is being served from
function getUrl()
{
    $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $parts = parse_url($actual_link);
    $scheme = explode('/', $parts['scheme']);
    $host = explode('/', $parts['host']);
    $hostUrl = $scheme[0] . "://" . $host[0];
    return $hostUrl;
}

if ($json = json_decode(file_get_contents("php://input"), true))
    $request = $json;
elseif ($_POST)
    $request = $_POST;
elseif ($_GET)
    $request = $_GET;
$log = strftime('%Y-%m-%d');
$time = strftime('%H:%M:%S');

try {

    // Establish DB Connection
    $db = getDb();

    if (stripos($_SERVER['REQUEST_URI'], '/testEndpoint') !== false) {
        respond(200, array('success' => true, 'message' => 'Endpoint Reachable'));
    } elseif ((stripos($_SERVER['REQUEST_URI'], '/signup') !== false) && $_SERVER['REQUEST_METHOD'] === "POST") {
        // Variables sent with the POST request
        $companyName = $request['companyName'];
        $companyAddress = $request['companyAddress'];
        $companyTel = $request['companyTel'];
        $firstName = $request['firstName'];
        $lastName = $request['lastName'];
        $address = $request['address'];
        $email = $request['email'];
        $email_suffix = explode("@", $request['email'])[1];
        $tel = $request['tel'];
        $hash = password_hash($request['password'], PASSWORD_DEFAULT);
        // echo $email_suffix;
        // Check if user already exists
        $sql = "SELECT * FROM users WHERE userEmail = '$email'";
        $result = $db->query($sql);
        if ($db->errno) {
            respond(500, ["success" => false, "message" => 'db error: ' . $db->error]);
        }

        if ($result->num_rows === 0) {
            // If users does not already exist, it is created and assigned the Admin role for the Company
            $sql = "INSERT INTO users (userFirstName, userLastName, userEmail, userPhone, userPassword, userRoleID) VALUES ('$firstName', '$lastName', '$email', '$tel', '$hash', 1)";
            $db->query($sql);
            if ($db->errno)
                respond(500, ["success" => false, "message" => 'db error: ' . $db->error]);
            $userID = $db->insert_id;

            // Register Company Details
            $sql = "INSERT INTO company (companyName, companyAddress1, companyAdminID, emailSuffix) VALUES ('$companyName', '$companyAddress', '$userID', '$email_suffix')";
            $db->query($sql);
            if ($db->errno)
                respond(500, ["success" => false, "message" => 'db error: ' . $db->error]);
            $companyID = $db->insert_id;

            $sql = "UPDATE users SET companyID = '$companyID' WHERE userEmail = '$email'";
            $db->query($sql);
            if ($db->errno)
                respond(500, ["success" => false, "message" => 'db error: ' . $db->error]);

            // Query users table for particular new Admin User
            $sql = "SELECT users.*, company.companyName, role.roleName FROM users JOIN role ON users.userRoleID = role.roleID
                    LEFT JOIN company ON users.companyID = company.companyID WHERE users.userEmail = '$email'";
            $result = $db->query($sql);
            if ($db->errno)
                respond(500, ["success" => false, "message" => 'db error: ' . $db->error]);
            if ($result->num_rows === 1) {
                $userDetails = $result->fetch_array(MYSQLI_ASSOC);
                $userData = ["id" => $userDetails["userID"], "firstname" => $userDetails["userFirstName"], "lastname" => $userDetails["userLastName"], "email" => $userDetails["userEmail"], "role" => $userDetails["roleName"], "companyName" => $userDetails["companyName"], "companyID" => $userDetails["companyID"]];
                respond(200, ["success" => true, "message" => "Sign Up Successful", "userData" => $userData]);
            } else {
                respond(400, ["success" => false, "message" => "User not Registered"]);
            }
        } else {
            respond(400, ["success" => false, "message" => "User already Registered"]);
        }
    } elseif ((stripos($_SERVER['REQUEST_URI'], '/login') !== false) && $_SERVER['REQUEST_METHOD'] === "POST") {
        $email = $request["email"];
        $password = $request["password"];

        // Query users table for particular User
        $sql = "SELECT users.*, company.companyName, role.roleName FROM users JOIN role ON users.userRoleID = role.roleID
                LEFT JOIN company ON users.companyID = company.companyID WHERE users.userEmail = '$email'";
        $result = $db->query($sql);
        if ($db->errno)
            respond(500, ["success" => false, "message" => 'db error: ' . $db->error]);
        if ($result->num_rows === 1) {
            $userDetails = $result->fetch_array(MYSQLI_ASSOC);
            $pass_ok = password_verify($password, $userDetails['userPassword']);
            // Checks if the password matches
            if ($pass_ok) {
                $userData = ["id" => $userDetails["userID"], "firstname" => $userDetails["userFirstName"], "lastname" => $userDetails["userLastName"], "email" => $userDetails["userEmail"], "role" => $userDetails["roleName"], "companyName" => $userDetails["companyName"], "companyID" => $userDetails["companyID"]];
                respond(200, ["success" => true, "message" => "Login Successful", "userData" => $userData]);
            } else
                respond(400, ['success' => false, 'error' => 'login failed, invalid email or password']);
        } else {
            respond(400, ['success' => false, 'error' => 'User not registered']);
        }
    } elseif ((stripos($_SERVER['REQUEST_URI'], '/user') !== false) && $_SERVER['REQUEST_METHOD'] === "POST") {
        $companyID = $request["companyID"];
        $firstName = $request['firstName'];
        $lastName = $request['lastName'];
        $email = $request['email'];
        $email_suffix = explode("@", $request['email'])[1];
        // $courseID = $request['courseID'];
        $tel = $request['tel'];
        // $code = rand(200, 999);
        // $password = $firstName . $code;
        $hash = password_hash("LearningPlatform", PASSWORD_DEFAULT);

        // Checks if user already exists
        $sql = "SELECT * FROM users WHERE userEmail = '$email'";
        $result = $db->query($sql);
        if ($db->errno) {
            respond(500, ["success" => false, "message" => 'db error: ' . $db->error]);
        }
        if ($result->num_rows === 0) {
            // Checks if user already exists
            $sql = "SELECT * FROM company WHERE companyID = '$companyID'";
            $result = $db->query($sql);
            if ($db->errno) {
                respond(500, ["success" => false, "message" => 'db error: ' . $db->error]);
            }
            if ($result["emailSuffix"] === $email_suffix) {
                // if users does exist create a new user and assign to a company
                $sql = "INSERT INTO users (userFirstName, userLastName, userEmail, userPhone, userPassword, userRoleID, companyID) VALUES ('$firstName', '$lastName', '$email', '$tel', '$hash', 2, '$companyID')";
                $db->query($sql);
                if ($db->errno) {
                    respond(500, ["success" => false, "message" => 'db error: ' . $db->error]);
                }
                $userID = $db->insert_id;
            } else {
                respond(200, ["success" => false, "message" => "User Email not Company Email"]);
            }
            respond(200, ["success" => true, "message" => "User Created Successfully"]);
        } else {
            respond(200, ["success" => false, "message" => "User Already Exists"]);
        }
    } elseif ((stripos($_SERVER['REQUEST_URI'], '/course') !== false) && $_SERVER['REQUEST_METHOD'] === "GET") {

        // Get available courses and bundles
        $sql = "SELECT * FROM course";
        $result = $db->query($sql);
        if ($db->errno)
            respond(500, ["success" => false, "message" => 'db error: ' . $db->error]);

        if ($result->num_rows > 0) {
            $courseDetails = $result->fetch_all(MYSQLI_ASSOC);

            $sql2 = "SELECT courseB.bundleID, bundle.bundleTitle, 
                bundle.bundleDescription, bundle.price,
                COUNT(courseB.courseID) AS CourseCount, bundle.createDate 
                FROM courseBundle AS courseB
                JOIN bundle ON courseB.bundleID = bundle.bundleID
                GROUP BY courseB.bundleID;";
            $result2 = $db->query($sql2);
            if ($db->errno)
                respond(500, ["success" => false, "message" => 'db error: ' . $db->error]);
            $bundleDetails = $result2->fetch_all(MYSQLI_ASSOC);

            respond(200, ["success" => true, "message" => "Courses are available", "courses" => $courseDetails, "bundles" => $bundleDetails]);
        } else {
            respond(400, ['success' => false, 'error' => 'No Courses Available']);
        }
    } elseif ((stripos($_SERVER['REQUEST_URI'], '/courseEnrollment') !== false) && $_SERVER['REQUEST_METHOD'] === "POST") {


        $userID = $request["userID"];
        $courseID = $request["courseID"];

        // Check if user already enrolled for the particular course
        $sql = "SELECT * FROM courseEnrolment WHERE userID = '$userID' AND courseID = '$courseID'";
        $result = $db->query($sql);
        if ($db->errno) {
            respond(500, ["success" => false, "message" => 'db error: ' . $db->error]);
        }
        if ($result->num_rows === 0) {
            $sql = "INSERT INTO courseEnrolment (courseID, userID)  VALUES ('$courseID', '$userID')";
            $db->query($sql);
            if ($db->errno) {
                respond(500, ["success" => false, "message" => 'db error: ' . $db->error]);
            }
            respond(200, ["success" => true, "message" => "Enrollment Successful"]);
        } else {
            respond(200, ["success" => false, "message" => "Already Enrolled"]);
        }
    } elseif ((stripos($_SERVER['REQUEST_URI'], '/enrolledCourses') !== false) && $_SERVER['REQUEST_METHOD'] === "POST") {

        $userID = $request["userID"];

        $sql = "SELECT * FROM courseEnrolment JOIN course ON courseEnrolment.courseID = course.courseID WHERE courseEnrolment.userID = '$userID'";
        $result = $db->query($sql);
        if ($db->errno) {
            respond(500, ["success" => false, "message" => 'db error: ' . $db->error]);
        }
        if ($result->num_rows > 0) {
            $enrolledCourses = $result->fetch_all(MYSQLI_ASSOC);
            respond(200, ["success" => true, "message" => "You have enrolled courses", "enrolledCourses" => $enrolledCourses]);
        }
    } elseif ((stripos($_SERVER['REQUEST_URI'], '/companyUsers') !== false) && $_SERVER['REQUEST_METHOD'] === "POST") {

        $companyID = $request["companyID"];

        $sql = "SELECT userID, userFirstName, userLastName, userEmail FROM users WHERE companyID = '$companyID' AND userRoleID = 2";

        $result = $db->query($sql);
        if ($db->errno) {
            respond(500, ["success" => false, "message" => 'db error: ' . $db->error]);
        }
        if ($result->num_rows > 0) {
            $companyUsers = $result->fetch_all(MYSQLI_ASSOC);
            respond(200, ["success" => true, "message" => "Users Available", "users" => $companyUsers]);
        } else {
            respond(400, ["success" => false, "message" => "No Users Available"]);
        }
    } else {
        respond(404, array('success' => false, 'message' => 'resource or endpoint not found'));
    }
} catch (Exception $e) {
    try {
        $entry = ['time' => $time, 'request' => $request, 'error' => json_encode($e)];
        $fp = file_put_contents('logs/' . $log . '.txt', json_encode($entry, JSON_PRETTY_PRINT), FILE_APPEND);
        respond(500, array('success' => false, 'message' => $e->getMessage()));
    } catch (Exception $ex) {
        respond(500, array('success' => false, 'message' => $e->getMessage() . '|' . $ex->getMessage()));
    }
} finally {
    if ($db)
        $db->close();
}
