<?php

namespace MediaWiki\Extension\AchievementBadges;

use BetaFeatures;
use MediaWiki\Extension\AchievementBadges\Hooks\HookRunner;
use LogEntryBase;
use SpecialPage;
use TemplateParser;
use User;

/**
 * Special page
 *
 * @file
 */

class SpecialAchievements extends SpecialPage {

	public const PAGE_NAME = 'Achievements';

	/** @var TemplateParser */
	private $templateParser;

	public function __construct() {
		parent::__construct( self::PAGE_NAME );
		$this->templateParser = new TemplateParser( __DIR__ . '/templates' );
	}

	/**
	 * @inheritDoc
	 */
	public function execute( $subPage ) {
		$this->addHelpLink( 'Extension:AchievementBadges' );

		$user = $this->getUser();

		$config = $this->getConfig();
		$betaConfigEnabled = $config->get( Constants::CONFIG_KEY_ENABLE_BETA_FEATURE );
		$userBetaEnabled = $betaConfigEnabled && BetaFeatures::isFeatureEnabled( $user,
				Constants::PREF_KEY_ACHIEVEMENT_ENABLE );
		if ( $betaConfigEnabled ) {
			// An anonymous user can't enable beta features
			$this->requireLogin( 'achievementbadges-anon-text' );
		}

		parent::execute( $subPage );

		$out = $this->getOutput();
		$out->addModuleStyles( 'ext.achievementbadges.special.achievements.styles' );

		if ( $betaConfigEnabled && !$userBetaEnabled ) {
			$out->addWikiTextAsInterface( $this->msg( 'achievementbadges-disabled' )->text() );
			return;
		}

		$allAchvs = $config->get( Constants::CONFIG_KEY_ACHIEVEMENTS );
		uasort( $allAchvs, function ( $a, $b ) {
			return $a['priority'] - $b['priority'];
		} );

		HookRunner::getRunner()->onSpecialAchievementsBeforeGetEarned( $user );
		$earnedAchvs = $user->isAnon() ? [] : $this->getEarnedAchievementNames( $user );

		$dataEarnedAchvs = [];
		$dataNotEarningAchvs = [];
		$iconFallback = $config->get( Constants::CONFIG_KEY_ACHIEVEMENT_FALLBACK_ICON );
		if ( $iconFallback === false ) {
			$iconFallback = $config->get( 'ExtensionAssetsPath' ) .
				'/AchievementBadges/images/achievement-icon-fallback.svg';
		}
		foreach ( $allAchvs as $key => $info ) {
			$icon = $this->getAchievementIcon( $info['icon'] ?? $iconFallback );
			if ( $info['type'] == 'stats' ) {
				$max = count( $info['thresholds'] );
				for ( $i = 0; $i < $max; $i++ ) {
					$suffixedKey = $key . (string)( $i + 1 );
					$isEarned = in_array( $suffixedKey, $earnedAchvs );
					$new = $this->getDataAchievement( $suffixedKey, $icon, $user, $isEarned );
					if ( $isEarned ) {
						$dataEarnedAchvs[] = $new;
					} else {
						$dataNotEarningAchvs[] = $new;
					}
				}
			} else {
				$isEarned = in_array( $key, $earnedAchvs );
				$new = $this->getDataAchievement( $key, $icon, $user, $isEarned );

				if ( $isEarned ) {
					$dataEarnedAchvs[] = $new;
				} else {
					$dataNotEarningAchvs[] = $new;
				}
			}
		}

		$out->addHTML( $this->templateParser->processTemplate( 'SpecialAchievements', [
			'bool-earned-achievements' => (bool)$dataEarnedAchvs,
			'data-earned-achievements' => $dataEarnedAchvs,
			'bool-not-earning-achievements' => (bool)$dataNotEarningAchvs,
			'data-not-earning-achievements' => $dataNotEarningAchvs,
			'msg-header-not-earning-achievements' => $this->msg(
				'special-achievements-header-not-earning-achievements' ),
		] ) );
	}

	/**
	 * @param string|array $path
	 */
	private function getAchievementIcon( $path ) {
		if ( is_array( $path ) ) {
			// The screenshot parameter is either a string with a filename
			// or an array that specifies a screenshot for each language,
			// and default screenshots for rtl and ltr languages
			$language = $this->getLanguage();
			$langCode = $language->getCode();

			if ( array_key_exists( $langCode, $path ) ) {
				$path = $path[$langCode];
			} else {
				$path = $path[$language->getDir()];
			}
		}

		return $path;
	}

	/**
	 * @param array $key
	 * @param string $icon
	 * @param user $user
	 * @param bool $isEarned
	 * @return array
	 */
	private function getDataAchievement( $key, $icon, User $user, $isEarned ) {
		$data = [
			'text-type' => $key,
			'text-class' => implode( ' ', [
				'achievement',
				$isEarned ? 'earned' : 'not-earning',
			] ),
			'text-icon' => $icon,
			'text-name' => $this->msg( "achievement-name-$key", $user->getName() )->parse(),
		];
		if ( $isEarned ) {
			$data['html-description'] = $this->msg( "achievement-description-$key", $user->getName() )
				->parse();
		} else {
			$data['html-hint'] = $this->msg( "achievement-hint-$key", $user->getName() )->parse();
		}

		return $data;
	}

	/**
	 * @param User $user
	 * @return string[]
	 */
	private function getEarnedAchievementNames( User $user ): array {
		$dbr = wfGetDB( DB_REPLICA );

		/** @var stdClass $rows */
		$rows = $dbr->select(
			[ 'logging', 'actor' ],
			[
				'log_action',
				'log_params',
			],
			[
				'log_type' => Constants::LOG_TYPE,
				'actor_user' => $user->getId(),
			],
			__METHOD__,
			[],
			[
				'actor' => [ 'JOIN', 'actor_id = log_actor' ],
			]
		);

		$achvs = [];
		foreach ( $rows as $row ) {
			$params = LogEntryBase::extractParams( $row->log_params );
			if ( isset( $params['index'] ) ) {
				$achvs[] = $row->log_action . ( $params['index'] + 1 );
			} else {
				$achvs[] = $row->log_action;
			}
		}
		return $achvs;
	}

	/**
	 * @inheritDoc
	 */
	public function getDescription() {
		return $this->msg( 'special-achievements' )->text();
	}

	/**
	 * @inheritDoc
	 */
	protected function getGroupName() {
		return 'users';
	}
}
