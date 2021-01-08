<?php

namespace MediaWiki\Extension\AchievementBadges\Special;

use BetaFeatures;
use LogEntryBase;
use MediaWiki\Extension\AchievementBadges\Achievement;
use MediaWiki\Extension\AchievementBadges\Constants;
use MediaWiki\Extension\AchievementBadges\Hooks\HookRunner;
use MediaWiki\Logger\LoggerFactory;
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

	/** @var User */
	private $target;

	public function __construct() {
		parent::__construct( self::PAGE_NAME );
		$this->templateParser = new TemplateParser( __DIR__ . '/../templates' );
		$this->logger = LoggerFactory::getInstance( 'AchievementBadges' );
	}

	/** @inheritDoc */
	public function execute( $subPage ) {
		// $this->target should be set before output of the header
		$viewer = $this->getUser();
		$target = $this->target = $this->getObtainerFromSubPage( $subPage ) ?? $viewer;
		parent::execute( $subPage );
		$config = $this->getConfig();
		$out = $this->getOutput();
		$betaConfigEnabled = $config->get( Constants::CONFIG_KEY_ENABLE_BETA_FEATURE );
		$targetEnabledBeta = $betaConfigEnabled && BetaFeatures::isFeatureEnabled( $target,
			Constants::PREF_KEY_ACHIEVEMENT_ENABLE );
		if ( $target->equals( $viewer ) && $betaConfigEnabled ) {
			// An anonymous user can't enable beta features
			$this->requireLogin( 'achievementbadges-anon-text' );
		}
		if ( $betaConfigEnabled && !$targetEnabledBeta ) {
			if ( $target->equals( $viewer ) ) {
				$msg = $this->msg( 'achievementbadges-disabled', $viewer->getName() );
			} else {
				$msg = $this->msg( 'achievementbadges-target-not-disabled-ab', $target->getName() );
			}
			$out->addWikiTextAsInterface( $msg->parse() );
			return;
		}

		$this->addHelpLink( 'Extension:AchievementBadges' );
		$out->addModuleStyles( 'ext.achievementbadges.special.achievements.styles' );
		$allAchvs = $config->get( Constants::CONFIG_KEY_ACHIEVEMENTS );
		uasort( $allAchvs, function ( $a, $b ) {
			$a = $a['priority'] ?? Constants::DEFAULT_ACHIEVEMENT_PRIORITY;
			$b = $b['priority'] ?? Constants::DEFAULT_ACHIEVEMENT_PRIORITY;
			return $a - $b;
		} );

		HookRunner::getRunner()->onSpecialAchievementsBeforeGetEarned( $target );
		$earnedAchvs = $target->isAnon() ? [] : $this->getEarnedAchievementData( $target );
		$this->logger->debug( "User $target achieved " . count( $earnedAchvs ) . ' (' .
			implode( ', ', array_keys( $earnedAchvs ) ) . ") achievements of " . count( $allAchvs ) );

		$dataEarnedAchvs = [];
		$dataNotEarningAchvs = [];
		$lang = $this->getLanguage();
		$showNotEarned = $target->equals( $this->getUser() );

		foreach ( $allAchvs as $key => $info ) {
			$icon = Achievement::getAchievementIcon( $lang, $info['icon'] ?? null );
			if ( $info['type'] == 'stats' ) {
				$max = count( $info['thresholds'] );
				for ( $i = 0; $i < $max; $i++ ) {
					$suffixedKey = "$key-$i";
					$isEarned = array_key_exists( $suffixedKey, $earnedAchvs );
					$timestamp = $earnedAchvs[$suffixedKey] ?? null;
					$new = $this->getDataAchievement( $suffixedKey, $icon, $target, $isEarned, $timestamp );
					if ( $isEarned ) {
						$dataEarnedAchvs[] = $new;
					} elseif ( $showNotEarned ) {
						$dataNotEarningAchvs[] = $new;
					}
				}
			} else {
				$isEarned = array_key_exists( $key, $earnedAchvs );
				$timestamp = $earnedAchvs[$key] ?? null;
				$new = $this->getDataAchievement( $key, $icon, $target, $isEarned, $timestamp );

				if ( $isEarned ) {
					$dataEarnedAchvs[] = $new;
				} elseif ( $showNotEarned ) {
					$dataNotEarningAchvs[] = $new;
				}
			}
		}

		$out->addHTML( $this->templateParser->processTemplate( 'SpecialAchievements', [
			'has-earned-achievements' => (bool)$dataEarnedAchvs,
			'data-earned-achievements' => $dataEarnedAchvs,
			'has-not-earning-achievements' => (bool)$dataNotEarningAchvs,
			'data-not-earning-achievements' => $dataNotEarningAchvs,
			'msg-header-not-earning-achievements' => $this->msg(
				'special-achievements-header-not-earning-achievements', $target->getName() ),
		] ) );
	}

	/**
	 * @param string|null $subPage
	 * @return User|null
	 */
	private function getObtainerFromSubPage( $subPage ) {
		if ( !$subPage ) {
			return null;
		}
		$user = User::newFromName( $subPage );
		if ( !$user ) {
			return null;
		}
		return $user;
	}

	/**
	 * @param string $key
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
			'text-name' => $this->msg( "achievementbadges-achievement-name-$key", $user->getName() )->text(),
		];
		if ( $isEarned ) {
			$data['html-description'] = $this->msg( "achievementbadges-achievement-description-$key", $user->getName() )
				->parse();
			list( $timePeriod, $timestamp ) = Achievement::getHumanTimes( $this->getLanguage(), $user, $timestamp );
			$data['data-time'] = [
				'text-timestamp' => $timestamp,
				'text-time-period' => $timePeriod,
			];
		} else {
			$data['html-hint'] = $this->msg( "achievementbadges-achievement-hint-$key", $user->getName() )->parse();
		}

		return $data;
	}

	/**
	 * @param User $user
	 * @return string[]
	 */
	private function getEarnedAchievementData( User $user ): array {
		$dbr = wfGetDB( DB_REPLICA );
		$query = Achievement::getQueryInfo( $dbr );
		$query['conds'] = array_merge( $query['conds'], [
			'log_actor' => $user->getActorId(),
		] );
		/** @var stdClass $rows */
		$rows = $dbr->select(
			$query['tables'],
			[
				'log_action',
				'log_params',
				'log_timestamp',
			],
			$query['conds'],
			__METHOD__,
			[],
			$query['joins']
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

	/** @inheritDoc */
	public function getDescription() {
		if ( isset( $this->target ) && !$this->getUser()->equals( $this->target ) ) {
			return $this->msg( 'special-achievements-other-user', $this->target->getName() )->escaped();
		}
		return $this->msg( 'special-achievements' )->escaped();
	}

	/** @inheritDoc */
	protected function getGroupName() {
		return 'users';
	}
}
