<?php
namespace Core\Users\Controllers;

class Users extends \CodeIgniter\Controller
{
    public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Users\Models\UsersModel();
    }
    
    public function index( $page = false ) {
        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            'config' => $this->config
        ];

        switch ( $page ) {
            case 'update':
                $item = false;
                $ids = uri('segment', 4);
                if( $ids ){
                    $item = db_get("*", TB_USERS, [ "ids" => $ids ]);
                }

                $plans = db_fetch("*", TB_PLANS, "", "id", "ASC");
                $group_roles = db_fetch("*", TB_ROLES, "", "id", "ASC");

                $data['content'] = view('Core\Users\Views\update', ["result" => $item, 'plans' => $plans, "group_roles" => $group_roles, 'config' => $this->config]);
                break;

            case 'role':
                if (!find_modules("payment")) {
                    redirect_to( get_module_url() );
                }

                $ids = uri('segment', 4);
                $request = \Config\Services::request();
                $result = db_fetch("*", TB_ROLES, [], "id", "ASC");
                $item = db_get("*", TB_ROLES, "ids = '{$ids}'");
                if(!is_ajax() || uri("segment", 4) == ""){
                    $data['content'] = view('Core\Users\Views\role', [
                        "roles" => $request->roles,
                        "result" => $result,
                        "item" => $item
                    ]);
                }else{
                   $data['content'] = view('Core\Users\Views\update_role', [
                        "roles" => $request->roles,
                        "result" => $result,
                        "item" => $item,
                        'config' => $this->config
                    ]); 
                }
                break;

            case 'report':
                if (!find_modules("payment")) {
                    redirect_to( get_module_url() );
                }
                $data['content'] = view('Core\Users\Views\report', [
                    "result" => $this->model->get_report(),
                    'config' => $this->config
                ]);
                break;
            
            default:
                $start = 0;
                $limit = 1;

                $pager = \Config\Services::pager();
                $total = $this->model->get_list(false);

                $datatable = [
                    "responsive" => true,
                    "columns" => [
                        "id" => __("ID"),
                        "user" => __("User"),
                        "admin" => __("Admin"),
                        "role" => __("Role"),
                        "plan" => __("Plan"),
                        "expiration_date" => __("Expiration date"),
                        "login_type" => __("Login type"),
                        "status" => __("Status"),
                        "created" => __("Created"),
                    ],
                    "total_items" => $total,
                    "per_page" => 50,
                    "current_page" => 1,

                ];

                $data_content = [
                    'start' => $start,
                    'limit' => $limit,
                    'total' => $total,
                    'pager' => $pager,
                    'datatable'  => $datatable,
                    'config' => $this->config
                ];

                $data['content'] = view('Core\Users\Views\list', $data_content);
                break;
        }

        return view('Core\Users\Views\index', $data);
    }

    public function ajax_list(){
        $total_items = $this->model->get_list(false);
        $result = $this->model->get_list(true);
        $actions = get_blocks("block_action_user", false);
        $data = [
            "result" => $result,
            "actions" => $actions
        ];
        ms( [
            "total_items" => $total_items,
            "data" => view('Core\Users\Views\ajax_list', $data)
        ] );
    }

    public function export(){
        export_csv(TB_USERS, "users");
    }

    public function bulk_upload(){
        // Check if this is a duplicate check request
        if (post('action') === 'check_duplicates') {
            $this->check_duplicates();
            return;
        }

        // Check if this is a process upload request (with CSV content)
        if (post('action') === 'process_upload') {
            $this->process_csv_upload();
            return;
        }

        // Legacy file upload handling (not used anymore but kept for compatibility)
        $file = $_FILES['csv_file'] ?? null;

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            ms([
                "status" => "error",
                "message" => __('Please select a valid CSV file to upload')
            ]);
        }

        // Validate file type
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_extension !== 'csv') {
            ms([
                "status" => "error",
                "message" => __('Only CSV files are allowed')
            ]);
        }

        // Read and parse CSV file
        $csv_data = [];
        $handle = fopen($file['tmp_name'], 'r');

        if ($handle === false) {
            ms([
                "status" => "error",
                "message" => __('Failed to read CSV file')
            ]);
        }

        // Read header row
        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            ms([
                "status" => "error",
                "message" => __('CSV file is empty or invalid')
            ]);
        }

        // Validate required columns
        $required_columns = ['ids', 'is_admin', 'fullname', 'username', 'email', 'password', 'plan', 'expiration_date', 'timezone', 'status', 'last_login', 'changed', 'created'];
        $missing_columns = array_diff($required_columns, $headers);

        if (!empty($missing_columns)) {
            fclose($handle);
            ms([
                "status" => "error",
                "message" => __('Missing required columns') . ': ' . implode(', ', $missing_columns)
            ]);
        }

        // Read data rows
        $row_number = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $row_number++;
            if (count($row) === count($headers)) {
                $csv_data[] = array_combine($headers, $row);
            }
        }
        fclose($handle);

        if (empty($csv_data)) {
            ms([
                "status" => "error",
                "message" => __('No valid data found in CSV file')
            ]);
        }

        // Get user action (update or skip duplicates)
        $duplicate_action = post('duplicate_action') ?? 'update'; // 'update' or 'skip'

        // Process bulk import
        $success_count = 0;
        $skip_count = 0;
        $update_count = 0;
        $error_count = 0;
        $errors = [];

        foreach ($csv_data as $index => $row) {
            $line_number = $index + 2; // +2 because index starts at 0 and we have header row

            try {
                // Skip admin account (id: 1) - never import/update admin
                $row_id = (int)($row['id'] ?? 0);
                if ($row_id === 1) {
                    $skip_count++;
                    continue;
                }

                // Extract required fields with default values
                $csv_ids = trim($row['ids'] ?? '');
                $email = trim($row['email'] ?? '');
                $username = trim($row['username'] ?? '');
                $fullname = trim($row['fullname'] ?? '');
                $whatsapp = trim($row['whatsapp'] ?? '');
                $password = trim($row['password'] ?? '');
                $plan_id = (int)($row['plan'] ?? 0);
                $expiration_date = (int)($row['expiration_date'] ?? 0);
                $timezone = trim($row['timezone'] ?? 'UTC');
                $is_admin = (int)($row['is_admin'] ?? 0);
                $role = (int)($row['role'] ?? 0);
                $status = (int)($row['status'] ?? 2);
                $login_type = trim($row['login_type'] ?? 'direct');
                $avatar = trim($row['avatar'] ?? '');
                $last_login = (int)($row['last_login'] ?? 0);

                // Sanitize username - remove email format if present
                if (!empty($username) && strpos($username, '@') !== false) {
                    // Extract part before @ symbol
                    $username = explode('@', $username)[0];
                    $username = trim($username);
                }

                // Apply default values for missing fields
                $pid = $row['pid'] ?? null;
                $language = $row['lang'] ?? null; // CSV column 'lang' maps to database column 'language'
                $data = $row['data'] ?? null;
                $recovery_key = $row['recovery_key'] ?? null;

                // Validate required fields
                if (empty($email) || empty($username) || empty($fullname)) {
                    $errors[] = "Line {$line_number}: Missing required fields (email, username, or fullname)";
                    $error_count++;
                    continue;
                }

                // Check if user already exists by ids, username, or email
                $existing_user = null;
                if (!empty($csv_ids)) {
                    $existing_user = db_get("*", TB_USERS, ['ids' => $csv_ids]);
                }
                if (!$existing_user) {
                    $existing_user = db_get("*", TB_USERS, ['email' => $email]);
                }
                if (!$existing_user) {
                    $existing_user = db_get("*", TB_USERS, ['username' => $username]);
                }

                if ($existing_user) {
                    // Handle duplicate based on user action
                    if ($duplicate_action === 'skip') {
                        $skip_count++;
                        continue;
                    }

                    // Update existing user
                    $update_data = [
                        "fullname" => $fullname,
                        "username" => $username,
                        "whatsapp" => $whatsapp,
                        "plan" => $plan_id,
                        "expiration_date" => $expiration_date,
                        "timezone" => $timezone,
                        "is_admin" => $is_admin,
                        "role" => $role,
                        "status" => $status,
                        "login_type" => $login_type,
                        "last_login" => $last_login,
                        "changed" => time()
                    ];

                    // Only update password if provided in CSV
                    if (!empty($password)) {
                        // Check if password is already MD5 hashed (32 characters)
                        if (strlen($password) === 32 && ctype_xdigit($password)) {
                            $update_data['password'] = $password;
                        } else {
                            $update_data['password'] = md5($password);
                        }
                    }

                    db_update(TB_USERS, $update_data, ["id" => $existing_user->id]);

                    // Update team permissions if plan changed
                    if ($plan_id > 0) {
                        $plan = db_get("*", TB_PLANS, ['id' => $plan_id]);
                        if ($plan) {
                            $team = db_get("*", TB_TEAM, ["owner" => $existing_user->id]);
                            if ($team) {
                                db_update(TB_TEAM, [
                                    "permissions" => $plan->permissions,
                                    "pid" => $plan->id
                                ], ["owner" => $existing_user->id]);
                            }
                        }
                    }

                    $update_count++;
                } else {
                    // Creating new user - check for duplicate username (excluding current user)
                    $username_check = db_get("*", TB_USERS, ['username' => $username]);
                    if ($username_check) {
                        $errors[] = "Line {$line_number}: Username '{$username}' already exists";
                        $error_count++;
                        continue;
                    }

                    // Check for duplicate email
                    $email_check = db_get("*", TB_USERS, ['email' => $email]);
                    if ($email_check) {
                        $errors[] = "Line {$line_number}: Email '{$email}' already exists";
                        $error_count++;
                        continue;
                    }

                    // Check for duplicate whatsapp
                    if (!empty($whatsapp)) {
                        $whatsapp_check = db_get("*", TB_USERS, ['whatsapp' => $whatsapp]);
                        if ($whatsapp_check) {
                            $errors[] = "Line {$line_number}: WhatsApp number '{$whatsapp}' already exists";
                            $error_count++;
                            continue;
                        }
                    }

                    // Validate plan exists
                    $plan = null;
                    if ($plan_id > 0) {
                        $plan = db_get("*", TB_PLANS, ['id' => $plan_id]);
                        if (!$plan) {
                            $errors[] = "Line {$line_number}: Plan ID {$plan_id} does not exist";
                            $error_count++;
                            continue;
                        }
                    }

                    // Generate avatar if not provided
                    if (empty($avatar)) {
                        $avatar = save_img(get_avatar($fullname), WRITEPATH.'avatar/');
                    }

                    // Handle password - check if MD5 hashed or plain text
                    if (empty($password)) {
                        $password = md5('123456'); // Default password
                    } else {
                        // Check if password is already MD5 hashed (32 characters)
                        if (strlen($password) === 32 && ctype_xdigit($password)) {
                            // Already hashed, use as is
                        } else {
                            $password = md5($password);
                        }
                    }

                    // Generate unique ids if not provided or if it already exists
                    $user_ids = $csv_ids;
                    if (empty($user_ids)) {
                        $user_ids = ids();
                    } else {
                        // Check if ids already exists
                        $ids_check = db_get("*", TB_USERS, ['ids' => $user_ids]);
                        if ($ids_check) {
                            // Generate new unique ids
                            $user_ids = ids();
                        }
                    }

                    // Use created timestamp from CSV or current time
                    $created_time = !empty($row['created']) ? (int)$row['created'] : time();
                    $changed_time = !empty($row['changed']) ? (int)$row['changed'] : time();

                    // Prepare insert data
                    $insert_data = [
                        "ids" => $user_ids,
                        "pid" => $pid,
                        "is_admin" => $is_admin,
                        "role" => $role,
                        "fullname" => $fullname,
                        "username" => $username,
                        "email" => $email,
                        "whatsapp" => $whatsapp,
                        "password" => $password,
                        "plan" => $plan_id,
                        "expiration_date" => $expiration_date,
                        "timezone" => $timezone,
                        "language" => $language,
                        "login_type" => $login_type,
                        "avatar" => $avatar,
                        "data" => $data,
                        "status" => $status,
                        "last_login" => $last_login,
                        "recovery_key" => $recovery_key,
                        "changed" => $changed_time,
                        "created" => $created_time
                    ];

                    // Insert new user using direct database connection
                    try {
                        $db = \Config\Database::connect();
                        $builder = $db->table(TB_USERS);
                        $result = $builder->insert($insert_data);

                        if (!$result) {
                            $db_error = $db->error();
                            $errors[] = "Line {$line_number}: Database error - " . ($db_error['message'] ?? 'Unknown error');
                            $error_count++;
                            continue;
                        }

                        $user_id = $db->insertID();

                        if (!$user_id || $user_id === 0) {
                            $errors[] = "Line {$line_number}: Failed to get insert ID after database insert";
                            $error_count++;
                            continue;
                        }
                    } catch (\Exception $e) {
                        $errors[] = "Line {$line_number}: Exception during insert - " . $e->getMessage();
                        $error_count++;
                        continue;
                    }

                    // Create team for new user
                    if ($plan) {
                        try {
                            $team_data = [
                                "ids" => ids(),
                                "owner" => $user_id,
                                "pid" => $plan_id,
                                "permissions" => $plan->permissions
                            ];

                            $db = \Config\Database::connect();
                            $builder = $db->table(TB_TEAM);
                            $team_result = $builder->insert($team_data);

                            if (!$team_result) {
                                $db_error = $db->error();
                                $errors[] = "Line {$line_number}: User created but failed to create team - " . ($db_error['message'] ?? 'Unknown error');
                                // Don't increment error_count here as user was created
                            }
                        } catch (\Exception $e) {
                            $errors[] = "Line {$line_number}: User created but team creation exception - " . $e->getMessage();
                            // Don't increment error_count here as user was created
                        }
                    }

                    $success_count++;
                }

            } catch (\Exception $e) {
                $errors[] = "Line {$line_number}: " . $e->getMessage();
                $error_count++;
            }
        }

        // Prepare result message
        $message = __('Bulk upload completed') . ': ';
        $message .= $success_count . ' ' . __('created') . ', ';
        $message .= $update_count . ' ' . __('updated') . ', ';
        $message .= $skip_count . ' ' . __('skipped') . ', ';
        $message .= $error_count . ' ' . __('errors');

        $result = [
            "status" => ($error_count > 0 && $success_count == 0 && $update_count == 0) ? "error" : "success",
            "message" => $message,
            "details" => [
                "created" => $success_count,
                "updated" => $update_count,
                "skipped" => $skip_count,
                "errors" => $error_count,
                "total_processed" => count($csv_data)
            ]
        ];

        if (!empty($errors)) {
            $result['errors'] = array_slice($errors, 0, 10); // Show first 10 errors
            if (count($errors) > 10) {
                $result['errors'][] = '... and ' . (count($errors) - 10) . ' more errors';
            }
        }

        ms($result);
    }

    // Check for duplicates before processing
    public function check_duplicates(){
        // Get CSV data from session or temp storage
        $csv_content = post('csv_content');

        if (empty($csv_content)) {
            ms([
                "status" => "error",
                "message" => __('No CSV data provided')
            ]);
        }

        // Parse CSV content
        $lines = explode("\n", $csv_content);
        $headers = str_getcsv(array_shift($lines));

        $duplicates = [];
        $row_number = 1;

        foreach ($lines as $line) {
            $row_number++;
            if (empty(trim($line))) continue;

            $row = str_getcsv($line);
            if (count($row) !== count($headers)) continue;

            $data = array_combine($headers, $row);

            $csv_ids = trim($data['ids'] ?? '');
            $username = trim($data['username'] ?? '');
            $email = trim($data['email'] ?? '');

            // Check for existing users
            $existing = null;

            if (!empty($csv_ids)) {
                $existing = db_get("ids, username, email, fullname", TB_USERS, ['ids' => $csv_ids]);
            }
            if (!$existing && !empty($username)) {
                $existing = db_get("ids, username, email, fullname", TB_USERS, ['username' => $username]);
            }
            if (!$existing && !empty($email)) {
                $existing = db_get("ids, username, email, fullname", TB_USERS, ['email' => $email]);
            }

            if ($existing) {
                $duplicates[] = [
                    'line' => $row_number,
                    'ids' => $existing->ids,
                    'username' => $existing->username,
                    'email' => $existing->email,
                    'fullname' => $existing->fullname
                ];
            }
        }

        ms([
            "status" => "success",
            "duplicates" => $duplicates,
            "total_duplicates" => count($duplicates)
        ]);
    }

    // Process CSV upload with duplicate action
    public function process_csv_upload(){
        $csv_content = post('csv_content');
        $duplicate_action = post('duplicate_action') ?? 'update';

        // Debug logging to file
        $debug_log = WRITEPATH . 'logs/bulk_upload_debug.log';
        file_put_contents($debug_log, "\n=== PROCESS CSV UPLOAD CALLED ===\n", FILE_APPEND);
        file_put_contents($debug_log, "Duplicate Action: " . $duplicate_action . "\n", FILE_APPEND);
        file_put_contents($debug_log, "CSV Content Length: " . strlen($csv_content) . "\n", FILE_APPEND);
        file_put_contents($debug_log, "Timestamp: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

        if (empty($csv_content)) {
            file_put_contents($debug_log, "ERROR: No CSV content provided\n", FILE_APPEND);
            ms([
                "status" => "error",
                "message" => __('No CSV data provided')
            ]);
        }

        // Parse CSV content
        $lines = explode("\n", $csv_content);
        $headers = str_getcsv(array_shift($lines));

        // Validate required columns
        $required_columns = ['ids', 'is_admin', 'fullname', 'username', 'email', 'password', 'plan', 'expiration_date', 'timezone', 'status', 'last_login', 'changed', 'created'];
        $missing_columns = array_diff($required_columns, $headers);

        if (!empty($missing_columns)) {
            ms([
                "status" => "error",
                "message" => __('Missing required columns') . ': ' . implode(', ', $missing_columns)
            ]);
        }

        // Parse CSV data
        $csv_data = [];
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;

            $row = str_getcsv($line);
            if (count($row) === count($headers)) {
                $csv_data[] = array_combine($headers, $row);
            }
        }

        if (empty($csv_data)) {
            ms([
                "status" => "error",
                "message" => __('No valid data found in CSV')
            ]);
        }

        // Process bulk import (reuse existing logic)
        $success_count = 0;
        $skip_count = 0;
        $update_count = 0;
        $error_count = 0;
        $errors = [];

        foreach ($csv_data as $index => $row) {
            $line_number = $index + 2; // +2 because index starts at 0 and we have header row

            try {
                // Skip admin account (id: 1) - never import/update admin
                $row_id = (int)($row['id'] ?? 0);
                if ($row_id === 1) {
                    $debug_log = WRITEPATH . 'logs/bulk_upload_debug.log';
                    file_put_contents($debug_log, "Line {$line_number}: Skipping admin account (id: 1)\n", FILE_APPEND);
                    $skip_count++;
                    continue;
                }

                // Extract required fields with default values
                $csv_ids = trim($row['ids'] ?? '');
                $email = trim($row['email'] ?? '');
                $username = trim($row['username'] ?? '');
                $fullname = trim($row['fullname'] ?? '');
                $whatsapp = trim($row['whatsapp'] ?? '');
                $password = trim($row['password'] ?? '');
                $plan_id = (int)($row['plan'] ?? 0);
                $expiration_date = (int)($row['expiration_date'] ?? 0);
                $timezone = trim($row['timezone'] ?? 'UTC');
                $is_admin = (int)($row['is_admin'] ?? 0);
                $role = (int)($row['role'] ?? 0);
                $status = (int)($row['status'] ?? 2);
                $login_type = trim($row['login_type'] ?? 'direct');
                $avatar = trim($row['avatar'] ?? '');
                $last_login = (int)($row['last_login'] ?? 0);

                // Sanitize username - remove email format if present
                if (!empty($username) && strpos($username, '@') !== false) {
                    // Extract part before @ symbol
                    $username = explode('@', $username)[0];
                    $username = trim($username);
                }

                // Apply default values for missing fields
                $pid = $row['pid'] ?? null;
                $language = $row['lang'] ?? null; // CSV column 'lang' maps to database column 'language'
                $data = $row['data'] ?? null;
                $recovery_key = $row['recovery_key'] ?? null;

                // Validate required fields
                if (empty($email) || empty($username) || empty($fullname)) {
                    $errors[] = "Line {$line_number}: Missing required fields (email, username, or fullname)";
                    $error_count++;
                    continue;
                }

                // Check if user already exists by ids, username, or email
                $existing_user = null;
                if (!empty($csv_ids)) {
                    $existing_user = db_get("*", TB_USERS, ['ids' => $csv_ids]);
                }
                if (!$existing_user) {
                    $existing_user = db_get("*", TB_USERS, ['email' => $email]);
                }
                if (!$existing_user) {
                    $existing_user = db_get("*", TB_USERS, ['username' => $username]);
                }

                if ($existing_user) {
                    $debug_log = WRITEPATH . 'logs/bulk_upload_debug.log';
                    file_put_contents($debug_log, "Line {$line_number}: Found existing user - ID: {$existing_user->id}, Email: {$email}, Action: {$duplicate_action}\n", FILE_APPEND);

                    // Handle duplicate based on user action
                    if ($duplicate_action === 'skip') {
                        file_put_contents($debug_log, "Line {$line_number}: Skipping existing user\n", FILE_APPEND);
                        $skip_count++;
                        continue;
                    }

                    file_put_contents($debug_log, "Line {$line_number}: Updating existing user\n", FILE_APPEND);

                    // Update existing user
                    $update_data = [
                        "fullname" => $fullname,
                        "username" => $username,
                        "whatsapp" => $whatsapp,
                        "plan" => $plan_id,
                        "expiration_date" => $expiration_date,
                        "timezone" => $timezone,
                        "is_admin" => $is_admin,
                        "role" => $role,
                        "status" => $status,
                        "login_type" => $login_type,
                        "last_login" => $last_login,
                        "changed" => time()
                    ];

                    // Only update password if provided in CSV
                    if (!empty($password)) {
                        // Check if password is already MD5 hashed (32 characters)
                        if (strlen($password) === 32 && ctype_xdigit($password)) {
                            $update_data['password'] = $password;
                        } else {
                            $update_data['password'] = md5($password);
                        }
                    }

                    db_update(TB_USERS, $update_data, ["id" => $existing_user->id]);
                    file_put_contents($debug_log, "Line {$line_number}: User updated successfully\n", FILE_APPEND);

                    // Update team permissions if plan changed
                    if ($plan_id > 0) {
                        $plan = db_get("*", TB_PLANS, ['id' => $plan_id]);
                        if ($plan) {
                            $team = db_get("*", TB_TEAM, ["owner" => $existing_user->id]);
                            if ($team) {
                                db_update(TB_TEAM, [
                                    "permissions" => $plan->permissions,
                                    "pid" => $plan->id
                                ], ["owner" => $existing_user->id]);
                            }
                        }
                    }

                    $update_count++;
                } else {
                    $debug_log = WRITEPATH . 'logs/bulk_upload_debug.log';
                    file_put_contents($debug_log, "Line {$line_number}: No existing user found, creating new user\n", FILE_APPEND);

                    // Creating new user - NO duplicate checks needed here
                    // We already checked for duplicates above (lines 672-680)
                    // If we're here, it means the user doesn't exist

                    // Validate plan exists
                    $plan = null;
                    if ($plan_id > 0) {
                        $plan = db_get("*", TB_PLANS, ['id' => $plan_id]);
                        if (!$plan) {
                            $errors[] = "Line {$line_number}: Plan ID {$plan_id} does not exist";
                            $error_count++;
                            continue;
                        }
                    }

                    // Generate avatar if not provided
                    if (empty($avatar)) {
                        $avatar = save_img(get_avatar($fullname), WRITEPATH.'avatar/');
                    }

                    // Handle password - check if MD5 hashed or plain text
                    if (empty($password)) {
                        $password = md5('123456'); // Default password
                    } else {
                        // Check if password is already MD5 hashed (32 characters)
                        if (strlen($password) === 32 && ctype_xdigit($password)) {
                            // Already hashed, use as is
                        } else {
                            $password = md5($password);
                        }
                    }

                    // Generate unique ids if not provided or if it already exists
                    $user_ids = $csv_ids;
                    if (empty($user_ids)) {
                        $user_ids = ids();
                    } else {
                        // Check if ids already exists
                        $ids_check = db_get("*", TB_USERS, ['ids' => $user_ids]);
                        if ($ids_check) {
                            // Generate new unique ids
                            $user_ids = ids();
                        }
                    }

                    // Use created timestamp from CSV or current time
                    $created_time = !empty($row['created']) ? (int)$row['created'] : time();
                    $changed_time = !empty($row['changed']) ? (int)$row['changed'] : time();

                    // Prepare insert data
                    $insert_data = [
                        "ids" => $user_ids,
                        "pid" => $pid,
                        "is_admin" => $is_admin,
                        "role" => $role,
                        "fullname" => $fullname,
                        "username" => $username,
                        "email" => $email,
                        "whatsapp" => $whatsapp,
                        "password" => $password,
                        "plan" => $plan_id,
                        "expiration_date" => $expiration_date,
                        "timezone" => $timezone,
                        "language" => $language,
                        "login_type" => $login_type,
                        "avatar" => $avatar,
                        "data" => $data,
                        "status" => $status,
                        "last_login" => $last_login,
                        "recovery_key" => $recovery_key,
                        "changed" => $changed_time,
                        "created" => $created_time
                    ];

                    // Insert new user using direct database connection
                    try {
                        $db = \Config\Database::connect();
                        $builder = $db->table(TB_USERS);
                        $result = $builder->insert($insert_data);

                        if (!$result) {
                            $db_error = $db->error();
                            $errors[] = "Line {$line_number}: Database error - " . ($db_error['message'] ?? 'Unknown error');
                            $error_count++;
                            continue;
                        }

                        $user_id = $db->insertID();

                        if (!$user_id || $user_id === 0) {
                            $errors[] = "Line {$line_number}: Failed to get insert ID after database insert";
                            $error_count++;
                            continue;
                        }

                        // Create team for new user
                        if ($plan) {
                            try {
                                $team_data = [
                                    "ids" => ids(),
                                    "owner" => $user_id,
                                    "pid" => $plan_id,
                                    "permissions" => $plan->permissions
                                ];

                                $db = \Config\Database::connect();
                                $builder = $db->table(TB_TEAM);
                                $team_result = $builder->insert($team_data);

                                if (!$team_result) {
                                    $db_error = $db->error();
                                    $errors[] = "Line {$line_number}: User created but failed to create team - " . ($db_error['message'] ?? 'Unknown error');
                                }
                            } catch (\Exception $e) {
                                $errors[] = "Line {$line_number}: User created but team creation exception - " . $e->getMessage();
                            }
                        }

                        $success_count++;

                    } catch (\Exception $e) {
                        $errors[] = "Line {$line_number}: Exception during insert - " . $e->getMessage();
                        $error_count++;
                        continue;
                    }
                }

            } catch (\Exception $e) {
                $errors[] = "Line {$line_number}: Exception - " . $e->getMessage();
                $error_count++;
            }
        }

        // Prepare result
        $debug_log = WRITEPATH . 'logs/bulk_upload_debug.log';
        file_put_contents($debug_log, "\n=== FINAL COUNTS ===\n", FILE_APPEND);
        file_put_contents($debug_log, "Created: {$success_count}\n", FILE_APPEND);
        file_put_contents($debug_log, "Updated: {$update_count}\n", FILE_APPEND);
        file_put_contents($debug_log, "Skipped: {$skip_count}\n", FILE_APPEND);
        file_put_contents($debug_log, "Errors: {$error_count}\n", FILE_APPEND);

        $result = [
            "status" => "success",
            "message" => __('Bulk upload completed'),
            "details" => [
                "created" => $success_count,
                "updated" => $update_count,
                "skipped" => $skip_count,
                "errors" => $error_count,
                "total_processed" => $success_count + $update_count + $skip_count + $error_count
            ]
        ];

        if (!empty($errors)) {
            $result['errors'] = array_slice($errors, 0, 10);
            if (count($errors) > 10) {
                $result['errors'][] = '... and ' . (count($errors) - 10) . ' more errors';
            }
        }

        file_put_contents($debug_log, "Result: " . json_encode($result) . "\n", FILE_APPEND);
        ms($result);
    }

    public function view($ids = ""){

        $user = db_get("*", TB_USERS, ["ids" => $ids]);
        if(empty($user)){
            ms([
                "status" => "error",
                "message" => __("This account does not exist")
            ]);
        }

        $team = db_get("*", TB_TEAM, ["owner" => $user->id]);
        if(empty($user)){
            ms([
                "status" => "error",
                "message" => __("This account does not belong to any team")
            ]);
        }

        set_session([
            "tmp_uid" => get_session("uid"),
            "tmp_team_id" => get_session("team_id"),
            "uid" => $user->ids,
            "team_id" => $team->ids,
        ]);

        ms([
            "status" => "success",
            "message" => __("Success")
        ]);
    }
    
    public function save( $ids = '' ){

        $fullname = post('fullname');
        $username = post('username');
        $email = post('email');
        $whatsapp = post('whatsapp');
        $password = post('password');
        $confirm_password = post('confirm_password');
        $plan_id = (int)post('plan');
        $expiration_date = post('expiration_date');
        $timezone = post('timezone');
        $is_admin = (int)post('is_admin');
        $role = (int)post('role');
        $status = (int)post('status');
        $item = db_get( "*", TB_USERS, ['ids' => $ids] );
        $plan = db_get("*", TB_PLANS, ['id' => $plan_id]);

        if(!$item)
        {
            $email_check = db_get( "*", TB_USERS, ['email' => $email] );
            $whatsapp_check = db_get( "*", TB_USERS, ['whatsapp' => $whatsapp] );
            $username_check = db_get( "*", TB_USERS, ['username' => $username] );
            validate('null', __('Fullname'), $fullname);
            validate('null', __('Email'), $email);
            validate('null', __('WhatsAPp'), $whatsapp);
            validate('username', __('Username'), $username);
            validate('min_length', __('Username'), $username, 6);
            validate('not_empty', __('This email already exists'), $email_check);
            validate('not_empty', __('This whatsapp already exists'), $whatsapp_check);
            validate('not_empty', __('This username already exists'), $username_check);
            validate('null', __('Password'), $password);
            validate('min_length', __('Password'), $password, 6);
            validate('null', __('Confirm password'), $confirm_password);
            validate('other', __('Your password and confirmation password do not match'), $password, $confirm_password);
            validate('empty', __('Please select a plan'), $plan);
            validate('null', __('Expiration date'), $expiration_date);
            validate('null', __('Timezone'), $timezone);

            $avatar = save_img( get_avatar($fullname), WRITEPATH.'avatar/' );

            $id = db_insert(TB_USERS , [
                "ids" => ids(),
                "is_admin" => $is_admin,
                "role" => $role,
                "fullname" => $fullname,
                "username" => $username,
                "email" => $email,
                "whatsapp" => $whatsapp,
                "password" => md5($password),
                "plan" => $plan_id,
                "expiration_date" => $expiration_date?strtotime(date_sql($expiration_date)):0,
                "timezone" => $timezone,
                "login_type" => 'direct',
                "avatar" => $avatar,
                "status" => $status,
                "changed" => time(),
                "created" => time()
            ]);

            db_insert( TB_TEAM, [
                "ids" => ids(),
                "owner" => $id,
                "pid" => $plan_id,
                "permissions" => $plan->permissions
            ]);
        }
        else
        {
            $email_check = db_get( "*", TB_USERS, ['email' => $email, 'id != ' => $item->id] );
            $whatsapp_check = db_get( "*", TB_USERS, ['whatsapp' => $whatsapp, 'id != ' => $item->id] );
            $username_check = db_get( "*", TB_USERS, ['username' => $username, 'id != ' => $item->id] );
            validate('null', __('Fullname'), $fullname);
            validate('username', __('Username'), $username);
            validate('min_length', __('Username'), $username, 6);
            validate('null', __('Email'), $email);
            validate('email', __('Email'), $email);
            validate('null', __('Whatsapp'), $whatsapp);
            validate('whatsapp', __('Whatsapp'), $whatsapp);
            validate('not_empty', __('This email already exists'), $email_check);
             validate('not_empty', __('This email already exists'), $whatsapp_check);
            validate('not_empty', __('This username already exists'), $username_check);
            
            if($password != "")
            {
                validate('min_length', __('Password'), $password, 6);
                validate('null', __('Confirm password'), $confirm_password);
                validate('other', __('Your password and confirmation password do not match'), $password, $confirm_password);
            }

            validate('empty', __('Please select a plan'), $plan);
            validate('null', __('Expiration date'), $expiration_date);
            validate('null', __('Timezone'), $timezone);

            $data = [
                "is_admin" => $is_admin,
                "role" => $role,
                "fullname" => $fullname,
                "username" => $username,
                "email" => $email,
                "Whatsapp" => $whatsapp,
                "plan" => $plan_id,
                "expiration_date" => $expiration_date?strtotime(date_sql($expiration_date)):0,
                "timezone" => $timezone,
                "status" => $status,
                "changed" => time()
            ];

            if($password != "")
            {
                $data['password'] = md5($password);
            }

            db_update(TB_USERS , $data, ["ids" => $ids]);

            if( $plan )
            {
                $team = db_get("*", TB_TEAM, ["owner" => $item->id]);
                update_team_data("number_accounts", $plan->number_accounts, $team->id);

                db_update( TB_TEAM, [
                    "permissions" => $plan->permissions,
                    "pid" => $plan->id
                ],
                [
                    "owner" => $item->id
                ]);
            }
        }

        ms([
            "status" => "success",
            "message" => __('Success')
        ]);
    }

    public function delete( $ids = '' ){

        if($ids == ''){
            $ids = post('ids');
        }

        if( empty($ids) ){
            ms([
                "status" => "error",
                "message" => __('Please select an item to delete')
            ]);
        }

        if( is_array($ids) )
        {
            foreach ($ids as $id) 
            {
                db_delete(TB_USERS, ['ids' => $id]);
            }
        }
        elseif( is_string($ids) )
        {
            db_delete(TB_USERS, ['ids' => $ids]);
        }

        ms([
            "status" => "success",
            "message" => __('Success')
        ]);

    }

    /*
    * ROLES
    */

    public function role_save($ids = "")
    {
        if (!find_modules("payment")) {
            redirect_to( get_module_url() );
        }

        $name = post('name');
        $permissions = post('permissions');
        $permissions['profile_status'] = 1;

        validate('null', __('Name'), $name);

        $item = db_get("*", TB_ROLES, "ids = '{$ids}'");
        if(!$item){

            db_insert(TB_ROLES, [
                "ids" => ids(),
                "name" => $name,
                "permissions" => json_encode( $permissions )
            ]);

        }else{

            db_update(
                TB_ROLES, 
                [
                    "name" => $name,
                    "permissions" => json_encode( $permissions ),
                ], 
                [ "ids" => $ids ]
            );
            
        }

        ms([
            "status" => "success",
            "message" => __('Success')
        ]);

    }

}