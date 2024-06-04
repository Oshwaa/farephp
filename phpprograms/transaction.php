<?php
require_once "C:\\xampp\\htdocs\\phpprograms\\config.php";
include "C:\\xampp\\htdocs\\phpprograms\\sendBalance.php";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rfid = $_POST["rfid"];

    if (isset($_POST["fare"]) && isset($_POST["driver"])) {
        $fare = $_POST["fare"];
        $driver = $_POST["driver"];

        $user = getUserInfo($conn, $rfid);

        if ($user) {
            if ($user["ID"] == $driver) {
                echo "Unlock";
            } else {
                $driverInfo = getUserInfo($conn, $driver);

                if ($driverInfo) {
                    $balance = $user["Balance"];

                    if ($balance >= $fare) {
                        echo "Unlock";
                        updateBalance($conn, $rfid, -$balance);
                        updateBalance($conn, $driver, $balance);
                        insertTransaction($conn, $rfid, $fare, 'Paid', $driver);
                        insertTransaction($conn, $driver, $fare, 'Collected', $rfid);
                    } else {
                        echo "Insufficient balance";
                    }
                } else {
                    echo "Driver's RFID not found";
                }
            }
        } else {
            echo "RFID not found";
        }
    } else {
        echo "Fare or driver parameter is missing";
    }
}

$conn->close();
?>
