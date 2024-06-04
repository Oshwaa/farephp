<?php
require_once "C:\\xampp\\htdocs\\phpprograms\\config.php";

// Establish database connection
function connectToDatabase() {
    global $servername, $username, $password, $dbname;
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// Function to execute prepared statement with error handling
function executePreparedStatement($stmt) {
    if ($stmt->execute()) {
        return true;
    } else {
        echo "Error: " . $stmt->error;
        return false;
    }
}

// Function to add fare amount to driver's balance
function addFareToDriver($conn, $driver, $fare) {
    $stmt = $conn->prepare("UPDATE users SET Balance = Balance + ? WHERE ID = ?");
    $stmt->bind_param("is", $fare, $driver);
    if (executePreparedStatement($stmt)) {
        echo "Unlock";
    }
    $stmt->close();
}

// Function to insert a transaction record
function insertTransaction($conn, $sender, $amount, $type, $endpoint) {
    $sql = "INSERT INTO transactions (ID, Date, Amount, Type, Endpoint) VALUES (?, CURDATE(), ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siss", $sender, $amount, $type, $endpoint);
    if (executePreparedStatement($stmt)) {
        return true;
    } else {
        return false;
    }
}

// Main logic
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = connectToDatabase();
    $rfid = $_POST["rfid"];

    // Check for required parameters
    if (isset($_POST["fare"]) && isset($_POST["driver"])) {
        $fare = $_POST["fare"];
        $driver = $_POST["driver"];

        $stmt = $conn->prepare("SELECT * FROM users WHERE ID = ?");
        $stmt->bind_param("s", $rfid);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row["ID"] == $driver) {
                echo "Unlock";
            } else {
                $stmtDriver = $conn->prepare("SELECT * FROM users WHERE ID = ?");
                $stmtDriver->bind_param("s", $driver);
                $stmtDriver->execute();
                $resultDriver = $stmtDriver->get_result();
                if ($resultDriver->num_rows > 0) {
                    $balance = $row["Balance"];
                    if ($balance >= $fare) {
                        $balance -= $fare;
                        $stmtUpdate = $conn->prepare("UPDATE users SET Balance = ? WHERE ID = ?");
                        $stmtUpdate->bind_param("is", $balance, $rfid);
                        if (executePreparedStatement($stmtUpdate)) {
                            if (insertTransaction($conn, $rfid, $fare, 'Paid', $driver) &&
                                insertTransaction($conn, $driver, $fare, 'Collected', $rfid)) {
                                addFareToDriver($conn, $driver, $fare);
                            }
                        }
                        $stmtUpdate->close();
                    } else {
                        echo "Insufficient balance";
                    }
                } else {
                    echo "Driver's RFID not found";
                }
                $stmtDriver->close();
            }
        } else {
            echo "RFID not found";
        }
        $stmt->close();
    } else {
        echo "Fare or driver parameter is missing";
    }
    $conn->close();
} else {
    echo "Invalid request method";
}
?>
