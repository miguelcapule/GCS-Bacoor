<?php
require '../../includes/session.php'; // Include session handling
require '../../includes/conn.php'; // Include database connection

$schedule_id = $_GET['schedule_id']; // Get the schedule ID from the URL

// Fetch the schedule details from the database using $schedule_id
$schedule_info = mysqli_query($conn, "SELECT * FROM tbl_schedules WHERE schedule_id = '$schedule_id'");

if (!$schedule_info) {
    $_SESSION['error'] = 'Failed to fetch schedule details: ' . mysqli_error($conn);
    header('location: ../somewhere.php'); // Redirect to a relevant page
    exit();
}

$schedule = mysqli_fetch_array($schedule_info);

// Ensure the user is a student
if ($_SESSION['role'] != 'Student') {
    $_SESSION['error'] = "Unauthorized access.";
    header('location: ../../login.php');
    exit();
}

// Get student details from the session
$student_id = $_SESSION['id'];
$student_lname = $_SESSION['name']; // Assuming session stores student lname, fname together

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $date = $_POST['date'];
    $time = $_POST['time'];
    $purpose = $_POST['purpose'];

    // Check for scheduling conflicts
    $conflictCheck = $conn->prepare("SELECT * FROM tbl_schedules WHERE appointment_date = ? AND appointment_time = ? AND schedule_id != ?");
    $conflictCheck->bind_param("ssi", $date, $time, $schedule_id);
    $conflictCheck->execute();
    $result = $conflictCheck->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Conflict: The selected time slot is already booked.";
    } else {
        // Prepare the SQL statement to update the schedule
        $stmt = $conn->prepare("UPDATE tbl_schedules SET appointment_date = ?, appointment_time = ?, purpose = ? WHERE schedule_id = ?");
        $stmt->bind_param("sssi", $date, $time, $purpose, $schedule_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Schedule updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating schedule: " . $stmt->error;
        }

        $stmt->close();
    }

    $conflictCheck->close();

    // Redirect to the same page to display success/error message
    header('location: ../schedules/edit.schedule.php?schedule_id=' . $schedule_id); 
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Scheduled Appointment | GCS Bacoor</title>

    <?php include '../../includes/links.php'; ?>
    <!-- SweetAlert CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css">
    <!-- SweetAlert JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>
</head>
<body class="hold-transition layout-fixed layout-navbar-fixed layout-footer-fixed">
    <div class="wrapper">
        <?php include '../../includes/navbar.php'; ?>
        <?php include '../../includes/sidebar.php'; ?>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Edit Schedule</h1>
                        </div><!-- /.col -->
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item active">Edit Schedule</li>
                            </ol>
                        </div><!-- /.col -->
                    </div><!-- /.row -->
                </div><!-- /.container-fluid -->
            </div>
            <!-- /.content-header -->

            <?php
            // Display success or error messages
            if (isset($_SESSION['success'])) {
                echo "<script>
                        swal('Success!', '{$_SESSION['success']}', 'success').then(function() {
                            window.location = '../schedules/edit.schedule.php?schedule_id=$schedule_id';
                        });
                      </script>";
                unset($_SESSION['success']);
            } elseif (isset($_SESSION['error'])) {
                echo "<script>
                        swal('Error!', '{$_SESSION['error']}', 'error').then(function() {
                            window.location = '../schedules/edit.schedule.php?schedule_id=$schedule_id';
                        });
                      </script>";
                unset($_SESSION['error']);
            }
            ?>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Schedule Info</h3>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="" id="scheduleForm">
                                        <div class="form-group mb-4">
                                            <label for="student_name">Student</label>
                                            <input type="text" name="student_name" class="form-control" value="<?php echo $student_lname; ?>" readonly>
                                        </div>
                                        <div class="form-group mb-4">
                                            <label for="date">Date</label>
                                            <input type="date" name="date" class="form-control" id="date" value="<?php echo $schedule['appointment_date']; ?>" required>
                                        </div>
                                        <div class="form-group mb-4">
                                            <label for="time">Time</label>
                                            <select id="time" name="time" class="form-control" required>
                                                <option value="">Select Time</option>
                                                <?php
                                                // Define available time slots
                                                $time_slots = [
                                                    "08:00", "09:00", "10:00", "11:00",
                                                    "12:00", "13:00", "14:00", "15:00", 
                                                    "16:00", "17:00"
                                                ];
                                                foreach ($time_slots as $slot) {
                                                    // Check if the slot is already booked
                                                    $selected = ($slot == $schedule['appointment_time']) ? 'selected' : '';
                                                    echo "<option value='$slot' $selected>" . date("h:i A", strtotime($slot)) . "</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="form-group mb-4">
                                            <label for="purpose">Purpose</label>
                                            <input type="text" name="purpose" class="form-control" id="purpose" value="<?php echo $schedule['purpose']; ?>" required>
                                        </div>
                                        <button type="submit" name="submit" class="btn btn-danger">Update Schedule</button>
                                    </form>
                                </div>
                                <!-- /.card-body -->
                            </div>
                            <!-- /.card -->
                        </div>
                    </div>
                </div>
            </section>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->
        <?php include '../../includes/footer.php'; ?>

        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">
            <!-- Control sidebar content goes here -->
        </aside>
        <!-- /.control-sidebar -->
    </div>
    <!-- ./wrapper -->

    <?php include '../../includes/script.php'; ?>
</body>
</html>

<?php
$conn->close(); // Close the database connection
?>
