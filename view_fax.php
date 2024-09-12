<?php
// Replace these with your VoIP.ms API credentials
require_once 'config.php';

// Check if the fax ID is provided
if (!isset($_GET['id'])) {
    echo "No fax ID provided.";
    exit;
}

$fax_id = $_GET['id'];

// Fetch the specific fax using VoIP.ms API
function getFax($user, $pass, $fax_id) {
    $method = "getFaxMessagePDF";
    $url = "https://voip.ms/api/v1/rest.php?api_username={$user}&api_password={$pass}&method={$method}&id={$fax_id}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    $result = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($result, true);
}

// Get the fax
$fax_data = getFax($voip_user, $voip_pass, $fax_id);

// Check if the API response is successful
if ($fax_data['status'] !== 'success') {
    echo "Error fetching the fax: " . $fax_data['status'];
    exit;
}

// Decode the base64 PDF
$pdf_content = base64_decode($fax_data['message_base64']);

// Output the PDF directly to the browser
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="fax_' . $fax_id . '.pdf"');
echo $pdf_content;
?>

