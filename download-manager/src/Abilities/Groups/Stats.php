<?php

namespace WPDM\Abilities\Groups;

use WPDM\__\ActivityReport;
use WPDM\Abilities\AbilityGroup;
use WPDM\Package\PackageService;

/**
 * Stats abilities: download counts, download history and activity reports.
 *
 * @since 3.3.73
 */
class Stats extends AbilityGroup {

	const CATEGORY = 'wpdm-stats';

	const MAX_RANGE_DAYS = 366;

	public function register() {
		$date = [
			'type'    => 'string',
			'pattern' => '^\d{4}-\d{2}-\d{2}$',
		];

		$this->registerAbility(
			'wpdm/get-download-stats',
			[
				'label'               => __( 'Get download stats', 'download-manager' ),
				'description'         => __( 'Counts downloads over a date range (up to 366 days, in the site\'s timezone), per day or per package. Defaults to the last 30 days. Without a package ID, authors get their own packages and administrators get the whole site.', 'download-manager' ),
				'input_schema'        => [
					'type'                 => 'object',
					'default'              => [],
					'additionalProperties' => false,
					'properties'           => [
						'package_id' => [
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Only this package.',
						],
						'date_from'  => $date + [ 'description' => 'First day, YYYY-MM-DD.' ],
						'date_to'    => $date + [ 'description' => 'Last day, YYYY-MM-DD. Defaults to today.' ],
						'group_by'   => [
							'type'        => 'string',
							'enum'        => [ 'day', 'package' ],
							'default'     => 'day',
							'description' => '"day" gives one row per day with downloads; "package" gives the top 100 packages.',
						],
					],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'date_from' => [ 'type' => 'string' ],
						'date_to'   => [ 'type' => 'string' ],
						'group_by'  => [ 'type' => 'string' ],
						'total'     => [ 'type' => 'integer' ],
						'rows'      => [
							'type'  => 'array',
							'items' => [
								'type'       => 'object',
								'properties' => [
									'date'       => [ 'type' => 'string' ],
									'package_id' => [ 'type' => 'integer' ],
									'title'      => [ 'type' => 'string' ],
									'downloads'  => [ 'type' => 'integer' ],
								],
								'required'   => [ 'downloads' ],
							],
						],
					],
					'required'   => [ 'date_from', 'date_to', 'group_by', 'total', 'rows' ],
				],
				'permission_callback' => function ( $input ) {
					return empty( $input['package_id'] ) ? PackageService::userCanAuthor() : PackageService::userCanEdit( (int) $input['package_id'] );
				},
				'execute_callback'    => [ $this, 'getDownloadStats' ],
				'annotations'         => [
					'readonly'   => true,
					'idempotent' => true,
				],
			]
		);

		$history_schema = $this->pageSchema(
			[
				'type'       => 'object',
				'properties' => [
					'id'            => [ 'type' => 'integer' ],
					'package_id'    => [ 'type' => 'integer' ],
					'title'         => [ 'type' => 'string' ],
					'user'          => [
						'type'       => 'object',
						'properties' => [
							'id'   => [
								'type'        => 'integer',
								'description' => '0 for guests.',
							],
							'name' => [ 'type' => 'string' ],
						],
					],
					'downloaded_at' => [ 'type' => 'string' ],
					'file'          => [ 'type' => 'string' ],
					'version'       => [ 'type' => 'string' ],
					'ip'            => [
						'type'        => 'string',
						'description' => 'Administrators only.',
					],
				],
				'required'   => [ 'id', 'package_id', 'title', 'user', 'downloaded_at' ],
			]
		);
		$history_schema['properties'] += [
			'date_from' => [ 'type' => 'string' ],
			'date_to'   => [ 'type' => 'string' ],
		];

		$this->registerAbility(
			'wpdm/get-download-history',
			[
				'label'               => __( 'Get download history', 'download-manager' ),
				'description'         => __( 'Lists individual downloads, newest first, one page at a time: which package, which user, when, and which file and version. Covers a date range of up to 366 days, the last 30 by default. Filter by package or user ID. Without a package ID, authors get their own packages and administrators the whole site; only administrators see IP addresses.', 'download-manager' ),
				'input_schema'        => [
					'type'                 => 'object',
					'default'              => [],
					'additionalProperties' => false,
					'properties'           => [
						'package_id' => [
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Only this package.',
						],
						'user_id'    => [
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Only downloads by this user.',
						],
						'date_from'  => $date + [ 'description' => 'First day, YYYY-MM-DD.' ],
						'date_to'    => $date + [ 'description' => 'Last day, YYYY-MM-DD. Defaults to today.' ],
					] + $this->pagingProperties(),
				],
				'output_schema'       => $history_schema,
				'permission_callback' => function ( $input ) {
					return empty( $input['package_id'] ) ? PackageService::userCanAuthor() : PackageService::userCanEdit( (int) $input['package_id'] );
				},
				'execute_callback'    => [ $this, 'getDownloadHistory' ],
				'annotations'         => [
					'readonly'   => true,
					'idempotent' => true,
				],
			]
		);

		$this->registerPrompt(
			'wpdm/activity-report',
			[
				'label'               => __( 'Activity report', 'download-manager' ),
				'description'         => __( 'Instructions to write a weekly or monthly download report from wpdm/get-activity-summary and wpdm/get-download-stats.', 'download-manager' ),
				'input_schema'        => [
					'type'                 => 'object',
					'default'              => [],
					'additionalProperties' => false,
					'properties'           => [
						'period'   => [
							'type'        => 'string',
							'enum'        => [ 'last-week', 'last-month' ],
							'default'     => 'last-week',
							'description' => '"last-week" or "last-month".',
						],
						'audience' => [
							'type'        => 'string',
							'description' => 'Who the report is for, e.g. "the marketing team".',
						],
					],
				],
				'permission_callback' => function () {
					return current_user_can( WPDM_ADMIN_CAP );
				},
				'execute_callback'    => [ $this, 'activityReportPrompt' ],
			]
		);

		$this->registerAbility(
			'wpdm/get-activity-summary',
			[
				'label'               => __( 'Get activity summary', 'download-manager' ),
				'description'         => __( 'Returns the data behind the weekly or monthly activity report email: download totals compared with the period before, top and trending packages, user activity and downloads per category. Covers the last complete week (Monday to Sunday) or calendar month.', 'download-manager' ),
				'input_schema'        => [
					'type'                 => 'object',
					'default'              => [],
					'additionalProperties' => false,
					'properties'           => [
						'period' => [
							'type'    => 'string',
							'enum'    => [ 'last-week', 'last-month' ],
							'default' => 'last-week',
						],
					],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'period'             => [ 'type' => 'string' ],
						'start'              => [ 'type' => 'string' ],
						'end'                => [ 'type' => 'string' ],
						'download_summary'   => [ 'type' => 'object' ],
						'top_packages'       => [ 'type' => 'array' ],
						'trending_packages'  => [ 'type' => 'array' ],
						'user_activity'      => [ 'type' => 'object' ],
						'category_breakdown' => [ 'type' => 'array' ],
					],
					'required'   => [ 'period', 'start', 'end' ],
				],
				'permission_callback' => function () {
					return current_user_can( WPDM_ADMIN_CAP );
				},
				'execute_callback'    => [ $this, 'getActivitySummary' ],
				'annotations'         => [
					'readonly'   => true,
					'idempotent' => true,
				],
			]
		);
	}

	public function activityReportPrompt( array $input ) {
		$period   = isset( $input['period'] ) && 'last-month' === $input['period'] ? 'last-month' : 'last-week';
		$audience = isset( $input['audience'] ) ? trim( (string) $input['audience'] ) : '';
		$lines    = [
			sprintf( 'Write a download activity report for the %s on this site, using the Download Manager tools.', 'last-month' === $period ? 'last calendar month' : 'last complete week (Monday to Sunday)' ),
			'',
			sprintf( '1. Call wpdm/get-activity-summary with period "%s". It returns the totals compared with the period before, the top and trending packages, user activity and downloads per category.', $period ),
			'2. Call wpdm/get-download-stats grouped by "day" for the same start and end dates, to see which days were busiest.',
			'3. Write the report with these sections: Summary (total downloads and the change from the previous period), Top packages, Trending packages, Busiest days, Categories, and Notes (anything unusual, such as a package with zero downloads or a sudden spike).',
			'4. Give numbers as a short table where it helps. Keep each section brief.',
		];
		if ( $audience ) {
			$lines[] = sprintf( '5. Write it for this audience: %s.', $audience );
		}

		return $this->promptMessages( implode( "\n", $lines ) );
	}

	public function getDownloadStats( array $input ) {
		global $wpdb;

		$query = $this->query( $input );
		if ( is_wp_error( $query ) ) {
			return $query;
		}
		list( $from, $to, $join, $where, $args ) = $query;
		$where = implode( ' AND ', $where );
		$tz    = wp_timezone();
		$stats = "{$wpdb->prefix}ahm_download_stats";

		$group_by = isset( $input['group_by'] ) ? $input['group_by'] : 'day';
		$rows     = [];
		if ( 'package' === $group_by ) {
			$results = $wpdb->get_results( $wpdb->prepare( "SELECT s.pid, COUNT(*) AS downloads FROM {$stats} s {$join} WHERE {$where} GROUP BY s.pid ORDER BY downloads DESC LIMIT 100", $args ) );
			foreach ( $results as $row ) {
				$rows[] = [
					'package_id' => (int) $row->pid,
					'title'      => html_entity_decode( (string) get_the_title( (int) $row->pid ), ENT_QUOTES, 'UTF-8' ),
					'downloads'  => (int) $row->downloads,
				];
			}
		} else {
			// Shift by the site's UTC offset so days follow the site's calendar. The offset can
			// change inside the range (daylight saving), so count each constant-offset stretch
			// on its own and merge the days.
			$counts = [];
			foreach ( $this->offsetStretches( $tz, $args[0], $args[1] ) as list( $start, $end, $offset ) ) {
				$results = $wpdb->get_results( $wpdb->prepare( "SELECT FLOOR((s.timestamp + %d) / 86400) AS site_day, COUNT(*) AS downloads FROM {$stats} s {$join} WHERE {$where} AND s.timestamp >= %d AND s.timestamp < %d GROUP BY site_day", array_merge( [ $offset ], $args, [ $start, $end ] ) ) );
				foreach ( $results as $row ) {
					$date            = gmdate( 'Y-m-d', (int) $row->site_day * DAY_IN_SECONDS );
					$counts[ $date ] = ( isset( $counts[ $date ] ) ? $counts[ $date ] : 0 ) + (int) $row->downloads;
				}
			}
			ksort( $counts );
			foreach ( $counts as $date => $downloads ) {
				$rows[] = [
					'date'      => $date,
					'downloads' => $downloads,
				];
			}
		}

		$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$stats} s {$join} WHERE {$where}", $args ) );

		return [
			'date_from' => $from->format( 'Y-m-d' ),
			'date_to'   => $to->format( 'Y-m-d' ),
			'group_by'  => $group_by,
			'total'     => $total,
			'rows'      => $rows,
		];
	}

	public function getDownloadHistory( array $input ) {
		global $wpdb;

		$query = $this->query( $input );
		if ( is_wp_error( $query ) ) {
			return $query;
		}
		list( $from, $to, $join, $where, $args ) = $query;
		if ( ! empty( $input['user_id'] ) ) {
			$where[] = 's.uid = %d';
			$args[]  = (int) $input['user_id'];
		}
		$where    = implode( ' AND ', $where );
		$page     = isset( $input['page'] ) ? (int) $input['page'] : 1;
		$per_page = isset( $input['per_page'] ) ? (int) $input['per_page'] : 20;
		$stats    = "{$wpdb->prefix}ahm_download_stats";
		$admin    = current_user_can( WPDM_ADMIN_CAP );

		$total   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$stats} s {$join} WHERE {$where}", $args ) );
		$results = $wpdb->get_results( $wpdb->prepare( "SELECT s.id, s.pid, s.uid, s.timestamp, s.ip, s.filename, s.version FROM {$stats} s {$join} WHERE {$where} ORDER BY s.timestamp DESC, s.id DESC LIMIT %d OFFSET %d", array_merge( $args, [ $per_page, ( $page - 1 ) * $per_page ] ) ) );

		$names = [];
		$rows  = [];
		foreach ( $results as $row ) {
			$uid = (int) $row->uid;
			if ( ! isset( $names[ $uid ] ) ) {
				$user          = $uid ? get_userdata( $uid ) : null;
				/* translators: %d: user ID */
				$names[ $uid ] = $uid ? ( $user ? $user->display_name : sprintf( __( 'Deleted user #%d', 'download-manager' ), $uid ) ) : __( 'Guest', 'download-manager' );
			}
			$file = (string) $row->filename;
			$path = wp_parse_url( $file, PHP_URL_PATH );
			$item = [
				'id'            => (int) $row->id,
				'package_id'    => (int) $row->pid,
				'title'         => html_entity_decode( (string) get_the_title( (int) $row->pid ), ENT_QUOTES, 'UTF-8' ),
				'user'          => [
					'id'   => $uid,
					'name' => $names[ $uid ],
				],
				'downloaded_at' => $this->isoDate( (int) $row->timestamp ),
				'file'          => wp_basename( is_string( $path ) ? $path : $file ),
				'version'       => (string) $row->version,
			];
			if ( $admin ) {
				$item['ip'] = (string) $row->ip;
			}
			$rows[] = $item;
		}

		return $this->page( $rows, $total, $page, $per_page ) + [
			'date_from' => $from->format( 'Y-m-d' ),
			'date_to'   => $to->format( 'Y-m-d' ),
		];
	}

	/**
	 * The date range and the rows the caller may see: one package, their own packages,
	 * or, for administrators, every package.
	 *
	 * @param array $input date_from, date_to, package_id.
	 *
	 * @return array|\WP_Error [ from, to, join SQL, where clauses, prepare args ]
	 */
	private function query( array $input ) {
		global $wpdb;

		$tz    = wp_timezone();
		$today = new \DateTimeImmutable( 'today', $tz );
		$to    = ! empty( $input['date_to'] ) ? \DateTimeImmutable::createFromFormat( '!Y-m-d', $input['date_to'], $tz ) : $today;
		$from  = ! empty( $input['date_from'] ) ? \DateTimeImmutable::createFromFormat( '!Y-m-d', $input['date_from'], $tz ) : $to->modify( '-29 days' );
		$real = function ( $day, $key ) use ( $input ) {
			return ! empty( $input[ $key ] ) ? $day && $day->format( 'Y-m-d' ) === $input[ $key ] : true;
		};
		if ( ! $from || ! $to || ! $real( $from, 'date_from' ) || ! $real( $to, 'date_to' ) ) {
			return $this->error( 'wpdm_invalid_date', __( 'Dates must be real days in YYYY-MM-DD form.', 'download-manager' ), 400 );
		}
		if ( $from > $to || $from->diff( $to )->days >= self::MAX_RANGE_DAYS ) {
			return $this->error( 'wpdm_invalid_range', sprintf( __( 'date_from must be on or before date_to, and the range at most %d days.', 'download-manager' ), self::MAX_RANGE_DAYS ), 400 );
		}

		$package_id = ! empty( $input['package_id'] ) ? (int) $input['package_id'] : 0;
		if ( $package_id && 'wpdmpro' !== get_post_type( $package_id ) ) {
			return $this->error( 'wpdm_package_not_found', __( 'No package has that ID.', 'download-manager' ), 404 );
		}

		$join  = '';
		$where = [ 's.timestamp >= %d', 's.timestamp < %d' ];
		$args  = [ $from->getTimestamp(), $to->modify( '+1 day' )->getTimestamp() ];
		if ( $package_id ) {
			$where[] = 's.pid = %d';
			$args[]  = $package_id;
		} elseif ( ! current_user_can( WPDM_ADMIN_CAP ) ) {
			$join    = "INNER JOIN {$wpdb->posts} p ON p.ID = s.pid";
			$where[] = 'p.post_author = %d';
			$args[]  = get_current_user_id();
		}

		return [ $from, $to, $join, $where, $args ];
	}

	/**
	 * Split a timestamp range where the timezone's UTC offset changes.
	 *
	 * @param \DateTimeZone $tz    Site timezone.
	 * @param int           $start First timestamp, inclusive.
	 * @param int           $end   Last timestamp, exclusive.
	 *
	 * @return array[] [ start, end, offset in seconds ] per stretch.
	 */
	private function offsetStretches( \DateTimeZone $tz, $start, $end ) {
		$stretches = [];
		$cursor    = (int) $start;
		// The first entry describes the state at $start; the rest are changes inside the range.
		foreach ( array_slice( (array) $tz->getTransitions( $cursor, (int) $end ), 1 ) as $transition ) {
			if ( $transition['ts'] <= $cursor || $transition['ts'] >= $end ) {
				continue;
			}
			$stretches[] = [ $cursor, (int) $transition['ts'], $tz->getOffset( new \DateTimeImmutable( '@' . $cursor ) ) ];
			$cursor      = (int) $transition['ts'];
		}
		$stretches[] = [ $cursor, (int) $end, $tz->getOffset( new \DateTimeImmutable( '@' . $cursor ) ) ];

		return $stretches;
	}

	public function getActivitySummary( array $input ) {
		$period = isset( $input['period'] ) ? $input['period'] : 'last-week';
		$report = new ActivityReport( 'last-month' === $period ? 'monthly' : 'weekly' );
		$data   = $report->collectData( [ 'download_summary', 'top_packages', 'trending_packages', 'user_activity', 'category_breakdown' ] );

		// Downloader emails stay out of agent output; only commerce abilities expose customer contact details.
		if ( isset( $data['user_activity']['top_downloaders'] ) && is_array( $data['user_activity']['top_downloaders'] ) ) {
			foreach ( $data['user_activity']['top_downloaders'] as &$downloader ) {
				unset( $downloader['email'] );
			}
			unset( $downloader );
		}

		return [
			'period'             => $period,
			'start'              => $this->isoDate( $report->getStartDate() ),
			'end'                => $this->isoDate( $report->getEndDate() ),
			'download_summary'   => isset( $data['download_summary'] ) ? (object) $data['download_summary'] : new \stdClass(),
			'top_packages'       => isset( $data['top_packages'] ) ? array_values( $data['top_packages'] ) : [],
			'trending_packages'  => isset( $data['trending_packages'] ) ? array_values( $data['trending_packages'] ) : [],
			'user_activity'      => isset( $data['user_activity'] ) ? (object) $data['user_activity'] : new \stdClass(),
			'category_breakdown' => isset( $data['category_breakdown'] ) ? array_values( $data['category_breakdown'] ) : [],
		];
	}
}
