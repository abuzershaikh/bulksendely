<?php
define('ENVIRONMENT', 'production');
require '/var/www/wappbuzz/vendor/autoload.php';
$paths = new Config\Paths();
$bootstrap = rtrim($paths->systemDirectory, '\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';
require $bootstrap;

$app = Config\Services::codeigniter();
$app->initialize();

$db = \Config\Database::connect();
$pTable = $db->prefixTable('android_campaign_status');
echo "prefixTable result: " . $pTable . "\n";

$row1 = $db->table('sp_android_campaign_status')->where('ids', '6kaewb8qauxffzccrowk2fgb')->get()->getRowArray();
echo "Query 'sp_android_campaign_status': " . ($row1 ? "FOUND (ids=" . $row1['ids'] . ", sent=" . $row1['sent_count'] . ")" : "NOT FOUND") . "\n";

$row2 = $db->table($pTable)->where('ids', '6kaewb8qauxffzccrowk2fgb')->get()->getRowArray();
echo "Query pTable: " . ($row2 ? "FOUND (ids=" . $row2['ids'] . ", sent=" . $row2['sent_count'] . ")" : "NOT FOUND") . "\n";

$row3 = $db->table('android_campaign_status')->where('ids', '6kaewb8qauxffzccrowk2fgb')->get()->getRowArray();
echo "Query 'android_campaign_status': " . ($row3 ? "FOUND (ids=" . $row3['ids'] . ", sent=" . $row3['sent_count'] . ")" : "NOT FOUND") . "\n";
