?php
require_once "C:\\xampp\\htdocs\\phpprograms\\config.php";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = "6372834";

$sql = "SELECT * FROM transactions WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$rows = array();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    echo json_encode($rows);
} else {
    echo json_encode(array("message" => "No results"));
}

// Close statements and connection
$stmt->close();
$conn->close();
?>
