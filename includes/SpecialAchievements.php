<?php

namespace MediaWiki\Extension\AchievementBadges;

use BetaFeatures;
use ExtensionRegistry;
use Hooks;
use SpecialPage;
use TemplateParser;

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
	 * @param string $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$output = $this->getOutput();
		$output->addModuleStyles( 'ext.achievementbadges.special.achievements.styles' );

		if ( !$this->isAchievementEnabled() ) {
			$output->addWikiTextAsInterface(
				$this->msg( 'achievementbadges-disabled' )->text()
			);
			return;
		}

		$achvs = [];
		Hooks::run( 'BeforeCreateAchievement', [ &$achvs ] );

		if ( !$achvs ) {
			$output->addWikiTextAsInterface( '제공되는 도전과제가 아직 없습니다.' );
			return;
		}

		foreach ( $achvs as $key => $info ) {
			$achvs['hint'] = $this->msg( 'achievement-hint-' . $key )->parse();
			$achvs['name'] = $this->msg( 'achievement-name-' . $key )->parse();
		}
		$output->addHTML( $this->templateParser->processTemplate( 'Achievements', [
			'data-achievements' => $achvs,
		] ) );
	}

	/**
	 * @return bool
	 */
	public function isAchievementEnabled() {
		$enabledBeta = $this->getConfig()
			->get( Constants::CONFIG_KEY_ACHIEVEMENT_BADGES_ENABLE_BETA_FEATURE );
		if ( !$enabledBeta || !ExtensionRegistry::getInstance()->isLoaded( 'BetaFeatures' ) ) {
			return true;
		}
		return BetaFeatures::isFeatureEnabled( $this->getUser(), Constants::PREF_KEY_ACHIEVEMENT_ENABLE );
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->msg( 'special-achievements' )->text();
	}

	/**
	 * @return string
	 */
	protected function getGroupName() {
		return 'users';
	}
}
