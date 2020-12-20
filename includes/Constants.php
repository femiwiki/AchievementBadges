<?php
namespace MediaWiki\Extension\AchievementBadges;

use FatalError;

final class Constants {
	// These are tightly coupled to skin.json's config.
	/**
	 * @var string
	 */
	public const CONFIG_KEY_ACHIEVEMENT_BADGES_ENABLE_BETA_FEATURE =
		'AchievementBadgesEnableBetaFeature';
	/**
	 * @var string
	 */
	public const CONFIG_KEY_ACHIEVEMENT_BADGES_ACHIEVEMENTS = 'AchievementBadgesAchievements';

	/**
	 * @var string
	 */
	public const PREF_KEY_ACHIEVEMENT_ENABLE = 'achievementbadges-achievement-enable';

	/**
	 * @var string
	 */
	public const ACHV_KEY_ENABLE_ACHIEVEMENT_BADGES = 'enable-achievement-badges';

	/**
	 * @var string
	 */
	public const ACHV_KEY_SIGN_UP = 'sign-up';

	/**
	 * @var string
	 */
	public const LOG_TYPE = 'achievementbadges';

	/**
	 * @var string
	 */
	public const ECHO_EVENT_CATEGORY = 'achievement-badges';

	/**
	 * @var string
	 */
	public const EVENT_KEY_EARN = 'achievementbadges-earn';

	/**
	 * This class is for namespacing constants only. Forbid construction.
	 * @throws FatalError
	 */
	private function __construct() {
		throw new FatalError( "Cannot construct a utility class." );
	}
}
