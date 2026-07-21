<?php
/**
 * KOVCHEG CMS scheduler endpoint.
 * Copyright KOVCHEG CMS. Proprietary / all rights reserved.
 */
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';

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
    // Keep the audit log manageable on shared hosting.
    \Kovcheg\DB::run('DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 365 DAY)');
    // Safe runtime cleanup: cache/tmp only, never user uploads.
    $garbage=cleanup_runtime_garbage(7);
    try{\Kovcheg\DB::run('DELETE FROM user_remember_tokens WHERE expires_at<CURRENT_TIMESTAMP');}catch(Throwable){}
    try{\Kovcheg\DB::run('DELETE FROM auth_rate_limits WHERE updated_at<DATE_SUB(CURRENT_TIMESTAMP,INTERVAL 30 DAY)');}catch(Throwable){}
    $deliveries = \Kovcheg\DB::all("SELECT * FROM webhook_deliveries WHERE status IN ('pending','failed') AND (next_attempt_at IS NULL OR next_attempt_at<=CURRENT_TIMESTAMP) AND attempts<6 ORDER BY id ASC LIMIT 20");
    $delivered = 0; $failed = 0;
    foreach ($deliveries as $delivery) {
        try { $result = deliver_webhook($delivery); } catch (Throwable $e) { $result=['ok'=>false,'code'=>0,'error'=>$e->getMessage()]; }
        if ($result['ok']) { \Kovcheg\DB::run("UPDATE webhook_deliveries SET status='delivered',attempts=attempts+1,last_error=NULL,delivered_at=CURRENT_TIMESTAMP WHERE id=?",[$delivery['id']]); $delivered++; }
        else { $attempt=(int)$delivery['attempts']+1;$delay=min(3600,60*(2**max(0,$attempt-1)));$next=date('Y-m-d H:i:s',time()+$delay);$error='HTTP '.$result['code'].' '.substr((string)$result['error'],0,800);\Kovcheg\DB::run("UPDATE webhook_deliveries SET status='failed',attempts=?,last_error=?,next_attempt_at=? WHERE id=?",[$attempt,$error,$next,$delivery['id']]);if($attempt>=6)admin_notify('error','Webhook не доставлен','Доставка #'.$delivery['id'].' окончательно завершилась ошибкой: '.$error,app_url('/admin?section=webhooks'));$failed++; }
    }
    $expiredStories=0;
    try {
        $rows=\Kovcheg\DB::all("SELECT id,stored_path FROM user_stories WHERE deleted_at IS NULL AND expires_at<=CURRENT_TIMESTAMP ORDER BY id LIMIT 100");
        foreach($rows as $story){$file=BASE_PATH.'/storage/uploads/'.(string)$story['stored_path'];if(is_file($file))@unlink($file);\Kovcheg\DB::run('UPDATE user_stories SET deleted_at=CURRENT_TIMESTAMP WHERE id=?',[(int)$story['id']]);$expiredStories++;}
    } catch (Throwable $e) { log_error($e); }
    $weather=weather_refresh_known_cities(8);
    $birthdays=process_birthday_notifications();
    $delayed=process_delayed_message_notifications(150);
    $push=process_push_queue(50,6);
    audit('scheduler.run');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>true,'ran_at'=>date('c'),'version'=>APP_VERSION,'webhooks_delivered'=>$delivered,'webhooks_failed'=>$failed,'push_sent'=>$push['sent'],'push_failed'=>$push['failed'],'push_invalid_subscriptions'=>$push['invalid'],'delayed_bell_created'=>$delayed['created'],'delayed_bell_cancelled'=>$delayed['cancelled'],'expired_stories_removed'=>$expiredStories,'weather_refreshed'=>$weather['refreshed'],'weather_failed'=>$weather['failed'],'birthday_notifications'=>$birthdays,'garbage_files_removed'=>$garbage['removed'],'garbage_bytes_freed'=>$garbage['bytes']], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    log_error($e);
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Scheduler failed'], JSON_UNESCAPED_UNICODE);
} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}
