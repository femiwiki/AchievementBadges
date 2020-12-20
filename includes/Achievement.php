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
	 * user: The user who earned the achievement.
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
		$registry = $config->get( Constants::ACHIEVEMENT_BADGES_ACHIEVEMENTS );

		if ( !isset( $registry[$key] ) ) {
			throw new MWException( "Achievement key not found: {$key}" );
		}
		$registry[$key]['type'] = $registry[$key]['type'] ?? 'instant';
		if ( $registry[$key]['type'] !== 'instant' ) {
			throw new MWException(
				"$__METHOD__ is called with only an instant achievement, but $key is not" );
		}

		$count = self::selectLogCount( $key, $user );
		if ( $count > 0 ) {
			// The achievement was earned already.
			return;
		}

		self::achieveInternal( $key, $user );
	}

	/**
	 * @param array $info arguments:
	 * key:
	 * user: The user who earned the achievement.
	 * stats:
	 */
	public static function sendStats( $info ) {
		if ( empty( $info['key'] ) ) {
			throw new MWException( "'key' parameter is mandatory" );
		}

		$user = $info['user'];
		if ( !self::isAchievementBadgesAvailable( $user ) ) {
			wfDebug( '[AchievementBadges] user cannot use AchievementBadges' );
			return;
		}
		$key = $info['key'];
		$stats = $info['stats'];
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$registry = $config->get( Constants::ACHIEVEMENT_BADGES_ACHIEVEMENTS );
		if ( !isset( $registry[$key] ) ) {
			throw new MWException( "Achievement key not found: {$key}" );
		}
		if ( $registry[$key]['type'] !== 'stats' ) {
			throw new MWException( "Only instant achievement can be called by " . __METHOD__ );
		}
		$thresholds = $registry[$key]['thresholds'];
		wfDebug( "[AchievementBadges] Check {$stats} is in thresholds " . implode( $thresholds, ', ' ) );
		if ( $stats < $thresholds[0] ) {
			return;
		}
		$numThresholds = count( $thresholds );

		$earned = self::selectLogCount( $key, $user );
		wfDebug( '[AchievementBadges] ' . $user->getName() . " has $earned achieved achievements" );
		if ( $earned == $numThresholds ) {
			return;
		}

		for ( $i = $earned; $i < $numThresholds; $i++ ) {
			if ( $stats >= $thresholds[$i] ) {
				self::achieveInternal( $key, $user, $i );
			} else {
				break;
			}
		}
	}

	/**
	 * @param string $key
	 * @param User $user
	 * @param int|null $index
	 */
	private static function achieveInternal( $key, User $user, $index = null ) {
		wfDebug( '[AchievementBadges] ' . $user->getName() . ' obtained ' . $key .
			( $index ? " with $index" : '' ) );

		$suffixedKey = $key;
		if ( $index && $index > 0 ) {
			$suffixedKey .= (string)( $index + 1 );
		}
		$logEntry = new ManualLogEntry( Constants::LOG_TYPE, $key );
		$logEntry->setPerformer( $user );
		$logEntry->setTarget( SpecialPage::getTitleFor( SpecialAchievements::PAGE_NAME ) );
		$logEntry->setParameters( [
			'4::key' => $key,
			'index' => $index,
		] );
		$logEntry->insert();

		EchoEvent::create( [
			'type' => Constants::EVENT_KEY_EARN,
			'agent' => $user,
			'extra' => [ 'key' => $suffixedKey ],
		] );
	}

	/**
	 * @param string $key
	 * @param User $user
	 */
	private static function selectLogCount( $key, User $user ) {
		$dbr = wfGetDB( DB_REPLICA );
		return $dbr->selectRowCount(
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
