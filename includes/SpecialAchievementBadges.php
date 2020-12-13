<?php

namespace MediaWiki\Extension\AchievementBadges;

use BetaFeatures;
use SpecialPage;

/**
 * Special page
 *
 * @file
 */

class SpecialAchievementBadges extends SpecialPage {

	public function __construct() {
		parent::__construct( 'AchievementBadges' );
	}

	/**
	 * @param string $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$output = $this->getOutput();

		if ( !$this->isAchievementEnabled() ) {
			$output->addWikiTextAsInterface(
				$this->msg( 'achievementbadges-disabled' )->text()
			);
			return;
		}

		$output->addWikiTextAsInterface( '아직 제공되는 도전과제가 없습니다.' );
	}

	/**
	 * @return bool
	 */
	public function isAchievementEnabled() {
		return BetaFeatures::isFeatureEnabled( $this->getUser(), Hooks::ACHIEVEMENT_ENABLE );
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->msg( 'special-achievementbadges' )->text();
	}

	/**
	 * @return string
	 */
	protected function getGroupName() {
		return 'users';
	}
}
