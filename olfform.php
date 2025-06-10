<?php
include 'conn.php'; // Database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $barber_ids = $_POST['barber_id']; // Array of selected barbers
    $service_ids = $_POST['service_id']; // Array of selected services
    $appointment_dates = $_POST['appointment_date']; // Array of selected dates

    // Debugging: Print the data being inserted
    echo "Name: $name, Email: $email, Phone: $phone<br>";

    // Check if user exists
    $sql_check = "SELECT user_id FROM users WHERE email = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->execute([$email]);
    $user = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $user_id = $user['user_id'];
    } else {
        // Insert new user
        $sql_user = "INSERT INTO users (name, email, phone) VALUES (?, ?, ?)";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->execute([$name, $email, $phone]);
        $user_id = $conn->lastInsertId();
    }

    // Loop through selected barbers, services, and dates to create appointments
    foreach ($barber_ids as $barber_id) {
        foreach ($service_ids as $service_id) {
            foreach ($appointment_dates as $appointment_date) {
                // Check if barber exists
                $sql_check_barber = "SELECT barber_id FROM barbers WHERE barber_id = ?";
                $stmt_check_barber = $conn->prepare($sql_check_barber);
                $stmt_check_barber->execute([$barber_id]);
                $barber = $stmt_check_barber->fetch(PDO::FETCH_ASSOC);

                if (!$barber) {
                    die("Invalid barber ID: $barber_id");
                }

                // Insert appointment
                $sql_appointment = "INSERT INTO appointments (user_id, barber_id, service_id, appointment_date, status) 
                                    VALUES (?, ?, ?, ?, 'Pending')";
                $stmt_appointment = $conn->prepare($sql_appointment);
                $stmt_appointment->execute([$user_id, $barber_id, $service_id, $appointment_date]);
            }
        }
    }
    header('Location: rawtabledisplay.php');
}

// Fetch barbers and services (same as before)
$barbers = [
    ["barber_id" => 1, "name" => "Jordan Lastimoso"],
    ["barber_id" => 2, "name" => "Jhon Paul Gutib"],
    ["barber_id" => 3, "name" => "Christian Sojor"],
    ["barber_id" => 4, "name" => "Christopher Bantam"],
    ["barber_id" => 5, "name" => "Ramil Delacruz"]
];

$services = [
    ["service_id" => 1, "service_name" => "Haircut", "price" => 60],
    ["service_id" => 2, "service_name" => "Haircut with Massage", "price" => 100],
    ["service_id" => 3, "service_name" => "Hair Coloring", "price" => 300],
    ["service_id" => 4, "service_name" => "Haircut with Drinks", "price" => 230],
    ["service_id" => 5, "service_name" => "Premium Package", "price" => 950]
];
?>

<!DOCTYPE html>
<html>

<head>
    <title>Book an Appointment | BarbersOyanib</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" type="image/png" href="icon white.png">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #151515;
            color: #fff;
        }

        .container {
            width: 60%;
            margin: 20px auto;
            padding: 20px;
            background: #1b1b1b;
            border-radius: 8px;
            box-shadow: 0 0 10px rgb(26, 194, 206);
        }

        h2 {
            font-size: 2.5rem;
            text-align: center;
            color: #03e9f4;
            margin-bottom: 20px;
        }

        /* Form Styles */
        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #222;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #333;
        }

        th {
            background: #333;
            color: #03e9f4;
        }

        input,
        select {
            letter-spacing: 2px;
            width: 100%;
            padding: 8px;
            background: #151515;
            color: #03e9f4;
            border: 1px solid #03e9f4;
            border-radius: 4px;
            outline: none;
        }

        input:focus,
        select:focus {
            border-color: #03e9f4;
            box-shadow: 0 0 5px rgba(12, 169, 208, 1);
        }

        /* Buttons */
        button {
            padding: 10px 20px;
            background: #03e9f4;
            color: rgb(1, 37, 39);
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: rgb(61, 243, 234);
            box-shadow: 0 0 10px rgb(74, 226, 246);
        }

        .delete-date {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
        }

        .delete-date:hover {
            background: #ff5555;
            box-shadow: 0 0 10px #ff5555;
        }

        .form-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .logo {
            display: block;
            margin-top: 40px;
            margin: 0 auto 20px;
            width: 500px;
        }

        footer {
            text-align: center;
            color: #03e9f4;
            margin-top: 140px;
        }

        /* For WebKit browsers (Chrome, Safari, Edge) */
        ::-webkit-scrollbar {
            width: 3px;
            /* Make the scrollbar thinner */
        }

        ::-webkit-scrollbar-track {
            background: transparent;
            /* Track color */
        }

        ::-webkit-scrollbar-thumb {
            background: cyan;
            /* Thumb color */
            border-radius: 2px;
            /* Optional: Rounded corners */
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #00b3b3;
            /* Thumb color on hover */
        }

        /* Style the datetime-local input */
        input[type="datetime-local"] {
            color: #00b3b3;
            /* Text color */
            border: 2px solid #00b3b3;
            /* Border color */
            padding: 8px;
            border-radius: 4px;
            font-size: 16px;
            width: 100%;
            /* Make it fit the table cell */
            box-sizing: border-box;
            /* Ensure padding doesn't affect width */
        }

        /* Change the color of the calendar icon (WebKit browsers only) */
        input[type="datetime-local"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            /* Invert the icon color (e.g., black to white) */
        }

        input[type="datetime-local"]:hover {
            border-color: #00ffff;
            /* Change border color on hover */
        }

        input[type="datetime-local"]:focus {
            outline: none;
            /* Remove default focus outline */
            border-color: #00ffff;
            /* Change border color on focus */
            box-shadow: 0 0 5px rgba(0, 255, 255, 0.5);
            /* Add a glow effect */
        } 
        a{
        text-align: center;
        text-decoration: none;
        color:  #00ffff;
        }
        a:hover{
            font-weight: bold;
        }
    </style>
    <script>
        let barbers = <?php echo json_encode($barbers); ?>;
        let services = <?php echo json_encode($services); ?>;

        function addDate() {
            let dateTable = document.getElementById("dateTable");
            let newRow = dateTable.insertRow();
            let cell1 = newRow.insertCell(0);
            let cell2 = newRow.insertCell(1);

            let input = document.createElement("input");
            input.type = "datetime-local";
            input.name = "appointment_date[]";
            input.required = true;
            cell1.appendChild(input);

            let deleteButton = document.createElement("button");
            deleteButton.innerHTML = '<i class="fa fa-trash"></i> Delete';
            deleteButton.className = "delete-date";
            deleteButton.onclick = function() {
                this.parentNode.parentNode.remove();
            };
            cell2.appendChild(deleteButton);
        }
    </script>
</head>

<body>
    <div class="container">
        <img src="icon white.png" alt="logo" class="logo">
        <h2>Book an Appointment</h2>
        <form method="POST" autocomplete="off">
            <!-- User Information Table -->
            <table id="userInfoTable">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                </tr>
                <tr>
                    <td><input type="text" name="name" required></td>
                    <td><input type="text" name="email" required></td>
                    <td><input type="text" name="phone" required></td>
                </tr>
            </table>

            <!-- Barber and Service Selection Table -->
            <table>
                <tr>
                    <th>Barber</th>
                    <th>Service</th>
                </tr>
                <tr>
                    <td>
                        <select name="barber_id[]" multiple required>
                            <?php foreach ($barbers as $barber): ?>
                                <option value="<?php echo $barber['barber_id']; ?>"><?php echo $barber['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <select name="service_id[]" multiple required>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo $service['service_id']; ?>">
                                    <?php echo $service['service_name']; ?> - ₱<?php echo $service['price']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>

            <!-- Appointment Dates Table -->
            <table id="dateTable">
                <tr>
                    <th>Appointment Date</th>
                    <th>Action</th>
                </tr>
                <tr>
                    <td><input type="datetime-local" name="appointment_date[]" required></td>
                    <td><button type="button" class="delete-date" onclick="this.parentNode.parentNode.remove()"><i class="fa fa-trash"></i> Delete</button></td>
                </tr>
            </table>

            <div class="form-buttons">
                <button type="button" onclick="addDate()"><i class="fa fa-plus"></i> Add Date</button>
                <button type="submit"><i class="fa fa-check"></i> Reserve Now</button>
            </div>
            <a href="rawtabledisplay.php">Check Normalization Algorithm</a>
        </form>
        <footer>
            &copy; <?php echo date("Y") . ' Pacifico Oyanib III, OYANIBCUT barber shop. All Rights Reserved.'; ?>
        </footer>
    </div>
</body>

</html>