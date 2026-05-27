<?php
require __DIR__ . "/bootstrap.php";
require __DIR__ . "/db.php";

$userId = isset($_SESSION["user_id"]) ? (string)$_SESSION["user_id"] : null;
$userEmail = null;

try {
    if ($userId) {
        $emailStmt = $pdo->prepare("
            select email
            from users
            where id = ?
            limit 1
        ");
        $emailStmt->execute([$userId]);

        $userEmail = $emailStmt->fetchColumn();
        $userEmail = $userEmail ? strtolower(trim((string)$userEmail)) : null;
    }

    if (!$userId || !$userEmail) {
        $sql = "
            select 
                q.id, 
                q.slug, 
                q.title, 
                q.description,
                q.created_by,
                q.is_public,
                q.language_code,
                u.name as creator_name
            from quiz q
            left join users u on q.created_by = u.id
            where q.is_published = 1
            and q.is_public = 1
            order by q.created_at desc
        ";

        $stmt = $pdo->query($sql);
        $quizzes = $stmt->fetchAll();

        json_success([
            "quizzes" => $quizzes
        ]);
    }

    $sql = "
        select 
            q.id, 
            q.slug, 
            q.title, 
            q.description,
            q.created_by,
            q.is_public,
            q.language_code,
            u.name as creator_name
        from quiz q
        left join users u on q.created_by = u.id
        where q.is_published = 1
        and (
            q.is_public = 1
            or q.created_by = ?
            or exists (
                select 1
                from quiz_viewer_email v
                where v.quiz_id = q.id
                and lower(v.user_email) = ?
            )
        )
        order by q.created_at desc
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $userEmail]);
    $quizzes = $stmt->fetchAll();

    json_success([
        "quizzes" => $quizzes
    ]);

} catch (Throwable $e) {
    app_log_exception("QUIZZES ERROR: ", $e);
    json_error("Nem sikerült betölteni a kvízeket.", 500);
}