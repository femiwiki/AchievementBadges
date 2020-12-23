<?php

namespace MediaWiki\Extension\AchievementBadges;

use BetaFeatures;
use LogEntryBase;
use MediaWiki\Extension\AchievementBadges\Hooks\HookRunner;
use MediaWiki\Logger\LoggerFactory;
use MWTimestamp;
use Psr\Log\LoggerInterface;
use SpecialPage;
use TemplateParser;
use User;

/**
 * Special page
 *
 * @file
 */

class SpecialAchievements extends SpecialPage {

	public const PAGE_NAME = 'Achievements';

	/** @var TemplateParser */
	private $templateParser;

	/** @var LoggerInterface */
	private $logger;

	public function __construct() {
		parent::__construct( self::PAGE_NAME );
		$this->templateParser = new TemplateParser( __DIR__ . '/templates' );
		$this->logger = LoggerFactory::getInstance( 'AchievementBadges' );
	}

	/**
	 * @inheritDoc
	 */
	public function execute( $subPage ) {
		$this->addHelpLink( 'Extension:AchievementBadges' );

		$user = $this->getUser();

		$config = $this->getConfig();
		$betaConfigEnabled = $config->get( Constants::CONFIG_KEY_ENABLE_BETA_FEATURE );
		$userBetaEnabled = $betaConfigEnabled && BetaFeatures::isFeatureEnabled( $user,
				Constants::PREF_KEY_ACHIEVEMENT_ENABLE );
		if ( $betaConfigEnabled ) {
			// An anonymous user can't enable beta features
			$this->requireLogin( 'achievementbadges-anon-text' );
		}

		parent::execute( $subPage );

		$out = $this->getOutput();
		$out->addModuleStyles( 'ext.achievementbadges.special.achievements.styles' );

		if ( $betaConfigEnabled && !$userBetaEnabled ) {
			$out->addWikiTextAsInterface( $this->msg( 'achievementbadges-disabled' )->text() );
			return;
		}

		$allAchvs = $config->get( Constants::CONFIG_KEY_ACHIEVEMENTS );
		uasort( $allAchvs, function ( $a, $b ) {
			$a = $a['priority'] ?? 1000;
			$b = $b['priority'] ?? 1000;
			return $a - $b;
		} );

		HookRunner::getRunner()->onSpecialAchievementsBeforeGetEarned( $user );
		$earnedAchvs = $user->isAnon() ? [] : $this->getEarnedAchievementData( $user );
		$this->logger->debug( "User $user achieved " . count( $earnedAchvs ) . ' (' .
			implode( ', ', array_keys( $earnedAchvs ) ) . ") achievements of " . count( $allAchvs ) );

		$dataEarnedAchvs = [];
		$dataNotEarningAchvs = [];
		$iconFallback = $config->get( Constants::CONFIG_KEY_ACHIEVEMENT_FALLBACK_ICON );
		if ( $iconFallback === false ) {
			$iconFallback = $config->get( 'ExtensionAssetsPath' ) .
				'/AchievementBadges/images/achievement-icon-fallback.svg';
		}
		foreach ( $allAchvs as $key => $info ) {
			$icon = $this->getAchievementIcon( $info['icon'] ?? $iconFallback );
			if ( $info['type'] == 'stats' ) {
				$max = count( $info['thresholds'] );
				for ( $i = 0; $i < $max; $i++ ) {
					$suffixedKey = "$key-$i";
					$isEarned = array_key_exists( $suffixedKey, $earnedAchvs );
					$timestamp = $earnedAchvs[$suffixedKey] ?? null;
					$new = $this->getDataAchievement( $suffixedKey, $icon, $user, $isEarned, $timestamp );
					if ( $isEarned ) {
						$dataEarnedAchvs[] = $new;
					} else {
						$dataNotEarningAchvs[] = $new;
					}
				}
			} else {
				$isEarned = array_key_exists( $key, $earnedAchvs );
				$timestamp = $earnedAchvs[$key] ?? null;
				$new = $this->getDataAchievement( $key, $icon, $user, $isEarned, $timestamp );

				if ( $isEarned ) {
					$dataEarnedAchvs[] = $new;
				} else {
					$dataNotEarningAchvs[] = $new;
				}
			}
		}

		$out->addHTML( $this->templateParser->processTemplate( 'SpecialAchievements', [
			'bool-earned-achievements' => (bool)$dataEarnedAchvs,
			'data-earned-achievements' => $dataEarnedAchvs,
			'bool-not-earning-achievements' => (bool)$dataNotEarningAchvs,
			'data-not-earning-achievements' => $dataNotEarningAchvs,
			'msg-header-not-earning-achievements' => $this->msg(
				'special-achievements-header-not-earning-achievements' ),
		] ) );
	}

	/**
	 * @param string|array $path
	 * @return string
	 */
	private function getAchievementIcon( $path ) {
		if ( is_array( $path ) ) {
			// The screenshot parameter is either a string with a filename
			// or an array that specifies a screenshot for each language,
			// and default screenshots for rtl and ltr languages
			$language = $this->getLanguage();
			$langCode = $language->getCode();

			if ( array_key_exists( $langCode, $path ) ) {
				$path = $path[$langCode];
			} else {
				$path = $path[$language->getDir()];
			}
		}

		return $path;
	}

	/**
	 * @param array $key
	 * @param string $icon
	 * @param user $user
	 * @param bool $isEarned
	 * @param string|null $timestamp
	 * @return array
	 */
	private function getDataAchievement( $key, $icon, User $user, $isEarned, $timestamp = null ) {
		$data = [
			'text-type' => $key,
			'text-class' => implode( ' ', [
				'achievement',
				$isEarned ? 'earned' : 'not-earning',
			] ),
			'text-icon' => $icon,
			'text-name' => $this->msg( "achievement-name-$key", $user->getName() )->parse(),
		];
		if ( $isEarned ) {
			$data['html-description'] = $this->msg( "achievement-description-$key", $user->getName() )
				->parse();
			$timestamp = MWTimestamp::getInstance( $timestamp );
			$language = $this->getLanguage();
			$d = $language->userDate( $timestamp, $user );
			$t = $language->userTime( $timestamp, $user );
			$s = ' ' . $this->msg( 'achievement-earned-at', $d, $t )->parse();
			$data['data-time'] = [
				'text-time-period' => $this->getLanguage()->getHumanTimestamp(
					$timestamp,
					MWTimestamp::getInstance(),
					$user
				),
				'text-timestamp' => $s,
			];
		} else {
			$data['html-hint'] = $this->msg( "achievement-hint-$key", $user->getName() )->parse();
		}

		return $data;
	}

	/**
	 * @param User $user
	 * @return string[]
	 */
	private function getEarnedAchievementData( User $user ): array {
		$dbr = wfGetDB( DB_REPLICA );

		/** @var stdClass $rows */
		$rows = $dbr->select(
			[ 'logging', 'actor' ],
			[
				'log_action',
				'log_params',
				'log_timestamp',
			],
			[
				'log_type' => Constants::LOG_TYPE,
				'actor_user' => $user->getId(),
				'log_deleted = 0',
			],
			__METHOD__,
			[],
			[
				'actor' => [ 'JOIN', 'actor_id = log_actor' ],
			]
		);

		$achvs = [];
		foreach ( $rows as $row ) {
			$params = LogEntryBase::extractParams( $row->log_params );
			// $this->logger->debug( 'A log is founded with param: ' .
			// str_replace( "\n", ' ', print_r( $params, true ) ) );
			if ( isset( $params['5::index'] ) ) {
				$key = $row->log_action;
				$index = $params['5::index'];
				$achvs["$key-$index"] = $row->log_timestamp;
			} else {
				$achvs[$row->log_action] = $row->log_timestamp;
			}
		}
		return $achvs;
	}

	/**
	 * @inheritDoc
	 */
	public function getDescription() {
		return $this->msg( 'special-achievements' )->text();
	}

	/**
	 * @inheritDoc
	 */
	protected function getGroupName() {
		return 'users';
	}
}
