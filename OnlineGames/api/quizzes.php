    <?php
    require __DIR__ . "/bootstrap.php";
    require __DIR__ . "/db.php";

    $userId = isset($_SESSION["user_id"]) ? (string)$_SESSION["user_id"] : null;
    $userEmail = null;

    if ($userId) {
        $emailStmt = $pdo->prepare("
            SELECT email
            FROM users
            WHERE id = ?
            LIMIT 1
        ");
        $emailStmt->execute([$userId]);
        $userEmail = $emailStmt->fetchColumn();
        $userEmail = $userEmail ? strtolower(trim((string)$userEmail)) : null;
    }

    try {
        if (!$userId || !$userEmail) {

            $sql = "
                SELECT 
                    q.id, 
                    q.slug, 
                    q.title, 
                    q.description,
                    q.created_by,
                    q.is_public,
                    q.language_code,
                    u.name AS creator_name
                FROM quiz q
                LEFT JOIN users u ON q.created_by = u.id
                WHERE q.is_published = 1
                AND q.is_public = 1
                ORDER BY q.created_at DESC
            ";

            $stmt = $pdo->query($sql);
            $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($quizzes, JSON_UNESCAPED_UNICODE);
            exit;
        }

        $sql = "
            SELECT 
                q.id, 
                q.slug, 
                q.title, 
                q.description,
                q.created_by,
                q.is_public,
                q.language_code,
                u.name AS creator_name
            FROM quiz q
            LEFT JOIN users u ON q.created_by = u.id
            WHERE q.is_published = 1
            AND (
                    q.is_public = 1
                    OR q.created_by = ?
                    OR EXISTS (
                        SELECT 1
                        FROM quiz_viewer_email v
                        WHERE v.quiz_id = q.id
                        AND v.user_email = ?
                    )
            )
            ORDER BY q.created_at DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $userEmail]);
        $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($quizzes, JSON_UNESCAPED_UNICODE);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "SQL hiba: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
