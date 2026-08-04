<?php
/**
 * MLBB Drone View Generator
 * Generate custom drone view XML for Mobile Legends
 * Supports 1x to 2000x (or any custom level)
 * 
 * Upload to: InfinityFree / any PHP hosting
 * 
 * API MODE:
 *   drone_generator.php?zoom=20          -> JSON response
 *   drone_generator.php?zoom=100&raw=1   -> Raw XML only
 *   drone_generator.php?zoom=50&batch=20 -> Batch 1x to 50x
 */

// ============================================================
// BASE CONFIGURATION
// ============================================================

// TRUE 1x - Original Game Camera (close up view)
$BASE_1X = [
    1  => ['x' => 11.12,  'y' => -13.29, 'z' => 11.06,  'rx' => 38.57, 'ry' => 44.9,   'rz' => -0.07, 'fov' => 0,  'dis' => 18],
    2  => ['x' => -11.12, 'y' => -13.29, 'z' => -11.06, 'rx' => 38.57, 'ry' => -134.1, 'rz' => -0.07, 'fov' => 0,  'dis' => 18],
    3  => ['x' => -11.12, 'y' => -13.29, 'z' => -11.06, 'rx' => 38.57, 'ry' => -134.1, 'rz' => -0.07, 'fov' => 0,  'dis' => 18],
    4  => ['x' => 0.4,    'y' => -10.98, 'z' => 9.98,   'rx' => 45,    'ry' => 1.9,    'rz' => 1.1,   'fov' => 0,  'dis' => 15],
    5  => ['x' => -7,     'y' => -14,    'z' => -10.75, 'rx' => 57,    'ry' => -180,   'rz' => 0,     'fov' => 0,  'dis' => 15],
    6  => ['x' => 9.47,   'y' => -36,    'z' => -21.2,  'rx' => 55,    'ry' => -180,   'rz' => 0,     'fov' => 0,  'dis' => 40],
    7  => ['x' => -9.47,  'y' => -36,    'z' => 21.2,   'rx' => 55,    'ry' => 0,      'rz' => 0,     'fov' => 0,  'dis' => 40],
    8  => ['x' => -6.54,  'y' => -17,    'z' => -6.27,  'rx' => 60,    'ry' => -134.1, 'rz' => -0.07, 'fov' => 0,  'dis' => 15],
    9  => ['x' => 11.12,  'y' => -13.29, 'z' => 11.06,  'rx' => 38.57, 'ry' => 44.9,   'rz' => -0.07, 'fov' => 0,  'dis' => 40],
    10 => ['x' => -11.12, 'y' => -13.29, 'z' => -11.06, 'rx' => 38.57, 'ry' => -134.1, 'rz' => -0.07, 'fov' => 0,  'dis' => 40],
];

// 20x Target - Drone View (far view)
$BASE_20X = [
    1  => ['x' => 21.65,  'y' => -30.60, 'z' => 21.70,  'rx' => 42.86, 'ry' => 44.9,   'rz' => -0.07, 'fov' => 0,  'dis' => 36],
    2  => ['x' => -21.65, 'y' => -30.60, 'z' => -21.70, 'rx' => 42.86, 'ry' => -134.1, 'rz' => -0.07, 'fov' => 0,  'dis' => 36],
    3  => ['x' => -21.65, 'y' => -30.60, 'z' => -21.70, 'rx' => 42.86, 'ry' => -134.1, 'rz' => -0.07, 'fov' => 0,  'dis' => 36],
    4  => ['x' => 0.4,    'y' => -10.98, 'z' => 9.98,   'rx' => 45,    'ry' => 1.9,    'rz' => 1.1,   'fov' => 0,  'dis' => 15],
    5  => ['x' => -7,     'y' => -14,    'z' => -10.75, 'rx' => 57,    'ry' => -180,   'rz' => 0,     'fov' => 0,  'dis' => 15],
    6  => ['x' => 9.47,   'y' => -36,    'z' => -21.2,  'rx' => 55,    'ry' => -180,   'rz' => 0,     'fov' => 0,  'dis' => 40],
    7  => ['x' => -9.47,  'y' => -36,    'z' => 21.2,   'rx' => 55,    'ry' => 0,      'rz' => 0,     'fov' => 0,  'dis' => 40],
    8  => ['x' => -6.54,  'y' => -17,    'z' => -6.27,  'rx' => 60,    'ry' => -134.1, 'rz' => -0.07, 'fov' => 0,  'dis' => 15],
    9  => ['x' => 21.65,  'y' => -30.60, 'z' => 21.70,  'rx' => 42.86, 'ry' => 44.9,   'rz' => -0.07, 'fov' => 0,  'dis' => 40],
    10 => ['x' => -21.65, 'y' => -30.60, 'z' => -21.70, 'rx' => 42.86, 'ry' => -134.1, 'rz' => -0.07, 'fov' => 0,  'dis' => 40],
];

// Chinese comments mapping
$COMMENTS = [
    8  => '<!--破晓行动-->',
    10 => '<!--新版乱斗相机-->',
];

// ============================================================
// GENERATE XML FUNCTION
// ============================================================
function generateDroneXML($zoomLevel, $base1x, $base20x, $comments) {
    $lines = [];

    // Interpolation factor: 0 at 1x, 1 at 20x
    $t = ($zoomLevel - 1) / 19.0;

    // For zoom > 20, extend the curve
    if ($zoomLevel > 20) {
        $t = 1.0 + ($zoomLevel - 20) * 0.05;
    }

    for ($cid = 1; $cid <= 10; $cid++) {
        $v1 = $base1x[$cid];
        $v20 = $base20x[$cid];

        if ($zoomLevel <= 20) {
            $x  = $v1['x']  + $t * ($v20['x'] - $v1['x']);
            $y  = $v1['y']  + $t * ($v20['y'] - $v1['y']);
            $z  = $v1['z']  + $t * ($v20['z'] - $v1['z']);
            $rx = $v1['rx'] + $t * ($v20['rx'] - $v1['rx']);
            $fov = $v1['fov'] + $t * ($v20['fov'] - $v1['fov']);
            $dis = $v1['dis'] + $t * ($v20['dis'] - $v1['dis']);
        } else {
            $extra = ($zoomLevel - 20) / 100.0;
            $x  = $v20['x']  * (1 + $extra * 0.5);
            $y  = $v20['y']  * (1 + $extra * 0.3);
            $z  = $v20['z']  * (1 + $extra * 0.5);
            $rx = min(75, $v20['rx'] + $extra * 10);
            $fov = min(60, $v20['fov'] + $extra * 20);
            $dis = min(200, $v20['dis'] * (1 + $extra));
        }

        $comment = isset($comments[$cid]) ? $comments[$cid] : '';

        // First line (iId=1) has NO leading spaces, rest have 6 spaces
        $indent = ($cid == 1) ? '' : '      ';

        $line = sprintf(
            '%s<SCameraCamp iId="%d" fPosX="%.2f" fPosY="%.2f" fPosZ="%.2f" fRotX="%.2f" fRotY="%.1f" fRotZ="%.2f" fFov="%.1f" fScreenPtCastDis="%.1f"/>%s',
            $indent, $cid, $x, $y, $z, $rx, $v1['ry'], $v1['rz'], $fov, $dis, $comment
        );
        $lines[] = $line;
    }

    return implode("\n", $lines);
}

// ============================================================
// API MODE
// URL Examples:
//   drone_generator.php?zoom=20              -> JSON response
//   drone_generator.php?zoom=100&raw=1       -> Raw XML only
//   drone_generator.php?zoom=50&batch=1      -> Batch 1x to 50x (JSON)
// ============================================================

if (isset($_GET['zoom'])) {

    $zoomLevel = (int)$_GET['zoom'];

    if ($zoomLevel < 1 || $zoomLevel > 2000) {
        http_response_code(400);
        header("Content-Type: application/json; charset=UTF-8");

        echo json_encode([
            "success" => false,
            "message" => "Zoom level must be between 1 and 2000"
        ]);
        exit;
    }

    // Batch mode: generate 1x to zoomLevel
    if (isset($_GET['batch'])) {
        $batchLines = [];
        for ($z = 1; $z <= $zoomLevel; $z++) {
            $batchLines[] = generateDroneXML($z, $BASE_1X, $BASE_20X, $COMMENTS);
        }
        $xml = implode("\n\n", $batchLines);

        if (isset($_GET['raw'])) {
            header("Content-Type: text/plain; charset=UTF-8");
            echo $xml;
            exit;
        }

        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode([
            "success" => true,
            "zoom_from" => 1,
            "zoom_to" => $zoomLevel,
            "xml" => $xml
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // Single zoom level
    $xml = generateDroneXML($zoomLevel, $BASE_1X, $BASE_20X, $COMMENTS);

    // Raw XML Only
    if (isset($_GET['raw'])) {
        header("Content-Type: text/plain; charset=UTF-8");
        echo $xml;
        exit;
    }

    // JSON API Response
    header("Content-Type: application/json; charset=UTF-8");

    echo json_encode([
        "success" => true,
        "zoom" => $zoomLevel,
        "xml" => $xml
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    exit;
}

// ============================================================
// WEB UI MODE (POST requests)
// ============================================================
$xmlOutput = '';
$error = '';
$zoomInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $zoomInput = trim($_POST['zoom'] ?? '');

    if ($zoomInput === '') {
        $error = "Please enter a zoom level!";
    } elseif (!is_numeric($zoomInput)) {
        $error = "Please enter a valid number!";
    } else {
        $zoomLevel = (int)$zoomInput;

        if ($zoomLevel < 1 || $zoomLevel > 2000) {
            $error = "Zoom level must be between 1 and 2000!";
        } else {
            $xmlOutput = generateDroneXML($zoomLevel, $BASE_1X, $BASE_20X, $COMMENTS);
        }
    }
}

// Handle batch generation (1x to Nx)
$batchXml = '';
if (isset($_POST['batch']) && is_numeric($_POST['batch_max'] ?? '')) {
    $batchMax = min(2000, (int)$_POST['batch_max']);
    $batchLines = [];
    for ($z = 1; $z <= $batchMax; $z++) {
        $batchLines[] = generateDroneXML($z, $BASE_1X, $BASE_20X, $COMMENTS);
    }
    $batchXml = implode("\n\n", $batchLines);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MLBB Drone View Generator</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            color: #fff;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        h1 {
            text-align: center;
            font-size: 2.5em;
            margin-bottom: 10px;
            background: linear-gradient(90deg, #e94560, #ff6b6b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 30px rgba(233, 69, 96, 0.3);
        }
        .subtitle {
            text-align: center;
            color: #8892b0;
            margin-bottom: 30px;
            font-size: 1.1em;
        }
        .card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
        }
        .card h2 {
            color: #e94560;
            margin-bottom: 15px;
            font-size: 1.3em;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #ccd6f6;
            font-weight: 500;
        }
        input[type="number"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            background: rgba(0,0,0,0.3);
            color: #fff;
            font-size: 1.1em;
            transition: all 0.3s;
        }
        input[type="number"]:focus {
            outline: none;
            border-color: #e94560;
            box-shadow: 0 0 15px rgba(233, 69, 96, 0.3);
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 15px;
            margin-right: 10px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #e94560, #ff6b6b);
            color: #fff;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(233, 69, 96, 0.4);
        }
        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .btn-secondary:hover {
            background: rgba(255,255,255,0.2);
        }
        .error {
            background: rgba(233, 69, 96, 0.2);
            border: 1px solid #e94560;
            color: #ff6b6b;
            padding: 12px;
            border-radius: 10px;
            margin-top: 15px;
        }
        .output-section {
            display: none;
        }
        .output-section.active {
            display: block;
        }
        .xml-box {
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 12px;
            padding: 20px;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 0.85em;
            line-height: 1.6;
            color: #c9d1d9;
            overflow-x: auto;
            white-space: pre;
            max-height: 500px;
            overflow-y: auto;
        }
        .actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .preset-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .preset-btn {
            padding: 6px 14px;
            border: 1px solid rgba(233, 69, 96, 0.4);
            background: rgba(233, 69, 96, 0.1);
            color: #e94560;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.85em;
            transition: all 0.2s;
        }
        .preset-btn:hover {
            background: #e94560;
            color: #fff;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .info-item {
            background: rgba(255,255,255,0.03);
            padding: 15px;
            border-radius: 10px;
            border-left: 3px solid #e94560;
        }
        .info-item h4 {
            color: #e94560;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        .info-item p {
            color: #8892b0;
            font-size: 0.85em;
        }
        .batch-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .api-section {
            background: rgba(0,0,0,0.2);
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }
        .api-section code {
            display: block;
            background: #0d1117;
            padding: 10px 15px;
            border-radius: 8px;
            margin: 8px 0;
            color: #7ee787;
            font-family: monospace;
            font-size: 0.85em;
            word-break: break-all;
        }
        @media (max-width: 600px) {
            h1 { font-size: 1.8em; }
            .actions { flex-direction: column; }
            .btn { width: 100%; margin-right: 0; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎮 MLBB Drone View</h1>
        <p class="subtitle">Generate custom camera XML for Mobile Legends Bang Bang</p>

        <!-- Input Card -->
        <div class="card">
            <h2>⚙️ Generate Single Zoom Level</h2>
            <form method="POST" action="">
                <label for="zoom">Enter Zoom Level (1 - 2000):</label>
                <input type="number" id="zoom" name="zoom" min="1" max="2000" 
                       value="<?php echo htmlspecialchars($zoomInput ?: '10'); ?>" 
                       placeholder="e.g., 10, 50, 100, 500">

                <div class="preset-buttons">
                    <button type="button" class="preset-btn" onclick="setZoom(1)">1x</button>
                    <button type="button" class="preset-btn" onclick="setZoom(5)">5x</button>
                    <button type="button" class="preset-btn" onclick="setZoom(10)">10x</button>
                    <button type="button" class="preset-btn" onclick="setZoom(20)">20x</button>
                    <button type="button" class="preset-btn" onclick="setZoom(50)">50x</button>
                    <button type="button" class="preset-btn" onclick="setZoom(100)">100x</button>
                    <button type="button" class="preset-btn" onclick="setZoom(500)">500x</button>
                    <button type="button" class="preset-btn" onclick="setZoom(1000)">1000x</button>
                </div>

                <div class="actions">
                    <button type="submit" class="btn btn-primary">🚀 Generate XML</button>
                </div>

                <?php if ($error): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Batch Generation -->
        <div class="card">
            <h2>📦 Batch Generate (1x to Nx)</h2>
            <form method="POST" action="">
                <input type="hidden" name="batch" value="1">
                <label for="batch_max">Generate from 1x to:</label>
                <input type="number" id="batch_max" name="batch_max" min="1" max="2000" 
                       value="20" placeholder="e.g., 20, 50, 100">
                <div class="actions">
                    <button type="submit" class="btn btn-secondary">📥 Generate Batch XML</button>
                </div>
            </form>
        </div>

        <!-- API Documentation -->
        <div class="card">
            <h2>🔌 API Mode</h2>
            <p style="color:#8892b0;margin-bottom:12px;">Use URL parameters to get XML via API:</p>
            <div class="api-section">
                <p style="color:#ccd6f6;font-weight:600;margin-bottom:8px;">JSON Response:</p>
                <code>yourdomain.com/drone_generator.php?zoom=20</code>

                <p style="color:#ccd6f6;font-weight:600;margin:12px 0 8px;">Raw XML Only:</p>
                <code>yourdomain.com/drone_generator.php?zoom=100&raw=1</code>

                <p style="color:#ccd6f6;font-weight:600;margin:12px 0 8px;">Batch JSON (1x to Nx):</p>
                <code>yourdomain.com/drone_generator.php?zoom=50&batch=1</code>

                <p style="color:#ccd6f6;font-weight:600;margin:12px 0 8px;">Batch Raw XML:</p>
                <code>yourdomain.com/drone_generator.php?zoom=50&batch=1&raw=1</code>
            </div>
        </div>

        <!-- Output Section -->
        <?php if ($xmlOutput): ?>
        <div class="card output-section active">
            <h2>📄 Generated XML (<?php echo htmlspecialchars($zoomInput); ?>x)</h2>
            <div class="xml-box" id="xmlOutput"><?php echo htmlspecialchars($xmlOutput); ?></div>
            <div class="actions">
                <button class="btn btn-primary" onclick="copyToClipboard()">📋 Copy XML</button>
                <button class="btn btn-secondary" onclick="downloadXML()">💾 Download .xml</button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Batch Output -->
        <?php if ($batchXml): ?>
        <div class="card output-section active">
            <h2>📦 Batch XML (1x to <?php echo (int)$_POST['batch_max']; ?>x)</h2>
            <div class="xml-box" id="batchOutput"><?php echo htmlspecialchars($batchXml); ?></div>
            <div class="actions">
                <button class="btn btn-primary" onclick="copyBatchToClipboard()">📋 Copy All</button>
                <button class="btn btn-secondary" onclick="downloadBatchXML()">💾 Download .xml</button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Info Card -->
        <div class="card">
            <h2>ℹ️ How It Works</h2>
            <div class="info-grid">
                <div class="info-item">
                    <h4>1x (Base)</h4>
                    <p>Original game camera position. Close-up view.</p>
                </div>
                <div class="info-item">
                    <h4>20x (Drone)</h4>
                    <p>Far drone view with wider angle and distance.</p>
                </div>
                <div class="info-item">
                    <h4>Interpolation</h4>
                    <p>Values smoothly transition from 1x to 20x.</p>
                </div>
                <div class="info-item">
                    <h4>Beyond 20x</h4>
                    <p>Continues scaling gradually up to 2000x.</p>
                </div>
            </div>
        </div>

        <!-- Usage Card -->
        <div class="card">
            <h2>📱 How to Use</h2>
            <ol style="color: #8892b0; line-height: 2; padding-left: 20px;">
                <li>Enter your desired zoom level (1-2000)</li>
                <li>Click "Generate XML"</li>
                <li>Copy or download the generated XML</li>
                <li>Replace the camera config in your MLBB files</li>
                <li>Path usually: <code style="background: rgba(0,0,0,0.3); padding: 2px 8px; border-radius: 4px;">Android/data/com.mobile.legends/...</code></li>
                <li><strong style="color: #e94560;">Warning:</strong> Only use in custom/training mode. Ranked matches may result in ban.</li>
            </ol>
        </div>
    </div>

    <script>
        function setZoom(val) {
            document.getElementById('zoom').value = val;
        }

        function copyToClipboard() {
            const text = document.getElementById('xmlOutput').innerText;
            navigator.clipboard.writeText(text).then(() => {
                alert('XML copied to clipboard!');
            });
        }

        function downloadXML() {
            const text = document.getElementById('xmlOutput').innerText;
            const zoom = document.getElementById('zoom').value || 'custom';
            const blob = new Blob([text], { type: 'application/xml' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'MLBB_DroneView_' + zoom + 'x.xml';
            a.click();
            URL.revokeObjectURL(url);
        }

        function copyBatchToClipboard() {
            const text = document.getElementById('batchOutput').innerText;
            navigator.clipboard.writeText(text).then(() => {
                alert('Batch XML copied to clipboard!');
            });
        }

        function downloadBatchXML() {
            const text = document.getElementById('batchOutput').innerText;
            const max = document.getElementById('batch_max').value || '20';
            const blob = new Blob([text], { type: 'application/xml' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'MLBB_DroneView_1x_to_' + max + 'x.xml';
            a.click();
            URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>
