<?php
namespace MediaWiki\Extension\AchievementBadges;

use MediaWiki\Extension\AchievementBadges\Hooks\HookRunner;
use MediaWiki\MediaWikiServices;

return [
	'AchievementBadgesHookRunner' => static function ( MediaWikiServices $services ): HookRunner {
		return new HookRunner( $services->getHookContainer() );
	},
];
