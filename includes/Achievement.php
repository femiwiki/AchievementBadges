<?php

namespace MediaWiki\Extension\AchievementBadges;

use BetaFeatures;
use EchoEvent;
use FatalError;
use LogPage;
use ManualLogEntry;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MWException;
use Psr\Log\LoggerInterface;
use SpecialPage;
use User;

class Achievement {
	/**
	 * @var LoggerInterface
	 */
	private static $logger = null;

	/**
	 * You should not call the constructor.
	 * @throws FatalError
	 */
	public function __construct() {
		throw new FatalError( "Cannot construct a utility class." );
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
		$registry = $config->get( Constants::CONFIG_KEY_ACHIEVEMENTS );

		if ( !isset( $registry[$key] ) ) {
			throw new MWException( "Unknown achievement key: {$key}" );
		}
		$registry[$key]['type'] = $registry[$key]['type'] ?? 'instant';
		if ( $registry[$key]['type'] !== 'instant' ) {
			throw new MWException(
				"$__METHOD__ is called with only an instant achievement, but $key is not" );
		}

		$count = self::selectLogCount( $key, $user );
		if ( $count > 0 ) {
			self::getLogger()->debug( "User $user satisfied the condition of achievement $key," .
				"but ignored because already achieved it." );
			// The achievement was earned already.
			return;
		} else {
			self::getLogger()->debug( "User $user satisfied the condition of achievement $key" );
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
			self::getLogger()->debug( 'The user cannot use AchievementBadges' );
			return;
		}
		$key = $info['key'];
		$stats = $info['stats'];
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$registry = $config->get( Constants::CONFIG_KEY_ACHIEVEMENTS );
		if ( !isset( $registry[$key] ) ) {
			throw new MWException( "Unknown achievement key: {$key}" );
		}
		if ( $registry[$key]['type'] !== 'stats' ) {
			throw new MWException( "Only instant achievement can be called by " . __METHOD__ );
		}
		self::getLogger()->debug( $user->getName() . " has a stats with a value $stats at $key" );
		$thresholds = $registry[$key]['thresholds'];
		if ( $stats < $thresholds[0] ) {
			return;
		}
		$numThresholds = count( $thresholds );

		$earned = self::selectLogCount( $key, $user );
		self::getLogger()->debug( $user->getName() . " has $earned achieved achievements at $key" );

		for ( $i = $earned; $i < $numThresholds; $i++ ) {
			if ( $stats >= $thresholds[$i] ) {
				self::getLogger()->debug( $user->getName() . " exceeds {$i}-index threshold of $key" );
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
		$logEntry = new ManualLogEntry( Constants::LOG_TYPE, $key );
		$logEntry->setPerformer( $user );
		$logEntry->setTarget( SpecialPage::getTitleFor( SpecialAchievements::PAGE_NAME ) );
		$params = [
			'4::key' => $key,
		];
		if ( $index !== null ) {
			$params['5::index'] = $index;
		}
		$logEntry->setParameters( $params );

		$logEntry->insert();
		self::getLogger()->debug( 'A log is inserted with param: ' .
			str_replace( "\n", ' ', print_r( $params, true ) ) );

		$suffixedKey = $key;
		if ( $index !== null ) {
			$suffixedKey .= "-$index";
		}
		EchoEvent::create( [
			'type' => Constants::EVENT_KEY_EARN,
			'agent' => $user,
			'extra' => [ 'key' => $suffixedKey ],
		] );
		self::getLogger()->debug( "A echo event of achievement $suffixedKey is created for $user " );
	}

	/**
	 * @param string $key
	 * @param User $user
	 * @return int
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
				$dbr->bitAnd( 'log_deleted', LogPage::DELETED_ACTION | LogPage::DELETED_USER ) . ' = 0 ',
			],
			__METHOD__,
			[ 'DISTINCT' ],
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
		if ( $user->isSystemUser() ) {
			return false;
		}
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
			// can use it.
			return true;
		}
		return false;
	}

	/**
	 * @return LoggerInterface
	 */
	private static function getLogger(): LoggerInterface {
		if ( !self::$logger ) {
			self::$logger = LoggerFactory::getInstance( 'AchievementBadges' );
		}
		return self::$logger;
	}
}
