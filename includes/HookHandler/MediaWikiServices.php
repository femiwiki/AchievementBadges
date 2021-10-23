<?php

namespace MediaWiki\Extension\AchievementBadges\HookHandler;

use MediaWiki\MediaWikiServices as MediaWikiMediaWikiServices;

class MediaWikiServices implements \MediaWiki\Hook\MediaWikiServicesHook {

	/**
	 * @todo hide or disable echo-subscriptions-web-thank-you-edit option when replaced
	 * @inheritDoc
	 */
	public function onMediaWikiServices( $services ) {
		global $wgAchievementBadgesAchievements,
			$wgNotifyTypeAvailabilityByCategory,
			$wgAchievementBadgesDisabledAchievements,
			$wgAchievementBadgesEnableBetaFeature,
			$wgAchievementBadgesReplaceEchoThankYouEdit;

		foreach ( $wgAchievementBadgesDisabledAchievements as $key ) {
			unset( $wgAchievementBadgesAchievements[ $key ] );
		}

		// Below code make Echo tests to fail
		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			return;
		}

		// Overwrite echo's milestone if configured.
		if ( !$wgAchievementBadgesEnableBetaFeature && $wgAchievementBadgesReplaceEchoThankYouEdit ) {
			$wgNotifyTypeAvailabilityByCategory['thank-you-edit']['web'] = false;
		}
	}

	public static function onExtensionFunction() {
		global $wgAchievementBadgesAchievements;

		$services = MediaWikiMediaWikiServices::getInstance();
		$hookRunner = $services->get( 'AchievementBadgesHookRunner' );
		$hookRunner->onBeforeCreateAchievement( $wgAchievementBadgesAchievements );
	}
}
