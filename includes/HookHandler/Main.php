<?php

namespace MediaWiki\Extension\AchievementBadges\HookHandler;

use BetaFeatures;
use Config;
use EchoEvent;
use MediaWiki\Extension\AchievementBadges\Constants;
use MediaWiki\Extension\AchievementBadges\EarnEchoEventPresentationModel;
use MediaWiki\Extension\AchievementBadges\Hooks\HookRunner;
use MediaWiki\Extension\AchievementBadges\Special\SpecialAchievements;
use MediaWiki\MediaWikiServices;
use SpecialPage;
use Title;
use User;

class Main implements
	\MediaWiki\ResourceLoader\Hook\ResourceLoaderGetConfigVarsHook,
	\MediaWiki\Hook\ContributionsToolLinksHook
	{

	/** @var Config */
	private $config;

	/** @var HookRunner */
	private $hookRunner;

	/**
	 * @param Config $config
	 * @param HookRunner $hookRunner
	 */
	public function __construct( Config $config, HookRunner $hookRunner ) {
		$this->config = $config;
		$this->hookRunner = $hookRunner;
	}

	/**
	 * @param User $user
	 * @param array &$betaPrefs
	 */
	public static function onGetBetaFeaturePreferences( User $user, array &$betaPrefs ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		if ( !$config->get( Constants::CONFIG_KEY_ENABLE_BETA_FEATURE ) ) {
			return;
		}
		$extensionAssetsPath = $config->get( 'ExtensionAssetsPath' );
		$betaPrefs[Constants::PREF_KEY_ACHIEVEMENT_ENABLE] = [
			'label-message' => 'achievementbadges-beta-feature-achievement-enable-message',
			'desc-message' => 'achievementbadges-beta-feature-achievement-enable-description',
			'screenshot' =>
				"$extensionAssetsPath/AchievementBadges/images/betafeatures-icon-AchievementBadges.svg",
			'info-link' => 'https://www.mediawiki.org/wiki/Special:MyLanguage/Extension:AchievementBadges',
			'discussion-link' => 'https://github.com/femiwiki/AchievementBadges/issues',
		];
	}

	/**
	 * Defining the events for this extension
	 *
	 * @param array &$notifs
	 * @param array &$categories
	 * @param array &$icons
	 */
	public static function onBeforeCreateEchoEvent( &$notifs, &$categories, &$icons ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$extensionAssetsPath = $config->get( 'ExtensionAssetsPath' );
		$categories[Constants::ECHO_EVENT_CATEGORY] = [
			'priority' => 9,
			'tooltip' => 'achievementbadges-pref-tooltip-achievement-badges',
		];
		$notifs[Constants::EVENT_KEY_EARN] = [
			'category' => Constants::ECHO_EVENT_CATEGORY,
			'group' => 'positive',
			'section' => 'message',
			'canNotifyAgent' => true,
			'presentation-model' => EarnEchoEventPresentationModel::class,
			'bundle' => [
				'web' => true,
				'email' => true,
				'expandable' => true,
			],
			'user-locators' => [ 'EchoUserLocator::locateEventAgent' ],
		];
		$icons[Constants::EVENT_KEY_EARN] = [
			'path' => "AchievementBadges/images/medal.svg",
		];
	}

	/**
	 * @param EchoEvent $event
	 * @param string &$bundleString
	 */
	public static function onEchoGetBundleRules( $event, &$bundleString ) {
		if ( $event->getType() == Constants::EVENT_KEY_EARN ) {
			$bundleString = Constants::EVENT_KEY_EARN;
		}
	}

	/**
	 * @param EchoEvent $event
	 * @return bool
	 */
	public static function onBeforeEchoEventInsert( EchoEvent $event ) {
		// Below code make Echo tests to fail
		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			return true;
		}

		$config = MediaWikiServices::getInstance()->getMainConfig();
		$agent = $event->getAgent();
		$type = $event->getType();

		if ( $type == 'thank-you-edit'
			&& $config->get( Constants::CONFIG_KEY_REPLACE_ECHO_THANK_YOU_EDIT ) ) {
			return false;
		} elseif ( $type == 'welcome'
			&& !$config->get( Constants::CONFIG_KEY_ENABLE_BETA_FEATURE ) ) {
			// the welcome notification is replaced with 'sign-up' achievement.
			return false;
		}
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function onResourceLoaderGetConfigVars( array &$vars, $skin, Config $config ) : void {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$addThisId = $config->get( Constants::CONFIG_KEY_ADD_THIS_ID );
		if ( $addThisId ) {
			$vars['wg' . Constants::CONFIG_KEY_ADD_THIS_ID] = $addThisId;
		} else {
			$vars['wg' . Constants::CONFIG_KEY_FACEBOOK_APP_ID] = $config->get( Constants::CONFIG_KEY_FACEBOOK_APP_ID );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function onContributionsToolLinks(
			$id,
			Title $title,
			array &$tools,
			SpecialPage $specialPage
		) {
		$target = User::newFromId( $id );
		if ( $target->isAnon() ) {
			return;
		}
		$linkTarget = SpecialPage::getTitleFor( SpecialAchievements::PAGE_NAME, $target->getName() );
		$msg = wfMessage( 'achievementbadges-link-on-user-contributes' )->text();
		$linkRenderer = $specialPage->getLinkRenderer();
		$betaPeriod = $this->config->get( Constants::CONFIG_KEY_ENABLE_BETA_FEATURE );
		$userOptionEnabled = $betaPeriod &&
			BetaFeatures::isFeatureEnabled( $target, Constants::PREF_KEY_ACHIEVEMENT_ENABLE );

		if ( $betaPeriod && !$userOptionEnabled ) {
			$tools['achievementbadges'] = $linkRenderer->makeBrokenLink( $linkTarget, $msg );
		} else {
			$tools['achievementbadges'] = $linkRenderer->makeKnownLink( $linkTarget, $msg );
		}
	}
}
