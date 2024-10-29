<?php
/**
 * User: shahjada
 * Date: 2019-03-21
 * Time: 13:14
 */

namespace WPDM\__;


class CronJob
{
    private static $instance;

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }
        return self::$instance;
    }

	static function create($type, $data, $execute_at, $repeat_execution = 1, $interval = 0) {
		global $wpdb;
		$code = md5($type.$execute_at.json_encode($data));
		$wpdb->insert("{$wpdb->prefix}ahm_cron_jobs", [
			'code' => $code,
			'type' => $type,
			'data' => json_encode($data),
			'execute_at' => $execute_at,
			'repeat_execution' => max( (int)$repeat_execution, 1 ),
			'execution_count' => 0,
			'interval' => $interval,
			'created_at' => time(),
			'created_by' => get_current_user_id(),

		]);
	}

	static function delete($id) {
		global $wpdb;
		$wpdb->delete("{$wpdb->prefix}ahm_cron_jobs", ['ID' => (int)$id]);
	}

	function executeAll() {
		global $wpdb;
		$time = time();
		$jobs = $wpdb->get_results("select * from {$wpdb->prefix}ahm_cron_jobs where execute_at < $time limit 0, 10");
		foreach($jobs as $job) {
			$this->execute($job);
		}
	}

	function execute($job) {
		global $wpdb;
		if(is_int($job)) {
			$job = $wpdb->get_row("select * from {$wpdb->prefix}ahm_cron_jobs where ID = '{$job}'");
		}
		if($job->execution_count >= $job->repeat_execution) {
			$wpdb->delete("{$wpdb->prefix}ahm_cron_jobs", ['ID' => $job->ID]);
		} else {
			$target = $job->type;
			$payload = new $target(json_decode($job->data));
			$payload->dispatch();
			$execute_at = $job->execute_at + ($job->interval *  86400);
			$wpdb->update( "{$wpdb->prefix}ahm_cron_jobs", [
				'execution_count' => $job->execution_count + 1,
				'execute_at' => $execute_at,
			], [ 'ID' => $job->ID ] );
		}
	}

	static function getAll() {
		global $wpdb;
		// $time = time();
		// where execute_at < $time
		$jobs = $wpdb->get_results("select * from {$wpdb->prefix}ahm_cron_jobs");
		return $jobs;
	}
}

