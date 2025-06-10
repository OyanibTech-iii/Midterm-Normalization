<?php
include 'conn.php'; // Database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $barber_ids = $_POST['barber_id']; // Array of selected barbers
    $service_ids = $_POST['service_id']; // Array of selected services
    $appointment_dates = $_POST['appointment_date']; // Array of selected dates

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

// Fetch barbers and services
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/png" href="barber-png.png">
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

        /* Form Styles */
        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Flexbox Container for Barbers & Services */
        .tables-container {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            align-items: flex-start;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #222;
            border-radius: 8px;
            overflow: hidden;
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

        /* Inputs and Selects */
        input,
        select {
            letter-spacing: 2px;
            width: 100%;
            padding: 8px;
            background: transparent;
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
            margin: 0 auto 20px;
            width: 500px;
        }

        footer {
            text-align: center;
            color: #03e9f4;
            margin-top: 140px;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 3px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: cyan;
            border-radius: 2px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #00b3b3;
        }

        /* Style for datetime-local */
        input[type="datetime-local"] {
            color: #00b3b3;
            border: 2px solid #00b3b3;
            padding: 8px;
            border-radius: 4px;
            font-size: 16px;
            width: 100%;
            box-sizing: border-box;
        }

        input[type="datetime-local"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
        }

        input[type="datetime-local"]:hover {
            border-color: #00ffff;
        }

        input[type="datetime-local"]:focus {
            outline: none;
            border-color: #00ffff;
            box-shadow: 0 0 5px rgba(0, 255, 255, 0.5);
        }

        /* Links */
        a {
            text-align: center;
            text-decoration: none;
            color: #00ffff;
        }

        a:hover {
            text-decoration: underline;
        }

        /* Checkbox Styles */
        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-label input {
            transform: scale(1.2);
        }

        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #03e9f4;
            background-color: transparent;
            /* Baguhin ang kulay ng checkbox kapag na-check */
            cursor: pointer;

            appearance: none;
            /* Tinatanggal ang default style ng browser */

            border: 2px solid #03e9f4;
            /* Kulay ng border */
            border-radius: 4px;
            /* Para sa medyo rounded na style */
            background-color: transparent;
            /* Ginagawang transparent ang background */
            cursor: pointer;
            display: inline-block;
            position: relative;

        }

        input[type="checkbox"]:checked {
            background-color: transparent;
        }

        input[type="checkbox"]::before {
            content: "✔";
            /* Checkmark symbol */
            font-size: 16px;
            color: #03e9f4;
            /* Kulay ng checkmark */
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: none;
            /* Nakatago by default */
        }

        /* Ipakita ang checkmark kapag naka-check */
        input[type="checkbox"]:checked::before {
            display: block;
        }

        /* Kapag naka-hover */
        input[type="checkbox"]:hover {
            transform: scale(1.1);
        }

        td.align-price {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .price {
            margin-right: 10px;
        }

        /* Style for the suggestions dropdown */
        #email-suggestions {
            display: none;
            /* Hidden by default */
            position: absolute;
            background: #1b1b1b;
            border: 1px solid #03e9f4;
            border-radius: 4px;
            padding: 5px;
            z-index: 1000;
            width: 200px;
            margin-top: 5px;
        }

        #email-suggestions div {
            padding: 5px;
            cursor: pointer;
            color: #03e9f4;
        }

        #email-suggestions div:hover {
            background: #333;
        }

        h3 {
            font-size: 2.5rem;
            text-align: center;
            color: #03e9f4;
            margin-bottom: 2px;
        }

        p {
            margin-top: 0;
            text-align: center;
            color: #03e9f4;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="rawtabledisplay.php" style="width: 60%;margin-left:75%; margin-bottom: 30px;"> <i
                class="fa fa-search"></i> Check Normalization</a>
        <h3>The Third Wave Barbershop</h3>
        <p><i>"Crafted Cuts, Luxurious Feels – Your Style, Our Masterpiece."</i></p>
        <form method="POST" style="margin-top: 150px;">
            <table>
                <tr>
                    <th style="width:20%">Appointment Form</th>
                </tr>
            </table>
            <table>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                </tr>
                <tr>
                    <td><input type="text" name="name" required></td>
                    <td>
                        <input type="text" id="email" name="email" required>
                        <div id="email-suggestions">
                            <div onclick="selectSuggestion('gmail.com')">gmail.com</div>
                            <div onclick="selectSuggestion('yahoo.com')">yahoo.com</div>
                            <div onclick="selectSuggestion('outlook.com')">outlook.com</div>
                            <div onclick="selectSuggestion('icloud.com')">icloud.com</div>
                            <div onclick="selectSuggestion('hotmail.com')">hotmail.com</div>
                        </div>
                    </td>
                    <td><input type="text" name="phone"  required></td>
                    <!-- maxlength="11" thi logic is for the max-lenght control of the number entered by the user -->
                </tr>
            </table>
            <div class="tables-container">
                <!-- Barbers Table -->
                <table>
                    <tr>
                        <th>Select Barbers</th>
                    </tr>
                    <?php foreach ($barbers as $barber): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="barber_id[]" value="<?php echo $barber['barber_id']; ?>"
                                    style="margin-right: 10px;">
                                <?php echo $barber['name']; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <!-- Services Table -->
                <table>
                    <tr>
                        <th>Select Services</th>
                    </tr>
                    <?php foreach ($services as $service): ?>
                        <tr>
                            <td class="align-price">
                                <div>
                                    <input type="checkbox" name="service_id[]" value="<?php echo $service['service_id']; ?>"
                                        style="margin-right: 10px;">
                                    <?php echo $service['service_name']; ?>
                                </div>
                                <div class="price">
                                    ₱ <?php echo $service['price']; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <table id="dateTable">
                <tr>
                    <th>Appointment Date</th>
                    <th></th>
                    <th><a href="bookingform.php"><i class="fas fa-redo"></i> Clear all Fields</a></th>
                </tr>
                <tr>
                    <td> <button type="button" onclick="addDate()">Add Date <i class="fas fa-plus-circle"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><input type="datetime-local" name="appointment_date[]" required></td>
                    <td><button type="button" onclick="this.parentNode.parentNode.remove()">Remove <i
                                class="far fa-trash-alt"></i></button></td>
                </tr>

            </table>
            <button type="submit" style="width: 40%;"><i class="fa fa-check-circle"></i> Reserve Now</button>
        </form>
    </div>
    <script>
        function addDate() {
            let dateTable = document.getElementById("dateTable");
            let newRow = dateTable.insertRow();
            newRow.innerHTML = '<td><input type="datetime-local" name="appointment_date[]" required></td>' +
                '<td><button type="button" onclick="this.parentNode.parentNode.remove()">Remove <i class="far fa-trash-alt"></i></button></td>';
        }

        // Function to handle email input and show suggestions
        const emailInput = document.getElementById('email');
        const emailSuggestions = document.getElementById('email-suggestions');

        emailInput.addEventListener('input', function () {
            const value = emailInput.value;
            const emails = value.split(',').map(email => email.trim()); // Split emails by comma
            const lastEmail = emails[emails.length - 1]; // Get the last email being typed

            if (lastEmail.includes('@')) {
                // Show suggestions if "@" is present in the last email
                emailSuggestions.style.display = 'block';
                positionSuggestions(emailInput); // Position the suggestions dropdown
            } else {
                // Hide suggestions if "@" is not present
                emailSuggestions.style.display = 'none';
            }
        });

        // Function to position the suggestions dropdown
        function positionSuggestions(input) {
            const rect = input.getBoundingClientRect();
            emailSuggestions.style.top = `${rect.bottom + window.scrollY}px`;
            emailSuggestions.style.left = `${rect.left + window.scrollX}px`;
        }

        // Function to select a suggestion
        function selectSuggestion(domain) {
            const value = emailInput.value;
            const emails = value.split(',').map(email => email.trim()); // Split emails by comma
            const lastEmail = emails[emails.length - 1]; // Get the last email being typed

            if (lastEmail.includes('@')) {
                const emailParts = lastEmail.split('@'); // Split the last email by "@"
                emails[emails.length - 1] = emailParts[0] + '@' + domain; // Append the selected domain
                emailInput.value = emails.join(', '); // Join emails back with commas
            }

            emailSuggestions.style.display = 'none'; // Hide suggestions after selection
        }
    </script>
</body>

</html>