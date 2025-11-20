<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/Quanlychitieu/function/db_connection.php';

if(!isset($_SESSION['user_id'])){
    header("Location: /Quanlychitieu/view/auth/login.php");
    exit();
}

$conn = getDbConnection();

// Xử lý form
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $date = $_POST['date'];
    $type = $_POST['type']; // Lấy loại: 'income' hoặc 'expense'
    $category = $_POST['category'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $user_id = $_SESSION['user_id'];

    // Cập nhật câu lệnh SQL để thêm cột 'type'
    $stmt = $conn->prepare("INSERT INTO transactions (user_id, date, type, category, description, amount) VALUES (?, ?, ?, ?, ?, ?)");
    
    // issssd: i(int), s(string date), s(string type), s(string category), s(string desc), d(double amount)
    $stmt->bind_param("issssd", $user_id, $date, $type, $category, $description, $amount);
    
    if ($stmt->execute()) {
        header("Location: transactions.php");
        exit();
    } else {
        $error_message = "Có lỗi xảy ra, vui lòng thử lại.";
    }
}

$page_title = 'Thêm giao dịch mới';
$active_page = 'transactions';

include $_SERVER['DOCUMENT_ROOT'] . '/Quanlychitieu/includes/header.php';
?>

<style>
    .add-transaction-page { padding: 40px 0; min-height: 80vh; display: flex; align-items: center; justify-content: center; }
    .form-card { background: white; width: 100%; max-width: 500px; padding: 30px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid #edf2f7; }
    
    /* SWITCH CHỌN LOẠI (THU / CHI) */
    .type-switch {
        display: flex; background: #f1f5f9; border-radius: 12px; padding: 4px; margin-bottom: 25px;
    }
    .type-option {
        flex: 1; text-align: center; cursor: pointer; padding: 10px;
        border-radius: 8px; font-weight: 600; color: #64748b; transition: all 0.3s;
        position: relative;
    }
    /* Ẩn input radio thật */
    .type-switch input { display: none; }
    
    /* Khi chọn Chi tiêu (Màu đỏ) */
    #type-expense:checked + label {
        background: white; color: #e53e3e; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    /* Khi chọn Thu nhập (Màu xanh) */
    #type-income:checked + label {
        background: white; color: #059669; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .form-group { margin-bottom: 20px; }
    .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #4a5568; font-size: 14px; }
    .form-control { width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 15px; outline: none; box-sizing: border-box; transition: 0.3s; }
    
    /* Màu viền input thay đổi theo loại */
    .input-expense:focus { border-color: #e53e3e; box-shadow: 0 0 0 3px rgba(229, 62, 62, 0.1); }
    .input-income:focus { border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1); }

    .btn-submit { width: 100%; padding: 14px; color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: 0.3s; font-size: 16px; }
    /* Nút thay đổi màu */
    .btn-expense { background: #e53e3e; }
    .btn-expense:hover { background: #c53030; }
    .btn-income { background: #059669; }
    .btn-income:hover { background: #047857; }

    .btn-back { display: block; text-align: center; margin-top: 20px; color: #718096; text-decoration: none; font-weight: 500; }
    .btn-back:hover { color: #2d3748; }
</style>

<section class="add-transaction-page">
    <div class="container">
        <div class="form-card">
            <h2 style="text-align:center; margin-bottom:20px; color:#2d3748;">Thêm giao dịch</h2>

            <form method="post" action="transactions_add.php" id="transForm">
                
                <div class="type-switch">
                    <input type="radio" id="type-expense" name="type" value="expense" checked onchange="toggleType('expense')">
                    <label for="type-expense" class="type-option">🔴 Chi tiêu</label>

                    <input type="radio" id="type-income" name="type" value="income" onchange="toggleType('income')">
                    <label for="type-income" class="type-option">🟢 Thu nhập</label>
                </div>

                <div class="form-group">
                    <label class="form-label">Số tiền</label>
                    <div style="position: relative;">
                        <input type="number" name="amount" id="amountInput" class="form-control input-expense" placeholder="0" step="1000" min="0" required style="padding-right: 40px; font-weight: bold; font-size: 18px;">
                        <span style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #718096; font-weight: bold;">₫</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Danh mục</label>
                    <select name="category" id="categorySelect" class="form-control input-expense" required>
                        </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Ngày giao dịch</label>
                    <input type="date" name="date" class="form-control input-expense" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Mô tả (Tùy chọn)</label>
                    <textarea name="description" class="form-control input-expense" placeholder="Ghi chú..."></textarea>
                </div>

                <button type="submit" id="submitBtn" class="btn-submit btn-expense">
                    <i class="fas fa-save"></i> Lưu khoản chi
                </button>
            </form>

            <a href="transactions.php" class="btn-back">← Quay lại danh sách</a>
        </div>
    </div>
</section>

<script>
    // Định nghĩa danh sách danh mục
    const categories = {
        expense: [
            {val: 'Food', text: '🍔 Ăn uống'},
            {val: 'Transport', text: '🛵 Di chuyển'},
            {val: 'Shopping', text: '🛍️ Mua sắm'},
            {val: 'Bills', text: '🧾 Hóa đơn (Điện/Nước)'},
            {val: 'Entertainment', text: '🎬 Giải trí'},
            {val: 'Other', text: '📦 Khác'}
        ],
        income: [
            {val: 'Salary', text: '💰 Lương'},
            {val: 'Bonus', text: '🎁 Thưởng'},
            {val: 'Investment', text: '📈 Đầu tư'},
            {val: 'Other', text: '📥 Thu nhập khác'}
        ]
    };

    function toggleType(type) {
        const categorySelect = document.getElementById('categorySelect');
        const submitBtn = document.getElementById('submitBtn');
        const inputs = document.querySelectorAll('.form-control');

        // 1. Đổi danh sách danh mục
        categorySelect.innerHTML = ''; // Xóa cũ
        categories[type].forEach(cat => {
            let option = document.createElement('option');
            option.value = cat.val;
            option.textContent = cat.text;
            categorySelect.appendChild(option);
        });

        // 2. Đổi màu sắc giao diện (Đỏ cho Chi, Xanh cho Thu)
        if (type === 'expense') {
            submitBtn.className = 'btn-submit btn-expense';
            submitBtn.innerHTML = '<i class="fas fa-minus-circle"></i> Lưu khoản chi';
            
            inputs.forEach(input => {
                input.classList.remove('input-income');
                input.classList.add('input-expense');
                if(input.id === 'amountInput') input.style.color = '#e53e3e';
            });
        } else {
            submitBtn.className = 'btn-submit btn-income';
            submitBtn.innerHTML = '<i class="fas fa-plus-circle"></i> Lưu khoản thu';
            
            inputs.forEach(input => {
                input.classList.remove('input-expense');
                input.classList.add('input-income');
                if(input.id === 'amountInput') input.style.color = '#059669';
            });
        }
    }

    // Chạy lần đầu để load danh mục mặc định (Expense)
    toggleType('expense');
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/Quanlychitieu/includes/footer.php'; ?>