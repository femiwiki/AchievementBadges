<?php

namespace MediaWiki\Extension\AchievementBadges;

use User;

class AchievementRechecker {
	/**
	 * @param User $user
	 * @return bool
	 */
	public static function checkAlwaysTrue( User $user ) {
		return true;
	}
}
