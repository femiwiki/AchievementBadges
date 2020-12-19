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
	 * @inheritDoc
	 */
	public function execute( $subPage ) {
		$this->addHelpLink( 'Extension:AchievementBadges' );

		$user = $this->getUser();

		$betaConfigEnabled = $this->getConfig()
			->get( Constants::CONFIG_KEY_ACHIEVEMENT_BADGES_ENABLE_BETA_FEATURE );
		$userBetaEnabled = $betaConfigEnabled && BetaFeatures::isFeatureEnabled( $user,
				Constants::PREF_KEY_ACHIEVEMENT_ENABLE );
		if ( $betaConfigEnabled ) {
			// An anonymous user can't enable beta features
			$this->requireLogin( 'achievementbadges-anon-text' );
		}

		parent::execute( $subPage );

		$out = $this->getOutput();
		$out->addModuleStyles( 'ext.achievementbadges.special.achievements.styles' );

		if ( !$userBetaEnabled ) {
			$out->addWikiTextAsInterface( $this->msg( 'achievementbadges-disabled' )->text() );
			return;
		}

		$allAchvs = [];
		Hooks::run( 'BeforeCreateAchievement', [ &$allAchvs ] );

		$earnedAchvs = $user->isAnon() ? [] : $this->getEarnedAchievements( $user );

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
				// @TODO send gender information as param
				'text-name' => $this->msg( "achievement-name-$key" )->parse(),
			];

			if ( $isEarned ) {
				// @TODO send gender information as param
				$new['html-description'] = $this->msg( "achievement-description-$key" )->parse();
				$dataEarnedAchvs[] = $new;
			} else {
				// @TODO send gender information as param
				$new['html-hint'] = $this->msg( "achievement-hint-$key" )->parse();
				$dataNotEarningAchvs[] = $new;
			}
		}

		$out->addHTML( $this->templateParser->processTemplate( 'SpecialAchievements', [
			'data-earned-achievements' => $dataEarnedAchvs,
			'data-not-earning-achievements' => $dataNotEarningAchvs,
			'msg-header-not-earning-achievements' => $this->msg(
				'special-achievements-header-not-earning-achievements' ),
		] ) );
	}

	/**
	 * @param User $user
	 * @return string[]
	 */
	protected function getEarnedAchievements( User $user ): array {
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
