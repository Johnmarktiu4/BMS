<?php
require_once 'db_conn.php';
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Invalid request'];
$conn = getDBConnection();

if (!$conn) {
    echo json_encode($response);
    exit;
}

if (!isset($_POST['action'])) {
    echo json_encode($response);
    closeDBConnection($conn);
    exit;
}

$action = $_POST['action'];

/* ===============================================
   1. FETCH INVENTORY (WITH DECLARED VALUE)
   =============================================== */
if ($action === 'fetch_inventory') {
    $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 999;
    $search = isset($_POST['search']) ? $conn->real_escape_string($_POST['search']) : '';
    $where = "WHERE i.archived = 0";
    if ($search) {
        $where .= " AND i.item_name LIKE '%$search%'";
    }

    $sql = "SELECT i.*, COALESCE(m.value, 0) AS declared_value 
            FROM inventory i 
            LEFT JOIN item_meta m ON i.id = m.inventory_id AND m.meta_key = 'declared_value'
            $where 
            ORDER BY i.item_name ASC 
            LIMIT $limit";
    
    $result = $conn->query($sql);
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $row['declared_value'] = (float)$row['declared_value'];
        $items[] = $row;
    }

    $response = [
        'status' => 'success',
        'data' => [
            'items' => $items,
            'pagination' => [
                'current_page' => 1,
                'total_pages' => 1,
                'total' => count($items),
                'limit' => $limit
            ]
        ]
    ];
}

/* ===============================================
   2. FETCH ALL TRANSACTIONS (FULL HISTORY + ACTIVE)
   =============================================== */
elseif ($action === 'fetch_all_transactions') {
    $sql = "
        SELECT 
            t.id,
            t.inventory_id,
            t.action_type,
            t.quantity,
            t.transacted_by,
            t.transaction_date,
            t.return_date,
            t.returned_date,
            t.return_period_days,
            t.remarks,
            i.item_name,
            COALESCE(m.value, 0) AS declared_value,
            COALESCE(r.full_name, b.name, 'Unknown') AS borrower_name,
            COALESCE(r.id, b.id) AS borrower_id
        FROM inventory_transactions t
        JOIN inventory i ON t.inventory_id = i.id
        LEFT JOIN item_meta m ON i.id = m.inventory_id AND m.meta_key = 'declared_value'
        LEFT JOIN residents r ON t.borrower_id = r.id
        LEFT JOIN borrowers b ON t.borrower_id = b.id AND r.id IS NULL
        WHERE i.archived = 0
        ORDER BY t.created_at DESC
    ";

    $result = $conn->query($sql);
    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $row['declared_value'] = (float)$row['declared_value'];
        $transactions[] = $row;
    }

    $response = ['status' => 'success', 'data' => $transactions];
}

/* ===============================================
   3. BORROW MULTIPLE ITEMS
   =============================================== */
elseif ($action === 'borrow_multiple') {
    $borrower_id = (int)$_POST['borrower_id'];
    $borrower_name = $conn->real_escape_string($_POST['borrower_name']);
    $items_json = $_POST['items'] ?? '';
    $items = json_decode($items_json, true);

    $transaction_date = $conn->real_escape_string($_POST['transaction_date'] ?? date('Y-m-d'));
    $transacted_by = $conn->real_escape_string($_POST['transacted_by'] ?? '');
    $return_date = $conn->real_escape_string($_POST['return_date']);
    $remarks = isset($_POST['remarks']) ? $conn->real_escape_string($_POST['remarks']) : '';

    if (!is_array($items) || empty($items)) {
        $response['message'] = 'No items selected';
        echo json_encode($response); exit;
    }

    $conn->autocommit(false);
    try {
        foreach ($items as $item) {
            $inventory_id = (int)$item['inventory_id'];
            $quantity = (int)$item['quantity'];

            if ($quantity <= 0) continue;

            // Check stock
            $stock_check = $conn->query("SELECT current_stock FROM inventory WHERE id = $inventory_id AND archived = 0");
            if ($stock_check->num_rows === 0) {
                throw new Exception("Item ID $inventory_id not found");
            }
            $stock = $stock_check->fetch_assoc()['current_stock'];
            if ($stock < $quantity) {
                throw new Exception("Not enough stock for item ID $inventory_id");
            }

            // Update inventory
            $conn->query("UPDATE inventory 
                          SET qty_on_hand = qty_on_hand - $quantity,
                              current_stock = current_stock - $quantity,
                              status = IF(current_stock - $quantity > 0, 'In Stock', 'Out of Stock')
                          WHERE id = $inventory_id");

            // Record borrow transaction
            $conn->query("INSERT INTO inventory_transactions 
                          (inventory_id, action_type, quantity, transacted_by, transaction_date, 
                           return_date, remarks, borrower_id)
                          VALUES ($inventory_id, 'Borrow', $quantity, '$transacted_by', '$transaction_date', 
                                  '$return_date', '$remarks', $borrower_id)");
        }

        $conn->commit();
        $response = ['status' => 'success', 'message' => 'Borrow recorded successfully'];
    } catch (Exception $e) {
        $conn->rollback();
        $response = ['status' => 'error', 'message' => $e->getMessage()];
    }
    $conn->autocommit(true);
}

/* ===============================================
   4. RETURN MULTIPLE ITEMS (GOOD + DAMAGED)
   =============================================== */
elseif ($action === 'return_multiple') {
    $returns_json = $_POST['returns'] ?? '';
    $returns = json_decode($returns_json, true);
    $remarks = isset($_POST['remarks']) ? $conn->real_escape_string($_POST['remarks']) : '';
    $today = date('Y-m-d');

    if (!is_array($returns) || empty($returns)) {
        $response['message'] = 'No return data';
        echo json_encode($response); exit;
    }

    $conn->autocommit(false);
    try {
        foreach ($returns as $ret) {
            $trans_id = (int)$ret['transaction_id'];
            $good_qty = (int)($ret['return_qty'] ?? 0);
            $damaged_qty = (int)($ret['damaged_qty'] ?? 0);

            if ($good_qty + $damaged_qty === 0) continue;

            $trans = $conn->query("SELECT * FROM inventory_transactions WHERE id = $trans_id AND action_type = 'Borrow' AND returned_date IS NULL");
            if ($trans->num_rows === 0) continue;

            $t = $trans->fetch_assoc();
            $inventory_id = $t['inventory_id'];
            $borrower_id = $t['borrower_id'];
            $borrowed_qty = $t['quantity'];

            if (($good_qty + $damaged_qty) > $borrowed_qty) {
                throw new Exception("Cannot return more than borrowed ($borrowed_qty)");
            }

            // Return in good condition
            if ($good_qty > 0) {
                $conn->query("UPDATE inventory 
                              SET current_stock = current_stock + $good_qty,
                                  qty_on_hand = qty_on_hand + $good_qty
                              WHERE id = $inventory_id");

                $conn->query("INSERT INTO inventory_transactions 
                              (inventory_id, action_type, quantity, transaction_date, returned_date, remarks, borrower_id)
                              VALUES ($inventory_id, 'Return', $good_qty, '$today', '$today', '$remarks', $borrower_id)");
            }

            // Damaged / Lost
            if ($damaged_qty > 0) {
                $conn->query("UPDATE inventory 
                              SET qty_damaged = qty_damaged + $damaged_qty
                              WHERE id = $inventory_id");

                $conn->query("INSERT INTO inventory_transactions 
                              (inventory_id, action_type, quantity, transaction_date, returned_date, remarks, borrower_id)
                              VALUES ($inventory_id, 'Broken', $damaged_qty, '$today', '$today', 'Damaged: $remarks', $borrower_id)");
            }

            // Mark original borrow as returned
            $conn->query("UPDATE inventory_transactions 
                          SET returned_date = '$today'
                          WHERE id = $trans_id");
        }

        $conn->commit();
        $response = ['status' => 'success', 'message' => 'Return processed successfully'];
    } catch (Exception $e) {
        $conn->rollback();
        $response = ['status' => 'error', 'message' => $e->getMessage()];
    }
    $conn->autocommit(true);
}

/* ===============================================
   5. RECORD REPLACEMENT
   =============================================== */
elseif ($action === 'record_replacement') {
    $trans_id = (int)$_POST['transaction_id'];
    $quantity = (int)$_POST['quantity'];
    $reason = $conn->real_escape_string($_POST['reason'] ?? 'Damaged');
    $remarks = $conn->real_escape_string($_POST['remarks'] ?? '');

    if ($quantity <= 0) {
        $response['message'] = 'Invalid quantity';
        echo json_encode($response); exit;
    }

    $trans = $conn->query("SELECT inventory_id, borrower_id FROM inventory_transactions WHERE id = $trans_id");
    if ($trans->num_rows === 0) {
        $response['message'] = 'Transaction not found';
        echo json_encode($response); exit;
    }
    $t = $trans->fetch_assoc();

    $conn->query("UPDATE inventory SET qty_replaced = qty_replaced + $quantity WHERE id = {$t['inventory_id']}");
    $conn->query("INSERT INTO inventory_transactions 
                  (inventory_id, action_type, quantity, transaction_date, remarks, borrower_id)
                  VALUES ({$t['inventory_id']}, 'Replace', $quantity, CURDATE(), 'Replaced ($reason): $remarks', {$t['borrower_id']})");

    $response = ['status' => 'success', 'message' => 'Replacement recorded'];
}

/* ===============================================
   6. RECORD PAYMENT
   =============================================== */
elseif ($action === 'record_payment') {
    $trans_id = (int)$_POST['transaction_id'];
    $quantity = (int)$_POST['quantity'];
    $amount_paid = (float)$_POST['amount_paid'];
    $reason = $conn->real_escape_string($_POST['reason'] ?? '');
    $remarks = $conn->real_escape_string($_POST['remarks'] ?? '');

    if ($quantity <= 0 || $amount_paid <= 0) {
        $response['message'] = 'Invalid quantity or amount';
        echo json_encode($response); exit;
    }

    $trans = $conn->query("SELECT inventory_id, borrower_id FROM inventory_transactions WHERE id = $trans_id");
    if ($trans->num_rows === 0) {
        $response['message'] = 'Transaction not found';
        echo json_encode($response); exit;
    }
    $t = $trans->fetch_assoc();

    $conn->query("INSERT INTO inventory_transactions 
                  (inventory_id, action_type, quantity, transaction_date, remarks, borrower_id)
                  VALUES ({$t['inventory_id']}, 'Payment', $quantity, CURDATE(), 'Payment ₱$amount_paid - $reason: $remarks', {$t['borrower_id']})");

    $response = ['status' => 'success', 'message' => 'Payment recorded successfully'];
}

/* ===============================================
   7. ADD NEW ITEM (WITH DECLARED VALUE)
   =============================================== */
elseif ($action === 'add_item') {
    $item_name = trim($conn->real_escape_string($_POST['item_name']));
    $description = $conn->real_escape_string($_POST['description'] ?? '');
    $declared_value = $_POST['declared_value'] !== '' ? (float)$_POST['declared_value'] : null;
    $remarks = $conn->real_escape_string($_POST['remarks'] ?? '');

    if (empty($item_name)) {
        $response['message'] = 'Item name is required';
        echo json_encode($response); exit;
    }

    $conn->begin_transaction();
    try {
        $conn->query("INSERT INTO inventory 
                      (item_name, description, current_stock, qty_on_hand, status, remarks)
                      VALUES ('$item_name', '$description', 0, 0, 'Out of Stock', '$remarks')");
        $item_id = $conn->insert_id;

        if ($declared_value !== null) {
            $value_str = (string)$declared_value;
            $conn->query("INSERT INTO item_meta (inventory_id, meta_key, value) 
                          VALUES ($item_id, 'declared_value', '$value_str')");
        }

        $conn->commit();
        $response = ['status' => 'success', 'message' => 'Item added', 'item_id' => $item_id];
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = 'Error: ' . $e->getMessage();
    }
}

/* ===============================================
   DEFAULT RESPONSE
   =============================================== */
else {
    $response['message'] = "Unknown action: $action";
}

echo json_encode($response);
closeDBConnection($conn);
?>