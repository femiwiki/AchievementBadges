<?php
namespace MediaWiki\Extension\AchievementBadges;

use FatalError;

final class Constants {
	// These are tightly coupled to skin.json's config.
	/** @var string */
	public const CONFIG_KEY_ENABLE_BETA_FEATURE = 'AchievementBadgesEnableBetaFeature';

	/** @var string */
	public const CONFIG_KEY_ACHIEVEMENTS = 'AchievementBadgesAchievements';

	/** @var string */
	public const CONFIG_KEY_DISABLED_ACHIEVEMENTS = 'AchievementBadgesDisabledAchievements';

	/** @var string */
	public const CONFIG_KEY_REPLACE_ECHO_THANK_YOU_EDIT = 'AchievementBadgesReplaceEchoThankYouEdit';

	/** @var string */
	public const CONFIG_KEY_REPLACE_ECHO_WELCOME = 'AchievementBadgesReplaceEchoWelcome';

	/** @var string */
	public const CONFIG_KEY_ACHIEVEMENT_FALLBACK_ICON = 'AchievementBadgesAchievementFallbackIcon';

	/** @var string */
	public const CONFIG_KEY_ACHIEVEMENT_FALLBACK_OG_IMAGE = 'AchievementBadgesAchievementFallbackOpenGraphImage';

	/** @var string */
	public const CONFIG_KEY_FACEBOOK_APP_ID = 'AchievementBadgesFacebookAppId';

	/** @var string */
	public const CONFIG_KEY_ADD_THIS_ID = 'AchievementBadgesAddThisId';

	/** @var string */
	public const PREF_KEY_ACHIEVEMENT_ENABLE = 'achievementbadges-beta-feature-achievement-enable';

	/** @var string */
	public const LOG_TYPE = 'achievementbadges';

	/** @var string */
	public const ECHO_EVENT_CATEGORY = 'achievement-badges';

	/** @var string */
	public const EVENT_KEY_EARN = 'achievementbadges-earn';

	/** @var int */
	public const DEFAULT_ACHIEVEMENT_PRIORITY = 1000;

	/** @var string */
	public const ACHV_KEY_ENABLE_ACHIEVEMENT_BADGES = 'enable-achievement-badges';

	/** @var string */
	public const ACHV_KEY_SIGN_UP = 'sign-up';

	/** @var string */
	public const ACHV_KEY_LONG_USER_PAGE = 'long-user-page';

	/** @var string */
	public const ACHV_KEY_EDIT_PAGE = 'edit-page';

	/** @var string */
	public const ACHV_KEY_EDIT_SIZE = 'edit-size';

	/** @var string */
	public const ACHV_KEY_CREATE_PAGE = 'create-page';

	/** @var string */
	public const ACHV_KEY_CONTRIBS_SUNDAY = 'contribs-sunday';

	/** @var string */
	public const ACHV_KEY_CONTRIBS_MONDAY = 'contribs-monday';

	/** @var string */
	public const ACHV_KEY_CONTRIBS_TUESDAY = 'contribs-tuesday';

	/** @var string */
	public const ACHV_KEY_CONTRIBS_WEDNESDAY = 'contribs-wednesday';

	/** @var string */
	public const ACHV_KEY_CONTRIBS_THURSDAY = 'contribs-thursday';

	/** @var string */
	public const ACHV_KEY_CONTRIBS_FRIDAY = 'contribs-friday';

	/** @var string */
	public const ACHV_KEY_CONTRIBS_SATURDAY = 'contribs-saturday';

	/** @var string */
	public const ACHV_KEY_VISUAL_EDIT = 'visual-edit';

	/** @var string */
	public const ACHV_KEY_THANKS = 'thanks';

	/** @var string */
	public const ACHV_KEY_BE_THANKED = 'be-thanked';

	/** This class is for namespacing constants only. Forbid construction.
	 * @return void
	 * @throws FatalError
	 */
	private function __construct() {
		throw new FatalError( "Cannot construct a utility class." );
	}
}
