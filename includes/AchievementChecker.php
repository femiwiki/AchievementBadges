<?php

namespace MediaWiki\Extension\AchievementBadges;

use User;

class AchievementChecker {
	/**
	 * @param User $user
	 * @return bool
	 */
	public static function checkAlwaysTrue( User $user ) {
		return true;
	}
}
