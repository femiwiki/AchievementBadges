<?php

namespace MediaWiki\Extension\AchievementBadges\HookHandler;

use Config;
use ExtensionRegistry;
use MediaWiki\MediaWikiServices;
use User;

class AchievementRegister implements
				\MediaWiki\User\Hook\UserSaveSettingsHook,
				\MediaWiki\Hook\AddNewAccountHook
	{

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
	 * @param User $user
	 */
	public static function onSpecialAchievementsBeforeGetEarned( User $user ) {
		if ( $user->isAnon() ) {
			return;
		}
		Achievement::achieve( [ 'key' => Constants::ACHV_KEY_SIGN_UP, 'user' => $user ] );
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
