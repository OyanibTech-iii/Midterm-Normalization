<?php
include 'conn.php';

function splitData($data)
{
    return array_filter(array_map('trim', explode(',', $data)));
}

function validateData($row)
{
    return array_map(fn($value) => htmlspecialchars(trim($value ?? '')), $row);
}

// Fetch raw data from the database
$rawSql = "
    SELECT 
        users.user_id,
        users.name AS user_name,
        users.email,
        users.phone,
        barbers.barber_id,
        barbers.name AS barber_name,
        services.service_id,
        services.service_name,
        appointments.appointment_id,
        appointments.appointment_date,
        appointments.status
    FROM 
        appointments
    JOIN users ON appointments.user_id = users.user_id
    JOIN barbers ON appointments.barber_id = barbers.barber_id
    JOIN services ON appointments.service_id = services.service_id
    ORDER BY users.user_id, appointments.appointment_date
";

$rawStmt = $conn->prepare($rawSql);
$rawStmt->execute();
$rawData = $rawStmt->fetchAll(PDO::FETCH_ASSOC);

// Group raw data by user_id
$groupedRawData = [];
foreach ($rawData as $row) {
    $userId = $row['user_id'];
    if (!isset($groupedRawData[$userId])) {
        $groupedRawData[$userId] = [
            'user_id' => $userId,
            'user_name' => $row['user_name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'barber_names' => [],
            'service_names' => [],
            'appointment_dates' => [],
            'statuses' => []
        ];
    }
    $groupedRawData[$userId]['barber_names'][] = $row['barber_name'];
    $groupedRawData[$userId]['service_names'][] = $row['service_name'];
    $groupedRawData[$userId]['appointment_dates'][] = $row['appointment_date'];
    $groupedRawData[$userId]['statuses'][] = $row['status'];
}

// Prepare the grouped data for display
$displayRawData = [];
foreach ($groupedRawData as $userId => $data) {
    $displayRawData[] = [
        'user_id' => $userId,
        'user_name' => $data['user_name'],
        'email' => $data['email'],
        'phone' => $data['phone'],
        'barber_names' => implode(', ', array_unique($data['barber_names'])),
        'service_names' => implode(', ', array_unique($data['service_names'])),
        'appointment_dates' => implode(', ', array_unique($data['appointment_dates'])),
        'statuses' => implode(', ', array_unique($data['statuses']))
    ];
}
// Fetch and prepare 1NF data
$nf1Sql = "
    SELECT 
        users.user_id,
        users.name AS user_name,
        users.email,
        users.phone,
        barbers.name AS barber_names,
        services.service_name AS service_names,
        appointments.appointment_date,
        appointments.status
    FROM 
        appointments
    JOIN users ON appointments.user_id = users.user_id
    JOIN barbers ON appointments.barber_id = barbers.barber_id
    JOIN services ON appointments.service_id = services.service_id
    ORDER BY users.user_id, appointments.appointment_date
";

$nf1Stmt = $conn->prepare($nf1Sql);
$nf1Stmt->execute();
$nf1Data = $nf1Stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to split and flatten data
function flattenData($data)
{
    $flattened = [];
    foreach ($data as $row) {
        // Split each field that may contain multiple values
        $userNames = splitData($row['user_name']);
        $emails = splitData($row['email']);
        $phones = splitData($row['phone']);
        $barberNames = splitData($row['barber_names']);
        $serviceNames = splitData($row['service_names']);
        $appointmentDates = splitData($row['appointment_date']);
        $statuses = splitData($row['status']);

        // Create a new row for each combination of split values
        foreach ($userNames as $userName) {
            foreach ($emails as $email) {
                foreach ($phones as $phone) {
                    foreach ($barberNames as $barberName) {
                        foreach ($serviceNames as $serviceName) {
                            foreach ($appointmentDates as $appointmentDate) {
                                foreach ($statuses as $status) {
                                    $flattened[] = [
                                        'user_id' => $row['user_id'],
                                        'user_name' => $userName,
                                        'email' => $email,
                                        'phone' => $phone,
                                        'barber_names' => $barberName,
                                        'service_names' => $serviceName,
                                        'appointment_date' => $appointmentDate,
                                        'status' => $status
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    return $flattened;
}

//instead of using a DISTINCT clause, we can use a combination of array_map and array_unique to achieve the same result.
// This will ensure that the data is unique based on the user_id and appointment_date.
// Flatten the 1NF data
$flattenedNf1Data = flattenData($nf1Data);
// Fetch and prepare 2NF data
$nf2Table1 = []; // Users table
$nf2Table2 = []; // Emails table
$nf2Table3 = []; // Phones table
$nf2Table4 = []; // Barbers table
$nf2Table5 = []; // Services table
$nf2Table6 = []; // Appointments table

// Track displayed values to avoid duplicates
$displayedUserNames = [];
$displayedEmails = [];
$displayedPhones = [];
$displayedAppointmentDates = [];
$displayedBarbers = []; // Track displayed barbers
$displayedServices = []; // Track displayed services

foreach ($rawData as $row) {
    // Split user_name, email, and phone if they contain commas
    $userNames = splitData($row['user_name']);
    $emails = splitData($row['email']);
    $phones = splitData($row['phone']);

    // Users table
    foreach ($userNames as $userName) {
        if (!in_array($userName, $displayedUserNames)) {
            $nf2Table1[] = [
                'user_id' => $row['user_id'],
                'user_name' => $userName
            ];
            $displayedUserNames[] = $userName; // Mark this value as displayed
        }
    }

    // Emails table
    foreach ($emails as $email) {
        if (!in_array($email, $displayedEmails)) {
            $nf2Table2[] = [
                'row_id' => $row['user_id'],
                'email' => $email
            ];
            $displayedEmails[] = $email; // Mark this value as displayed
        }
    }

    // Phones table
    foreach ($phones as $phone) {
        if (!in_array($phone, $displayedPhones)) {
            $nf2Table3[] = [
                'row_id' => $row['user_id'],
                'phone' => $phone
            ];
            $displayedPhones[] = $phone; // Mark this value as displayed
        }
    }

    // Barbers table
    if (isset($row['barber_id'])) {
        if (!in_array($row['barber_id'], $displayedBarbers)) {
            $nf2Table4[] = [
                'row_id' => $row['barber_id'],
                'barber_names' => $row['barber_name']
            ];
            $displayedBarbers[] = $row['barber_id']; // Mark this barber as displayed
        }
    }

    // Services table
    if (isset($row['service_id'])) {
        if (!in_array($row['service_id'], $displayedServices)) {
            $nf2Table5[] = [
                'row_id' => $row['service_id'],
                'service_names' => $row['service_name']
            ];
            $displayedServices[] = $row['service_id']; // Mark this service as displayed
        }
    }

    // Appointments table
    $userId = $row['user_id'];
    $appointmentDate = $row['appointment_date'];

    // Check if the appointment date has already been displayed for this user
    if (!isset($displayedAppointmentDates[$userId])) {
        $displayedAppointmentDates[$userId] = []; // Initialize array for this user
    }

    if (!in_array($appointmentDate, $displayedAppointmentDates[$userId])) {
        $nf2Table6[] = [
            'row_id' => $userId,
            'appointment_date' => $appointmentDate
        ];
        $displayedAppointmentDates[$userId][] = $appointmentDate; // Mark this date as displayed
    }
}
// Fetch and prepare 3NF data
$nf3Table1 = []; // User-Barber-Service-Appointment mapping
$nf3Table2 = []; // Emails table
$nf3Table3 = []; // Phones table

// Track unique emails and phones to avoid duplicates
$uniqueEmails = [];
$uniquePhones = [];

foreach ($rawData as $row) {
    // User-Barber-Service-Appointment mapping
    if (isset($row['barber_id']) && isset($row['service_id']) && isset($row['appointment_id'])) {
        $nf3Table1[] = [
            'user_id' => $row['user_id'],
            'barber_id' => $row['barber_id'],
            'service_id' => $row['service_id'],
            'appointment_id' => $row['appointment_id']
        ];
    }

    // Emails table
    $emails = splitData($row['email']); // Split and trim emails
    foreach ($emails as $email) {
        $emailKey = $row['user_id'] . '|' . $email; // Create a unique key for each email
        if (!isset($uniqueEmails[$emailKey])) {
            $nf3Table2[] = [
                'row_id' => $row['user_id'],
                'email' => $email
            ];
            $uniqueEmails[$emailKey] = true; // Mark this email as added
        }
    }

    // Phones table
    $phones = splitData($row['phone']); // Split and trim phones
    foreach ($phones as $phone) {
        $phoneKey = $row['user_id'] . '|' . $phone; // Create a unique key for each phone
        if (!isset($uniquePhones[$phoneKey])) {
            $nf3Table3[] = [
                'row_id' => $row['user_id'],
                'phone_number' => $phone
            ];
            $uniquePhones[$phoneKey] = true; // Mark this phone as added
        }
    }
}
$nf3ServicesTable = [];
$uniqueServices = [];

foreach ($rawData as $row) {
    if (isset($row['service_id']) && isset($row['service_name'])) {
        $serviceKey = $row['service_id'];
        if (!isset($uniqueServices[$serviceKey])) {
            $nf3ServicesTable[] = [
                'service_id' => $row['service_id'],
                'service_name' => $row['service_name']
            ];
            $uniqueServices[$serviceKey] = true; // Mark this service as added
        }
    }
}

// Fetch and prepare Barbers table for 3NF
$nf3BarbersTable = [];
$uniqueBarbers = [];

foreach ($rawData as $row) {
    if (isset($row['barber_id']) && isset($row['barber_name'])) {
        $barberKey = $row['barber_id'];
        if (!isset($uniqueBarbers[$barberKey])) {
            $nf3BarbersTable[] = [
                'barber_id' => $row['barber_id'],
                'barber_name' => $row['barber_name']
            ];
            $uniqueBarbers[$barberKey] = true; // Mark this barber as added
        }
    }
}

// Sort the services and barbers tables by ID
usort($nf3ServicesTable, function ($a, $b) {
    return $a['service_id'] - $b['service_id'];
});

usort($nf3BarbersTable, function ($a, $b) {
    return $a['barber_id'] - $b['barber_id'];
});
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Data</title>
    <link rel="icon" type="image/png" href="icon white.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap');

        body {
            font-family: 'Montserrat', sans-serif;
            margin: 20px;
            background: #151515;
            color: #fff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: #1b1b1b;
            border-radius: 8px;
            overflow: hidden;
        }

        table,
        th,
        td {
            border: 1px solid #333;
            /* Darker border */
        }

        th,
        td {
            padding: 12px;
            text-align: left;
        }

        th {
            background: #333;
            /* Dark header background */
            color: #03e9f4;
            /* Cyan header text */
        }

        tr:nth-child(even) {
            background-color: #222;
            /* Alternate row color */
        }


        tr:hover {
            background-color: #2a2a2a;
            /* Hover row color */
        }

        .buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .buttons button {
            padding: 10px 20px;
            background: #03e9f4;
            color: rgb(1, 37, 39);
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }

        .buttons button:hover {
            cursor: wait;
            font-weight: bold;
            background: rgb(61, 243, 234);
            box-shadow: 0 0 10px rgb(74, 226, 246);
        }

        .container {
            display: none;
            /* Hide containers by default */
            padding: 20px;
            background: #1b1b1b;
            border-radius: 8px;
            margin-top: 20px;
        }

        .container.active {
            display: block;
            /* Show active container */
        }

        /* New styles for the header and button alignment */
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .header-container h1 {
            margin: 0;
        }

        .header-container button {
            background: #03e9f4;
            color: rgb(1, 37, 39);
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-container button:hover {
            background: rgb(61, 243, 234);
            box-shadow: 0 0 10px rgb(74, 226, 246);
        }
    </style>
</head>

<body>
    <div class="header-container">
        <h1 style="color: #03e9f4;">Appointment Raw Data</h1>
        <button onclick="window.location.href='bookingform.php'">
            <i class="fas fa-calendar-plus"></i>
            Book another Appointment
        </button>
    </div>
    <table>
        <thead>
            <tr>
                <th>User ID</th>
                <th>User Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Barber(s)</th>
                <th>Service(s)</th>
                <th>Appointment Date(s)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($displayRawData as $appointment): ?>
                <tr>
                    <td><?php echo htmlspecialchars($appointment['user_id']); ?></td>
                    <td><?php echo htmlspecialchars($appointment['user_name']); ?></td>
                    <td><?php echo htmlspecialchars($appointment['email']); ?></td>
                    <td><?php echo htmlspecialchars($appointment['phone']); ?></td>
                    <td><?php echo htmlspecialchars($appointment['barber_names']); ?></td>
                    <td><?php echo htmlspecialchars($appointment['service_names']); ?></td>
                    <td><?php echo htmlspecialchars($appointment['appointment_dates']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h1 style="color: #03e9f4;">Select Normalize Form to Display:</h1>
    <div class="buttons">
        <button onclick="showContainer('container1')">1NF</button>
        <button onclick="showContainer('container2')">2NF</button>
        <button onclick="showContainer('container3')">3NF</button>
    </div>

    <!-- Containers -->
    <div id="container1" class="container">
        <h2 style="color: #03e9f4;">First Normal Form (1NF)</h2>
        <table>
            <tr>
                <th>User ID</th>
                <th>User Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Barber</th>
                <th>Service</th>
                <th>Appointment Date</th>
            </tr>
            <?php foreach ($flattenedNf1Data as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['user_id']) ?></td>
                    <td><?= htmlspecialchars($row['user_name']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['phone']) ?></td>
                    <td><?= htmlspecialchars($row['barber_names']) ?></td>
                    <td><?= htmlspecialchars($row['service_names']) ?></td>
                    <td><?= htmlspecialchars($row['appointment_date']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div id="container2" class="container">
        <h2 style="color: #03e9f4;">Second Normal Form (2NF)</h2>
        <div style="display: flex; justify-content: center; gap: 10px; margin-bottom: 20px;">
            <table style="width: 25%; padding: 0; margin:0;">
                <tr>
                    <th>User ID</th>
                    <th>User Name</th>
                </tr>
                <?php foreach ($nf2Table1 as $row): ?>
                    <tr>
                        <td><?= $row['user_id'] ?></td>
                        <td><?= $row['user_name'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div style="display: flex; justify-content: center; gap: 10px; margin-bottom: 20px;">
            <table style="width: 25%; padding: 0; margin:0;">
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                </tr>
                <?php foreach ($nf2Table2 as $row): ?>
                    <tr>
                        <td><?= $row['row_id'] ?></td>
                        <td><?= $row['email'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <table style="width: 25%; padding: 0; margin:0;">
                <tr>
                    <th>ID</th>
                    <th>Phone</th>
                </tr>
                <?php foreach ($nf2Table3 as $row): ?>
                    <tr>
                        <td><?= $row['row_id'] ?></td>
                        <td><?= $row['phone'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div style="display: flex; justify-content: center; gap: 10px; margin-bottom: 20px;">
            <table style="width: 25%; padding: 0; margin:0;">
                <tr>
                    <th>ID</th>
                    <th>Barber Names</th>
                </tr>
                <?php foreach ($nf2Table4 as $row): ?>
                    <tr>
                        <td><?= $row['row_id'] ?></td>
                        <td><?= $row['barber_names'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <table style="width: 25%; padding: 0; margin:0;">
                <tr>
                    <th>ID</th>
                    <th>Service Names</th>
                </tr>
                <?php foreach ($nf2Table5 as $row): ?>
                    <tr>
                        <td><?= $row['row_id'] ?></td>
                        <td><?= $row['service_names'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div style="display: flex; justify-content: center; margin-bottom: 20px;">
            <table style="width: 35%; padding: 0; margin:0;">
                <tr>
                    <th>ID</th>
                    <th>Appointment Date</th>
                </tr>
                <?php foreach ($nf2Table6 as $row): ?>
                    <tr>
                        <td><?= $row['row_id'] ?></td>
                        <td><?= $row['appointment_date'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <div id="container3" class="container">
        <h2 style="color: #03e9f4;">Third Normal Form (3NF)</h2>
        <div style="display: flex; justify-content: center; margin-bottom: 20px;">
            <table style="width: 35%; padding: 0; margin:0;">
                <tr>
                    <th>User ID</th>
                    <th>Barber ID</th>
                    <th>Service ID</th>
                    <th>Appointment ID</th>
                </tr>
                <?php
                // Sort the $nf3Table1 array by appointment_id in ascending order
                usort($nf3Table1, function ($a, $b) {
                    return $a['appointment_id'] - $b['appointment_id'];
                });

                foreach ($nf3Table1 as $row): ?>
                    <tr>
                        <td><?= $row['user_id'] ?></td>
                        <td><?= $row['barber_id'] ?></td>
                        <td><?= $row['service_id'] ?></td>
                        <td><?= $row['appointment_id'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div style="display: flex; justify-content: space-around; margin-bottom: 20px;">
            <table style="width: 30%; padding: 0; margin:0;">
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                </tr>
                <?php foreach ($nf3Table2 as $row): ?>
                    <tr>
                        <td><?= $row['row_id'] ?></td>
                        <td><?= $row['email'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <table style="width: 30%; padding: 0; margin:0;">
                <tr>
                    <th>ID</th>
                    <th>Phone</th>
                </tr>
                <?php foreach ($nf3Table3 as $row): ?>
                    <tr>
                        <td><?= $row['row_id'] ?></td>
                        <td><?= $row['phone_number'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- New Services and Barbers tables -->
        <div style="display: flex; justify-content: space-around; margin-bottom: 20px;">
            <table style="width: 30%; padding: 0; margin:0;">
                <tr>
                    <th>Service ID</th>
                    <th>Service Name</th>
                </tr>
                <?php foreach ($nf3ServicesTable as $row): ?>
                    <tr>
                        <td><?= $row['service_id'] ?></td>
                        <td><?= $row['service_name'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <table style="width: 30%; padding: 0; margin:0;">
                <tr>
                    <th>Barber ID</th>
                    <th>Barber Name</th>
                </tr>
                <?php foreach ($nf3BarbersTable as $row): ?>
                    <tr>
                        <td><?= $row['barber_id'] ?></td>
                        <td><?= $row['barber_name'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
    <script>
        //this function will show the selected container and hide the others
        // It will also ensure that the selected container is displayed correctly
        function showContainer(containerId) {
            document.querySelectorAll('.container').forEach(container => {
                container.classList.remove('active');
            });
            const selectedContainer = document.getElementById(containerId);
            if (selectedContainer) {
                selectedContainer.classList.add('active');
            }
        }
    </script>
</body>

</html>