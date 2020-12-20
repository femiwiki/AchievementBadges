<?php

namespace MediaWiki\Extension\AchievementBadges;

use BetaFeatures;
use EchoEvent;
use ManualLogEntry;
use MediaWiki\MediaWikiServices;
use MWException;
use SpecialPage;
use User;

class Achievement {
	/**
	 * You should not call the constructor.
	 */
	protected function __construct() {
	}
	/**
	 * @param array $info arguments:
	 * key:
	 * user: The user who earned the achievement;
	 */
	public static function achieve( $info ) {
		if ( empty( $info['key'] ) ) {
			throw new MWException( "'key' parameter is mandatory" );
		}

		$user = $info['user'];
		if ( !self::isAchievementBadgesAvailable( $user ) ) {
			return;
		}
		$key = $info['key'];
		$config = MediaWikiServices::getInstance()->getMainConfig();

		$registry = $config->get( Constants::CONFIG_KEY_ACHIEVEMENT_BADGES_ACHIEVEMENTS );
		if ( !isset( $registry[$key] ) ) {
			throw new MWException( "Achievement key not found: {$key}" );
		}

		$dbr = wfGetDB( DB_REPLICA );
		$query = [];
		$count = $dbr->selectRowCount(
			[ 'logging', 'actor' ],
			'*',
			[
				'log_type' => Constants::LOG_TYPE,
				'log_action' => $key,
				'actor_user' => $user->getId(),
			],
			__METHOD__,
			[],
			[
				'actor' => [ 'JOIN', 'actor_id = log_actor' ],
			]
		);
		if ( $count ) {
			// The achievement was earned already.
			return;
		}

		wfDebug( '[AchievementBadges] ' . $user->getName() . ' obtained ' . $key );

		$logEntry = new ManualLogEntry( Constants::LOG_TYPE, $key );
		$logEntry->setPerformer( $user );
		$logEntry->setTarget( SpecialPage::getTitleFor( SpecialAchievements::PAGE_NAME ) );
		$logEntry->setParameters( [
			'4::key' => $key,
		] );
		$logEntry->insert();

		$result = EchoEvent::create( [
			'type' => Constants::EVENT_KEY_EARN,
			'extra' => [
				'key' => $key,
			],
			'agent' => $user,
		] );
	}

	/**
	 * @param User $user
	 * @return bool
	 */
	public static function isAchievementBadgesAvailable( User $user ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();

		$configEnabled = $config->get( Constants::CONFIG_KEY_ENABLE_BETA_FEATURE );
		$userOptionEnabled = $configEnabled &&
			BetaFeatures::isFeatureEnabled( $user, Constants::PREF_KEY_ACHIEVEMENT_ENABLE );

		if ( !$configEnabled ) {
			// If AchievementBadges is not a beta feature, it is available to everyone.
			return true;
		}
		if ( $user->isRegistered() && $userOptionEnabled ) {
			// If AchievementBadges is a beta feature, only a registered user which enables the feature
			// has access the feature.
			return true;
		}
		return false;
	}
}
