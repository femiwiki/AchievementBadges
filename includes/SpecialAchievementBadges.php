<?php

namespace MediaWiki\Extension\AchievementBadges;

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

	public function execute( $subPage ) {
		parent::execute( $subPage );

		$output = $this->getOutput();
		$output->addWikiTextAsInterface( 'Hello, world' );

	}

	public function getDescription() {
		return $this->msg( 'special-achievementbadges' )->text();
	}

	protected function getGroupName() {
		return 'users';
	}
}
