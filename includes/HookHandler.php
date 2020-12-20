<?php

namespace MediaWiki\Extension\AchievementBadges;

use Config;
use ExtensionRegistry;
use Hooks;
use MediaWiki\MediaWikiServices;
use User;

class HookHandler implements \MediaWiki\User\Hook\UserSaveSettingsHook {

	/**
	 * @var Config
	 */
	private $config;

	/**
	 * @param Config $config
	 */
	public function __construct( Config $config ) {
		$this->config = $config;
	}

	public static function initExtension() {
		global $wgAchievementBadgesAchievements;

		Hooks::run( 'BeforeCreateAchievement', [ &$wgAchievementBadgesAchievements ] );
	}

	/**
	 * @param User $user
	 * @param array &$betaPrefs
	 */
	public static function onGetBetaFeaturePreferences( User $user, array &$betaPrefs ) {
		$extensionAssetsPath = MediaWikiServices::getInstance()
			->getMainConfig()
			->get( 'ExtensionAssetsPath' );
		$betaPrefs[Constants::PREF_KEY_ACHIEVEMENT_ENABLE] = [
			'label-message' => 'achievementbadges-achievement-enable-message',
			'desc-message' => 'achievementbadges-achievement-enable-description',
			'info-link' => 'https://www.mediawiki.org/wiki/Special:MyLanguage/Extension:AchievementBadges',
			'discussion-link' => 'https://github.com/femiwiki/AchievementBadges/issues',
		];
	}

	/**
	 * @param array &$achievements
	 */
	public static function onBeforeCreateAchievement( &$achievements ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		if ( $config->get( Constants::CONFIG_KEY_ENABLE_BETA_FEATURE )
			&& ExtensionRegistry::getInstance()->isLoaded( 'BetaFeatures' ) ) {
			$achievements[Constants::ACHV_KEY_ENABLE_ACHIEVEMENT_BADGES] = [
				'priority' => 0,
				'icon' => '',
				'achievement-rechecker' =>
					'MediaWiki\Extension\AchievementBadges\AchievementRechecker::checkAlwaysTrue',
			];
		} else {
			$achievements[Constants::ACHV_KEY_SIGN_UP] = [
				'priority' => 0,
				'icon' => '',
				'achievement-rechecker' =>
					'MediaWiki\Extension\AchievementBadges\AchievementRechecker::checkAlwaysTrue',
			];
		}
	}

	/**
	 * Defining the events for this extension
	 *
	 * @param array &$notifs
	 * @param array &$categories
	 * @param array &$icons
	 */
	public static function onBeforeCreateEchoEvent( &$notifs, &$categories, &$icons ) {
		$categories[Constants::ECHO_EVENT_CATEGORY] = [
			'priority' => 9,
			'tooltip' => 'achievementbadges-pref-tooltip-achievement-badges',
		];

		$notifs[Constants::EVENT_KEY_EARN] = [
			'category' => Constants::ECHO_EVENT_CATEGORY,
			'group' => 'positive',
			'section' => 'message',
			'canNotifyAgent' => true,
			'presentation-model' => EarnEchoEventPresentationModel::class,
			'user-locators' => [ 'EchoUserLocator::locateEventAgent' ],
		];
	}

	/**
	 * @param User $user the User object that was created.
	 * @param bool $byEmail true when account was created "by email"
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onAddNewAccount( $user, $byEmail ) {
		Achievement::achieve( [ 'key' => Constants::ACHV_KEY_SIGN_UP, 'user' => $user ] );
	}

	/**
	 * @inheritDoc
	 */
	public function onUserSaveSettings( $user ) {
		if ( !$this->config->get( Constants::CONFIG_KEY_ACHIEVEMENT_BADGES_ENABLE_BETA_FEATURE ) ) {
			return true;
		}
		if ( $user->getOption( Constants::PREF_KEY_ACHIEVEMENT_ENABLE ) ) {
			Achievement::achieve( [
				'key' => Constants::ACHV_KEY_ENABLE_ACHIEVEMENT_BADGES,
				'user' => $user,
			] );
		}
	}
}
