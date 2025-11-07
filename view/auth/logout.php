<?php
session_start();
session_unset();
session_destroy();

// Vì logout đang ở /view/auth/
// nên quay về index ở cấp trên
header("Location: ../../index.php");
exit();
?>
