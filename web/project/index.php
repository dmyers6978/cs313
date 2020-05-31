<?php
if(!isset($_SESSION)){
    session_start();
}
if(!isset($_SESSION['loggedIn'])){
    $_SESSION['loggedIn'] = FALSE;
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once './model/mainModel.php';
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
if(!$action){
$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
}

switch($action){
    case 'contact':
        $table = '';
        $services = getServices();
        foreach($services as $service){
            $table .= "<tr><td>$service[servicename]</td><td>$service[servicetime]</td><td>$<label for='$service[serviceid]'>$service[serviceprice]</label>&nbsp;<input type='checkbox' id='$service[serviceid]' onchange='addCost(this)'></td></tr>";
        }
        include './views/contact.php';
        exit;
    break;

    case 'about':
        $_SESSION['message'] = "<p>The About Me page is still under construction.</p>";
        header('location: ./');
        exit;
    break;

    case 'status':
        if(!$_SESSION['loggedIn']){
            $_SESSION['location'] = './?action=status';
            $_SESSION['message'] = "<p>You need to login to see the progress on your bike.</p>";
            header('location: ./?action=login');
            exit;
        }
        if($_SESSION['userData']['userlevel'] === 2){
            $jobList = getAllJobs();
            $statusList = getStatusList();
            $table = "<table><thead><tr><th>Customer</th><th>Description</th><th>status</th><th>Dropped Off</th></tr></thead><tbody>";
            foreach($jobList as $job){
                $table .= "<tr><td>$job[userfirstname] $job[userlastname]</td><td>$job[jobtitle]</td><td>$job[statusname]<form method='post' action='./'><select name='statusId'><option value='0'>Select an option</option>";
                foreach($statusList as $status){
                    $table .= "<option value='$status[statusid]'>$status[statusname]</option>";
                }
                $table .= "</select><input type='hidden' name='action' value='editStatus'><input type='hidden' name='jobId' value='$job[jobid]'><input type='submit' value='Change Status'></form></td><td>".date_format(date_create($job['datetime']), 'm-d-Y H:i:s')."</td></tr>";
            }
            $table .= "</tbody></table>";
            $table .= "<form method='post' action='./'>
            <div><label for='jobTitle'>Description: </label><input type='text' name='jobTitle' id='jobTitle'></div>
            <div><label for='userid'>Select a user: </label><select id='userid' name='userid'><option value=0>Select a user</option>";
            $userList = getusers();
            foreach($userList as $user){
                $table .= "<option value='$user[userid]'>$user[userfirstname] $user[userlastname]</option>";
            }
            $serviceList = getServices();
            $table .= "</select></div><label for='serviceId'>Select services: </label>";
            foreach($serviceList as $service){
                $table .= "<div><span>$service[servicename] </span><input type='checkbox' name='services[]' value='$service[serviceid]'></div>";
            }
            $table .= "<input type='hidden' name='action' value='addJob'><input type='submit' value='Add Job'></form>";
        } else{
        $jobList = getJobs($_SESSION['userData']['userid']);
        $table = "<table><thead><tr><th>Description</th><th>Status</th></tr></thead><tbody>";
        foreach($jobList as $job){
            $table .= "<tr><td>$job[jobtitle]</td><td>$job[statusname]</td></tr>";
        }
        $table .= "</tbody></table>";
        }
        include './views/jobs.php';
        exit;

    break;

    case 'login':
        include './views/login.php';
        exit;
    break;

    case 'Login':
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
        $exists = checkUserEmail($email);
        $exists = $exists[0];
        if(!$exists){
            $_SESSION['message'] = "<p>We couldn't find any users with that email. <a href='./?action=register'>Click here to register</a> or try again.</p>";
            include './views/login.php';
            exit;
        }

        $match = password_verify($password, $exists['userpassword']);
        if(!$match){
            $_SESSION['message'] = "<p>Please check your password and try again.</p>";
            include './views/login.php';
            exit;
        }
        array_pop($exists);
        $_SESSION['loggedIn'] = TRUE;
        $_SESSION['userData'] = $exists;
        $_SESSION['message'] = "<p>Welcome back $exists[userfirstname].</p>";
        if(isset($_SESSIO['location'])){
            $location = "location: $_SESSION[location]";
            header($location);
            exit;
        } else{
        header('location: ./');
        exit;
        }
    break;


    case 'register':
        include './views/register.php';
        exit;
    break;

    case 'Register':
        $fName = filter_input(INPUT_POST, 'fName', FILTER_SANITIZE_STRING);
        $lName = filter_input(INPUT_POST, 'lName', FILTER_SANITIZE_STRING);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_VALIDATE_INT);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

        if(empty($fName) || empty($lName) || empty($phone) || empty($email) || empty($password)){
            $_SESSION['mesage'] = "<p>Please fill out all form fields.</p>";
            include './views/register.php';
            exit;
        }

        $hashedPass = password_hash($password, PASSWORD_DEFAULT);

        $success = addUser($fName, $lName, $phone, $email, $hashedPass);
        if($success === 1){
            $_SESSION['message'] = "<p>Thank you for registering $fName, please login to continue.</p>";
            header('location: ./?action=login');
            exit;
        } else{
            $_SESSION['message'] = "<p>Something went wrong, please try again later.</p>";
            include './views/register.php';
            exit;
        }
    break;

    case 'logout':
        session_unset();
        session_destroy();
        header('location: ./');
    break;

    case 'addJob':
        $jobTitle = filter_input(INPUT_POST, 'jobTitle', FILTER_SANITIZE_STRING);
        $userId = filter_input(INPUT_POST, 'userid', FILTER_VALIDATE_INT);
        $serviceId = filter_input(INPUT_POST, 'services', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);

        $success = addJob($jobTitle, $userId);
        $jobId = getJobId($jobTitle, $userId);
        $jobId = $jobId[0]['jobid'];
        foreach($serviceId as $service){
        $success = addJobService($jobId, $service);
        }
        header('location: ./?action=status');
        exit;
    break;

    case 'editStatus':
        $statusId = filter_input(INPUT_POST, 'statusId', FILTER_VALIDATE_INT);
        $jobId = filter_input(INPUT_POST, 'jobId', FILTER_VALIDATE_INT);
        $success = updateJob($statusId, $jobId);
        header('location: ./?action=status');
    break;

    case 'email':
        $_SESSION['message'] = "<p>The email feature is still under construction.</p>";
        header('location: ./');
        exit;
    break;

    default:
    include './views/home.php';
    exit;
}