<?php
session_start();
require_once __DIR__ . '/Connection.php';

class Admin extends Connection {

    public function __construct() {
        parent::__construct();
        $this->initTables();
    }

    // ── Ensure DB schema is up to date ──────────────────────────────────────
    private function initTables(): void {
        // Add email column to admins if it doesn't exist yet
        try {
            $this->conn->exec("ALTER TABLE admins ADD COLUMN email VARCHAR(255) NOT NULL DEFAULT '' AFTER username");
        } catch (PDOException $e) {
            // Column already exists — safe to ignore
        }

        // Add starting_balance column to materials and populate from bincard
        try {
            $this->conn->exec("ALTER TABLE materials ADD COLUMN starting_balance INT NOT NULL DEFAULT 0 AFTER quantity");

            // Populate starting_balance = current_balance + total_issued - total_received
            // This reverses all bincard transactions to get back to the original starting stock
            $this->conn->exec("
                UPDATE materials m
                LEFT JOIN (
                    SELECT
                        b.material_id,
                        (SELECT Balance FROM bincard WHERE material_id = b.material_id ORDER BY id DESC LIMIT 1) AS latest_bal,
                        COALESCE(SUM(CASE WHEN b.Receipts NOT LIKE '%RR%' THEN b.Issues ELSE 0 END), 0) AS total_out,
                        COALESCE(
                            SUM(CASE WHEN b.Receipts REGEXP '^[0-9]+$' AND CAST(b.Receipts AS UNSIGNED) > 0
                                     THEN CAST(b.Receipts AS UNSIGNED) ELSE 0 END) +
                            SUM(CASE WHEN b.Receipts LIKE '%RR%' THEN b.Issues ELSE 0 END)
                        , 0) AS total_in
                    FROM bincard b
                    GROUP BY b.material_id
                ) bc ON bc.material_id = m.id
                SET m.starting_balance = COALESCE(bc.latest_bal + bc.total_out - bc.total_in, m.quantity)
            ");
        } catch (PDOException $e) {
            // Column already exists — safe to ignore
        }

        // Create password_resets table
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS password_resets (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                email      VARCHAR(255) NOT NULL,
                token      VARCHAR(64)  NOT NULL UNIQUE,
                expires_at DATETIME     NOT NULL,
                created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    // ── REGISTER USER ────────────────────────────────────────────────────────
    public function register(string $username, string $password, string $email = ''): bool {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $sql  = "INSERT INTO admins (username, email, password) VALUES (:username, :email, :password)";
        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':username' => $username,
            ':email'    => $email,
            ':password' => $hashedPassword,
        ]);
    }

    // ── CHECK USERNAME EXISTS ────────────────────────────────────────────────
    public function checkUserExists(string $username): bool {
        $sql  = "SELECT id FROM admins WHERE username = :username";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':username' => $username]);
        return $stmt->rowCount() > 0;
    }

    // ── CHECK EMAIL EXISTS ───────────────────────────────────────────────────
    public function checkEmailExists(string $email): bool {
        $sql  = "SELECT id FROM admins WHERE email = :email";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->rowCount() > 0;
    }

    // ── LOGIN USER ───────────────────────────────────────────────────────────
    public function login(string $username, string $password): bool {
        $sql  = "SELECT * FROM admins WHERE username = :username";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':username' => $username]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            return true;
        }

        return false;
    }

    // ── LOGOUT ───────────────────────────────────────────────────────────────
    public function logout(): void {
        session_unset();
        session_destroy();
    }

    // ── CHECK LOGIN STATUS ───────────────────────────────────────────────────
    public function isLoggedIn(): bool {
        return isset($_SESSION['user_id']);
    }

    // ── GET ALL ADMINS ───────────────────────────────────────────────────────
    public function getAllAdmins(): array {
        $sql  = "SELECT id, username FROM admins ORDER BY id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── PASSWORD RESET: CREATE TOKEN ─────────────────────────────────────────
    public function createPasswordReset(string $email): string|false {
        // Delete any existing tokens for this email
        $del = $this->conn->prepare("DELETE FROM password_resets WHERE email = :email");
        $del->execute([':email' => $email]);

        $token = bin2hex(random_bytes(32)); // 64-char hex token

        // Use MySQL NOW() for expiry so PHP/MySQL timezone differences don't cause false "expired"
        $sql  = "INSERT INTO password_resets (email, token, expires_at)
                 VALUES (:email, :token, DATE_ADD(NOW(), INTERVAL 24 HOUR))";
        $stmt = $this->conn->prepare($sql);
        $ok   = $stmt->execute([
            ':email' => $email,
            ':token' => $token,
        ]);

        return $ok ? $token : false;
    }

    // ── PASSWORD RESET: VALIDATE TOKEN ──────────────────────────────────────
    public function validateResetToken(string $token): string|false {
        $sql  = "SELECT email FROM password_resets WHERE token = :token AND expires_at > NOW()";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['email'] : false;
    }

    // ── PASSWORD RESET: APPLY NEW PASSWORD ──────────────────────────────────
    public function resetPassword(string $token, string $newPassword): bool {
        $email = $this->validateResetToken($token);
        if (!$email) return false;

        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

        $upd = $this->conn->prepare("UPDATE admins SET password = :password WHERE email = :email");
        $ok  = $upd->execute([':password' => $hashed, ':email' => $email]);

        if ($ok) {
            // Consume the token
            $del = $this->conn->prepare("DELETE FROM password_resets WHERE token = :token");
            $del->execute([':token' => $token]);
        }

        return $ok;
    }
}
