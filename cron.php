<?php
/**
 * KOVCHEG CMS scheduler endpoint.
 * Author and copyright: Ланцет Семён Борисович.
 * License: proprietary / all rights reserved.
 */
declare(strict_types=1);

require __DIR__.'/app/bootstrap.php';
require_once BASE_PATH.'/app/BlogGrowth.php';

$isCli = PHP_SAPI === 'cli';
$key = (string)($_GET['key'] ?? '');
$expected = (string)setting('cron_key', '');
if (!$isCli && ($expected === '' || !hash_equals($expected, $key))) {
    http_response_code(403);
    exit('Forbidden');
}

$lockPath = BASE_PATH.'/storage/cron.lock';
$lock = fopen($lockPath, 'c+');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    http_response_code(409);
    exit('Scheduler is already running');
}

try {
    \Kovcheg\DB::run('DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 365 DAY)');
    $garbage = cleanup_runtime_garbage(7);

    try {
        \Kovcheg\DB::run('DELETE FROM user_remember_tokens WHERE expires_at<CURRENT_TIMESTAMP');
    } catch (Throwable $error) {
        log_error($error);
    }
    try {
        \Kovcheg\DB::run('DELETE FROM auth_rate_limits WHERE updated_at<DATE_SUB(CURRENT_TIMESTAMP,INTERVAL 30 DAY)');
    } catch (Throwable $error) {
        log_error($error);
    }

    $published = \Kovcheg\Blog\Growth::publishScheduled();

    $deliveries = [];
    try {
        $deliveries = \Kovcheg\DB::all(
            "SELECT * FROM webhook_deliveries
             WHERE status IN ('pending','failed')
               AND (next_attempt_at IS NULL OR next_attempt_at<=CURRENT_TIMESTAMP)
               AND attempts<6
             ORDER BY id ASC LIMIT 20"
        );
    } catch (Throwable $error) {
        log_error($error);
    }

    $delivered = 0;
    $failed = 0;
    foreach ($deliveries as $delivery) {
        try {
            $result = deliver_webhook($delivery);
        } catch (Throwable $error) {
            $result = ['ok'=>false,'code'=>0,'error'=>$error->getMessage()];
        }

        if (!empty($result['ok'])) {
            \Kovcheg\DB::run(
                "UPDATE webhook_deliveries
                 SET status='delivered',attempts=attempts+1,last_error=NULL,delivered_at=CURRENT_TIMESTAMP
                 WHERE id=?",
                [(int)$delivery['id']]
            );
            $delivered++;
            continue;
        }

        $attempt = (int)$delivery['attempts'] + 1;
        $delay = min(3600, 60 * (2 ** max(0, $attempt - 1)));
        $next = date('Y-m-d H:i:s', time() + $delay);
        $errorText = 'HTTP '.(int)($result['code'] ?? 0).' '.mb_substr((string)($result['error'] ?? ''), 0, 800);
        \Kovcheg\DB::run(
            "UPDATE webhook_deliveries
             SET status='failed',attempts=?,last_error=?,next_attempt_at=?
             WHERE id=?",
            [$attempt,$errorText,$next,(int)$delivery['id']]
        );
        $failed++;
    }

    audit('scheduler.run', null, null, [
        'scheduled_published'=>$published,
        'webhooks_delivered'=>$delivered,
        'webhooks_failed'=>$failed,
        'garbage_removed'=>(int)($garbage['removed'] ?? 0),
    ]);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok'=>true,
        'ran_at'=>date('c'),
        'version'=>APP_VERSION,
        'scheduled_published'=>$published,
        'webhooks_delivered'=>$delivered,
        'webhooks_failed'=>$failed,
        'garbage_files_removed'=>(int)($garbage['removed'] ?? 0),
        'garbage_bytes_freed'=>(int)($garbage['bytes'] ?? 0),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $error) {
    log_error($error);
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Scheduler failed'], JSON_UNESCAPED_UNICODE);
} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}
