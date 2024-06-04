<?php
require_once "C:\\xampp\\htdocs\\phpprograms\\config.php";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function insertTransaction($conn, $sender, $amount, $type, $endpoint) {
    $sql = "INSERT INTO transactions (ID, Date, Amount, Type, Endpoint) VALUES (?, CURDATE(), ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siss", $sender, $amount, $type, $endpoint);
    $stmt->execute();  // Execute the prepared statement
    $stmt->close();
}

function updateBalance($conn, $userID, $updateAmount) {
    $sql = "UPDATE users SET Balance = Balance + ? WHERE ID = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $updateAmount, $userID);
    $stmt->execute();  // Execute the prepared statement
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rfid = $_POST["rfid"];

    // Check if the 'fare' parameter is present
    if (isset($_POST["fare"]) && isset($_POST["driver"])) {
        $fare = $_POST["fare"];
        $driver = $_POST["driver"];

        // Prepare the statement to fetch the user's information based on the RFID
        $stmt = $conn->prepare("SELECT * FROM users WHERE ID = ?");
        // Bind the parameter
        $stmt->bind_param("s", $rfid);
        // Execute the query
        $stmt->execute();
        // Get the result
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row["ID"] == $driver) {

                echo "Unlock";
            } else {
                // Check if the driver exists in the ID column
                $stmtDriver = $conn->prepare("SELECT * FROM users WHERE ID = ?");
                $stmtDriver->bind_param("s", $driver);
                $stmtDriver->execute();
                $resultDriver = $stmtDriver->get_result();
                if ($resultDriver->num_rows > 0) {
                    $balance = $row["Balance"];
                    if ($balance >= $fare) {
                            echo "Unlock";
                            updateBalance($conn,$rfid,-$fare);
                            updateBalance($conn,$driver,$fare);
                            insertTransaction($conn, $rfid, $fare,'Paid',$driver);
                            insertTransaction($conn, $driver, $fare,'Collected',$rfid);
                    } else {
                        echo "Insufficient balance";
                    }
                } else {
                    // Driver not found
                    echo "Driver's RFID not found";
                }
                $stmtDriver->close();
            }
        } else {
            // RFID not found
            echo "RFID not found";
        }

        $stmt->close();
    } else {
        // 'fare' or 'driver' parameter is missing
        echo "Fare or driver parameter is missing";
    }
}

$conn->close();
?>
