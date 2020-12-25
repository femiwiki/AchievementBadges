<?php

namespace MediaWiki\Extension\AchievementBadges\Special;

use LogPage;
use MediaWiki\Extension\AchievementBadges\Achievement;
use MediaWiki\Extension\AchievementBadges\Constants;
use MediaWiki\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use SpecialPage;
use TemplateParser;
use User;

/**
 * Special page
 *
 * @file
 */

class SpecialShareAchievementBadge extends SpecialPage {

	public const PAGE_NAME = 'ShareAchievementBadge';

	/** @var TemplateParser */
	private $templateParser;

	/** @var LoggerInterface */
	private $logger;

	public function __construct() {
		parent::__construct( self::PAGE_NAME );
		$this->templateParser = new TemplateParser( __DIR__ . '/../templates' );
		$this->logger = LoggerFactory::getInstance( 'AchievementBadges' );
	}

	/**
	 * @inheritDoc
	 */
	public function execute( $subPage ) {
		$this->addHelpLink( 'Extension:AchievementBadges' );

		$viewer = $this->getUser();
		$isAnon = $viewer->isAnon();
		$config = $this->getConfig();

		$out = $this->getOutput();
		$out->addModuleStyles( 'ext.achievementbadges.special.shareachievementsbadge.styles' );

		$split = explode( '/', $subPage, 2 );
		if ( count( $split ) != 2 ) {
			$out->addWikiTextAsInterface( $this->msg( 'special-shareachievementsbadge-invalid' )->text() );
			return;
		}
		list( $obtainerText, $suffixedKey ) = $split;
		preg_match( '/(.+)\-(\d+)/', $suffixedKey, $matches );
		if ( empty( $matches ) ) {
			$key = $suffixedKey;
		} else {
			$key = $matches[1];
			$index = $matches[2];
		}

		$registry = $config->get( Constants::CONFIG_KEY_ACHIEVEMENTS );
		if ( !array_key_exists( $key, $registry ) ) {
			$out->addWikiTextAsInterface( $this->msg( 'special-shareachievementsbadge-invalid' )->text() );
			return;
		}
		$obtainer = USER::newFromName( $obtainerText );
		if ( !$obtainer ) {
			$out->addWikiTextAsInterface( $this->msg( 'special-shareachievementsbadge-invalid' )->text() );
			return;
		}

		$registry = $registry[$key];
		$dbr = wfGetDB( DB_REPLICA );
		$row = $dbr->selectRow(
			[ 'logging', 'actor' ],
			[
				'log_timestamp',
			],
			[
				'log_type' => Constants::LOG_TYPE,
				'log_action' => $key,
				'actor_user' => $obtainer->getId(),
				$dbr->bitAnd( 'log_deleted', LogPage::DELETED_ACTION | LogPage::DELETED_USER ) . ' = 0 ',
			],
			__METHOD__,
			[],
			[
				'actor' => [ 'JOIN', 'actor_id = log_actor' ],
			]
		);
		if ( !$row ) {
			$out->addWikiTextAsInterface( $this->msg( 'special-shareachievementsbadge-invalid' )->text() );
			return;
		}
		list( $timePeriod, $timestamp ) = Achievement::getHumanTimes( $this->getLanguage(), $viewer,
			$row->log_timestamp );

		$achvName = $this->msg( 'achievement-name-' . ( $suffixedKey ), $obtainer->getName() )->parse();
		$iconPath = Achievement::getAchievementIcon( $this->getLanguage(), $registry['icon'] ?? null );
		$out->addHTML( $this->templateParser->processTemplate( 'SpecialShareAchievementBadge', [
			'text-name' => $achvName,
			'text-obtainer' => $obtainer->getName(),
			'text-icon' => $iconPath,
			'text-time-period' => $timePeriod,
			'text-timestamp' => $timestamp,
		] ) );

		$sitename = $config->get( 'Sitename' );

		$out->setHTMLTitle( $this->msg( 'pagetitle' )->plaintextParams( $achvName )->inContentLanguage() );

		$meta = [];
		$meta['og:type'] = 'article';
		$meta['og:site_name'] = $sitename;
		$meta['og:title'] = $achvName;
		$meta['og:description'] =
			$this->msg( 'achievement-description-' . ( $suffixedKey ), $obtainer->getName() );
		$meta['og:image'] = wfExpandUrl( $iconPath );

		foreach ( $meta as $property => $value ) {
			$out->addMeta(
				$property,
				$value
			);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getDescription() {
		return $this->msg( 'special-shareachievementsbadge' )->text();
	}

	/**
	 * @inheritDoc
	 */
	protected function getGroupName() {
		return 'users';
	}
}
