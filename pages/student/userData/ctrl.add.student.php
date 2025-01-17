<?php
include '../../../includes/session.php';

if (isset($_POST['submit'])) {

    $image = addslashes(file_get_contents($_FILES['image']['tmp_name']));
    
    $firstname = mysqli_real_escape_string($conn, $_POST['firstname']);
    $lastname = mysqli_real_escape_string($conn, $_POST['lastname']);
    $midname = mysqli_real_escape_string($conn, $_POST['midname']);
    $lrn = mysqli_real_escape_string($conn, $_POST['lrn']);
    $stud_no = mysqli_real_escape_string($conn, $_POST['stud_no']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']); // New email variable
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $password2 = mysqli_real_escape_string($conn, $_POST['password2']);

    $check_studno = mysqli_query($conn, "SELECT * FROM tbl_students WHERE stud_no = '$stud_no'") or die(mysqli_error($conn));
    $check_lrn = mysqli_query($conn, "SELECT * FROM tbl_students WHERE lrn = '$lrn'") or die(mysqli_error($conn));
    $check_email = mysqli_query($conn, "SELECT * FROM tbl_students WHERE email = '$email'") or die(mysqli_error($conn)); // Check for existing email

    $result = mysqli_num_rows($check_studno);
    $result2 = mysqli_num_rows($check_lrn);
    $result3 = mysqli_num_rows($check_email); // Result for email check

    if ($result > 0 || $result2 > 0 || $result3 > 0) {
        if ($result > 0 && $result2 > 0) {
            $_SESSION['lrn-studno'] = true;
            header('location: ../add.student.php');
        } elseif ($result2 > 0) {
            $_SESSION['double-lrn'] = true;
            header('location: ../add.student.php');
        } elseif ($result > 0) {
            $_SESSION['double-studno'] = true;
            header('location: ../add.student.php');
        } elseif ($result3 > 0) { // Check for duplicate email
            $_SESSION['double-email'] = true;
            header('location: ../add.student.php');
        }
    } else {
        if ($password != $password2) {
            $_SESSION['password_unmatch'] = true;
            header('location: ../add.student.php');
        } else {
            $hashpwd = password_hash($password, PASSWORD_BCRYPT);
            $insertUser = mysqli_query($conn, "INSERT INTO tbl_students (img, student_fname, student_lname, student_mname, stud_no, lrn, username, password, email) VALUES ('$image', '$firstname', '$lastname', '$midname', '$stud_no', '$lrn', '$username', '$hashpwd', '$email')"); // Insert email
            $_SESSION['success'] = true;
            header('location: ../add.student.php');
        }
    }
}
