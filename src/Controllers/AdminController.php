<?php
declare(strict_types=1);

class AdminController
{
    private function boot(): void
    {
        requireLogin();
        requireAdmin();
    }

    // ── Dashboard ──────────────────────────────────────────────────────────
    public function dashboard(array $params = []): void
    {
        $this->boot();
        $stats = [
            'users'       => Database::fetchColumn('SELECT COUNT(*) FROM rsgrup_users'),
            'enrollments' => Database::fetchColumn('SELECT COUNT(*) FROM rsgrup_enrollments WHERE status="active"'),
            'exams_done'  => Database::fetchColumn('SELECT COUNT(*) FROM rsgrup_exam_attempts'),
            'courses'     => Database::fetchColumn('SELECT COUNT(*) FROM rsgrup_courses'),
        ];
        $recentActivity = Database::fetchAll('SELECT al.*, u.name, u.email FROM rsgrup_activity_log al LEFT JOIN rsgrup_users u ON u.id=al.user_id ORDER BY al.created_at DESC LIMIT 10');
        $metaTitle = 'Admin Dashboard';
        $robots    = 'noindex,nofollow';
        include BASE_PATH . '/templates/admin/dashboard.php';
    }

    // ── Courses ────────────────────────────────────────────────────────────
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
        $id    = Sanitize::int($_POST['id'] ?? 0);
        $title = Sanitize::string($_POST['title'] ?? '');
        $desc  = Sanitize::html($_POST['description'] ?? '');
        $slug  = Sanitize::slug($title);
        $active = isset($_POST['active']) ? 1 : 0;
        if ($id) {
            Database::execute('UPDATE rsgrup_courses SET title=?,description=?,slug=?,active=?,updated_at=NOW() WHERE id=?', [$title,$desc,$slug,$active,$id]);
        } else {
            Database::execute('INSERT INTO rsgrup_courses (title,description,slug,active,created_at,updated_at) VALUES (?,?,?,?,NOW(),NOW())', [$title,$desc,$slug,$active]);
        }
        ActivityLogger::log($_SESSION['user_id'], 'course_saved', "Curso: {$title}");
        header('Location: '.BASE_URL.'/admin/cursos'); exit;
    }

    // ── Deliveries ─────────────────────────────────────────────────────────
    public function deliveries(array $params = []): void
    {
        $this->boot();
        $deliveries = Database::fetchAll('SELECT d.*, e.title AS exam_title FROM rsgrup_deliveries d LEFT JOIN rsgrup_exams e ON e.id=d.exam_id ORDER BY d.sort_order ASC');
        $exams      = Database::fetchAll('SELECT id, title FROM rsgrup_exams ORDER BY title');
        $courses    = Database::fetchAll('SELECT id, title FROM rsgrup_courses ORDER BY title');
        $metaTitle  = 'Entregas';
        $robots     = 'noindex,nofollow';
        include BASE_PATH . '/templates/admin/deliveries.php';
    }

    public function saveDelivery(array $params = []): void
    {
        $this->boot();
        Csrf::verify();
        $id          = Sanitize::int($_POST['id'] ?? 0);
        $courseId    = Sanitize::int($_POST['course_id'] ?? 0);
        $title       = Sanitize::string($_POST['title'] ?? '');
        $description = Sanitize::html($_POST['description'] ?? '');
        $type        = in_array($_POST['type']??'', ['matricula','entrega','practica']) ? $_POST['type'] : 'entrega';
        $price       = Sanitize::float($_POST['price'] ?? 0);
        $paymentType = ($_POST['payment_type']??'online') === 'presencial' ? 'presencial' : 'online';
        $examId      = Sanitize::int($_POST['exam_id'] ?? 0) ?: null;
        $sortOrder   = Sanitize::int($_POST['sort_order'] ?? 0);
        $notifyEmail = isset($_POST['notify_email']) ? 1 : 0;
        $notifyWa    = isset($_POST['notify_whatsapp']) ? 1 : 0;
        $slug        = Sanitize::slug($title);
        $active      = isset($_POST['active']) ? 1 : 0;

        // PDF upload
        $pdfPath = $_POST['existing_pdf'] ?? null;
        if (!empty($_FILES['pdf_file']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                $filename = uniqid('pdf_') . '.pdf';
                $dest = BASE_PATH . '/private_files/' . $filename;
                move_uploaded_file($_FILES['pdf_file']['tmp_name'], $dest);
                $pdfPath = $filename;
            }
        }

        $fields = [
            $courseId,$title,$description,$slug,$type,$price,$paymentType,$examId,
            $sortOrder,$notifyEmail,$notifyWa,$pdfPath,$active
        ];

        if ($id) {
            Database::execute(
                'UPDATE rsgrup_deliveries SET course_id=?,title=?,description=?,slug=?,type=?,price=?,payment_type=?,exam_id=?,sort_order=?,notify_email=?,notify_whatsapp=?,pdf_path=?,active=?,updated_at=NOW() WHERE id=?',
                array_merge($fields, [$id])
            );
        } else {
            Database::execute(
                'INSERT INTO rsgrup_deliveries (course_id,title,description,slug,type,price,payment_type,exam_id,sort_order,notify_email,notify_whatsapp,pdf_path,active,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())',
                $fields
            );
        }
        ActivityLogger::log($_SESSION['user_id'], 'delivery_saved', "Entrega: {$title}");
        header('Location: '.BASE_URL.'/admin/entregas'); exit;
    }

    public function deleteDelivery(array $params = []): void
    {
        $this->boot();
        Csrf::verify();
        $id = Sanitize::int($params['id'] ?? 0);
        Database::execute('DELETE FROM rsgrup_deliveries WHERE id=?', [$id]);
        ActivityLogger::log($_SESSION['user_id'], 'delivery_deleted', "Entrega ID: {$id}");
        header('Location: '.BASE_URL.'/admin/entregas'); exit;
    }

    // ── Exams ──────────────────────────────────────────────────────────────
    public function exams(array $params = []): void
    {
        $this->boot();
        $exams     = Database::fetchAll('SELECT e.*, d.title AS delivery_title FROM rsgrup_exams e LEFT JOIN rsgrup_deliveries d ON d.exam_id=e.id ORDER BY e.id DESC');
        $metaTitle = 'Exámenes';
        $robots    = 'noindex,nofollow';
        include BASE_PATH . '/templates/admin/exams.php';
    }

    public function examEditor(array $params = []): void
    {
        $this->boot();
        $id        = Sanitize::int($params['id'] ?? 0);
        $exam      = $id ? ExamModel::findWithQuestions($id) : null;
        $deliveries = Database::fetchAll('SELECT id,title FROM rsgrup_deliveries ORDER BY sort_order');
        $metaTitle  = $id ? 'Editar Exámen' : 'Nuevo Exámen';
        $robots     = 'noindex,nofollow';
        include BASE_PATH . '/templates/admin/exam_edit.php';
    }

    public function saveExam(array $params = []): void
    {
        $this->boot();
        Csrf::verify();
        $id          = Sanitize::int($_POST['id'] ?? 0);
        $title       = Sanitize::string($_POST['title'] ?? '');
        $description = Sanitize::html($_POST['description'] ?? '');
        $deliveryId  = Sanitize::int($_POST['delivery_id'] ?? 0) ?: null;

        if ($id) {
            Database::execute('UPDATE rsgrup_exams SET title=?,description=?,updated_at=NOW() WHERE id=?', [$title,$description,$id]);
        } else {
            Database::execute('INSERT INTO rsgrup_exams (title,description,created_at,updated_at) VALUES (?,?,NOW(),NOW())', [$title,$description]);
            $id = (int) Database::lastInsertId();
        }

        // Link delivery
        if ($deliveryId) {
            Database::execute('UPDATE rsgrup_deliveries SET exam_id=? WHERE id=?', [$id, $deliveryId]);
        }

        // Save questions
        if (isset($_POST['questions']) && is_array($_POST['questions'])) {
            ExamModel::saveQuestions($id, $_POST['questions']);
        }

        ActivityLogger::log($_SESSION['user_id'], 'exam_saved', "Exámen: {$title}");
        header('Location: '.BASE_URL.'/admin/examenes'); exit;
    }

    public function deleteExam(array $params = []): void
    {
        $this->boot();
        Csrf::verify();
        $id = Sanitize::int($params['id'] ?? 0);
        Database::execute('DELETE FROM rsgrup_exams WHERE id=?', [$id]);
        ActivityLogger::log($_SESSION['user_id'], 'exam_deleted', "Exámen ID: {$id}");
        header('Location: '.BASE_URL.'/admin/examenes'); exit;
    }

    // ── Users ──────────────────────────────────────────────────────────────
    public function users(array $params = []): void
    {
        $this->boot();
        $search = Sanitize::string($_GET['search'] ?? '');
        $role   = in_array($_GET['role'] ?? '', ['alumno','admin']) ? $_GET['role'] : '';
        $where  = 'WHERE 1=1';
        $bind   = [];
        if ($search) {
            $where .= ' AND (u.name LIKE ? OR u.surnames LIKE ? OR u.email LIKE ?)';
            $s = "%{$search}%";
            $bind = array_merge($bind, [$s, $s, $s]);
        }
        if ($role) { $where .= ' AND u.role=?'; $bind[] = $role; }

        // LEFT JOIN para detectar si tiene matrícula activa
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
        if (!$user) { http_response_code(404); include BASE_PATH.'/public/404.php'; return; }
        $enrollments = Database::fetchAll('SELECT en.*,d.title,d.type FROM rsgrup_enrollments en JOIN rsgrup_deliveries d ON d.id=en.delivery_id WHERE en.user_id=? ORDER BY en.created_at DESC', [$id]);
        $logs        = Database::fetchAll('SELECT * FROM rsgrup_activity_log WHERE user_id=? ORDER BY created_at DESC LIMIT 50', [$id]);
        $metaTitle   = 'Usuario: '.htmlspecialchars($user['name']);
        $robots      = 'noindex,nofollow';
        include BASE_PATH . '/templates/admin/user_detail.php';
    }

    public function saveUser(array $params = []): void
    {
        $this->boot();
        Csrf::verify();
        $id   = Sanitize::int($_POST['id'] ?? 0);
        $data = [
            'name'        => Sanitize::string($_POST['name'] ?? ''),
            'surnames'    => Sanitize::string($_POST['surnames'] ?? ''),
            'email'       => Sanitize::email($_POST['email'] ?? ''),
            'phone'       => Sanitize::string($_POST['phone'] ?? '', 20),
            'address'     => Sanitize::string($_POST['address'] ?? ''),
            'postal_code' => Sanitize::string($_POST['postal_code'] ?? '', 10),
            'city'        => Sanitize::string($_POST['city'] ?? ''),
            'province'    => Sanitize::string($_POST['province'] ?? ''),
            'instagram'   => Sanitize::string($_POST['instagram'] ?? '', 100),
            'tiktok'      => Sanitize::string($_POST['tiktok'] ?? '', 100),
            'role'        => in_array($_POST['role']??'', ['alumno','admin']) ? $_POST['role'] : 'alumno',
        ];
        $pass = $_POST['password'] ?? '';
        if ($pass) $data['password'] = $pass;

        if ($id) {
            UserModel::update($id, $data);
        } else {
            $id = UserModel::create($data);
        }
        ActivityLogger::log($_SESSION['user_id'], 'user_saved', "Usuario ID: {$id}");
        header('Location: '.BASE_URL.'/admin/usuarios/'.$id); exit;
    }

    // ── Activity ───────────────────────────────────────────────────────────
    public function activity(array $params = []): void
    {
        $this->boot();
        $logs      = Database::fetchAll('SELECT al.*, u.name, u.email FROM rsgrup_activity_log al LEFT JOIN rsgrup_users u ON u.id=al.user_id ORDER BY al.created_at DESC LIMIT 200');
        $metaTitle = 'Actividad';
        $robots    = 'noindex,nofollow';
        include BASE_PATH . '/templates/admin/activity.php';
    }

    // ── Settings ───────────────────────────────────────────────────────────
    public function settings(array $params = []): void
    {
        $this->boot();
        $settings  = Database::fetchAll('SELECT `key`, `value` FROM rsgrup_settings');
        $s         = [];
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

        // Certificate PNG upload
        if (!empty($_FILES['cert_bg']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['cert_bg']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png','jpg','jpeg'])) {
                $dest = BASE_PATH . '/public/uploads/certificates/background.' . $ext;
                move_uploaded_file($_FILES['cert_bg']['tmp_name'], $dest);
                $_POST['cert_bg_path'] = '/uploads/certificates/background.' . $ext;
            }
        }

        $allowed = [
            'paypal_client_id','paypal_secret','paypal_mode',
            'smtp_host','smtp_port','smtp_user','smtp_password','smtp_from_name','smtp_from_email',
            'evolution_api_url','evolution_api_token','evolution_instance',
            'wa_contact_number',
            'email_template_subject','email_template_body',
            'whatsapp_template',
            'cert_bg_path','cert_name_x','cert_name_y','cert_name_fontsize','cert_name_color',
        ];

        foreach ($allowed as $key) {
            if (isset($_POST[$key])) {
                $value = ($key === 'email_template_body') ? Sanitize::html($_POST[$key]) : Sanitize::string($_POST[$key], 2000);
                Database::execute(
                    'INSERT INTO rsgrup_settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)',
                    [$key, $value]
                );
            }
        }

        ActivityLogger::log($_SESSION['user_id'], 'settings_saved', 'Ajustes actualizados');
        $_SESSION['flash_success'] = 'Ajustes guardados correctamente.';
        header('Location: '.BASE_URL.'/admin/settings'); exit;
    }

    public function createApiToken(array $params = []): void
    {
        $this->boot();
        Csrf::verify();
        $label = Sanitize::string($_POST['label'] ?? 'Token');
        $token = bin2hex(random_bytes(32));
        Database::execute('INSERT INTO rsgrup_api_tokens (label,token,created_at) VALUES (?,?,NOW())', [$label, $token]);
        ActivityLogger::log($_SESSION['user_id'], 'api_token_created', "Token: {$label}");
        header('Location: '.BASE_URL.'/admin/settings'); exit;
    }

    public function deleteApiToken(array $params = []): void
    {
        $this->boot();
        Csrf::verify();
        $id = Sanitize::int($params['id'] ?? 0);
        Database::execute('DELETE FROM rsgrup_api_tokens WHERE id=?', [$id]);
        header('Location: '.BASE_URL.'/admin/settings'); exit;
    }
}
