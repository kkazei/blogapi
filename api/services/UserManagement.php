<?php

require_once (__DIR__ . '/../utils/Response.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../config/secretKey.php');
require_once(__DIR__ . '/../vendor/autoload.php');

use Firebase\JWT\JWT;

class UserManagement extends GlobalUtil
{
    private $pdo;
    private $conn;
    private $secretKey;

    public function __construct($pdo)
    {
        $databaseService = new DatabaseAccess();
        $this->conn = $databaseService->connect();

        $keys = new Secret();
        $this->secretKey = $keys->generateSecretKey();
        $this->pdo = $pdo;

    }

    public function updateUserData($id, $userData) {
        $tableName = 'users'; 

        // Map frontend field names to backend table column names
        $fieldMappings = [
            'firstname' => 'user_firstname',
            'lastname' => 'user_lastname',
            'email' => 'user_email',
            'usertype' => 'user_role'

        ];


        $mappedUserData = [];
        foreach ($userData as $key => $value) {
            if (isset($fieldMappings[$key])) {
                $mappedUserData[$fieldMappings[$key]] = $value;
            }
        }

        // Construct SQL UPDATE statement
        $updateStatements = array_map(function ($attr) {
            return "$attr = ?"; 
        }, array_keys($mappedUserData));
        $sql = "UPDATE $tableName SET " . implode(', ', $updateStatements) . " WHERE user_id = ?";

        try {
            $stmt = $this->pdo->prepare($sql);
            $values = array_values($mappedUserData);
            $values[] = (int) $id;
            $stmt->execute($values);

            return $this->sendResponse("Form data updated", 200);
        } catch (\PDOException $e) {
            $errmsg = $e->getMessage();
            error_log("Failed to update: " . $errmsg); // Add logging here
            return $this->sendErrorResponse("Failed to update: " . $errmsg, 400);
        }
    }


    public function getUsers() {
        $tableName = 'users';
        $userRole = 'users'; // Define the user role to filter
        
        // Construct SQL SELECT statement with WHERE clause
        $sql = "SELECT * FROM $tableName WHERE user_role = :userRole";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['userRole' => $userRole]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Check if there are users found
            if ($users) {
                return $this->sendResponse($users, 200);
            } else {
                return $this->sendErrorResponse("No users found with role '$userRole'", 404);
            }
        } catch (\PDOException $e) {
            $errmsg = $e->getMessage();
            error_log("Failed to fetch users: " . $errmsg); // Add logging here
            return $this->sendErrorResponse("Failed to fetch users: " . $errmsg, 500);
        }
    }
    
    
}
   
?>