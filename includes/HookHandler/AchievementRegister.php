<?php

namespace MediaWiki\Extension\AchievementBadges\HookHandler;

use Config;
use ExtensionRegistry;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Extension\AchievementBadges\Achievement;
use MediaWiki\Extension\AchievementBadges\Constants;
use MediaWiki\MediaWikiServices;
use User;

class AchievementRegister implements
				\MediaWiki\Auth\Hook\LocalUserCreatedHook,
				\MediaWiki\Storage\Hook\PageSaveCompleteHook,
				\MediaWiki\User\Hook\UserSaveSettingsHook
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
				'type' => 'instant',
				'priority' => 0,
				'icon' => '',
			];
		} else {
			$achievements[Constants::ACHV_KEY_SIGN_UP] = [
				'type' => 'instant',
				'priority' => 0,
				'icon' => '',
			];
		}
		if ( $config->get( Constants::CONFIG_KEY_REPLACE_ECHO_THANK_YOU_EDIT ) ) {
			$achievements[Constants::ACHV_KEY_EDIT_COUNT] = [
				'type' => 'stats',
				'thresholds' => [ 1, 10, 100, 1000, 10000 ],
				'priority' => 200,
				'icon' => '',
			];
		}
		$achievements[Constants::ACHV_KEY_LONG_USER_PAGE] = [
			'type' => 'instant',
			'priority' => 100,
			'icon' => '',
		];
	}

	/**
	 * @param User $user
	 */
	public static function onSpecialAchievementsBeforeGetEarned( User $user ) {
		if ( $user->isAnon() ) {
			return;
		}
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$registry = $config->get( Constants::ACHIEVEMENT_BADGES_ACHIEVEMENTS );
		if ( !isset( $registry[Constants::ACHV_KEY_SIGN_UP] ) ) {
			return;
		}
		Achievement::achieve( [ 'key' => Constants::ACHV_KEY_SIGN_UP, 'user' => $user ] );
	}

	/**
	 * @inheritDoc
	 */
	public function onLocalUserCreated( $user, $autocreated ) {
		if ( $autocreated ) {
			return;
		}
		$config = $this->config;
		$registry = $config->get( Constants::ACHIEVEMENT_BADGES_ACHIEVEMENTS );
		if ( !isset( $registry[Constants::ACHV_KEY_SIGN_UP] ) ) {
			return;
		}
		Achievement::achieve( [ 'key' => Constants::ACHV_KEY_SIGN_UP, 'user' => $user ] );
	}

	/**
	 * @inheritDoc
	 */
	public function onUserSaveSettings( $user ) {
		if ( !$this->config->get( Constants::CONFIG_KEY_ENABLE_BETA_FEATURE ) ) {
			return true;
		}
		if ( $user->getOption( Constants::PREF_KEY_ACHIEVEMENT_ENABLE ) ) {
			Achievement::achieve( [
				'key' => Constants::ACHV_KEY_ENABLE_ACHIEVEMENT_BADGES,
				'user' => $user,
			] );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function onPageSaveComplete(
		$wikiPage,
		$user,
		$summary,
		$flags,
		$revisionRecord,
		$editResult
	) {
		if ( $editResult->isNullEdit() ) {
			LoggerFactory::getInstance( 'AchievementBadges' )->debug( 'null edit is ignored.' );
			return;
		}
		$user = User::newFromIdentity( $user );
		if ( $user->isAnon() ) {
			return;
		}

		$editCount = $user->getEditCount() + 1;
		Achievement::sendStats( [
			'key' => Constants::ACHV_KEY_EDIT_COUNT,
			'user' => $user,
			'stats' => $editCount,
		] );

		if ( $wikiPage->getTitle()->equals( $user->getUserPage() ) &&
			$revisionRecord->getSize() > 500 ) {
				Achievement::achieve( [
					'key' => Constants::ACHV_KEY_LONG_USER_PAGE,
					'user' => $user,
				] );
		}
	}
}
