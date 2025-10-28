<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Installation Wizard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container { 
            background: white;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        h1 { 
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .form-group { 
            margin-bottom: 20px;
        }
        label { 
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        input[type="text"],
        input[type="password"],
        select {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e1e8ed;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }
        .small-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        button { 
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
        }
        .error-box, .success-box {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error-box {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
        }
        .success-box {
            background: #efe;
            border: 1px solid #cfc;
            color: #3c3;
        }
        ul { 
            margin-left: 20px;
        }
        .section-title {
            font-size: 18px;
            color: #667eea;
            margin: 25px 0 15px 0;
            font-weight: 600;
        }
        .requirements {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .req-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }
        .req-status {
            font-weight: 600;
        }
        .req-pass { color: #28a745; }
        .req-fail { color: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Installation Wizard</h1>
        <p class="subtitle">Let's set up your application</p>

        <?php
        require_once __DIR__ . '/../App/Installer.php';
        use App\Installer;

        $installer = new Installer();
        $errors = [];
        $success = false;

        // Check system requirements
        $requirements = [
            'PHP 8.0+' => version_compare(PHP_VERSION, '8.0', '>='),
            'MySQLi Extension' => extension_loaded('mysqli'),
            'PDO Extension' => extension_loaded('pdo'),
            'JSON Extension' => extension_loaded('json'),
            'MBString Extension' => extension_loaded('mbstring'),
            'OpenSSL Extension' => extension_loaded('openssl'),
            'cURL Extension' => extension_loaded('curl'),
            'FileInfo Extension' => extension_loaded('fileinfo'),
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $config = [
                'db_driver' => $_POST['db_driver'] ?? 'mysql',
                'db_host' => $_POST['db_host'] ?? 'localhost',
                'db_port' => $_POST['db_port'] ?? '3306',
                'db_name' => trim($_POST['db_name'] ?? ''),
                'db_user' => trim($_POST['db_user'] ?? ''),
                'db_pass' => $_POST['db_pass'] ?? '',
                'app_env' => $_POST['app_env'] ?? 'production',
                'app_debug' => isset($_POST['app_debug']) ? 'true' : 'false',
            ];

            // Validate
            if (empty($config['db_name'])) {
                $errors[] = 'Database name is required';
            }
            if (empty($config['db_user']) && $config['db_driver'] !== 'sqlite') {
                $errors[] = 'Database username is required';
            }

            if (empty($errors)) {
                // Run installation
                ob_start();
                $result = $installer->runInstallation($config);
                ob_end_clean();

                if ($result) {
                    $success = true;
                } else {
                    $errors = array_merge($errors, $installer->getErrors());
                }
            }
        }
        ?>

        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <strong>❌ Installation Failed</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-box">
                <strong>✅ Installation Successful!</strong>
                <p style="margin-top: 10px;">Your application has been installed successfully.</p>
                <p style="margin-top: 10px;"><strong>Next Steps:</strong></p>
                <ul style="margin-top: 5px;">
                    <li>Delete the installation files for security</li>
                    <li>Configure your web server to point to src/public/</li>
                    <li>Review the .env file and adjust settings</li>
                    <li>Access your application</li>
                </ul>
            </div>
        <?php else: ?>
            <div class="requirements">
                <strong>System Requirements</strong>
                <?php foreach ($requirements as $req => $status): ?>
                    <div class="req-item">
                        <span><?php echo $req; ?></span>
                        <span class="req-status <?php echo $status ? 'req-pass' : 'req-fail'; ?>">
                            <?php echo $status ? '✓ Pass' : '✗ Fail'; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <form method="post">
                <div class="section-title">📊 Database Configuration</div>
                
                <div class="form-group">
                    <label for="db_driver">Database Driver</label>
                    <select id="db_driver" name="db_driver" required>
                        <option value="mysql">MySQL / MariaDB</option>
                        <option value="pgsql">PostgreSQL</option>
                        <option value="sqlite">SQLite</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="db_host">Database Host</label>
                    <input type="text" id="db_host" name="db_host" value="localhost" required>
                    <div class="small-text">Usually 'localhost' or '127.0.0.1'</div>
                </div>

                <div class="form-group">
                    <label for="db_port">Database Port</label>
                    <input type="text" id="db_port" name="db_port" value="3306" required>
                    <div class="small-text">MySQL: 3306, PostgreSQL: 5432</div>
                </div>

                <div class="form-group">
                    <label for="db_name">Database Name</label>
                    <input type="text" id="db_name" name="db_name" required>
                    <div class="small-text">Will be created if it doesn't exist</div>
                </div>

                <div class="form-group">
                    <label for="db_user">Database Username</label>
                    <input type="text" id="db_user" name="db_user" required>
                </div>

                <div class="form-group">
                    <label for="db_pass">Database Password</label>
                    <input type="password" id="db_pass" name="db_pass">
                </div>

                <div class="section-title">⚙️ Application Settings</div>

                <div class="form-group">
                    <label for="app_env">Environment</label>
                    <select id="app_env" name="app_env">
                        <option value="production">Production</option>
                        <option value="local">Local/Development</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="app_debug" value="1">
                        Enable Debug Mode
                    </label>
                    <div class="small-text">⚠️ Only enable for development environments</div>
                </div>

                <button type="submit">🎉 Install Application</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>