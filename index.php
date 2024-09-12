<?php

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_fax'])) {
        // Send fax
        sendFax($voip_user, $voip_pass, $_FILES['fax_file']['tmp_name'], $_POST['to_number']);
    }
}

function getFaxes($user, $pass, $date_from, $date_to) {
    $method = "getFaxMessages";
    $url = "https://voip.ms/api/v1/rest.php?api_username={$user}&api_password={$pass}&folder=INBOX&method={$method}&from={$date_from}&to={$date_to}";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    $result = curl_exec($ch);
    curl_close($ch);

    return json_decode($result, true);
}

function sendFax($voip_user, $voip_pass, $filename, $to_number) {
    // Convert the file to PDF if it's not already
    system('pandoc ' . $filename . ' -o ' . $filename . '.pdf');
    $filename .= '.pdf';

    // Send the fax using the API
    $method = "sendFaxMessage";
    $station_id = "1234";
    $file = base64_encode(file_get_contents($filename));
    $data = array(
        'api_username' => $voip_user,
        'api_password' => $voip_pass,
        'from_name' => 'FaxBot',
        'method' => $method,
        'to_number' => $to_number,
        'from_number' => $GLOBALS['from_number'],
        'file' => $file,
        'station_id' => $station_id
    );

    $ch = curl_init();
    $url = "https://voip.ms/api/v1/rest.php";
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $result = curl_exec($ch);
    curl_close($ch);

    $response = json_decode($result, true);
    if ($response['status'] === 'success') {
        echo "<p>Fax sent successfully!</p>";
    } else {
        echo "<p>Error sending fax: " . $response['status'] . "</p>";
    }
}

// Default date range (last 7 days)
$now = new DateTime();
$yesterday = clone $now;
$yesterday->modify('-7 day');
$date_from = $yesterday->format('Y-m-d');
$date_to = $now->format('Y-m-d');

// Handle form submission for date range
if (isset($_GET['date_from']) && isset($_GET['date_to'])) {
    $date_from = $_GET['date_from'];
    $date_to = $_GET['date_to'];
}

// Fetch faxes
$faxes = getFaxes($voip_user, $voip_pass, $date_from, $date_to);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fax Management</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f2f2f2; }
        .popup { display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: white; border: 1px solid #ccc; padding: 20px; z-index: 1000; }
        .popup .close-btn { float: right; cursor: pointer; }
        .overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 999; }
    </style>
</head>
<body>

<h1>Fax Management</h1>

<h2>Received Faxes</h2>
<form method="get">
    <label for="date_from">From:</label>
    <input type="date" id="date_from" name="date_from" value="<?= $date_from ?>">
    <label for="date_to">To:</label>
    <input type="date" id="date_to" name="date_to" value="<?= $date_to ?>">
    <button type="submit">Filter</button>
</form>

<table>
    <tr>
        <th>ID</th>
        <th>Caller ID</th>
        <th>Description</th>
        <th>Date</th>
        <th>Action</th>
    </tr>
    <?php foreach ($faxes['faxes'] as $fax): ?>
        <tr>
            <td><?= $fax['id'] ?></td>
            <td><?= $fax['callerid'] ?></td>
            <td><?= $fax['description'] ?></td>
            <td><?= $fax['date'] ?></td>
            <td><a href="view_fax.php?id=<?= $fax['id'] ?>" target="_blank">View</a></td>
        </tr>
    <?php endforeach; ?>
</table>

<h2>Send Fax</h2>
<button onclick="openPopup()">Send New Fax</button>

<div class="popup" id="sendFaxPopup">
    <span class="close-btn" onclick="closePopup()">X</span>
    <h3>Send a Fax</h3>
    <form method="post" enctype="multipart/form-data">
        <label for="to_number">To Number:</label>
        <input type="text" id="to_number" name="to_number" required>
        <br><br>
        <label for="fax_file">Choose File:</label>
        <input type="file" id="fax_file" name="fax_file" required>
        <br><br>
        <button type="submit" name="send_fax">Send Fax</button>
    </form>
</div>

<div class="overlay" id="overlay"></div>

<script>
    function openPopup() {
        document.getElementById('sendFaxPopup').style.display = 'block';
        document.getElementById('overlay').style.display = 'block';
    }

    function closePopup() {
        document.getElementById('sendFaxPopup').style.display = 'none';
        document.getElementById('overlay').style.display = 'none';
    }
</script>

</body>
</html>

