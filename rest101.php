<?php

require 'vendor/autoload.php';
require_once './config.php';

$db = new MySQLHandler('products');
if (!$db->connect(__HOST__, __USER__, __PASS__, __DB__)) {
    http_response_code(500);
    echo json_encode(["error" => "internal server error!"]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME']; 
$path = trim(str_replace($scriptName, '', $requestUri), '/');
$urlParts = explode('/', $path);

$resource = $urlParts[0] ?? null; 
$resourceID = $urlParts[1] ?? null;

header("Content-Type: application/json");

if ($resource !== 'products') {
    http_response_code(404);
    echo json_encode(["error" => "Resource doesn't exist"]);
    exit;
}

switch ($method) {
case 'GET':
    if ($resourceID) {
        $data = $db->get_record_by_id($resourceID);
        if ($data && !empty($data)) {
            http_response_code(200);
            echo json_encode($data[0]);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Resource doesn't exist"]);
        }
    } else {
        $all = $db->get_data();
        http_response_code(200);
        echo json_encode($all);
    }
    break;


    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $requiredFields = ['name', 'price', 'units_in_stock'];

        $invalidFields = [];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || (in_array($field, ['price', 'units_in_stock']) && !is_numeric($data[$field]))) {
                $invalidFields[] = $field;
            }
        }
        if (count($data) !== count($requiredFields)) {
            $invalidFields[] = 'wrong column';
        }

        if (!empty($invalidFields)) {
            http_response_code(400);
            echo json_encode(["error" => "Bad request"]);
            break;
        }

        $success = $db->save($data);
        if ($success) {
            http_response_code(201);
            echo json_encode(["status" => "Resource was added successfully!"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "internal server error!"]);
        }
        break;

    case 'PUT':
        if ($resourceID) {
            $data = json_decode(file_get_contents('php://input'), true);
            $requiredFields = ['name', 'price', 'units_in_stock'];

            $invalidFields = [];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || (in_array($field, ['price', 'units_in_stock']) && !is_numeric($data[$field]))) {
                $invalidFields[] = $field;
            }
        }
        if (count($data) !== count($requiredFields)) {
            $invalidFields[] = 'wrong column';
        }

        if (!empty($invalidFields)) {
            http_response_code(400);
            echo json_encode(["error" => "Bad request"]);
            break;
        }

        $existing = $db->get_record_by_id($resourceID);
        if (!$existing || empty($existing)) {
            http_response_code(404);
            echo json_encode(["error" => "Resource not found!"]);
            break;
        }

        $success = $db->update($data, $resourceID);
        if ($success) {
            http_response_code(200);
            echo json_encode($data);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "internal server error!"]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Bad request"]);
    }
    break;

case 'DELETE':
    if ($resourceID) {
        $existing = $db->get_record_by_id($resourceID);
        if (!$existing || empty($existing)) {
            http_response_code(404);
            echo json_encode(["error" => "Resource not found!"]);
            break;
        }

        $success = $db->delete($resourceID);
        if ($success) {
            http_response_code(200);
            echo json_encode(["status" => "Resource was deleted successfully!"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "internal server error!"]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Bad request"]);
    }
    break;

default:
    http_response_code(405);
    echo json_encode(["error" => "method not allowed!"]);
    break;
}