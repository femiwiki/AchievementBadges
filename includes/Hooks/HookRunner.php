<?php

namespace MediaWiki\Extension\AchievementBadges\Hooks;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;
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

	/**
	 * Convenience getter for static contexts
	 *
	 * See also core's Hooks::runner
	 *
	 * @return HookRunner
	 */
	public static function getRunner() : HookRunner {
		return new HookRunner(
			MediaWikiServices::getInstance()->getHookContainer()
		);
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
