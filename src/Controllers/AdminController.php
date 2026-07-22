<?php
declare(strict_types=1);

class AdminController
{
    private function boot(): void
    {
        requireLogin();
        requireAdmin();
    }

    // ── Dashboard ─────────────────────────────────────��───────────────
    public function dashboard(array $params = []): void
    {
        $this->boot();
        $stats = [
            'users'       => Database::fetchColumn('SELECT COUNT(*) FROM rsgrup_users'),
            'enrollments' => Database::fetchColumn('SELECT COUNT(*) FROM rsgrup_enrollments WHERE status="active"'),
            'exams_done'  => Database::fetchColumn('SELECT COUNT(*) FROM rsgrup_exam_attempts'),
            'courses'     => Database::fetchColumn('SELECT COUNT(*) FROM rsgrup_courses'),
        ];
        $recentActivity = Database::fetchAll(
            'SELECT al.*, u.name, u.email FROM rsgrup_activity_log al
             LEFT JOIN rsgrup_users u ON u.id=al.user_id
             ORDER BY al.created_at DESC LIMIT 10'
        );
        $metaTitle = 'Admin Dashboard';
        $robots    = 'noindex,nofollow';
        include BASE_PATH . '/templates/admin/dashboard.php';
    }

    // ── Courses ──────────────────────────────────────────────────────
    public function courses(array $params = []): void
    {
        $this->boot();
        $courses   = Database::fetchAll('SELECT * FROM rsgrup_courses ORDER BY id DESC');
        $metaTitle = 'Cursos';
        $robots    = 'noindex,nofollow';
        include BASE_PATH . '/templates/admin/courses.php';
    }

    public function saveCourse(array $params = []): void
    {
        $this->boot();
        Csrf::verify();
        $id     = Sanitize::int($_POST['id'] ?? 0);
        $title  = Sanitize::string($_POST['title'] ?? '');
        $desc   = Sanitize::html($_POST['description'] ?? '');
        $slug   = Sanitize::slug($title);
        $active = isset($_POST['active']) ? 1 : 0;
        if ($id) {
            Database::execute(
                'UPDATE rsgrup_courses SET title=?,description=?,slug=?,active=?,updated_at=NOW() WHERE id=?',
                [$title, $desc, $slug, $active, $id]
            );
        } else {
            Database::execute(
                'INSERT INTO rsgrup_courses (title,description,slug,active,created_at,updated_at) VALUES (?,?,?,?,NOW(),NOW())',
                [$title, $desc, $slug, $active]
            );
        }
        ActivityLogger::log($_SESSION['user_id'], 'course_saved', "Curso: {$title}");
        header('Location: ' . BASE_URL . '/admin/cursos');
        exit;
    }

    // ── Entregas (nivel inscripción alumno) ──────────────────────────
    public function deliveries(array $params = []): void
    {
        $this->boot();
        $deliveries = Database::fetchAll(
            'SELECT d.*,
                    c.title                    AS course_title,
                    COUNT(DISTINCT t.id)       AS topic_count,
                    COUNT(DISTINCT en.id)      AS enrolled_count
             FROM rsgrup_deliveries d
             LEFT JOIN rsgrup_courses     c  ON c.id  = d.course_id
             LEFT JOIN rsgrup_topics      t  ON t.delivery_id = d.id
             LEFT JOIN rsgrup_enrollments en ON en.delivery_id = d.id AND en.status = "active"
             GROUP BY d.id
             ORDER BY d.sort_order ASC'
        );
        $courses   = Database::fetchAll('SELECT id, title FROM rsgrup_courses ORDER BY title');
        $metaTitle = 'Entregas';
        $robots    = 'noindex,nofollow';
        include BASE_PATH . '/templates/admin/deliveries.php';
    }

    public function deliveryEnrolled(array $params = []): void
    {
        $this->boot();
        $deliveryId = Sanitize::int($params['id'] ?? 0);
        $rows = Database::fetchAll(
            'SELECT en.id AS enrollment_id, en.status,
                    DATE_FORMAT(en.created_at,\'%d/%m/%Y\') AS enrolled_at,
                    u.id AS user_id, u.name, u.surnames, u.email
             FROM rsgrup_enrollments en
             JOIN rsgrup_users u ON u.id = en.user_id
             WHERE en.delivery_id = ?
             ORDER BY en.created_at DESC',
            [$deliveryId]
        );
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($rows);
        exit;
    }

    public function deliveryUnenroll(array $params = []): void
    {
        $this->boot();
        Csrf::verify();
        $deliveryId   = Sanitize::int($params['id']            ?? 0);
        $enrollmentId = Sanitize::int($params['enrollment_id'] ?? 0);
        Database::execute(
            "UPDATE rsgrup_enrollments SET status='cancelled' WHERE id=? AND delivery_id=?",
            [$enrollmentId, $deliveryId]
        );
        ActivityLogger::log($_SESSION['user_id'], 'unenroll',
            "Baja inscripción ID:{$enrollmentId} entrega ID:{$deliveryId}");
        $_SESSION['flash_success'] = 'Alumno dado de baja correctamente.';
        header('Location: ' . BASE_URL . '/admin/entregas');
        exit;
    }

    public function saveDelivery(array $params = []): void
    {
        $this->boot();
        Csrf::verify();
        $id          = Sanitize::int($_POST['id'] ?? 0);
        $courseId    = Sanitize::int($_POST['course_id'] ?? 0);
        $title       = Sanitize::string($_POST['title'] ?? '');
        $description = Sanitize::html($_POST['description'] ?? '');
        $type        = in_array($_POST['type'] ?? '', ['matricula', 'entrega', 'practica'])
                           ? $_POST['type'] : 'entrega';
        $price       = Sanitize::float($_POST['price'] ?? 0);
        $paymentType = ($_POST['payment_type'] ?? 'online') === 'presencial' ? 'presencial' : 'online';
        $sortOrder   = Sanitize::int($_POST['sort_order'] ?? 0);
        $notifyEmail = isset($_POST['notify_email'])    ? 1 : 0;
        $notifyWa    = isset($_POST['notify_whatsapp']) ? 1 : 0;
        $slug        = Sanitize::slug($title);
        $active      = isset($_POST['active']) ? 1 : 0;

        $fields = [$courseId, $title, $description, $slug, $type, $price,
                   $paymentType, $sortOrder, $notifyEmail, $notifyWa, $active];
        if ($id) {
            Database::execute(
                'UPDATE rsgrup_deliveries
                 SET course_id=?,title=?,description=?,slug=?,type=?,price=?,
                     payment_type=?,sort_order=?,notify_email=?,notify_whatsapp=?,active=?,
                     updated_at=NOW()
                 WHERE id=?',
                array_merge($fields, [$id])
            );
        } else {
            Database::execute(
                'INSERT INTO rsgrup_deliveries
                     (course_id,title,description,slug,type,price,payment_type,
                      sort_order,notify_email,notify_whatsapp,active,created_at,updated_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())',
                $fields
            );
        }
        ActivityLogger::log($_SESSION['user_id'], 'delivery_saved', "Entrega: {$title}");
        header('Location: ' . BASE_URL . '/admin/entregas');
        exit;
    }

    public function deleteDelivery(array $params = []): void
    {
        $this->boot();
        Csrf::verify();
        $id = Sanitize::int($params['id'] ?? 0);
        Database::execute('DELETE FROM rsgrup_deliveries WHERE id=?', [$id]);
        ActivityLogger::log($_SESSION['user_id'], 'delivery_deleted', "Entrega ID: {$id}");
        header('Location: ' . BASE_URL . '/admin/entregas');
        exit;
    }

    // ── Temas (ex-Entregas) ─────────────────────────────────────────
    public function topics(array $params = []): void
    {
        $this->boot();
        $topics     = Database::fetchAll(
            'SELECT t.*,
                    d.title AS delivery_title,
                    e.title AS exam_title
             FROM rsgrup_topics t
             LEFT JOIN rsgrup_deliveries d ON d.id = t.delivery_id
             LEFT JOIN rsgrup_exams      e ON e.id = t.exam_id
             ORDER BY t.delivery_id ASC, t.sort_order ASC, t.id ASC'
        );
        $deliveries = Database::fetchAll('SELECT id, title FROM rsgrup_deliveries ORDER BY sort_order');
        $exams      = Database::fetchAll('SELECT id, title FROM rsgrup_exams ORDER BY title');
        $metaTitle  = 'Temas';
        $robots     = 'noindex,nofollow';
        include BASE_PATH . '/templates/admin/topics.php';
    }

    private function pdfFilename(string $title, string $dir): string
    {
        $base = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title);
        $base = preg_replace('/\s+/', '_', trim($base));
        $base = preg_replace('/[^A-Za-z0-9_\-]/', '', $base);
        $base = trim($base, '_-') ?: 'tema';
        $filename = $base . '.pdf';
        if (!file_exists($dir . '/' . $filename)) return $filename;
        $i = 2;
        do { $filename = $base . '_' . $i . '.pdf'; $i++; }
        while (file_exists($dir . '/' . $filename));
        return $filename;
    }

    public function saveTopic(array $params = []): void
    {
        $this->boot();
        Csrf::verify();
        $id          = Sanitize::int($_POST['id'] ?? 0);
        $deliveryId  = Sanitize::int($_POST['delivery_id'] ?? 0);
        $title       = Sanitize::string($_POST['title'] ?? '');
        $description = Sanitize::html($_POST['description'] ?? '');
        $examId      = Sanitize::int($_POST['exam_id'] ?? 0) ?: null;
        $sortOrder   = Sanitize::int($_POST['sort_order'] ?? 0);
        $active      = isset($_POST['active']) ? 1 : 0;

        // PDF upload
        $pdfFile = $id ? (Database::fetchColumn('SELECT pdf_file FROM rsgrup_topics WHERE id=?', [$id]) ?: null) : null;
        if (!empty($_FILES['pdf_file']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                $dir = BASE_PATH . '/private_files';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $filename = $this->pdfFilename($title, $dir);
                move_uploaded_file($_FILES['pdf_file']['tmp_name'], $dir . '/' . $filename);
                $pdfFile = $filename;
            }
        }

        if ($id) {
            TopicModel::update($id, [
                'delivery_id'  => $deliveryId,
                'exam_id'      => $examId,
                'title'        => $title,
                'description'  => $description,
                'pdf_file'     => $pdfFile,
                'sort_order'   => $sortOrder,
                'active'       => $active,
            ]);
        } else {
            TopicModel::create([
                'delivery_id'  => $deliveryId,
                'exam_id'      => $examId,
                'title'        => $title,
                'description'  => $description,
                'pdf_file'     => $pdfFile,
                'sort_order'   => $sortOrder,
                'active'       => $active,
            ]);
        }
        ActivityLogger::log($_SESSION['user_id'], 'topic_saved', "Tema: {$title}");
        header('Location: ' . BASE_URL . '/admin/temas');
        exit;
    }

    public function deleteTopic(array $params = []): void
    {
        $this->boot();
        Csrf::verify();
        $id = Sanitize::int($params['id'] ?? 0);
        TopicModel::delete($id);
        ActivityLogger::log($_SESSION['user_id'], 'topic_deleted', "Tema ID: {$id}");
        header('Location: ' . BASE_URL . '/admin/temas');
        exit;
    }

    // ── Exams ───────────────────────────────────────────────────────
    public function exams(array $params = []): void
    {
        $this->boot();
        $exams = Database::fetchAll(
            'SELECT e.*,
                    t.title AS topic_title,
                    COUNT(DISTINCT q.id) AS question_count
             FROM rsgrup_exams e
             LEFT JOIN rsgrup_topics          t ON t.exam_id = e.id
             LEFT JOIN rsgrup_exam_questions  q ON q.exam_id = e.id
             GROUP BY e.id
             ORDER BY e.id DESC'
        );
        $metaTitle = 'Exámenes';
        $robots    = 'noindex,nofollow';
        include BASE_PATH . '/templates/admin/exams.php';
    }

    public function examEditor(array $params = []): void
    {
        $this->boot();
        $id     = Sanitize::int($params['id'] ?? 0);
        $exam   = $id ? ExamModel::findWithQuestions($id) : null;
        $topics = Database::fetchAll(
            'SELECT t.id, CONCAT(d.title, " — ", t.title) AS title
             FROM rsgrup_topics t
             JOIN rsgrup_deliveries d ON d.id = t.delivery_id
             WHERE t.active = 1
             ORDER BY d.sort_order, t.sort_order'
        );
        $metaTitle = $id ? 'Editar Exámen' : 'Nuevo Exámen';
        $robots    = 'noindex,nofollow';
        include BASE_PATH . '/templates/admin/exam_edit.php';
    }

    public function saveExam(array $params = []): void
    {
        $this->boot();
        Csrf::verify();
        $id          = Sanitize::int($_POST['id'] ?? 0);
        $title       = Sanitize::string($_POST['title'] ?? '');
        $description = Sanitize::html($_POST['description'] ?? '');
        $topicId     = Sanitize::int($_POST['topic_id'] ?? 0) ?: null;
        if ($id) {
            Database::execute(
                'UPDATE rsgrup_exams SET title=?,description=?,updated_at=NOW() WHERE id=?',
                [$title, $description, $id]
            );
        } else {
            Database::execute(
                'INSERT INTO rsgrup_exams (title,description,created_at,updated_at) VALUES (?,?,NOW(),NOW())',
                [$title, $description]
            );
            $id = (int) Database::lastInsertId();
        }
        // Vincular examen al tema seleccionado
        if ($topicId) {
            Database::execute('UPDATE rsgrup_topics SET exam_id=? WHERE id=?', [$id, $topicId]);
        }
        if (isset($_POST['questions']) && is_array($_POST['questions'])) {
            ExamModel::saveQuestions($id, $_POST['questions']);
        }
        ActivityLogger::log($_SESSION['user_id'], 'exam_saved', "Exámen: {$title}");
        header('Location: ' . BASE_URL . '/admin/examenes');
        exit;
    }

    public function deleteExam(array $params = []): void
    {
        $this->boot();
        Csrf::verify();
        $id = Sanitize::int($params['id'] ?? 0);
        Database::execute('DELETE FROM rsgrup_exams WHERE id=?', [$id]);
        ActivityLogger::log($_SESSION['user_id'], 'exam_deleted', "Exámen ID: {$id}");
        header('Location: ' . BASE_URL . '/admin/examenes');
        exit;
    }

    // ── Users ────────────────────────────────────────────────────────
    public function users(array $params = []): void
    {
        $this->boot();
        $search = Sanitize::string($_GET['search'] ?? '');
        $role   = in_array($_GET['role'] ?? '', ['alumno', 'admin']) ? $_GET['role'] : '';
        $where  = 'WHERE 1=1';
        $bind   = [];
        if ($search) {
            $where .= ' AND (u.name LIKE ? OR u.surnames LIKE ? OR u.email LIKE ?)';
            $s      = "%{$search}%";
            $bind   = array_merge($bind, [$s, $s, $s]);
        }
        if ($role) { $where .= ' AND u.role=?'; $bind[] = $role; }
        $sql = "SELECT u.*,
                    CASE WHEN m.id IS NOT NULL THEN 1 ELSE 0 END AS has_matricula
                FROM rsgrup_users u
                LEFT JOIN rsgrup_enrollments m
                    ON m.user_id = u.id
                    AND m.status = 'active'
                    AND m.delivery_id IN (SELECT id FROM rsgrup_deliveries WHERE type='matricula')
                {$where}
                ORDER BY u.created_at DESC";
        $users     = Database::fetchAll($sql, $bind);
        $metaTitle = 'Usuarios';
        $robots    = 'noindex,nofollow';
        include BASE_PATH . '/templates/admin/users.php';
    }

    public function userDetail(array $params = []): void
    {
        $this->boot();
        $id   = Sanitize::int($params['id'] ?? 0);
        $user = UserModel::findById($id);
        if (!$user) {
            http_response_code(404);
            include BASE_PATH . '/public/404.php';
            return;
        }
        $enrollments = Database::fetchAll(
            'SELECT en.*, d.title, d.type FROM rsgrup_enrollments en
             JOIN rsgrup_deliveries d ON d.id = en.delivery_id
             WHERE en.user_id=? ORDER BY en.created_at DESC',
            [$id]
        );
        $logs      = Database::fetchAll(
            'SELECT * FROM rsgrup_activity_log WHERE user_id=? ORDER BY created_at DESC LIMIT 50',
            [$id]
        );
        $metaTitle = 'Usuario: ' . htmlspecialchars($user['name']);
        $robots    = 'noindex,nofollow';
        include BASE_PATH . '/templates/admin/user_detail.php';
    }

    public function userUnenroll(array $params = []): void
    {
        $this->boot();
        Csrf::verify();
        $userId       = Sanitize::int($params['id']            ?? 0);
        $enrollmentId = Sanitize::int($params['enrollment_id'] ?? 0);
        Database::execute(
            "UPDATE rsgrup_enrollments SET status='cancelled' WHERE id=? AND user_id=?",
            [$enrollmentId, $userId]
        );
        ActivityLogger::log($_SESSION['user_id'], 'unenroll',
            "Baja inscripción ID:{$enrollmentId} usuario ID:{$userId}");
        $_SESSION['flash_success'] = 'Inscripción cancelada correctamente.';
        header('Location: ' . BASE_URL . '/admin/usuarios/' . $userId);
        exit;
    }

    public function saveUser(array $params = []): void
    {
        $this->boot();
        Csrf::verify();
        $id   = Sanitize::int($_POST['id'] ?? 0);
        $data = [
            'name'        => Sanitize::string($_POST['name']        ?? ''),
            'surnames'    => Sanitize::string($_POST['surnames']    ?? ''),
            'email'       => Sanitize::email($_POST['email']        ?? ''),
            'phone'       => Sanitize::string($_POST['phone']       ?? '', 20),
            'address'     => Sanitize::string($_POST['address']     ?? ''),
            'postal_code' => Sanitize::string($_POST['postal_code'] ?? '', 10),
            'city'        => Sanitize::string($_POST['city']        ?? ''),
            'province'    => Sanitize::string($_POST['province']    ?? ''),
            'instagram'   => Sanitize::string($_POST['instagram']   ?? '', 100),
            'tiktok'      => Sanitize::string($_POST['tiktok']      ?? '', 100),
            'role'        => in_array($_POST['role'] ?? '', ['alumno', 'admin']) ? $_POST['role'] : 'alumno',
        ];
        $pass = $_POST['password'] ?? '';
        if ($pass) $data['password'] = $pass;
        if ($id) {
            UserModel::update($id, $data);
        } else {
            $id = UserModel::create($data);
        }
        ActivityLogger::log($_SESSION['user_id'], 'user_saved', "Usuario ID: {$id}");
        header('Location: ' . BASE_URL . '/admin/usuarios/' . $id);
        exit;
    }

    // ── Activity ─────────────────────────────────────────────────────
    public function activity(array $params = []): void
    {
        $this->boot();
        $logs = Database::fetchAll(
            'SELECT al.*, u.name, u.email FROM rsgrup_activity_log al
             LEFT JOIN rsgrup_users u ON u.id=al.user_id
             ORDER BY al.created_at DESC LIMIT 200'
        );
        $metaTitle = 'Actividad';
        $robots    = 'noindex,nofollow';
        include BASE_PATH . '/templates/admin/activity.php';
    }

    // ── Settings ─────────────────────────────────────────────────────
    public function settings(array $params = []): void
    {
        $this->boot();
        $settings = Database::fetchAll('SELECT `key`, `value` FROM rsgrup_settings');
        $s        = [];
        foreach ($settings as $row) $s[$row['key']] = $row['value'];
        $apiTokens = Database::fetchAll('SELECT * FROM rsgrup_api_tokens ORDER BY created_at DESC');
        $metaTitle = 'Ajustes';
        $robots    = 'noindex,nofollow';
        include BASE_PATH . '/templates/admin/settings.php';
    }

    public function saveSettings(array $params = []): void
    {
        $this->boot();
        Csrf::verify();
        if (!empty($_FILES['cert_bg']['tmp_name']) && $_FILES['cert_bg']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['cert_bg']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg'])) {
                $uploadDir = BASE_PATH . '/public/uploads/certificates/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = 'background.' . $ext;
                move_uploaded_file($_FILES['cert_bg']['tmp_name'], $uploadDir . $filename);
                $_POST['cert_bg_path'] = '/public/uploads/certificates/' . $filename;
            }
        }
        foreach (['brand_accent_color', 'cert_name_color'] as $colorKey) {
            if (!empty($_POST[$colorKey])) {
                $v = ltrim($_POST[$colorKey], '#');
                if (preg_match('/^[0-9a-fA-F]{6}$/', $v)) {
                    $_POST[$colorKey] = '#' . $v;
                } else {
                    unset($_POST[$colorKey]);
                }
            }
        }
        $examSchedule = in_array($_POST['exam_schedule'] ?? '',
            ['last_saturday', 'always', 'custom_days'])
            ? $_POST['exam_schedule'] : 'last_saturday';
        $_POST['exam_schedule'] = $examSchedule;
        $rawDays   = isset($_POST['exam_custom_days']) && is_array($_POST['exam_custom_days'])
            ? $_POST['exam_custom_days'] : [];
        $validDays = array_values(array_unique(
            array_filter(array_map('intval', $rawDays), fn($d) => $d >= 0 && $d <= 6)
        ));
        sort($validDays);
        $_POST['exam_custom_days'] = implode(',', $validDays);
        $allowed = [
            'brand_accent_color',
            'paypal_client_id', 'paypal_client_secret', 'paypal_mode',
            'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_from_name', 'smtp_from_email',
            'evolution_api_url', 'evolution_api_token', 'evolution_instance',
            'whatsapp_support_number', 'whatsapp_template',
            'email_template_subject', 'email_template_body',
            'cert_bg_path', 'cert_name_x', 'cert_name_y', 'cert_name_fontsize', 'cert_name_color',
            'exam_schedule', 'exam_custom_days',
        ];
        foreach ($allowed as $key) {
            if (!isset($_POST[$key])) continue;
            Database::execute(
                'INSERT INTO rsgrup_settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)',
                [$key, $_POST[$key]]
            );
        }
        ActivityLogger::log($_SESSION['user_id'], 'settings_saved', 'Ajustes guardados');
        $_SESSION['flash_success'] = 'Ajustes guardados correctamente.';
        header('Location: ' . BASE_URL . '/admin/settings');
        exit;
    }

    // ── API Tokens ───────────────────────────────────────────────────
    public function createApiToken(array $params = []): void
    {
        $this->boot();
        Csrf::verify();
        $label = Sanitize::string($_POST['label'] ?? 'Token');
        $token = bin2hex(random_bytes(32));
        Database::execute(
            'INSERT INTO rsgrup_api_tokens (label, token, created_at) VALUES (?, ?, NOW())',
            [$label, $token]
        );
        ActivityLogger::log($_SESSION['user_id'], 'token_created', "Token: {$label}");
        $_SESSION['flash_success'] =
            'Token generado: ' . $token . ' — Cópialo ahora, no se mostrará de nuevo.';
        header('Location: ' . BASE_URL . '/admin/settings');
        exit;
    }

    public function deleteApiToken(array $params = []): void
    {
        $this->boot();
        Csrf::verify();
        $id = Sanitize::int($params['id'] ?? 0);
        Database::execute('DELETE FROM rsgrup_api_tokens WHERE id=?', [$id]);
        ActivityLogger::log($_SESSION['user_id'], 'token_deleted', "Token ID: {$id}");
        $_SESSION['flash_success'] = 'Token eliminado.';
        header('Location: ' . BASE_URL . '/admin/settings');
        exit;
    }

    public function createToken(array $params = []): void { $this->createApiToken($params); }
    public function deleteToken(array $params = []): void  { $this->deleteApiToken($params); }

    // ── Títulos: impresión masiva ──────────────────────────────────────
    public function titlesBulk(array $params = []): void
    {
        $this->boot();
        $perPage = 20;
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $offset  = ($page - 1) * $perPage;
        $baseSql = "
            SELECT
                u.id           AS user_id,
                u.name,
                u.surnames,
                u.email,
                c.id           AS course_id,
                c.title        AS course_title,
                DATE_FORMAT(
                    COALESCE(
                        MIN(CASE WHEN d.type = 'matricula' THEN en.created_at END),
                        MIN(en.created_at)
                    ),
                    '%d/%m/%Y'
                ) AS enrolled_at,
                (
                    SELECT ea2.score
                    FROM   rsgrup_exam_attempts ea2
                    JOIN   rsgrup_enrollments   en2 ON en2.id = ea2.enrollment_id
                    WHERE  en2.user_id = u.id
                      AND  en2.delivery_id IN (
                               SELECT id FROM rsgrup_deliveries
                               WHERE course_id = c.id AND active = 1
                           )
                    ORDER BY ea2.created_at DESC
                    LIMIT 1
                ) AS score
            FROM rsgrup_users u
            JOIN rsgrup_enrollments en ON en.user_id = u.id AND en.status = 'active'
            JOIN rsgrup_deliveries  d  ON d.id = en.delivery_id AND d.active = 1
            JOIN rsgrup_courses     c  ON c.id = d.course_id AND c.active = 1
            GROUP BY u.id, c.id
            HAVING
                COUNT(DISTINCT en.delivery_id) = (
                    SELECT COUNT(*) FROM rsgrup_deliveries d2
                    WHERE d2.course_id = c.id AND d2.active = 1
                )
                AND EXISTS (
                    SELECT 1 FROM rsgrup_exam_attempts ea
                    JOIN rsgrup_enrollments en3 ON en3.id = ea.enrollment_id
                    WHERE en3.user_id = u.id
                      AND en3.delivery_id IN (
                              SELECT id FROM rsgrup_deliveries
                              WHERE course_id = c.id AND active = 1
                          )
                )
        ";
        $totalRows    = (int) Database::fetchColumn("SELECT COUNT(*) FROM ({$baseSql}) sub");
        $rows         = Database::fetchAll("{$baseSql} ORDER BY enrolled_at DESC LIMIT ? OFFSET ?",
                             [$perPage, $offset]);
        $passingScore = ExamModel::passingScore();
        $metaTitle    = 'Impresión masiva de títulos';
        $robots       = 'noindex,nofollow';
        include BASE_PATH . '/templates/admin/titles_bulk.php';
    }

    public function titlesBulkGenerate(array $params = []): void
    {
        $this->boot();
        Csrf::verify();
        $rawIds = isset($_POST['bulk_keys']) && is_array($_POST['bulk_keys'])
            ? $_POST['bulk_keys'] : [];
        if (empty($rawIds)) {
            $_SESSION['flash_error'] = 'No se seleccionó ningún alumno.';
            header('Location: ' . BASE_URL . '/admin/titulos-masivos');
            exit;
        }
        $rows = [];
        foreach ($rawIds as $key) {
            [$userId, $courseId] = array_map('intval', explode('_', $key));
            if (!$userId || !$courseId) continue;
            $row = Database::fetch(
                "SELECT u.name, u.surnames, u.email,
                        c.title AS delivery_title,
                        DATE_FORMAT(
                            COALESCE(
                                MIN(CASE WHEN d.type = 'matricula' THEN en.created_at END),
                                MIN(en.created_at)
                            ), '%d/%m/%Y'
                        ) AS enrolled_at
                 FROM rsgrup_users u
                 JOIN rsgrup_enrollments en ON en.user_id = u.id AND en.status = 'active'
                 JOIN rsgrup_deliveries  d  ON d.id = en.delivery_id AND d.course_id = ?
                 JOIN rsgrup_courses     c  ON c.id = d.course_id
                 WHERE u.id = ?
                 GROUP BY u.id, c.id",
                [$courseId, $userId]
            );
            if ($row) $rows[] = $row;
        }
        if (empty($rows)) {
            $_SESSION['flash_error'] = 'No se encontraron datos válidos.';
            header('Location: ' . BASE_URL . '/admin/titulos-masivos');
            exit;
        }
        $pdfContent = CertificateService::generateBulk($rows);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="titulos-masivos-' . date('Ymd-His') . '.pdf"');
        header('Content-Length: ' . strlen($pdfContent));
        header('Cache-Control: no-store');
        echo $pdfContent;
        exit;
    }
}
