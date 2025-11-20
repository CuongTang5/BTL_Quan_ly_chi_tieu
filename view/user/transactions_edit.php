<?php
session_start();
// Dùng đường dẫn tuyệt đối
include $_SERVER['DOCUMENT_ROOT'] . '/Quanlychitieu/function/db_connection.php';

// Kiểm tra đăng nhập
if(!isset($_SESSION['user_id'])){
    header("Location: /Quanlychitieu/view/auth/login.php");
    exit();
}

$conn = getDbConnection();
$user_id = $_SESSION['user_id'];
$id = $_GET['id'] ?? 0;

// 1. Lấy thông tin chi tiêu cũ
$stmt = $conn->prepare("SELECT * FROM transactions WHERE id=? AND user_id=?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

// Nếu không tìm thấy giao dịch (hoặc id sai), quay về danh sách
if (!$row) {
    header("Location: transactions.php");
    exit();
}

// 2. Xử lý cập nhật
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $date = $_POST['date'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];

    // Cập nhật database
    // Lưu ý: bind_param cần 6 tham số: date(s), category(s), description(s), amount(d), id(i), user_id(i)
    // Code cũ của bạn là "sssdi" (thiếu 1 chữ i), tôi đã sửa thành "sssdii"
    $stmt = $conn->prepare("UPDATE transactions SET date=?, category=?, description=?, amount=? WHERE id=? AND user_id=?");
    $stmt->bind_param("sssdii", $date, $category, $description, $amount, $id, $user_id);
    
    if ($stmt->execute()) {
        header("Location: transactions.php");
        exit();
    } else {
        $error_message = "Lỗi cập nhật, vui lòng thử lại.";
    }
}

// Cấu hình Header
$page_title = 'Sửa chi tiêu';
$active_page = 'transactions';

include $_SERVER['DOCUMENT_ROOT'] . '/Quanlychitieu/includes/header.php';
?>

<style>
    .edit-transaction-page {
        padding: 40px 0;
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .form-card {
        background: white;
        width: 100%;
        max-width: 500px;
        margin: 0 auto;
        padding: 40px;
        border-radius: 16px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
        border: 1px solid #edf2f7;
    }
    
    .form-header { text-align: center; margin-bottom: 30px; }
    
    .icon-wrapper {
        width: 70px; height: 70px; 
        background: #ebf8ff; /* Màu xanh dương nhạt để phân biệt với trang Thêm */
        border-radius: 50%; 
        display: flex; align-items: center; justify-content: center; 
        margin: 0 auto 15px auto; 
        color: #3182ce; font-size: 28px;
    }

    .form-header h2 { color: #2d3748; font-size: 24px; font-weight: 700; margin-bottom: 5px; }
    .form-header p { color: #718096; font-size: 14px; }

    .form-group { margin-bottom: 20px; }
    .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #4a5568; font-size: 14px; }
    
    .form-control {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 15px;
        transition: all 0.3s;
        color: #2d3748;
        outline: none;
        background-color: #fff;
        box-sizing: border-box;
    }
    
    .form-control:focus { 
        border-color: #3182ce; /* Màu focus xanh dương */
        box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1); 
    }
    
    textarea.form-control { resize: vertical; min-height: 100px; font-family: inherit; }

    .btn-submit {
        width: 100%;
        padding: 14px;
        background: #3182ce; /* Nút màu xanh dương */
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        transition: background 0.3s;
        display: flex; justify-content: center; align-items: center; gap: 10px;
        margin-top: 10px;
    }
    
    .btn-submit:hover { background: #2b6cb0; transform: translateY(-1px); }
    
    .btn-back {
        display: block; text-align: center; margin-top: 20px; color: #718096;
        text-decoration: none; font-weight: 500; transition: color 0.3s; font-size: 14px;
    }
    .btn-back:hover { color: #2d3748; text-decoration: underline; }
    
    .alert-error { background: #fed7d7; color: #c53030; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
</style>

<section class="edit-transaction-page">
    <div class="container">
        
        <div class="form-card">
            <div class="form-header">
                <div class="icon-wrapper">
                    <i class="fas fa-edit"></i>
                </div>
                <h2>Sửa khoản chi</h2>
                <p>Cập nhật thông tin giao dịch</p>
            </div>

            <?php if(isset($error_message)): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="transactions_edit.php?id=<?php echo $id; ?>">
                <div class="form-group">
                    <label class="form-label">Số tiền chi tiêu <span style="color:red">*</span></label>
                    <div style="position: relative;">
                        <input type="number" name="amount" class="form-control" 
                               value="<?php echo $row['amount']; ?>" 
                               min="0" required 
                               style="padding-right: 40px; font-weight: bold; color: #3182ce;">
                        <span style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #718096; font-weight: bold;">₫</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Danh mục <span style="color:red">*</span></label>
                    <select name="category" class="form-control" required>
                        <option value="" disabled>-- Chọn danh mục --</option>
                        <?php 
                            $current_cat = $row['category'];
                            $categories = [
                                'Food' => '🍔 Ăn uống',
                                'Transport' => '🛵 Di chuyển',
                                'Shopping' => '🛍️ Mua sắm',
                                'Bills' => '🧾 Hóa đơn (Điện/Nước/Net)',
                                'Entertainment' => '🎬 Giải trí',
                                'Other' => '📦 Khác'
                            ];
                            foreach($categories as $key => $label) {
                                $selected = ($current_cat == $key) ? 'selected' : '';
                                echo "<option value='$key' $selected>$label</option>";
                            }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Ngày chi tiêu <span style="color:red">*</span></label>
                    <input type="date" name="date" class="form-control" 
                           value="<?php echo $row['date']; ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Mô tả chi tiết</label>
                    <textarea name="description" class="form-control" placeholder="Mô tả..."><?php echo htmlspecialchars($row['description']); ?></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Cập nhật
                </button>
            </form>

            <a href="transactions.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Quay lại danh sách
            </a>
        </div>

    </div>
</section>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/Quanlychitieu/includes/footer.php'; ?>