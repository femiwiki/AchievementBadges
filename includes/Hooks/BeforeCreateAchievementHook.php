<?php

namespace MediaWiki\Extension\AchievementBadges\Hooks;

interface BeforeCreateAchievementHook {
	/**
	 * Hook runner for the `BeforeCreateAchievement` hook
	 *
	 * @param array &$achievements
	 */
	public function onBeforeCreateAchievement( array &$achievements );
}
