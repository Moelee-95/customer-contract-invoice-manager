<?php

require_once '../../db_connect.php';

class InvoiceManager {
    private $connection;
    
    public function __construct($db) {
        $this->connection = $db;
    }
    
    public function getAllInvoices() {
        try {
            // First get all invoices
            $query = "SELECT 
                        id,
                        created_by_user as createdBy,
                        customer_id as customerId,
                        customer_name as customerName,
                        inv_date as invoiceDate,
                        done_undone as done
                      FROM invoices 
                      ORDER BY inv_date DESC";
            
            $result = $this->connection->query($query);
            
            if (!$result) {
                throw new Exception('Query failed: ' . $this->connection->error);
            }
            
            $invoices = [];
            while ($row = $result->fetch_assoc()) {
                $invoice = [
                    'id' => (int)$row['id'],
                    'createdBy' => (int)$row['createdBy'],
                    'customerId' => (int)$row['customerId'],
                    'customerName' => $row['customerName'] ?? '',
                    'invoiceDate' => $row['invoiceDate'] ?? '',
                    'done' => (bool)$row['done'],
                    'items' => []
                ];
                
                // Get items for this invoice
                $itemsQuery = "SELECT 
                                id,
                                item_name as name,
                                price,
                                quantity,
                                subtotal,
                                note
                               FROM invoice_item 
                               WHERE invoice_id = ? 
                               ORDER BY id";
                
                $itemsStmt = $this->connection->prepare($itemsQuery);
                if ($itemsStmt) {
                    $itemsStmt->bind_param('i', $invoice['id']);
                    $itemsStmt->execute();
                    $itemsResult = $itemsStmt->get_result();
                    
                    while ($itemRow = $itemsResult->fetch_assoc()) {
                        $invoice['items'][] = [
                            'id' => (int)$itemRow['id'],
                            'name' => $itemRow['name'] ?? '',
                            'price' => (float)$itemRow['price'],
                            'quantity' => (int)$itemRow['quantity'],
                            'subtotal' => (float)$itemRow['subtotal'],
                            'note' => $itemRow['note'] ?? ''
                        ];
                    }
                    $itemsStmt->close();
                }
                
                $invoices[] = $invoice;
            }
            
            $result->free();
            
            return [
                'success' => true,
                'data' => $invoices,
                'message' => 'Invoices retrieved successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'data' => [],
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    public function getInvoiceById($id) {
        try {
            // Get the invoice
            $query = "SELECT 
                        id,
                        created_by_user as createdBy,
                        customer_id as customerId,
                        customer_name as customerName,
                        inv_date as invoiceDate,
                        done_undone as done
                      FROM invoices 
                      WHERE id = ?";
            
            $stmt = $this->connection->prepare($query);
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->connection->error);
            }
            
            $stmt->bind_param('i', $id);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $invoiceData = $result->fetch_assoc();
            
            if ($invoiceData) {
                $invoice = [
                    'id' => (int)$invoiceData['id'],
                    'createdBy' => (int)$invoiceData['createdBy'],
                    'customerId' => (int)$invoiceData['customerId'],
                    'customerName' => $invoiceData['customerName'] ?? '',
                    'invoiceDate' => $invoiceData['invoiceDate'] ?? '',
                    'done' => (bool)$invoiceData['done'],
                    'items' => []
                ];
                
                // Get items for this invoice
                $itemsQuery = "SELECT 
                                id,
                                item_name as name,
                                price,
                                quantity,
                                subtotal,
                                note
                               FROM invoice_item 
                               WHERE invoice_id = ? 
                               ORDER BY id";
                
                $itemsStmt = $this->connection->prepare($itemsQuery);
                if ($itemsStmt) {
                    $itemsStmt->bind_param('i', $invoice['id']);
                    $itemsStmt->execute();
                    $itemsResult = $itemsStmt->get_result();
                    
                    while ($itemRow = $itemsResult->fetch_assoc()) {
                        $invoice['items'][] = [
                            'id' => (int)$itemRow['id'],
                            'name' => $itemRow['name'] ?? '',
                            'price' => (float)$itemRow['price'],
                            'quantity' => (int)$itemRow['quantity'],
                            'subtotal' => (float)$itemRow['subtotal'],
                            'note' => $itemRow['note'] ?? ''
                        ];
                    }
                    $itemsStmt->close();
                }
                
                $stmt->close();
                
                return [
                    'success' => true,
                    'data' => $invoice,
                    'message' => 'Invoice retrieved successfully'
                ];
            } else {
                $stmt->close();
                return [
                    'success' => false,
                    'data' => null,
                    'message' => 'Invoice not found'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'data' => null,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
}

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Only GET requests are supported.'
    ]);
    exit();
}

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Failed to connect to database');
    }
    
    // Initialize invoice manager
    $invoiceManager = new InvoiceManager($db);
    
    // Check if specific invoice ID is requested
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $invoiceId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        
        if ($invoiceId === false) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid invoice ID provided'
            ]);
            exit();
        }
        
        $result = $invoiceManager->getInvoiceById($invoiceId);
    } else {
        // Get all invoices
        $result = $invoiceManager->getAllInvoices();
    }
    
    // Return JSON response
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'data' => [],
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

?>