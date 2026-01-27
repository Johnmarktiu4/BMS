 
<?php
session_start();
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
   1. FETCH INVENTORY (WITH DECLARED VALUE + PAGINATION SUPPORT)
   =============================================== */
if ($action === 'fetch_inventory') {
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 999;
    $offset = ($page - 1) * $limit;
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
            LIMIT $limit OFFSET $offset";
    $result = $conn->query($sql);
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $row['declared_value'] = (float)$row['declared_value'];
        $items[] = $row;
    }
    $countSql = "SELECT COUNT(*) as total FROM inventory i $where";
    $countResult = $conn->query($countSql);
    $total = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($total / $limit);
    $response = [
        'status' => 'success',
        'data' => [
            'items' => $items,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total' => (int)$total,
                'limit' => $limit
            ]
        ]
    ];
}
/* ===============================================
   2. FETCH STOCK MONITORING (FULL INVENTORY DETAILS)
   =============================================== */
elseif ($action === 'fetch_stock_monitoring') {
    $sql = "
        SELECT
            i.id, i.item_name, i.description, i.item_category,
            i.qty_on_hand, i.qty_received, i.qty_lost, i.qty_damaged, i.qty_replaced,
            i.current_stock, i.remarks, i.status,
            COALESCE(m.value, 0) AS declared_value, i.archived
        FROM inventory i
        LEFT JOIN item_meta m ON i.id = m.inventory_id AND m.meta_key = 'declared_value'
        ORDER BY i.item_name ASC
    ";
    $result = $conn->query($sql);
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $row['declared_value'] = (float)$row['declared_value'];
        $items[] = $row;
    }
    $response = ['status' => 'success', 'data' => $items];
}
/* ===============================================
   3. FETCH ALL TRANSACTIONS (BORROWED + RETURNED + FULL HISTORY)
   =============================================== */
elseif ($action === 'fetch_all_transactions') {
    $sql = "
        SELECT
            t.id,
            t.inventory_id,
            t.action_type,
            t.quantity,
            t.borrowed_quantity,
            t.replaced_quantity,
            t.pay_quantity,
            t.transacted_by,
            t.transaction_date,
            t.return_date,
            t.returned_date,
            t.return_period_days,
            t.remarks,
            i.item_name,
            t.info,
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
   4. ADD NEW ITEM (WITH DECLARED VALUE + DUPLICATE CHECK)
   =============================================== */
elseif ($action === 'add_item') {
    $item_name = trim($conn->real_escape_string($_POST['item_name']));
    $description = $conn->real_escape_string($_POST['description'] ?? '');
    $declared_value = $_POST['declared_value'] !== '' ? (float)$_POST['declared_value'] : null;
    $remarks = $conn->real_escape_string($_POST['remarks'] ?? '');
    $with_expiration = $conn->real_escape_string($_POST['with_expiration'] ?? 'No');
    $itemCategory = $conn->real_escape_string($_POST['item_category'] ?? '');
    $is_with_expiry = 0;
    if (empty($item_name)) {
        $response['message'] = 'Item name is required';
        echo json_encode($response);
        closeDBConnection($conn);
        exit;
    }

    if (empty($with_expiration)) {
        $response['message'] = 'Please specify if the item has expiration';
        echo json_encode($response);
        closeDBConnection($conn);
        exit;
    }

    if ($with_expiration === 'Yes') {
       $is_with_expiry = 1;
    }

    // Check for duplicate
    $check_sql = "SELECT id FROM inventory WHERE LOWER(item_name) = LOWER('$item_name') AND archived = 0 LIMIT 1";
    $check_result = $conn->query($check_sql);
    if ($check_result->num_rows > 0) {
        $existing = $check_result->fetch_assoc();
        $response = [
            'status' => 'success',
            'message' => 'Item already exists. Use "Stock In" to add quantity.',
            'item_id' => $existing['id'],
            'exists' => true
        ];
        echo json_encode($response);
        closeDBConnection($conn);
        exit;
    }
    $conn->begin_transaction();
    try {
        $conn->query("INSERT INTO inventory
                      (item_name, description, current_stock, qty_on_hand, qty_received, status, remarks, with_expiration, item_category)
                      VALUES ('$item_name', '$description', 0, 0, 0, 'Out of Stock', '$remarks', $is_with_expiry, '$itemCategory')");
        $item_id = $conn->insert_id;
        if ($declared_value !== null) {
            $value_str = (string)$declared_value;
            $conn->query("INSERT INTO item_meta (inventory_id, meta_key, value)
                          VALUES ($item_id, 'declared_value', '$value_str')");
        }
        $conn->commit();
        $response = ['status' => 'success', 'message' => 'Item added successfully', 'item_id' => $item_id];
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = 'Error: ' . $e->getMessage();
    }
}
/* ===============================================
   5. UPDATE ITEM
   =============================================== */
elseif ($action === 'update_item') {
    $id = (int)$_POST['id'];
    $item_name = trim($conn->real_escape_string($_POST['item_name']));
    $description = $conn->real_escape_string($_POST['description'] ?? '');
    $declared_value = $_POST['declared_value'] !== '' ? (float)$_POST['declared_value'] : null;
    $remarks = $conn->real_escape_string($_POST['remarks'] ?? '');
    if (empty($item_name)) {
        $response['message'] = 'Item name is required';
        echo json_encode($response);
        closeDBConnection($conn);
        exit;
    }
    $conn->begin_transaction();
    try {
        $conn->query("UPDATE inventory SET
                      item_name = '$item_name',
                      description = '$description',
                      remarks = '$remarks'
                      WHERE id = $id AND archived = 0");
        // Handle declared_value
        $meta_check = $conn->query("SELECT id FROM item_meta WHERE inventory_id = $id AND meta_key = 'declared_value'");
        if ($meta_check->num_rows > 0) {
            if ($declared_value !== null) {
                $value_str = (string)$declared_value;
                $conn->query("UPDATE item_meta SET value = '$value_str' WHERE inventory_id = $id AND meta_key = 'declared_value'");
            } else {
                $conn->query("DELETE FROM item_meta WHERE inventory_id = $id AND meta_key = 'declared_value'");
            }
        } elseif ($declared_value !== null) {
            $value_str = (string)$declared_value;
            $conn->query("INSERT INTO item_meta (inventory_id, meta_key, value) VALUES ($id, 'declared_value', '$value_str')");
        }
        $conn->commit();
        $response = ['status' => 'success', 'message' => 'Item updated successfully'];
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = 'Error: ' . $e->getMessage();
    }
}
/* ===============================================
   6. STOCK IN
   =============================================== */
elseif ($action === 'stock_in') {
    $inventory_id = (int)$_POST['inventory_id'];
    $quantity = (int)$_POST['quantity'];
    $transaction_date = $conn->real_escape_string($_POST['transaction_date'] ?? date('Y-m-d'));
    $remarks = $conn->real_escape_string($_POST['remarks'] ?? '');
    $acquisition_type = $conn->real_escape_string($_POST['acquisition_type'] ?? 'Purchase');
    $donated_by = $conn->real_escape_string($_POST['donated_by'] ?? '');
    $created_by = $_SESSION['user_id'];
    $expiration_date = $conn->real_escape_string($_POST['expiration_date'] ?? null);
    if ($quantity <= 0) {
        $response['message'] = 'Quantity must be greater than zero';
        echo json_encode($response);
        closeDBConnection($conn);
        exit;
    }
    // if ($acquisition_type === 'Purchase' && $price_per_unit <= 0) {
    //     $response['message'] = 'Price per unit is required for purchases';
    //     echo json_encode($response);
    //     closeDBConnection($conn);
    //     exit;
    // }
    $item = $conn->query("SELECT current_stock, qty_received FROM inventory WHERE id = $inventory_id AND archived = 0");
    if ($item->num_rows === 0) {
        $response['message'] = 'Item not found';
        echo json_encode($response);
        closeDBConnection($conn);
        exit;
    }
    $current = $item->fetch_assoc();
    $new_stock = $current['current_stock'] + $quantity;
    $new_received = $current['qty_received'] + $quantity;
    $status = $new_stock > 0 ? 'In Stock' : 'Out of Stock';
    $conn->autocommit(false);
    try {
        $conn->query("UPDATE inventory SET
                      qty_received = $new_received,
                      current_stock = $new_stock,
                      qty_on_hand = qty_on_hand + $quantity,
                      status = '$status'
                      WHERE id = $inventory_id");
        $conn->query("INSERT INTO inventory_transactions
                      (inventory_id, action_type, quantity, transaction_date, remarks)
                      VALUES ($inventory_id, 'Add', $quantity, '$transaction_date', '$remarks')");
        $conn->query("INSERT INTO stock_movement
                       (item_id, acquisition_type, donated_by, qty, movement_type, movement_date, remarks, created_by)
                       VALUES
                       ($inventory_id, '$acquisition_type', '$donated_by', $quantity, 'Stock In', '$transaction_date', '$remarks', $created_by)");
        $conn->commit();

        if ($expiration_date != null){
            $stmt = $conn->prepare("SELECT id, quantity FROM inventory_with_expiration WHERE inventory_id = ?
                                    AND expiration_date = ? AND status= 1 LIMIT 1");
            $stmt->bind_param("is", $inventory_id, $expiration_date);
            $stmt->execute();
            $items_with_expiry = $stmt->get_result();
            if ($items_with_expiry->num_rows === 0) {
                $conn->query("INSERT INTO inventory_with_expiration
                (inventory_id, expiration_date, quantity, created_date, status) VALUES
                ($inventory_id, '$expiration_date', $quantity, '$transaction_date', 1)");
            }
            else{
                $current_with_expiry = $items_with_expiry->fetch_assoc();
                $iwe = $current_with_expiry["quantity"] + $quantity;
                $iweid = $current_with_expiry["id"];
                $conn->query("UPDATE inventory_with_expiration SET quantity= $iwe WHERE id = $iweid AND status= 1");
                $conn->commit();
            }
        }

        $response = ['status' => 'success', 'message' => 'Stock added successfully'];
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = 'Error: ' . $e->getMessage();
    }
    $conn->autocommit(true);
}

/* ===============================================
   *** NEW: STOCK OUT / LOST / DAMAGED / REPLACE (the missing action) ***
   =============================================== */
elseif ($action === 'add_transaction') {
    $inventory_id = (int)$_POST['inventory_id'];
    $action_type   = $conn->real_escape_string($_POST['action_type'] ?? 'Broken'); // Broken or Replace
    $quantity      = (int)$_POST['quantity'];
    $transaction_date = $conn->real_escape_string($_POST['transaction_date'] ?? date('Y-m-d'));
    $remarks       = $conn->real_escape_string($_POST['remarks'] ?? '');
    $borrower_name = $conn->real_escape_string($_POST['borrower_name'] ?? '');
    $created_by    = $_SESSION['user_id'];
    $expiration = $conn->real_escape_string($_POST['expirationSelected'] ?? null);

    if ($quantity <= 0) {
        $response['message'] = 'Quantity must be greater than zero';
        echo json_encode($response);
        closeDBConnection($conn);
        exit;
    }

    $item = $conn->query("SELECT current_stock FROM inventory WHERE id = $inventory_id AND archived = 0");
    if ($item->num_rows === 0) {
        $response['message'] = 'Item not found';
        echo json_encode($response);
        closeDBConnection($conn);
        exit;
    }
    $current = $item->fetch_assoc();
    if ($current['current_stock'] < $quantity) {
        $response['message'] = 'Not enough stock';
        echo json_encode($response);
        closeDBConnection($conn);
        exit;
    }

    $conn->autocommit(false);
    try {
        // Update stock counters
        $conn->query("UPDATE inventory SET
                      current_stock = current_stock - $quantity,
                      qty_on_hand = qty_on_hand - $quantity,
                      qty_lost = qty_lost + IF('$action_type' = 'Broken' AND '$borrower_name' LIKE '%Lost%', $quantity, 0),
                      qty_damaged = qty_damaged + IF('$action_type' = 'Broken' AND '$borrower_name' NOT LIKE '%Lost%', $quantity, 0),
                      qty_replaced = qty_replaced + IF('$action_type' = 'Replace', $quantity, 0),
                      status = IF(current_stock - $quantity > 0, 'In Stock', 'Out of Stock')
                      WHERE id = $inventory_id");

        // Log transaction
        $log_action = ($action_type === 'Replace') ? 'Replace' : 'Broken';
        $conn->query("INSERT INTO inventory_transactions
                      (inventory_id, action_type, quantity, borrowed_quantity, replaced_quantity, pay_quantity, transaction_date, remarks)
                      VALUES ($inventory_id, '$log_action', $quantity, 0, 0, 0, '$transaction_date', '$remarks')");
        
        // Log stock movement
        $conn->query("INSERT INTO stock_movement
                       (item_id, qty, movement_type, movement_date, remarks, created_by)
                       VALUES
                       ($inventory_id, $quantity, 'Stock Out', '$transaction_date', '$remarks', $created_by)");
        $conn->commit();

        if ($expiration != null){
            $stmt = $conn->prepare("SELECT id, quantity FROM inventory_with_expiration WHERE inventory_id = ?
                                    AND expiration_date = ? AND status= 1 LIMIT 1");
            $stmt->bind_param("is", $inventory_id, $expiration);
            $stmt->execute();
            $items_with_expiry = $stmt->get_result();
            if ($items_with_expiry->num_rows !== 0) {
                $current_with_expiry = $items_with_expiry->fetch_assoc();
                $iwe = $current_with_expiry["quantity"] - $quantity;
                $iweid = $current_with_expiry["id"];
                if ($iwe < 0){
                    throw new Exception("Not enough stock for the selected expiration date.");
                }
                $conn->query("UPDATE inventory_with_expiration SET quantity= $iwe, status= 0 WHERE id = $iweid AND status= 1");
                $conn->commit();
            }
        }

        $response = ['status' => 'success', 'message' => 'Transaction recorded successfully'];
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = 'Error: ' . $e->getMessage();
    }
    $conn->autocommit(true);
}

/* ===============================================
   7. BORROW MULTIPLE ITEMS (RESIDENT AS BORROWER)
   =============================================== */
elseif ($action === 'borrow_multiple') {
    // ... (unchanged – everything below this line is exactly as you had it)
    $borrower_id = (int)$_POST['borrower_id'];
    $borrower_name = $conn->real_escape_string($_POST['borrower_name']);
    $items_json = $_POST['items'] ?? '';
    $items = json_decode($items_json, true);
    $transaction_date = $conn->real_escape_string($_POST['transaction_date'] ?? date('Y-m-d'));
    $transacted_by = $conn->real_escape_string($_POST['transacted_by'] ?? '');
    $return_date = $conn->real_escape_string($_POST['return_date']);
    $remarks = $conn->real_escape_string($_POST['remarks'] ?? '');
    if (!is_array($items) || empty($items)) {
        $response['message'] = 'No items selected';
        echo json_encode($response);
        closeDBConnection($conn);
        exit;
    }
    $conn->autocommit(false);
    try {
        foreach ($items as $item) {
            $inventory_id = (int)$item['inventory_id'];
            $quantity = (int)$item['quantity'];
            if ($quantity <= 0) continue;
            $stock_check = $conn->query("SELECT current_stock FROM inventory WHERE id = $inventory_id AND archived = 0");
            if ($stock_check->num_rows === 0) {
                throw new Exception("Item ID $inventory_id not found");
            }
            $stock = $stock_check->fetch_assoc()['current_stock'];
            if ($stock < $quantity) {
                throw new Exception("Not enough stock for item ID $inventory_id (Available: $stock)");
            }
            $conn->query("UPDATE inventory SET
                          qty_on_hand = qty_on_hand - $quantity,
                          current_stock = current_stock - $quantity,
                          status = IF(current_stock - $quantity > 0, 'In Stock', 'Out of Stock')
                          WHERE id = $inventory_id");
            $conn->query("INSERT INTO inventory_transactions
                          (inventory_id, action_type, quantity, borrowed_quantity, replaced_quantity, transacted_by, transaction_date,
                           return_date, remarks, borrower_id)
                          VALUES ($inventory_id, 'Borrow', $quantity, 0, 0, '$transacted_by', '$transaction_date',
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
   8. RETURN MULTIPLE ITEMS (GOOD + DAMAGED) – FIXED VERSION
   =============================================== */
elseif ($action === 'return_multiple') {
    $returns_json = $_POST['returns'] ?? '';
    $returns = json_decode($returns_json, true);
    $remarks = $conn->real_escape_string($_POST['remarks'] ?? '');
    $today = date('Y-m-d');

    if (!is_array($returns) || empty($returns)) {
        $response['message'] = 'No return data';
        echo json_encode($response);
        closeDBConnection($conn);
        exit;
    }

    $conn->autocommit(false);
    try {
        foreach ($returns as $ret) {
            $trans_id     = (int)$ret['transaction_id'];
            $good_qty     = (int)($ret['return_qty'] ?? 0);
            $damaged_qty  = (int)($ret['damaged_qty'] ?? 0);
            $total_return = $good_qty;

            if ($total_return === 0) continue;

            $trans = $conn->query("SELECT inventory_id, borrower_id, quantity AS borrowed_qty 
                                   FROM inventory_transactions 
                                   WHERE id = $trans_id AND action_type = 'Borrow'");
            if ($trans->num_rows === 0) continue;

            $t = $trans->fetch_assoc();
            $inventory_id = $t['inventory_id'];
            $borrower_id  = $t['borrower_id'];
            $borrowed_qty = $t['borrowed_qty'];

            if ($total_return > $borrowed_qty) {
                throw new Exception("Cannot return more than borrowed ($borrowed_qty)");
            }

            // Add back good items to stock
            if ($good_qty > 0) {
                $dateToday = date('Y-m-d');
                $conn->query("UPDATE inventory SET
                              current_stock = current_stock + $good_qty,
                              qty_on_hand = qty_on_hand + $good_qty
                              WHERE id = $inventory_id");

                // $conn->query("INSERT INTO inventory_transactions
                //               (inventory_id, action_type, quantity, transaction_date, returned_date, remarks, borrower_id)
                //               VALUES ($inventory_id, 'Return', $good_qty, '$today', '$today', '$remarks', $borrower_id)");
                $conn->query("UPDATE inventory_transactions set quantity = quantity - $good_qty, borrowed_quantity = borrowed_quantity + $good_qty, info = concat(info, ' -RETURNED $good_qty for item $inventory_id on $dateToday ') WHERE id = $trans_id");
            }

            // Record damaged/lost (does NOT go back to stock)
            if ($damaged_qty > 0) {
                $conn->query("UPDATE inventory SET qty_damaged = qty_damaged + $damaged_qty WHERE id = $inventory_id");
                // $conn->query("INSERT INTO inventory_transactions
                //               (inventory_id, action_type, quantity, transaction_date, returned_date, remarks, borrower_id)
                //               VALUES ($inventory_id, 'Broken', $damaged_qty, '$today', '$today', 'Damaged/Lost: $remarks', $borrower_id)");
            }

            // ONLY mark borrow transaction as fully returned when nothing is left
            if ($total_return >= $borrowed_qty) {
                $conn->query("UPDATE inventory_transactions SET returned_date = '$today' WHERE id = $trans_id");
            }
        }
        $conn->commit();
        $response = ['status' => 'success', 'message' => 'Return processed successfully'];
    } catch (Exception $e) {
        $conn->rollback();
        $response = ['status' => 'error', 'message' => $e->getMessage()];
    }
    $conn->autocommit(true);
}
elseif ($action === 'replace_multiple') {
    $replace_json = $_POST['replace'] ?? '';
    $replace = json_decode($replace_json, true);
    $remarks = $conn->real_escape_string($_POST['remarks'] ?? '');
    $today = date('Y-m-d');

    if (!is_array($replace) || empty($replace)) {
        $response['message'] = 'No return data';
        echo json_encode($response);
        closeDBConnection($conn);
        exit;
    }

    $conn->autocommit(false);
    try {
        foreach ($replace as $rep) {
            $trans_id     = (int)$rep['transaction_id'];
            $replace_qty     = (int)($rep['replace_qty'] ?? 0);
            $damaged_qty  = (int)($rep['damaged_qty'] ?? 0);
            $total_replace = $replace_qty;

            if ($total_replace === 0) continue;

            $trans = $conn->query("SELECT inventory_id, borrower_id, quantity AS borrowed_qty 
                                   FROM inventory_transactions 
                                   WHERE id = $trans_id AND action_type = 'Borrow'");
            if ($trans->num_rows === 0) continue;

            $t = $trans->fetch_assoc();
            $inventory_id = $t['inventory_id'];
            $borrower_id  = $t['borrower_id'];
            $borrowed_qty = $t['borrowed_qty'];

            if ($total_replace > $borrowed_qty) {
                throw new Exception("Cannot return more than borrowed ($borrowed_qty)");
            }

            // Add back good items to stock
            if ($replace_qty > 0) {
                $dateToday = date('Y-m-d');
                $conn->query("UPDATE inventory SET
                              current_stock = current_stock + $replace_qty,
                              qty_on_hand = qty_on_hand + $replace_qty
                              WHERE id = $inventory_id");

                // $conn->query("INSERT INTO inventory_transactions
                //               (inventory_id, action_type, quantity, transaction_date, returned_date, remarks, borrower_id)
                //               VALUES ($inventory_id, 'Return', $good_qty, '$today', '$today', '$remarks', $borrower_id)");
                $conn->query("UPDATE inventory_transactions set quantity = quantity - $replace_qty, replaced_quantity = replaced_quantity + $replace_qty, info = concat(info, ' -REPLACED $replace_qty for item $inventory_id on $dateToday ') WHERE id = $trans_id");
            }

            // Record damaged/lost (does NOT go back to stock)
            if ($remarks == 'Damaged') {
                $conn->query("UPDATE inventory SET qty_damaged = qty_damaged + $replace_qty WHERE id = $inventory_id");
                // $conn->query("INSERT INTO inventory_transactions
                //               (inventory_id, action_type, quantity, transaction_date, returned_date, remarks, borrower_id)
                //               VALUES ($inventory_id, 'Broken', $damaged_qty, '$today', '$today', 'Damaged/Lost: $remarks', $borrower_id)");
            }

            if ($remarks == 'Lost') {
                $conn->query("UPDATE inventory SET qty_lost = qty_lost + $replace_qty WHERE id = $inventory_id");
            }

            // ONLY mark borrow transaction as fully returned when nothing is left
            if ($total_replace >= $borrowed_qty) {
                $conn->query("UPDATE inventory_transactions SET returned_date = '$today' WHERE id = $trans_id");
            }
        }
        $conn->commit();
        $response = ['status' => 'success', 'message' => 'Return processed successfully'];
    } catch (Exception $e) {
        $conn->rollback();
        $response = ['status' => 'error', 'message' => $e->getMessage()];
    }
    $conn->autocommit(true);
}
elseif ($action === 'pay_multiple') {
    $pay_json = $_POST['pay'] ?? '';
    $pay = json_decode($pay_json, true);
    $remarks = $conn->real_escape_string($_POST['remarks'] ?? '');
    $today = date('Y-m-d');

    if (!is_array($pay) || empty($pay)) {
        $response['message'] = 'No return data';
        echo json_encode($response);
        closeDBConnection($conn);
        exit;
    }

    $conn->autocommit(false);
    try {
        foreach ($pay as $py) {
            $trans_id     = (int)$py['transaction_id'];
            $pay_qty     = (int)($py['pay_qty'] ?? 0);
            $damaged_qty  = (int)($py['damaged_qty'] ?? 0);
            $total_pay = $pay_qty;

            if ($total_pay === 0) continue;

            $trans = $conn->query("SELECT inventory_id, borrower_id, quantity AS borrowed_qty 
                                   FROM inventory_transactions 
                                   WHERE id = $trans_id AND action_type = 'Borrow'");
            if ($trans->num_rows === 0) continue;

            $t = $trans->fetch_assoc();
            $inventory_id = $t['inventory_id'];
            $borrower_id  = $t['borrower_id'];
            $borrowed_qty = $t['borrowed_qty'];

            if ($total_pay > $borrowed_qty) {
                throw new Exception("Cannot return more than borrowed ($borrowed_qty)");
            }

            // Add back good items to stock
            if ($pay_qty > 0) {
                $dateToday = date('Y-m-d');
                $conn->query("UPDATE inventory SET
                              current_stock = current_stock + $pay_qty,
                              qty_on_hand = qty_on_hand + $pay_qty
                              WHERE id = $inventory_id");

                // $conn->query("INSERT INTO inventory_transactions
                //               (inventory_id, action_type, quantity, transaction_date, returned_date, remarks, borrower_id)
                //               VALUES ($inventory_id, 'Return', $good_qty, '$today', '$today', '$remarks', $borrower_id)");
                $conn->query("UPDATE inventory_transactions set quantity = quantity - $pay_qty, pay_quantity = pay_quantity + $pay_qty, info = concat(info, ' -PAID $pay_qty for item $inventory_id on $dateToday ') WHERE id = $trans_id");
            }

            // Record damaged/lost (does NOT go back to stock)
            if ($remarks == 'Damaged') {
                $conn->query("UPDATE inventory SET qty_damaged = qty_damaged + $pay_qty WHERE id = $inventory_id");
                // $conn->query("INSERT INTO inventory_transactions
                //               (inventory_id, action_type, quantity, transaction_date, returned_date, remarks, borrower_id)
                //               VALUES ($inventory_id, 'Broken', $damaged_qty, '$today', '$today', 'Damaged/Lost: $remarks', $borrower_id)");
            }

            if ($remarks == 'Lost') {
                $conn->query("UPDATE inventory SET qty_lost = qty_lost + $pay_qty WHERE id = $inventory_id");
            }

            // ONLY mark borrow transaction as fully returned when nothing is left
            if ($total_pay >= $borrowed_qty) {
                $conn->query("UPDATE inventory_transactions SET returned_date = '$today' WHERE id = $trans_id");
            }
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
   9. RECORD REPLACEMENT – NOW REDUCES OUTSTANDING BORROW
   =============================================== */
elseif ($action === 'record_replacement') {
    $trans_id = (int)$_POST['transaction_id'];
    $quantity = (int)$_POST['quantity'];
    $reason = $conn->real_escape_string($_POST['reason'] ?? 'Damaged');
    $remarks = $conn->real_escape_string($_POST['remarks'] ?? '');
    $today = date('Y-m-d');

    if ($quantity <= 0) {
        $response['message'] = 'Invalid quantity';
        echo json_encode($response);
        closeDBConnection($conn);
        exit;
    }

    $trans = $conn->query("SELECT t.inventory_id, t.borrower_id, t.quantity AS borrowed_qty,
                                  COALESCE(SUM(rt.quantity),0) AS already_returned
                           FROM inventory_transactions t
                           LEFT JOIN inventory_transactions rt ON rt.inventory_id = t.inventory_id 
                              AND rt.action_type IN ('Return','Broken') 
                              AND rt.borrower_id = t.borrower_id
                           WHERE t.id = $trans_id AND t.action_type = 'Borrow'");
    if ($trans->num_rows === 0) {
        $response['message'] = 'Borrow transaction not found';
        echo json_encode($response);
        closeDBConnection($conn);
        exit;
    }

    $t = $trans->fetch_assoc();
    $remaining = $t['borrowed_qty'] - $t['already_returned'];

    if ($quantity > $remaining) {
        $response['message'] = "Cannot replace more than remaining ($remaining)";
        echo json_encode($response);
        closeDBConnection($conn);
        exit;
    }

    // Increase replaced counter
    $conn->query("UPDATE inventory SET qty_on_hand = qty_on_hand + $quantity, current_stock = current_stock + $quantity, qty_replaced = qty_replaced + $quantity WHERE id = {$t['inventory_id']}");
    // Log replacement
    // $conn->query("INSERT INTO inventory_transactions
    //               (inventory_id, action_type, quantity, transaction_date, remarks, borrower_id)
    //               VALUES ({$t['inventory_id']}, 'Replace', $quantity, '$today', 'Replaced ($reason): $remarks', {$t['borrower_id']})");
    $conn->query("UPDATE inventory_transactions set quantity = quantity - $quantity, replaced_quantity = replaced_quantity + $quantity, info = concat(info, ' -REPLACED $quantity for item {$t['inventory_id']} on $today ') WHERE id = $trans_id");

    // If everything is replaced → mark original borrow as returned
    if (($remaining - $quantity) <= 0) {
        $conn->query("UPDATE inventory_transactions SET returned_date = '$today' WHERE id = $trans_id");
    }

    $response = ['status' => 'success', 'message' => 'Replacement recorded'];
}
/* ===============================================
   10. RECORD PAYMENT
   =============================================== */
elseif ($action === 'record_payment') {
    $trans_id = (int)$_POST['transaction_id'];
    $quantity = (int)$_POST['quantity'];
    $amount_paid = (float)$_POST['amount_paid'];
    $reason = $conn->real_escape_string($_POST['reason'] ?? '');
    $remarks = $conn->real_escape_string($_POST['remarks'] ?? '');
    if ($quantity <= 0 || $amount_paid <= 0) {
        $response['message'] = 'Invalid quantity or amount';
        echo json_encode($response);
        closeDBConnection($conn);
        exit;
    }
    $trans = $conn->query("SELECT inventory_id, borrower_id FROM inventory_transactions WHERE id = $trans_id");
    if ($trans->num_rows === 0) {
        $response['message'] = 'Transaction not found';
        echo json_encode($response);
        closeDBConnection($conn);
        exit;
    }
    $t = $trans->fetch_assoc();
    $conn->query("INSERT INTO inventory_transactions
                  (inventory_id, action_type, quantity, transaction_date, remarks, borrower_id)
                  VALUES ({$t['inventory_id']}, 'Payment', $quantity, CURDATE(), 'Payment ₱$amount_paid - $reason: $remarks', {$t['borrower_id']})");
    $response = ['status' => 'success', 'message' => 'Payment recorded successfully'];
}
/* ===============================================
   11. GET SINGLE ITEM
   =============================================== */
elseif ($action === 'get_item') {
    $id = (int)$_POST['id'];
    $sql = "SELECT i.*, COALESCE(m.value, 0) AS declared_value
            FROM inventory i
            LEFT JOIN item_meta m ON i.id = m.inventory_id AND m.meta_key = 'declared_value'
            WHERE i.id = ? AND i.archived = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $row['declared_value'] = (float)$row['declared_value'];
        $response = ['status' => 'success', 'data' => $row];
    } else {
        $response['message'] = 'Item not found';
    }
    $stmt->close();
}
/* ===============================================
   12. ARCHIVE ITEM
   =============================================== */
elseif ($action === 'archive_item') {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("UPDATE inventory SET archived = 1, status = 'Archived' WHERE id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $response = ['status' => 'success', 'message' => 'Item archived successfully'];
    } else {
        $response['message'] = 'Failed to archive item';
    }
    $stmt->close();
}
/* ===============================================
   13. FETCH STOCK IN/OUT MONITORING
   =============================================== */
   elseif ($action === 'fetch_stock_in_out_monitoring') {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = ($page - 1) * $limit;

    $where = " WHERE i.archived = 0";

    $sql = "
        SELECT
            sm.id,
            sm.item_id,
            i.item_name,
            i.description,
            sm.qty,
            sm.movement_type,
            sm.movement_date,
            sm.remarks,
            sm.created_by
        FROM stock_movement sm
        JOIN inventory i ON sm.item_id = i.id
        $where
        ORDER BY sm.movement_date DESC, sm.id DESC
        LIMIT $offset, $limit
    ";
    
    $result = $conn->query($sql);
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    $count_sql = "SELECT COUNT(*) as total FROM stock_movement sm
                 JOIN inventory i ON sm.item_id = i.id
                 WHERE i.archived = 0";

    $count_result = $conn->query($count_sql);
    $total = $count_result->fetch_assoc()['total'];
    $response = ['status' => 'success', 'data' => $items, 'total' => $total];
}
   elseif ($action === 'fetch_stock_in_out_monitoring2') {
    $page = 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $search = (int)$_POST['search'];
    $offset = ($page - 1) * $limit;

    $sql = "
        SELECT
            sm.id,
            sm.item_id,
            i.item_name,
            i.description,
            sm.qty,
            sm.movement_type,
            sm.movement_date,
            sm.remarks,
            sm.created_by
        FROM stock_movement sm
        JOIN inventory i ON sm.item_id = i.id
        WHERE sm.item_id = $search
        ORDER BY sm.movement_date DESC, sm.id DESC
        LIMIT $offset, $limit
    ";
    
    $result = $conn->query($sql);
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    $count_sql = "SELECT COUNT(*) as total FROM stock_movement sm
                 JOIN inventory i ON sm.item_id = i.id
                 WHERE i.archived = 0";

    $count_result = $conn->query($count_sql);
    $total = $count_result->fetch_assoc()['total'];
    $response = ['status' => 'success', 'data' => $items, 'total' => $total];
}
/* ===============================================
    14. CHECK THE ITEM IF HAVE EXPIRATION
=============================================== */
elseif ($action === 'is_with_expiration') {
    $id = (int)$_POST['item_id'];
    $sql = "SELECT with_expiration 
            FROM inventory
            WHERE id = $id AND archived = 0";
    $result = $conn->query($sql);
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $response = ["status"=> "success","data"=> $items];
}
/* ===============================================
    15. GET EXPIRATION DATES
=============================================== */
elseif ($action === "get_expiration_dates") {
    $id = (int)$_POST["item_id"];
    $sql = "SELECT expiration_date FROM inventory_with_expiration WHERE inventory_id = $id and status= 1";
    $result = $conn->query($sql);
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $response = ["status"=> "success","data"=> $items];
}
/* ===============================================
    16. GET EXPIRATION DATES
=============================================== */
elseif ($action === "get_quantity_by_expiration") {
    $id = (int)$_POST["item_id"];
    $expiration_date = $conn->real_escape_string($_POST["expiration_date"]);
    $sql = "SELECT quantity FROM inventory_with_expiration WHERE inventory_id = $id AND expiration_date = '$expiration_date' AND status= 1";
    $result = $conn->query($sql);
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $response = ["status"=> "success","data"=> $items];
}
/* ===============================================
   DEFAULT
   =============================================== */
else {
    $response['message'] = "Unknown action: $action";
}
echo json_encode($response);
closeDBConnection($conn);
?>