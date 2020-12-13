<?php

namespace MediaWiki\Extension\AchievementBadges;

use MediaWiki\MediaWikiServices;
use User;

class Hooks {

	public const ACHIEVEMENT_ENABLE = 'achievementbadges-achievement-enable';

	/**
	 * @param User $user
	 * @param array &$betaPrefs
	 */
	public static function onGetBetaFeaturePreferences( User $user, array &$betaPrefs ) {
		$extensionAssetsPath = MediaWikiServices::getInstance()
			->getMainConfig()
			->get( 'ExtensionAssetsPath' );
		$betaPrefs[self::ACHIEVEMENT_ENABLE] = [
			'label-message' => 'achievementbadges-achievement-enable-message',
			'desc-message' => 'achievementbadges-achievement-enable-description',
			'info-link' => 'https://github.com/femiwiki/AchievementBadges',
			'discussion-link' => 'https://github.com/femiwiki/AchievementBadges/issues',
		];
	}
}
