<?php

namespace MediaWiki\Extension\AchievementBadges\HookHandler;

use Config;
use ExtensionRegistry;
use MediaWiki\Extension\AchievementBadges\Achievement;
use MediaWiki\Extension\AchievementBadges\Constants;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\RevisionStore;
use MWTimestamp;
use User;
use Wikimedia\Rdbms\ILoadBalancer;

class AchievementRegister implements
	\MediaWiki\Auth\Hook\LocalUserCreatedHook,
	\MediaWiki\Extension\AchievementBadges\Hooks\BeforeCreateAchievementHook,
	\MediaWiki\Extension\AchievementBadges\Hooks\SpecialAchievementsBeforeGetEarnedHook,
	\MediaWiki\Storage\Hook\PageSaveCompleteHook,
	\MediaWiki\User\Hook\UserSaveSettingsHook
	{

		/**
		 * @var Config
		 */
		private $config;

		/**
		 * @var ILoadBalancer
		 */
		private $mDb;

		/**
		 * @var RevisionStore
		 */
		private $revisionStore;

	/**
	 * @param Config $config
	 * @param ILoadBalancer $DBLoadBalancer
	 * @param RevisionStore $revisionStore
	 */
	public function __construct(
		Config $config,
		ILoadBalancer $DBLoadBalancer,
		RevisionStore $revisionStore
	) {
		$this->config = $config;
		$this->mDb = $DBLoadBalancer->getMaintenanceConnectionRef( DB_REPLICA );
		$this->revisionStore = $revisionStore;
	}

	private const WEEKDAYS = [
		Constants::ACHV_KEY_CONTRIBS_SUNDAY,
		Constants::ACHV_KEY_CONTRIBS_MONDAY,
		Constants::ACHV_KEY_CONTRIBS_TUESDAY,
		Constants::ACHV_KEY_CONTRIBS_WEDNESDAY,
		Constants::ACHV_KEY_CONTRIBS_THURSDAY,
		Constants::ACHV_KEY_CONTRIBS_FRIDAY,
		Constants::ACHV_KEY_CONTRIBS_SATURDAY,
	];

	/**
	 * @inheritDoc
	 */
	public function onBeforeCreateAchievement( array &$achievements ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		if ( $config->get( Constants::CONFIG_KEY_ENABLE_BETA_FEATURE )
			&& ExtensionRegistry::getInstance()->isLoaded( 'BetaFeatures' ) ) {
			$achievements[Constants::ACHV_KEY_ENABLE_ACHIEVEMENT_BADGES] = [
				'type' => 'instant',
				'priority' => 0,
			];
		} else {
			$achievements[Constants::ACHV_KEY_SIGN_UP] = [
				'type' => 'instant',
				'priority' => 0,
			];
		}
		if ( $config->get( Constants::CONFIG_KEY_REPLACE_ECHO_THANK_YOU_EDIT ) ) {
			$achievements[Constants::ACHV_KEY_EDIT_PAGE] = [
				'type' => 'stats',
				'thresholds' => [ 1, 10, 100, 1000, 10000 ],
				'priority' => 200,
			];
		}
		$achievements[Constants::ACHV_KEY_LONG_USER_PAGE] = [
			'type' => 'instant',
			'priority' => 100,
		];
		$achievements[Constants::ACHV_KEY_EDIT_SIZE] = [
			'type' => 'stats',
			'thresholds' => [ 1000, 5000, 10000, 50000, 100000 ],
			'priority' => 300,
		];
		$achievements[Constants::ACHV_KEY_CREATE_PAGE] = [
			'type' => 'stats',
			'thresholds' => [ 1, 5, 30, 100, 300, 1000 ],
			'priority' => 300,
		];
		foreach ( self::WEEKDAYS as $weekday ) {
			$achievements[$weekday] = [
				'type' => 'instant',
				'priority' => 500,
			];
		}
	}

	/**
	 * @inheritDoc
	 */
	public function onSpecialAchievementsBeforeGetEarned( User $user ) {
		if ( $user->isAnon() ) {
			return;
		}
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$registry = $config->get( Constants::CONFIG_KEY_ACHIEVEMENTS );
		if ( !isset( $registry[Constants::ACHV_KEY_SIGN_UP] ) ) {
			return;
		}
		Achievement::achieve( [ 'key' => Constants::ACHV_KEY_SIGN_UP, 'user' => $user ] );
	}

	/**
	 * @inheritDoc
	 */
	public function onLocalUserCreated( $user, $autocreated ) {
		if ( $autocreated ) {
			return;
		}
		$config = $this->config;
		$registry = $config->get( Constants::CONFIG_KEY_ACHIEVEMENTS );
		if ( !isset( $registry[Constants::ACHV_KEY_SIGN_UP] ) ) {
			return;
		}
		Achievement::achieve( [ 'key' => Constants::ACHV_KEY_SIGN_UP, 'user' => $user ] );
	}

	/**
	 * @inheritDoc
	 */
	public function onUserSaveSettings( $user ) {
		if ( !$this->config->get( Constants::CONFIG_KEY_ENABLE_BETA_FEATURE ) ) {
			return;
		}
		if ( $user->getOption( Constants::PREF_KEY_ACHIEVEMENT_ENABLE ) ) {
			Achievement::achieve( [
				'key' => Constants::ACHV_KEY_ENABLE_ACHIEVEMENT_BADGES,
				'user' => $user,
			] );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function onPageSaveComplete(
		$wikiPage,
		$user,
		$summary,
		$flags,
		$revisionRecord,
		$editResult
	) {
		if ( $editResult->isNullEdit() ) {
			LoggerFactory::getInstance( 'AchievementBadges' )->debug( 'null edit is ignored.' );
			return;
		} elseif ( $editResult->isRevert() ) {
			LoggerFactory::getInstance( 'AchievementBadges' )->debug( 'revert is ignored.' );
			return;
		}
		$user = User::newFromIdentity( $user );
		if ( $user->isAnon() ) {
			return;
		}

		Achievement::sendStats( [
			'key' => Constants::ACHV_KEY_EDIT_PAGE,
			'user' => $user,
			'stats' => $user->getEditCount(),
		] );
		if ( $wikiPage->getTitle()->equals( $user->getUserPage() ) &&
			$revisionRecord->getSize() > 500 ) {
				Achievement::achieve( [
					'key' => Constants::ACHV_KEY_LONG_USER_PAGE,
					'user' => $user,
				] );
		}
		// Set user's timezone!
		$userTimestamp = MWTimestamp::getInstance( $revisionRecord->getTimestamp() );
		$userTimestamp->offsetForUser( $user );
		$weekday = (int)$userTimestamp->format( 'w' );
		Achievement::achieve( [
			'key' => self::WEEKDAYS[$weekday],
			'user' => $user,
		] );

		if ( $editResult->isNew() ) {
			$query = $this->revisionStore->getQueryInfo();
			$newPages = $this->mDb->selectRowCount(
				$query['tables'],
				'*',
				[
					'actor_user' => $user->getId(),
					'rev_parent_id' => 0,
					$this->mDb->bitAnd(
						'rev_deleted', RevisionRecord::DELETED_USER
						) . ' != ' . RevisionRecord::DELETED_USER,
				],
				__METHOD__,
				[
					'LIMIT' => 1000,
				],
				$query['joins'],
			);
			Achievement::sendStats( [
				'key' => Constants::ACHV_KEY_CREATE_PAGE,
				'user' => $user,
				'stats' => $newPages,
			] );
			Achievement::sendStats( [
				'key' => Constants::ACHV_KEY_EDIT_SIZE,
				'user' => $user,
				'stats' => $revisionRecord->getSize(),
			] );
		} else {
			$parentRevision = $revisionRecord->getParentId();
			$parentRevision = $this->revisionStore->getRevisionById( $parentRevision );
			$diff = $revisionRecord->getSize() - $parentRevision->getSize();
			if ( $diff > 0 ) {
				Achievement::sendStats( [
					'key' => Constants::ACHV_KEY_EDIT_SIZE,
					'user' => $user,
					'stats' => $diff,
				] );
			}
		}
	}
}
