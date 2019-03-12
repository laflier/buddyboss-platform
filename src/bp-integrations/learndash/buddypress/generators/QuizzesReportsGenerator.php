<?php
/**
 * BuddyBoss LearnDash integration quizzes reports generator.
 * 
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */ 

namespace Buddyboss\LearndashIntegration\Buddypress\Generators;

use Buddyboss\LearndashIntegration\Library\ReportsGenerator;
use LDLMS_DB;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * @todo add title/description
 * 
 * @since BuddyBoss 1.0.0
 */
class QuizzesReportsGenerator extends ReportsGenerator
{
	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct()
	{
		$this->completed_table_title = __('Marked Quizzes', 'buddyboss');
		$this->incompleted_table_title = __('Unmarked Quizzes', 'buddyboss');

		add_action('bp_ld_sync/ajax/pre_fetch_reports', [$this, 'loadAdditionalFields']);

		parent::__construct();
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function columns()
	{
		return [
			'user_id'         => $this->column('user_id'),
			'user'            => $this->column('user'),
			'course_id'       => $this->column('course_id'),
			'course'          => $this->column('course'),
			'quiz'            => [
				'label'     => __( 'Quiz', 'buddyboss' ),
				'sortable'  => true,
				'order_key' => 'post_title',
			],
			'completion_date' => $this->column('completion_date'),
			'updated_date'    => $this->column('updated_date'),
			'score'           => [
				'label'     => __( 'Score', 'buddyboss' ),
				'sortable'  => true,
				'order_key' => 'activity_score',
			],
			'time_spent'      => $this->column('time_spent'),
			'quiz_points'     => [
				'label'     => __( 'Points Earned', 'buddyboss' ),
				'sortable'  => true,
				'order_key' => 'activity_points',
			],
			'attempts'         => [
				'label'     => __( 'Attempts', 'buddyboss' ),
				'sortable'  => false,
				'order_key' => 'post_title',
			],
		];
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function formatData($activity)
	{
		return [
			'user_id'         => $activity->user_id,
			'user'            => $activity->user_display_name,
			'course_id'       => $activity->activity_course_id,
			'course'          => $activity->activity_course_title,
			'quiz'            => $activity->post_title,
			'completion_date' => $this->completionDate($activity),
			'updated_date'    => $this->updatedDate($activity),
			'score'           => $activity->activity_score,
			'time_spent'      => $this->timeSpent($activity),
			'quiz_points'     => $activity->activity_points,
			'attempts'        => $activity->activity_attemps,
		];
	}
	/**
	 *
	 * @todo make PHP 5.4 compatible
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function loadAdditionalFields($generator)
	{
		if (! is_a($generator, static::class)) {
			return;
		}

		$this->excludeCourseTimeSpent();

		add_filter('bp_ld_sync/reports/activity_fields', [$this, 'addQuizActivityFields'], 10, 2);
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function addQuizActivityFields($strFields, $queryArgs)
	{
		global $wpdb;
		$metaTable = $wpdb->prefix ."learndash_user_activity_meta";
		$table = $wpdb->prefix ."learndash_user_activity";

		$strFields .= ", (
				SELECT mt_points.activity_meta_value
				FROM {$metaTable} as mt_points
				WHERE mt_points.activity_id = ld_user_activity.activity_id
				AND mt_points.activity_meta_key = 'points'
			) as activity_points
		";

		$strFields .= ", (
				SELECT mt_score.activity_meta_value
				FROM {$metaTable} as mt_score
				WHERE mt_score.activity_id = ld_user_activity.activity_id
				AND mt_score.activity_meta_key = 'percentage'
			) as activity_score
		";

		$strFields .= ", (
				SELECT mt_time_spent.activity_meta_value
				FROM {$metaTable} as mt_time_spent
				WHERE mt_time_spent.activity_id = ld_user_activity.activity_id
				AND mt_time_spent.activity_meta_key = 'timespent'
			) as activity_time_spent
		";

		$strFields .= ", (
				SELECT count(*)
				FROM {$table} as mt_attempts
				WHERE mt_attempts.post_id = posts.ID
				AND mt_attempts.user_id = users.ID
			) as activity_attemps
		";

		return $strFields;
	}
}
