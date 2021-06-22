<?php

namespace MediaWiki\Extension\AchievementBadges\Hooks;

use MediaWiki\HookContainer\HookContainer;
use User;

class HookRunner implements
	BeforeCreateAchievementHook,
	SpecialAchievementsBeforeGetEarnedHook
{

	/** @var HookContainer */
	private $hookContainer;

	/**
	 * @param HookContainer $hookContainer
	 */
	public function __construct( HookContainer $hookContainer ) {
		$this->hookContainer = $hookContainer;
	}

	// phpcs:disable MediaWiki.Commenting.FunctionComment.MissingDocumentationPublic
	public function onBeforeCreateAchievement( array &$achievements ) {
		return $this->hookContainer->run(
			'BeforeCreateAchievement',
			[ &$achievements ]
		);
	}

	public function onSpecialAchievementsBeforeGetEarned( User $user ) {
		return $this->hookContainer->run(
			'SpecialAchievementsBeforeGetEarned',
			[ $user ]
		);
	}

	// phpcs:enable
}
