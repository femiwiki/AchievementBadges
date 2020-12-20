<?php

namespace MediaWiki\Extension\AchievementBadges;

use BetaFeatures;
use Hooks;
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

	public function __construct() {
		parent::__construct( self::PAGE_NAME );
		$this->templateParser = new TemplateParser( __DIR__ . '/templates' );
	}

	/**
	 * @todo Ship gender information to messages as param.
	 * @inheritDoc
	 */
	public function execute( $subPage ) {
		$this->addHelpLink( 'Extension:AchievementBadges' );

		$user = $this->getUser();

		$betaConfigEnabled = $this->getConfig()->get( Constants::CONFIG_KEY_ENABLE_BETA_FEATURE );
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

		$allAchvs = $this->getConfig()->get( Constants::CONFIG_KEY_ACHIEVEMENT_BADGES_ACHIEVEMENTS );
		uasort( $allAchvs, function ( $a, $b ) {
			return $a['priority'] - $b['priority'];
		} );

		Hooks::run( 'SpecialAchievementsBeforeGetEarned', [ $user ] );
		$earnedAchvs = $user->isAnon() ? [] : $this->getEarnedAchievementNames( $user );

		$dataEarnedAchvs = [];
		$dataNotEarningAchvs = [];
		foreach ( $allAchvs as $key => $info ) {
			$isEarned = in_array( $key, $earnedAchvs );
			$new = [
				'text-type' => $key,
				'class' => [
					'achievement',
					$isEarned ? 'earned' : 'not-earning',
				],
				'text-name' => $this->msg( "achievement-name-$key" )->parse(),
			];

			if ( $isEarned ) {
				$new['html-description'] = $this->msg( "achievement-description-$key" )->parse();
				$dataEarnedAchvs[] = $new;
			} else {
				$new['html-hint'] = $this->msg( "achievement-hint-$key" )->parse();
				$dataNotEarningAchvs[] = $new;
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
	 * @param User $user
	 * @return string[]
	 */
	protected function getEarnedAchievementNames( User $user ): array {
		$dbr = wfGetDB( DB_REPLICA );

		/** @var stdClass $rows */
		$rows = $dbr->select(
			[ 'logging', 'actor' ],
			[
				'log_action',
			],
			[
				'log_type' => Constants::LOG_TYPE,
				'actor_user' => $user->getId(),
			],
			__METHOD__,
			[],
			[
				'actor' => [ 'JOIN', 'actor_id = log_actor' ],
			]
		);

		$achvs = [];
		foreach ( $rows as $row ) {
			$achvs[] = $row->log_action;
		}
		return $achvs;
	}

	/**
	 * @param array $achievements
	 * @param User $user
	 */
	public static function recheckAchievements( array $achievements, User $user ) {
		foreach ( $achievements as $key => $achv ) {
			$callable = $achv['rechecker'];
			if ( is_callable( $callable ) &&
				$callable( $user ) ) {
				Achievement::achieve( [ 'key' => $key, 'user' => $user ] );
			}
		}
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
