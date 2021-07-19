<?php

namespace MediaWiki\Extension\AchievementBadges\HookHandler;

use MediaWiki\Extension\AchievementBadges\Constants;
use MediaWiki\MediaWikiServices as MediaWikiMediaWikiServices;

class MediaWikiServices implements \MediaWiki\Hook\MediaWikiServicesHook {

	/**
	 * @todo hide or disable echo-subscriptions-web-thank-you-edit option when replaced
	 * @inheritDoc
	 */
	public function onMediaWikiServices( $services ) {
		global $wgAchievementBadgesAchievements, $wgNotifyTypeAvailabilityByCategory,
			$wgAchievementBadgesDisabledAchievements;

		$services = MediaWikiMediaWikiServices::getInstance();
		$hookRunner = $services->get( 'AchievementBadgesHookRunner' );
		$hookRunner->onBeforeCreateAchievement( $wgAchievementBadgesAchievements );

		foreach ( $wgAchievementBadgesDisabledAchievements as $key ) {
			unset( $wgAchievementBadgesAchievements[ $key ] );
		}

		// Below code make Echo tests to fail
		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			return;
		}

		// Overwrite echo's milestone if configured.
		$config = $services->getMainConfig();
		if ( !$config->get( Constants::CONFIG_KEY_ENABLE_BETA_FEATURE ) &&
			$config->get( Constants::CONFIG_KEY_REPLACE_ECHO_THANK_YOU_EDIT ) ) {
				$wgNotifyTypeAvailabilityByCategory['thank-you-edit']['web'] = false;
		}
	}
}
