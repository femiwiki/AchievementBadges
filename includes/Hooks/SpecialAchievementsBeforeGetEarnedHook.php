<?php

namespace MediaWiki\Extension\AchievementBadges\Hooks;

use User;

interface SpecialAchievementsBeforeGetEarnedHook {
	/**
	 * Hook runner for the `SpecialAchievementsBeforeGetEarned` hook
	 *
	 * @param User $user
	 */
	public function onSpecialAchievementsBeforeGetEarned( User $user );
}
