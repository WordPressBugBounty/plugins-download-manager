<?php

namespace WPDM\Abilities\Groups;

use WPDM\__\ActivityReport;
use WPDM\Abilities\AbilityGroup;
use WPDM\Package\PackageService;

/**
 * Stats abilities: download counts and activity reports.
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

	public function getDownloadStats( array $input ) {
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

		$stats = "{$wpdb->prefix}ahm_download_stats";
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
		$where = implode( ' AND ', $where );

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
			// Shift by the site's UTC offset so days follow the site's calendar.
			$offset  = $tz->getOffset( $from );
			$results = $wpdb->get_results( $wpdb->prepare( "SELECT FLOOR((s.timestamp + %d) / 86400) AS site_day, COUNT(*) AS downloads FROM {$stats} s {$join} WHERE {$where} GROUP BY site_day ORDER BY site_day", array_merge( [ $offset ], $args ) ) );
			foreach ( $results as $row ) {
				$rows[] = [
					'date'      => gmdate( 'Y-m-d', (int) $row->site_day * DAY_IN_SECONDS ),
					'downloads' => (int) $row->downloads,
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
