<?php
/**
 * webtrees: online genealogy
 * Copyright (C) 2018 webtrees development team
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace Fisharebest\Webtrees\Theme;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Controller\PageController;
use Fisharebest\Webtrees\Database;
use Fisharebest\Webtrees\Fact;
use Fisharebest\Webtrees\Filter;
use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\Functions\Functions;
use Fisharebest\Webtrees\GedcomRecord;
use Fisharebest\Webtrees\GedcomTag;
use Fisharebest\Webtrees\HitCounter;
use Fisharebest\Webtrees\Html;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Menu;
use Fisharebest\Webtrees\Module;
use Fisharebest\Webtrees\Module\AncestorsChartModule;
use Fisharebest\Webtrees\Module\CompactTreeChartModule;
use Fisharebest\Webtrees\Module\DescendancyChartModule;
use Fisharebest\Webtrees\Module\FamilyBookChartModule;
use Fisharebest\Webtrees\Module\FamilyTreeFavoritesModule;
use Fisharebest\Webtrees\Module\FanChartModule;
use Fisharebest\Webtrees\Module\GoogleMapsModule;
use Fisharebest\Webtrees\Module\HourglassChartModule;
use Fisharebest\Webtrees\Module\InteractiveTreeModule;
use Fisharebest\Webtrees\Module\LifespansChartModule;
use Fisharebest\Webtrees\Module\PedigreeChartModule;
use Fisharebest\Webtrees\Module\RelationshipsChartModule;
use Fisharebest\Webtrees\Module\StatisticsChartModule;
use Fisharebest\Webtrees\Module\TimelineChartModule;
use Fisharebest\Webtrees\Module\UserFavoritesModule;
use Fisharebest\Webtrees\Site;
use Fisharebest\Webtrees\Theme;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\User;
use Symfony\Component\HttpFoundation\Request;
use stdClass;

/**
 * Common functions for all themes.
 */
abstract class AbstractTheme {
	/**
	 * Where are our CSS, JS and other assets?
	 */
	const THEME_DIR  = '_common';
	const ASSET_DIR  = 'themes/' . self::THEME_DIR . '/css-2.0.0/';
	const STYLESHEET = self::ASSET_DIR . 'style.css';

	// Icons are created using <i class="..."></i>
	const ICONS = [
		// Icons for GEDCOM records
		'family'     => 'fas fa-users',
		'individual' => 'far fa-user',
		'media'      => 'far fa-file-image',
		'note'       => 'far fa-sticky-note',
		'repository' => 'fas fa-university',
		'source'     => 'far fa-file-alt',
		'submission' => 'fas fa-upload',
		'submitter'  => 'fas fa-user-plus',

		// Icons for sexes
		'F' => 'fas fa-venus',
		'M' => 'fas fa-mars',
		'U' => 'fas fa-genderless',

		// Icons for editing
		'add'    => 'fas fa-plus',
		'config' => 'far fa-cogs',
		'copy'   => 'far fa-copy',
		'create' => 'fas fa-plus',
		'delete' => 'fas fa-trash-alt',
		'edit'   => 'fas fa-pencil-alt',
		'link'   => 'fas fa-link',
		'unlink' => 'fas fa-unlink',

		// Icons for arrows
		'arrow-down'  => 'fas fa-arrow-down',
		'arrow-left'  => 'fas fa-arrow-left',
		'arrow-right' => 'fas fa-arrow-right',
		'arrow-up'    => 'fas fa-arrow-up',

		// Status icons
		'error'   => 'fas fa-exclamation-triangle',
		'info'    => 'fas fa-info-circle',
		'warning' => 'fas fa-exclamation-circle',

		// Icons for file types
		'mime-application-pdf' => '',
		'mime-text-html'       => '',

		// Other icons
		'mail'   => 'far fa-envelope',
		'help'   => 'fas fa-info-circle',
		'search' => 'fas fa-search',
	];

	/** @var  Request */
	protected $request;

	/** @var Tree */
	protected $tree;

	/** @var int The number of times this page has been shown */
	protected $page_views;

	/**
	 * Custom themes should place their initialization code in the function hookAfterInit(), not in
	 * the constructor, as all themes get constructed - whether they are used or not.
	 */
	final public function __construct() {
	}

	/**
	 * Create accessibility links for the header.
	 *
	 * "Skip to content" allows keyboard only users to navigate over the headers without
	 * pressing TAB many times.
	 *
	 * @return string
	 */
	public function accessibilityLinks() {
		return
			'<div class="wt-accessibility-links">' .
			'<a class="sr-only sr-only-focusable btn btn-info btn-sm" href="#content">' .
			/* I18N: Skip over the headers and menus, to the main content of the page */ I18N::translate('Skip to content') .
			'</a>' .
			'</div>';
	}

	/**
	 * Create scripts for analytics and tracking.
	 *
	 * @return string
	 */
	public function analytics() {
		if ($this->themeId() === '_administration' || !empty($_SERVER['HTTP_DNT'])) {
			return '';
		} else {
			return
				$this->analyticsBingWebmaster(
					Site::getPreference('BING_WEBMASTER_ID')
				) .
				$this->analyticsGoogleWebmaster(
					Site::getPreference('GOOGLE_WEBMASTER_ID')
				) .
				$this->analyticsGoogleTracker(
					Site::getPreference('GOOGLE_ANALYTICS_ID')
				) .
				$this->analyticsPiwikTracker(
					Site::getPreference('PIWIK_URL'),
					Site::getPreference('PIWIK_SITE_ID')
				) .
				$this->analyticsStatcounterTracker(
					Site::getPreference('STATCOUNTER_PROJECT_ID'),
					Site::getPreference('STATCOUNTER_SECURITY_ID')
				);
		}
	}

	/**
	 * Create the verification code for Google Webmaster Tools.
	 *
	 * @param string $verification_id
	 *
	 * @return string
	 */
	public function analyticsBingWebmaster($verification_id) {
		// Only need to add this to the home page.
		if (WT_SCRIPT_NAME === 'index.php' && $verification_id) {
			return '<meta name="msvalidate.01" content="' . $verification_id . '">';
		} else {
			return '';
		}
	}

	/**
	 * Create the verification code for Google Webmaster Tools.
	 *
	 * @param string $verification_id
	 *
	 * @return string
	 */
	public function analyticsGoogleWebmaster($verification_id) {
		// Only need to add this to the home page.
		if (WT_SCRIPT_NAME === 'index.php' && $verification_id) {
			return '<meta name="google-site-verification" content="' . $verification_id . '">';
		} else {
			return '';
		}
	}

	/**
	 * Create the tracking code for Google Analytics.
	 *
	 * See https://developers.google.com/analytics/devguides/collection/analyticsjs/advanced
	 *
	 * @param string $analytics_id
	 *
	 * @return string
	 */
	public function analyticsGoogleTracker($analytics_id) {
		if ($analytics_id) {
			// Add extra dimensions (i.e. filtering categories)
			$dimensions = (object) [
				'dimension1' => $this->tree ? $this->tree->getName() : '-',
				'dimension2' => $this->tree ? Auth::accessLevel($this->tree) : '-',
			];

			return
				'<script async src="https://www.google-analytics.com/analytics.js"></script>' .
				'<script>' .
				'window.ga=window.ga||function(){(ga.q=ga.q||[]).push(arguments)};ga.l=+new Date;' .
				'ga("create","' . $analytics_id . '","auto");' .
				'ga("send", "pageview", ' . json_encode($dimensions) . ');' .
				'</script>';
		} else {
			return '';
		}
	}

	/**
	 * Create the tracking code for Piwik Analytics.
	 *
	 * @param string $url     - The domain/path to Piwik
	 * @param string $site_id - The Piwik site identifier
	 *
	 * @return string
	 */
	public function analyticsPiwikTracker($url, $site_id) {
		$url = preg_replace(['/^https?:\/\//', '/\/$/'], '', $url);

		if ($url && $site_id) {
			return
				'<script>' .
				'var _paq=_paq||[];' .
				'(function(){var u=(("https:"==document.location.protocol)?"https://' . $url . '/":"http://' . $url . '/");' .
				'_paq.push(["setSiteId",' . $site_id . ']);' .
				'_paq.push(["setTrackerUrl",u+"piwik.php"]);' .
				'_paq.push(["trackPageView"]);' .
				'_paq.push(["enableLinkTracking"]);' .
				'var d=document,g=d.createElement("script"),s=d.getElementsByTagName("script")[0];g.defer=true;g.async=true;g.src=u+"piwik.js";' .
				's.parentNode.insertBefore(g,s);})();' .
				'</script>';
		} else {
			return '';
		}
	}

	/**
	 * Create the tracking code for Statcounter.
	 *
	 * @param string $project_id  - The statcounter project ID
	 * @param string $security_id - The statcounter security ID
	 *
	 * @return string
	 */
	public function analyticsStatcounterTracker($project_id, $security_id) {
		if ($project_id && $security_id) {
			return
				'<script>' .
				'var sc_project=' . (int) $project_id . ',sc_invisible=1,sc_security="' . $security_id .
				'",scJsHost = (("https:"===document.location.protocol)?"https://secure.":"http://www.");' .
				'document.write("<sc"+"ript src=\'"+scJsHost+"statcounter.com/counter/counter.js\'></"+"script>");' .
				'</script>';
		} else {
			return '';
		}
	}

	/**
	 * Where are our CSS, JS and other assets?
	 *
	 * @deprecated - use the constant directly
	 *
	 * @return string A relative path, such as "themes/foo/"
	 */
	public function assetUrl() {
		return self::ASSET_DIR;
	}

	/**
	 * Create a contact link for a user.
	 *
	 * @param User $user
	 *
	 * @return string
	 */
	public function contactLink(User $user) {
		$method = $user->getPreference('contactmethod');

		switch ($method) {
			case 'none':
				return '';
			case 'mailto':
				return '<a href="mailto:' . e($user->getEmail()) . '">' . e($user->getRealName()) . '</a>';
			default:
				$url = route(Auth::check() ? 'message' : 'contact', [
					'ged' => $this->tree->getName(),
					'to'  => $user->getUserName(),
					'url' => $this->request->getRequestUri(),
				]);

				return '<a href="' . e($url) . '">' . e($user->getRealName()) . '</a>';
		}
	}

	/**
	 * Create contact link for both technical and genealogy support.
	 *
	 * @param User $user
	 *
	 * @return string
	 */
	public function contactLinkEverything(User $user) {
		return I18N::translate('For technical support or genealogy questions contact %s.', $this->contactLink($user));
	}

	/**
	 * Create contact link for genealogy support.
	 *
	 * @param User $user
	 *
	 * @return string
	 */
	public function contactLinkGenealogy(User $user) {
		return I18N::translate('For help with genealogy questions contact %s.', $this->contactLink($user));
	}

	/**
	 * Create contact link for technical support.
	 *
	 * @param User $user
	 *
	 * @return string
	 */
	public function contactLinkTechnical(User $user) {
		return I18N::translate('For technical support and information contact %s.', $this->contactLink($user));
	}

	/**
	 * Create contact links for the page footer.
	 *
	 * @return string
	 */
	public function contactLinks() {
		$contact_user   = User::find($this->tree->getPreference('CONTACT_USER_ID'));
		$webmaster_user = User::find($this->tree->getPreference('WEBMASTER_USER_ID'));

		if ($contact_user && $contact_user === $webmaster_user) {
			return $this->contactLinkEverything($contact_user);
		} elseif ($contact_user && $webmaster_user) {
			return $this->contactLinkGenealogy($contact_user) . '<br>' . $this->contactLinkTechnical($webmaster_user);
		} elseif ($contact_user) {
			return $this->contactLinkGenealogy($contact_user);
		} elseif ($webmaster_user) {
			return $this->contactLinkTechnical($webmaster_user);
		} else {
			return '';
		}
	}

	/**
	 * Create a cookie warning.
	 *
	 * @return string
	 */
	public function cookieWarning() {
		if (
			empty($_SERVER['HTTP_DNT']) &&
			empty($_COOKIE['cookie']) &&
			(Site::getPreference('GOOGLE_ANALYTICS_ID') === '1' || Site::getPreference('PIWIK_SITE_ID') === '1' || Site::getPreference('STATCOUNTER_PROJECT_ID') === '1')
		) {
			return
				'<div class="wt-cookie-warning">' .
				I18N::translate('Cookies') . ' - ' .
				I18N::translate('This website uses cookies to learn about visitor behaviour.') . ' ' .
				'<button onclick="document.cookie=\'cookie=1\'; this.parentNode.classList.add(\'hidden\');">' . I18N::translate('continue') . '</button>' .
				'</div>';
		} else {
			return '';
		}
	}

	/**
	 * Add markup to the contact links.
	 *
	 * @return string
	 */
	public function formatContactLinks() {
		if ($this->tree) {
			return '<div class="wt-contact-links">' . $this->contactLinks() . '</div>';
		} else {
			return '';
		}
	}

	/**
	 * Add markup to the hit counter.
	 *
	 * @param int $count
	 *
	 * @return string
	 */
	public function formatPageViews($count) {
		if ($count > 0) {
			return
				'<div class="wt-page-views">' .
				I18N::plural('This page has been viewed %s time.', 'This page has been viewed %s times.', $count,
					'<span class="odometer">' . I18N::digits($count) . '</span>') .
				'</div>';
		} else {
			return '';
		}
	}

	/**
	 * Create a pending changes link for the page footer.
	 *
	 * @return string
	 */
	public function formatPendingChangesLink() {
		if ($this->pendingChangesExist()) {
			return '<div class="pending-changes-link">' . $this->pendingChangesLink() . '</div>';
		} else {
			return '';
		}
	}

	/**
	 * Add markup to the secondary menu.
	 *
	 * @return string
	 */
	public function formatSecondaryMenu() {
		return
			'<ul class="nav wt-secondary-menu">' .
			implode('', array_map(function (Menu $menu) {
				return $this->formatSecondaryMenuItem($menu);
			}, $this->secondaryMenu())) .
			'</ul>';
	}

	/**
	 * Add markup to an item in the secondary menu.
	 *
	 * @param Menu $menu
	 *
	 * @return string
	 */
	public function formatSecondaryMenuItem(Menu $menu) {
		return $menu->bootstrap4();
	}

	/**
	 * Create resources for the colors theme.
	 */
	public function hookAfterInit() {
	}

	/**
	 * Allow themes to add extra scripts to the page footer.
	 *
	 * @return string
	 */
	public function hookFooterExtraJavascript() {
		return '';
	}

	/**
	 * Display an icon for this fact.
	 *
	 * @param Fact $fact
	 *
	 * @return string
	 */
	public function icon(Fact $fact) {
		$icon = 'images/facts/' . $fact->getTag() . '.png';
		if (file_exists(self::ASSET_DIR . $icon)) {
			return '<img src="' . self::ASSET_DIR . $icon . '" title="' . GedcomTag::getLabel($fact->getTag()) . '">';
		} elseif (file_exists(self::ASSET_DIR . 'images/facts/NULL.png')) {
			// Spacer image - for alignment - until we move to a sprite.
			return '<img src="' . Theme::theme()->assetUrl() . 'images/facts/NULL.png">';
		} else {
			return '';
		}
	}

	/**
	 * Decorative icons are used in addition to text.
	 * They need additional markup to hide them from assistive technologies.
	 *
	 * Semantic icons are used in place of text.
	 * They need additional markup to convey their meaning to assistive technologies.
	 *
	 * @link http://fontawesome.io/accessibility
	 *
	 * @param string $icon
	 * @param string $text
	 *
	 * @return string
	 */
	public function replacementIconFunction($icon, $text = '') {
		if (array_key_exists($icon, self::ICONS)) {
			if ($text === '') {
				// Decorative icon.  Hiden from assistive technology.
				return '<i class="' . self::ICONS[$icon] . '" aria-hidden="true"></i>';
			} else {
				// Semantic icon.  Label for assistive technology.
				return
					'<i class="' . self::ICONS[$icon] . '" title="' . $text . '"></i>' .
					'<span class="sr-only">' . $text . '</span>';
			}
		} else {
			return $text;
		}
	}


	/**
	 * Display an individual in a box - for charts, etc.
	 *
	 * @param Individual $individual
	 *
	 * @return string
	 */
	public function individualBox(Individual $individual) {
		$personBoxClass = array_search($individual->getSex(), ['person_box' => 'M', 'person_boxF' => 'F', 'person_boxNN' => 'U']);
		if ($individual->canShow() && $individual->getTree()->getPreference('SHOW_HIGHLIGHT_IMAGES')) {
			$thumbnail = $individual->displayImage(40, 50, 'crop', []);
		} else {
			$thumbnail = '';
		}

		$content = '<span class="namedef name1">' . $individual->getFullName() . '</span>';
		$icons   = '';
		if ($individual->canShow()) {
			$content = '<a href="' . e($individual->url()) . '">' . $content . '</a>' .
				'<div class="namedef name1">' . $individual->getAddName() . '</div>';
			$icons = '<div class="icons">' .
				'<span class="iconz icon-zoomin" title="' . I18N::translate('Zoom in/out on this box.') . '"></span>' .
				'<div class="itr"><i class="icon-pedigree"></i><div class="popup">' .
				'<ul class="' . $personBoxClass . '">' . implode('', array_map(function (Menu $menu) {
					return $menu->bootstrap4();
				}, $this->individualBoxMenu($individual))) . '</ul>' .
				'</div>' .
				'</div>' .
				'</div>';
		}

		return
			'<div data-xref="' . e($individual->getXref()) . '" data-tree="' . e($individual->getTree()->getName()) . '" class="person_box_template ' . $personBoxClass . ' box-style1" style="width: ' . $this->parameter('chart-box-x') . 'px; height: ' . $this->parameter('chart-box-y') . 'px">' .
			$icons .
			'<div class="chart_textbox" style="max-height:' . $this->parameter('chart-box-y') . 'px;">' .
			$thumbnail .
			$content .
			'<div class="inout2 details1">' . $this->individualBoxFacts($individual) . '</div>' .
			'</div>' .
			'<div class="inout"></div>' .
			'</div>';
	}

	/**
	 * Display an empty box - for a missing individual in a chart.
	 *
	 * @return string
	 */
	public function individualBoxEmpty() {
		return '<div class="person_box_template person_boxNN box-style1" style="width: ' . $this->parameter('chart-box-x') . 'px; min-height: ' . $this->parameter('chart-box-y') . 'px"></div>';
	}

	/**
	 * Display an individual in a box - for charts, etc.
	 *
	 * @param Individual $individual
	 *
	 * @return string
	 */
	public function individualBoxLarge(Individual $individual) {
		$personBoxClass = array_search($individual->getSex(), ['person_box' => 'M', 'person_boxF' => 'F', 'person_boxNN' => 'U']);
		if ($individual->getTree()->getPreference('SHOW_HIGHLIGHT_IMAGES')) {
			$thumbnail = $individual->displayImage(40, 50, 'crop', []);
		} else {
			$thumbnail = '';
		}

		$content = '<span class="namedef name1">' . $individual->getFullName() . '</span>';
		$icons   = '';
		if ($individual->canShow()) {
			$content = '<a href="' . e($individual->url()) . '">' . $content . '</a>' .
				'<div class="namedef name2">' . $individual->getAddName() . '</div>';
			$icons = '<div class="icons">' .
				'<span class="iconz icon-zoomin" title="' . I18N::translate('Zoom in/out on this box.') . '"></span>' .
				'<div class="itr"><i class="icon-pedigree"></i><div class="popup">' .
				'<ul class="' . $personBoxClass . '">' . implode('', array_map(function (Menu $menu) {
					return $menu->bootstrap4();
				}, $this->individualBoxMenu($individual))) . '</ul>' .
				'</div>' .
				'</div>' .
				'</div>';
		}

		return
			'<div data-xref="' . e($individual->getXref()) . '" data-tree="' . e($individual->getTree()->getName()) . '" class="person_box_template ' . $personBoxClass . ' box-style2">' .
			$icons .
			'<div class="chart_textbox" style="max-height:' . $this->parameter('chart-box-y') . 'px;">' .
			$thumbnail .
			$content .
			'<div class="inout2 details2">' . $this->individualBoxFacts($individual) . '</div>' .
			'</div>' .
			'<div class="inout"></div>' .
			'</div>';
	}

	/**
	 * Display an individual in a box - for charts, etc.
	 *
	 * @param Individual $individual
	 *
	 * @return string
	 */
	public function individualBoxSmall(Individual $individual) {
		$personBoxClass = array_search($individual->getSex(), ['person_box' => 'M', 'person_boxF' => 'F', 'person_boxNN' => 'U']);
		if ($individual->getTree()->getPreference('SHOW_HIGHLIGHT_IMAGES')) {
			$thumbnail = $individual->displayImage(40, 50, 'crop', []);
		} else {
			$thumbnail = '';
		}

		return
			'<div data-xref="' . $individual->getXref() . '" class="person_box_template ' . $personBoxClass . ' iconz box-style0" style="width: ' . $this->parameter('compact-chart-box-x') . 'px; min-height: ' . $this->parameter('compact-chart-box-y') . 'px">' .
			'<div class="compact_view">' .
			$thumbnail .
			'<a href="' . e($individual->url()) . '">' .
			'<span class="namedef name0">' . $individual->getFullName() . '</span>' .
			'</a>' .
			'<div class="inout2 details0">' . $individual->getLifeSpan() . '</div>' .
			'</div>' .
			'<div class="inout"></div>' .
			'</div>';
	}

	/**
	 * Display an individual in a box - for charts, etc.
	 *
	 * @return string
	 */
	public function individualBoxSmallEmpty() {
		return '<div class="person_box_template person_boxNN box-style1" style="width: ' . $this->parameter('compact-chart-box-x') . 'px; min-height: ' . $this->parameter('compact-chart-box-y') . 'px"></div>';
	}

	/**
	 * Generate the facts, for display in charts.
	 *
	 * @param Individual $individual
	 *
	 * @return string
	 */
	public function individualBoxFacts(Individual $individual) {
		$html = '';

		$opt_tags = preg_split('/\W/', $individual->getTree()->getPreference('CHART_BOX_TAGS'), 0, PREG_SPLIT_NO_EMPTY);
		// Show BIRT or equivalent event
		foreach (explode('|', WT_EVENTS_BIRT) as $birttag) {
			if (!in_array($birttag, $opt_tags)) {
				$event = $individual->getFirstFact($birttag);
				if ($event) {
					$html .= $event->summary();
					break;
				}
			}
		}
		// Show optional events (before death)
		foreach ($opt_tags as $key => $tag) {
			if (!preg_match('/^(' . WT_EVENTS_DEAT . ')$/', $tag)) {
				$event = $individual->getFirstFact($tag);
				if (!is_null($event)) {
					$html .= $event->summary();
					unset($opt_tags[$key]);
				}
			}
		}
		// Show DEAT or equivalent event
		foreach (explode('|', WT_EVENTS_DEAT) as $deattag) {
			$event = $individual->getFirstFact($deattag);
			if ($event) {
				$html .= $event->summary();
				if (in_array($deattag, $opt_tags)) {
					unset($opt_tags[array_search($deattag, $opt_tags)]);
				}
				break;
			}
		}
		// Show remaining optional events (after death)
		foreach ($opt_tags as $tag) {
			$event = $individual->getFirstFact($tag);
			if ($event) {
				$html .= $event->summary();
			}
		}

		return $html;
	}

	/**
	 * Generate the LDS summary, for display in charts.
	 *
	 * @param Individual $individual
	 *
	 * @return string
	 */
	public function individualBoxLdsSummary(Individual $individual) {
		if ($individual->getTree()->getPreference('SHOW_LDS_AT_GLANCE')) {
			$BAPL = $individual->getFacts('BAPL') ? 'B' : '_';
			$ENDL = $individual->getFacts('ENDL') ? 'E' : '_';
			$SLGC = $individual->getFacts('SLGC') ? 'C' : '_';
			$SLGS = '_';

			foreach ($individual->getSpouseFamilies() as $family) {
				if ($family->getFacts('SLGS')) {
					$SLGS = '';
				}
			}

			return $BAPL . $ENDL . $SLGS . $SLGC;
		} else {
			return '';
		}
	}

	/**
	 * Links, to show in chart boxes;
	 *
	 * @param Individual $individual
	 *
	 * @return Menu[]
	 */
	public function individualBoxMenu(Individual $individual) {
		$menus = array_merge(
			$this->individualBoxMenuCharts($individual),
			$this->individualBoxMenuFamilyLinks($individual)
		);

		return $menus;
	}

	/**
	 * Chart links, to show in chart boxes;
	 *
	 * @param Individual $individual
	 *
	 * @return Menu[]
	 */
	public function individualBoxMenuCharts(Individual $individual) {
		$menus = [];
		foreach (Module::getActiveCharts($this->tree) as $chart) {
			$menu = $chart->getBoxChartMenu($individual);
			if ($menu) {
				$menus[] = $menu;
			}
		}

		usort($menus, function (Menu $x, Menu $y) {
			return I18N::strcasecmp($x->getLabel(), $y->getLabel());
		});

		return $menus;
	}

	/**
	 * Family links, to show in chart boxes.
	 *
	 * @param Individual $individual
	 *
	 * @return Menu[]
	 */
	public function individualBoxMenuFamilyLinks(Individual $individual) {
		$menus = [];

		foreach ($individual->getSpouseFamilies() as $family) {
			$menus[] = new Menu('<strong>' . I18N::translate('Family with spouse') . '</strong>', e($family->url()));
			$spouse  = $family->getSpouse($individual);
			if ($spouse && $spouse->canShowName()) {
				$menus[] = new Menu($spouse->getFullName(), e($spouse->url()));
			}
			foreach ($family->getChildren() as $child) {
				if ($child->canShowName()) {
					$menus[] = new Menu($child->getFullName(), e($child->url()));
				}
			}
		}

		return $menus;
	}

	/**
	 * Create part of an individual box
	 *
	 * @param Individual $individual
	 *
	 * @return string
	 */
	public function individualBoxSexSymbol(Individual $individual) {
		if ($individual->getTree()->getPreference('PEDIGREE_SHOW_GENDER')) {
			return $individual->sexImage('large');
		} else {
			return '';
		}
	}

	/**
	 * Initialise the theme. We cannot pass these in a constructor, as the construction
	 * happens in a theme file, and we need to be able to change it.
	 *
	 * @param Tree|null $tree The current tree (if there is one).
	 */
	final public function init(Tree $tree = null) {
		$this->request  = Request::createFromGlobals();
		$this->tree     = $tree;

		$this->hookAfterInit();
	}

	/**
	 * A small "powered by webtrees" logo for the footer.
	 *
	 * @return string
	 */
	public function logoPoweredBy() {
		return '<a href="' . WT_WEBTREES_URL . '" class="wt-powered-by-webtrees" title="' . WT_WEBTREES_URL . '" dir="ltr">' . WT_WEBTREES_URL . '</a>';
	}

	/**
	 * A menu for the day/month/year calendar views.
	 *
	 * @return Menu
	 */
	public function menuCalendar() {
		return new Menu(I18N::translate('Calendar'), '#', 'menu-calendar', ['rel' => 'nofollow'], [
			// Day view
			new Menu(I18N::translate('Day'), e(route('calendar', ['view' => 'day', 'ged' => $this->tree->getName()])), 'menu-calendar-day', ['rel' => 'nofollow']),
			// Month view
			new Menu(I18N::translate('Month'), e(route('calendar', ['view' => 'month', 'ged' => $this->tree->getName()])), 'menu-calendar-month', ['rel' => 'nofollow']),
			//Year view
			new Menu(I18N::translate('Year'), e(route('calendar', ['view' => 'year', 'ged' => $this->tree->getName()])), 'menu-calendar-year', ['rel' => 'nofollow']),
		]);
	}

	/**
	 * Generate a menu item to change the blocks on the current (index.php) page.
	 *
	 * @return Menu|null
	 */
	public function menuChangeBlocks() {
		if (WT_SCRIPT_NAME === 'index.php' && Auth::check() && Filter::get('route') === 'user-page') {
			return new Menu(I18N::translate('Customize this page'), route('user-page-edit', ['ged' => $this->tree->getName()]), 'menu-change-blocks');
		} elseif (WT_SCRIPT_NAME === 'index.php' && Auth::isManager($this->tree) && Filter::get('route') === 'tree-page') {
			return new Menu(I18N::translate('Customize this page'), route('tree-page-edit', ['ged' => $this->tree->getName()]), 'menu-change-blocks');
		} else {
			return null;
		}
	}

	/**
	 * Generate a menu for each of the different charts.
	 *
	 * @param Individual $individual
	 *
	 * @return Menu|null
	 */
	public function menuChart(Individual $individual) {
		$submenus = [];
		foreach (Module::getActiveCharts($this->tree) as $chart) {
			$menu = $chart->getChartMenu($individual);
			if ($menu) {
				$submenus[] = $menu;
			}
		}

		if (empty($submenus)) {
			return null;
		} else {
			usort($submenus, function (Menu $x, Menu $y) {
				return I18N::strcasecmp($x->getLabel(), $y->getLabel());
			});

			return new Menu(I18N::translate('Charts'), '#', 'menu-chart', ['rel' => 'nofollow'], $submenus);
		}
	}

	/**
	 * Generate a menu item for the ancestors chart.
	 *
	 * @param Individual $individual
	 *
	 * @return Menu|null
	 *
	 * @deprecated
	 */
	public function menuChartAncestors(Individual $individual) {
		$chart = new AncestorsChartModule(WT_ROOT . WT_MODULES_DIR . 'ancestors_chart');

		return $chart->getChartMenu($individual);
	}

	/**
	 * Generate a menu item for the compact tree.
	 *
	 * @param Individual $individual
	 *
	 * @return Menu|null
	 *
	 * @deprecated
	 */
	public function menuChartCompact(Individual $individual) {
		$chart = new CompactTreeChartModule(WT_ROOT . WT_MODULES_DIR . 'compact_tree_chart');

		return $chart->getChartMenu($individual);
	}

	/**
	 * Generate a menu item for the descendants chart.
	 *
	 * @param Individual $individual
	 *
	 * @return Menu|null
	 *
	 * @deprecated
	 */
	public function menuChartDescendants(Individual $individual) {
		$chart = new DescendancyChartModule(WT_ROOT . WT_MODULES_DIR . 'descendancy_chart');

		return $chart->getChartMenu($individual);
	}

	/**
	 * Generate a menu item for the family-book chart.
	 *
	 * @param Individual $individual
	 *
	 * @return Menu|null
	 *
	 * @deprecated
	 */
	public function menuChartFamilyBook(Individual $individual) {
		$chart = new FamilyBookChartModule(WT_ROOT . WT_MODULES_DIR . 'family_book_chart');

		return $chart->getChartMenu($individual);
	}

	/**
	 * Generate a menu item for the fan chart.
	 *
	 * We can only do this if the GD2 library is installed with TrueType support.
	 *
	 * @param Individual $individual
	 *
	 * @return Menu|null
	 *
	 * @deprecated
	 */
	public function menuChartFanChart(Individual $individual) {
		$chart = new FanChartModule(WT_ROOT . WT_MODULES_DIR . 'fan_chart');

		return $chart->getChartMenu($individual);
	}

	/**
	 * Generate a menu item for the interactive tree.
	 *
	 * @param Individual $individual
	 *
	 * @return Menu|null
	 *
	 * @deprecated
	 */
	public function menuChartInteractiveTree(Individual $individual) {
		$chart = new InteractiveTreeModule(WT_ROOT . WT_MODULES_DIR . 'tree');

		return $chart->getChartMenu($individual);
	}

	/**
	 * Generate a menu item for the hourglass chart.
	 *
	 * @param Individual $individual
	 *
	 * @return Menu|null
	 *
	 * @deprecated
	 */
	public function menuChartHourglass(Individual $individual) {
		$chart = new HourglassChartModule(WT_ROOT . WT_MODULES_DIR . 'hourglass_chart');

		return $chart->getChartMenu($individual);
	}

	/**
	 * Generate a menu item for the lifepsan chart.
	 *
	 * @param Individual $individual
	 *
	 * @return Menu|null
	 *
	 * @deprecated
	 */
	public function menuChartLifespan(Individual $individual) {
		$chart = new LifespansChartModule(WT_ROOT . WT_MODULES_DIR . 'lifespans_chart');

		return $chart->getChartMenu($individual);
	}

	/**
	 * Generate a menu item for the pedigree chart.
	 *
	 * @param Individual $individual
	 *
	 * @return Menu|null
	 *
	 * @deprecated
	 */
	public function menuChartPedigree(Individual $individual) {
		$chart = new PedigreeChartModule(WT_ROOT . WT_MODULES_DIR . 'pedigree_chart');

		return $chart->getChartMenu($individual);
	}

	/**
	 * Generate a menu item for the pedigree map.
	 *
	 * @param Individual $individual
	 *
	 * @return Menu|null
	 *
	 * @deprecated
	 */
	public function menuChartPedigreeMap(Individual $individual) {
		$chart = new GoogleMapsModule(WT_ROOT . WT_MODULES_DIR . 'googlemap');

		return $chart->getChartMenu($individual);
	}

	/**
	 * Generate a menu item for the relationship chart.
	 *
	 * @param Individual $individual
	 *
	 * @return Menu|null
	 *
	 * @deprecated
	 */
	public function menuChartRelationship(Individual $individual) {
		$chart = new RelationshipsChartModule(WT_ROOT . WT_MODULES_DIR . 'relationships_chart');

		return $chart->getChartMenu($individual);
	}

	/**
	 * Generate a menu item for the statistics charts.
	 *
	 * @return Menu|null
	 *
	 * @deprecated
	 */
	public function menuChartStatistics() {
		$chart = new StatisticsChartModule(WT_ROOT . WT_MODULES_DIR . 'statistics_chart');

		return $chart->getChartMenu(null);
	}

	/**
	 * Generate a menu item for the timeline chart.
	 *
	 * @param Individual $individual
	 *
	 * @return Menu|null
	 *
	 * @deprecated
	 */
	public function menuChartTimeline(Individual $individual) {
		$chart = new TimelineChartModule(WT_ROOT . WT_MODULES_DIR . 'timeline_chart');

		return $chart->getChartMenu($individual);
	}

	/**
	 * Generate a menu item for the control panel.
	 *
	 * @return Menu|null
	 */
	public function menuControlPanel() {
		if (Auth::isAdmin()) {
			return new Menu(I18N::translate('Control panel'), route('admin-control-panel'), 'menu-admin');
		} elseif (Auth::isManager($this->tree)) {
			return new Menu(I18N::translate('Control panel'), route('admin-control-panel-manager'), 'menu-admin');
		} else {
			return null;
		}
	}

	/**
	 * Favorites menu.
	 *
	 * @return Menu|null
	 */
	public function menuFavorites() {
		global $controller;

		$show_user_favorites = $this->tree && Module::getModuleByName('user_favorites') && Auth::check();
		$show_tree_favorites = $this->tree && Module::getModuleByName('gedcom_favorites');

		if ($show_user_favorites && $show_tree_favorites) {
			$favorites = array_merge(
				FamilyTreeFavoritesModule::getFavorites($this->tree, Auth::user()),
				UserFavoritesModule::getFavorites($this->tree, Auth::user())
			);
		} elseif ($show_user_favorites) {
			$favorites = UserFavoritesModule::getFavorites($this->tree, Auth::user());
		} elseif ($show_tree_favorites) {
			$favorites = FamilyTreeFavoritesModule::getFavorites($this->tree, Auth::user());
		} else {
			$favorites = [];
		}

		$submenus = [];
		$records  = [];
		foreach ($favorites as $favorite) {
			switch ($favorite->favorite_type) {
				case 'URL':
					$submenus[] = new Menu(e($favorite->title), e($favorite->url));
					break;
				default:
					$record = GedcomRecord::getInstance($favorite->xref, $this->tree);
					if ($record && $record->canShowName()) {
						$submenus[] = new Menu($record->getFullName(), e($record->url()));
						$records[]  = $record;
					}
					break;
			}
		}

		// @TODO we no longer have a global $controller
		if ($show_user_favorites && isset($controller->record) && $controller->record instanceof GedcomRecord && !in_array($controller->record, $records)) {
			$url = route('module', [
				'module' => 'user_favorites',
				'action' => 'AddFavorite',
				'ged'    => $this->tree->getName(),
				'xref'   => $controller->record->getXref(),
			]);

			$submenus[] = new Menu(I18N::translate('Add to favorites'), '#', '', [
				'data-url' => $url,
				'onclick'  => 'jQuery.post(this.dataset.url,function() {location.reload();})',
			]);
		}

		if (empty($submenus)) {
			return null;
		} else {
			return new Menu(I18N::translate('Favorites'), '#', 'menu-favorites', [], $submenus);
		}
	}

	/**
	 * A menu for the home (family tree) pages.
	 *
	 * @return Menu
	 */
	public function menuHomePage() {
		if (count(Tree::getAll()) === 1 || Site::getPreference('ALLOW_CHANGE_GEDCOM') !== '1') {
			return new Menu(I18N::translate('Family tree'), route('tree-page', ['ged' => $this->tree->getName()]), 'menu-tree');
		} else {
			$submenus = [];
			foreach (Tree::getAll() as $tree) {
				if ($tree == $this->tree) {
					$active = 'active ';
				} else {
					$active = '';
				}
				$submenus[] = new Menu(e($tree->getTitle()), route('tree-page', ['ged' => $tree->getName()]), $active . 'menu-tree-' . $tree->getTreeId());
			}

			return new Menu(I18N::translate('Family trees'), '#', 'menu-tree', [], $submenus);
		}
	}

	/**
	 * A menu to show a list of available languages.
	 *
	 * @return Menu|null
	 */
	public function menuLanguages() {
		$menu = new Menu(I18N::translate('Language'), '#', 'menu-language');

		foreach (I18N::activeLocales() as $locale) {
			$language_tag = $locale->languageTag();
			$class        = 'menu-language-' . $language_tag . (WT_LOCALE === $language_tag ? ' active' : '');
			$menu->addSubmenu(new Menu($locale->endonym(), '#', $class, [
				'onclick'       => 'return false;',
				'data-language' => $language_tag,
			]));
		}

		if (count($menu->getSubmenus()) > 1) {
			return $menu;
		} else {
			return null;
		}
	}

	/**
	 * Create a menu to show lists of individuals, families, sources, etc.
	 *
	 * @param string $surname The significant surname on the page
	 *
	 * @return Menu
	 */
	public function menuLists($surname) {
		// Do not show empty lists
		$row = Database::prepare(
			"SELECT SQL_CACHE" .
			" EXISTS(SELECT 1 FROM `##sources` WHERE s_file = ?) AS sour," .
			" EXISTS(SELECT 1 FROM `##other` WHERE o_file = ? AND o_type='REPO') AS repo," .
			" EXISTS(SELECT 1 FROM `##other` WHERE o_file = ? AND o_type='NOTE') AS note," .
			" EXISTS(SELECT 1 FROM `##media` WHERE m_file = ?) AS obje"
		)->execute([
			$this->tree->getTreeId(),
			$this->tree->getTreeId(),
			$this->tree->getTreeId(),
			$this->tree->getTreeId(),
		])->fetchOneRow();

		$submenus = [
			$this->menuListsIndividuals($surname),
			$this->menuListsFamilies($surname),
			$this->menuListsBranches($surname),
			$this->menuListsPlaces(),
		];
		if ($row->obje) {
			$submenus[] = $this->menuListsMedia();
		}
		if ($row->repo) {
			$submenus[] = $this->menuListsRepositories();
		}
		if ($row->sour) {
			$submenus[] = $this->menuListsSources();
		}
		if ($row->note) {
			$submenus[] = $this->menuListsNotes();
		}

		uasort($submenus, function (Menu $x, Menu $y) {
			return I18N::strcasecmp($x->getLabel(), $y->getLabel());
		});

		return new Menu(I18N::translate('Lists'), '#', 'menu-list', [], $submenus);
	}

	/**
	 * A menu for the list of branches
	 *
	 * @param string $surname The significant surname on the page
	 *
	 * @return Menu
	 */
	public function menuListsBranches($surname) {
		return new Menu(I18N::translate('Branches'), e(route('branches', ['ged' => $this->tree->getName(), 'surname' => $surname])), 'menu-branches', ['rel' => 'nofollow']);
	}

	/**
	 * A menu for the list of families
	 *
	 * @param string $surname The significant surname on the page
	 *
	 * @return Menu
	 */
	public function menuListsFamilies($surname) {
		return new Menu(I18N::translate('Families'), e(route('family-list', ['ged' => $this->tree->getName(), 'surname' => $surname])), 'menu-list-indi');
	}

	/**
	 * A menu for the list of individuals
	 *
	 * @param string $surname The significant surname on the page
	 *
	 * @return Menu
	 */
	public function menuListsIndividuals($surname) {
		return new Menu(I18N::translate('Individuals'), e(route('individual-list', ['ged' => $this->tree->getName(), 'surname' => $surname])), 'menu-list-indi');
	}

	/**
	 * A menu for the list of media objects
	 *
	 * @return Menu
	 */
	public function menuListsMedia() {
		return new Menu(I18N::translate('Media objects'), e(route('media-list', ['ged' => $this->tree->getName()])), 'menu-list-obje', ['rel' => 'nofollow']);
	}

	/**
	 * A menu for the list of notes
	 *
	 * @return Menu
	 */
	public function menuListsNotes() {
		return new Menu(I18N::translate('Shared notes'), e(route('note-list', ['ged' => $this->tree->getName()])), 'menu-list-note', ['rel' => 'nofollow']);
	}

	/**
	 * A menu for the list of individuals
	 *
	 * @return Menu
	 */
	public function menuListsPlaces() {
		return new Menu(I18N::translate('Place hierarchy'), e(Html::url('placelist.php' ,['ged' => $this->tree->getName()])), 'menu-list-plac', ['rel' => 'nofollow']);
	}

	/**
	 * A menu for the list of repositories
	 *
	 * @return Menu
	 */
	public function menuListsRepositories() {
		return new Menu(I18N::translate('Repositories'), e(route('repository-list', ['ged' => $this->tree->getName()])), 'menu-list-repo', ['rel' => 'nofollow']);
	}

	/**
	 * A menu for the list of sources
	 *
	 * @return Menu
	 */
	public function menuListsSources() {
		return new Menu(
			I18N::translate('Sources'), e(route('source-list', ['ged' => $this->tree->getName()])), 'menu-list-sour', ['rel' => 'nofollow']);
	}

	/**
	 * A login menu option (or null if we are already logged in).
	 *
	 * @return Menu|null
	 */
	public function menuLogin() {
		if (Auth::check()) {
			return null;
		} else {
			// Return to this page after login...
			$url = Functions::getQueryUrl();
			// ...but switch from the tree-page to the user-page
			$url = str_replace('route=tree-page', 'route=user-page', $url);

			return new Menu(I18N::translate('Sign in'), e(route('login', ['url' => $url])), 'menu-login', ['rel' => 'nofollow']);
		}
	}

	/**
	 * A logout menu option (or null if we are already logged out).
	 *
	 * @return Menu|null
	 */
	public function menuLogout() {
		if (Auth::check()) {
			return new Menu(I18N::translate('Sign out'), e(route('logout')), 'menu-logout');
		} else {
			return null;
		}
	}

	/**
	 * Get the additional menus created by each of the modules
	 *
	 * @return Menu[]
	 */
	public function menuModules() {
		$menus = [];
		foreach (Module::getActiveMenus($this->tree) as $module) {
			$menus[] = $module->getMenu();
		}

		return array_filter($menus);
	}

	/**
	 * A link to allow users to edit their account settings.
	 *
	 * @return Menu|null
	 */
	public function menuMyAccount() {
		if (Auth::check()) {
			return new Menu(I18N::translate('My account'), e(route('my-account', [])));
		} else {
			return null;
		}
	}

	/**
	 * A link to the user's individual record (individual.php).
	 *
	 * @return Menu|null
	 */
	public function menuMyIndividualRecord() {
		$record = Individual::getInstance($this->tree->getUserPreference(Auth::user(), 'gedcomid'), $this->tree);

		if ($record) {
			return new Menu(I18N::translate('My individual record'), e($record->url()), 'menu-myrecord');
		} else {
			return null;
		}
	}

	/**
	 * A link to the user's personal home page.
	 *
	 * @return Menu
	 */
	public function menuMyPage() {
		return new Menu(I18N::translate('My page'), route('user-page'), 'menu-mypage');
	}

	/**
	 * A menu for the user's personal pages.
	 *
	 * @return Menu|null
	 */
	public function menuMyPages() {
		if (Auth::id() && $this->tree !== null) {
			return new Menu(I18N::translate('My pages'), '#', 'menu-mymenu', [], array_filter([
				$this->menuMyPage(),
				$this->menuMyIndividualRecord(),
				$this->menuMyPedigree(),
				$this->menuMyAccount(),
				$this->menuControlPanel(),
				$this->menuChangeBlocks(),
			]));
		} else {
			return null;
		}
	}

	/**
	 * A link to the user's individual record.
	 *
	 * @return Menu|null
	 */
	public function menuMyPedigree() {
		$gedcomid = $this->tree->getUserPreference(Auth::user(), 'gedcomid');

		if ($gedcomid && Module::isActiveChart($this->tree, 'pedigree_chart')) {
			return new Menu(
				I18N::translate('My pedigree'),
				e(route('pedigree', ['xref' => $gedcomid, 'ged' => $this->tree->getName()])),
				'menu-mypedigree'
			);
		} else {
			return null;
		}
	}

	/**
	 * Create a pending changes menu.
	 *
	 * @return Menu|null
	 */
	public function menuPendingChanges() {
		if ($this->pendingChangesExist()) {
			$url = route('show-pending', [
				'ged' => $this->tree ? $this->tree->getName() : '',
				'url' => $this->request->getRequestUri()
			]);

			return new Menu(I18N::translate('Pending changes'), e($url), 'menu-pending');
		} else {
			return null;
		}
	}

	/**
	 * A menu with a list of reports.
	 *
	 * @return Menu|null
	 */
	public function menuReports() {
		$submenus = [];
		foreach (Module::getActiveReports($this->tree) as $report) {
			$submenus[] = $report->getReportMenu($this->tree);
		}

		if (empty($submenus)) {
			return null;
		} else {
			return new Menu(I18N::translate('Reports'), '#', 'menu-report', ['rel' => 'nofollow'], $submenus);
		}
	}

	/**
	 * Create the search menu.
	 *
	 * @return Menu
	 */
	public function menuSearch() {
		return new Menu(I18N::translate('Search'), '#', 'menu-search', ['rel' => 'nofollow'], array_filter([
			$this->menuSearchGeneral(),
			$this->menuSearchPhonetic(),
			$this->menuSearchAdvanced(),
			$this->menuSearchAndReplace(),
		]));
	}

	/**
	 * Create the general search sub-menu.
	 *
	 * @return Menu
	 */
	public function menuSearchGeneral() {
		return new Menu(I18N::translate('General search'), e(route('search-general', ['ged' => $this->tree->getName()])), 'menu-search-general', ['rel' => 'nofollow']);
	}

	/**
	 * Create the phonetic search sub-menu.
	 *
	 * @return Menu
	 */
	public function menuSearchPhonetic() {
		return new Menu(/* I18N: search using “sounds like”, rather than exact spelling */ I18N::translate('Phonetic search'), e(route('search-phonetic', ['ged' => $this->tree->getName(), 'action' => 'soundex'])), 'menu-search-soundex', ['rel' => 'nofollow']);
	}

	/**
	 * Create the advanced search sub-menu.
	 *
	 * @return Menu
	 */
	public function menuSearchAdvanced() {
		return new Menu(I18N::translate('Advanced search'), e(route('search-advanced', ['ged' => $this->tree->getName()])), 'menu-search-advanced', ['rel' => 'nofollow']);
	}

	/**
	 * Create the advanced search sub-menu.
	 *
	 * @return Menu
	 */
	public function menuSearchAndReplace() {
		if (Auth::isEditor($this->tree)) {
			return new Menu(I18N::translate('Search and replace'), e(route('search-replace', ['ged' => $this->tree->getName(), 'action' => 'replace'])), 'menu-search-replace');
		} else {
			return null;
		}
	}

	/**
	 * Themes menu.
	 *
	 * @return Menu|null
	 */
	public function menuThemes() {
		if ($this->tree !== null && Site::getPreference('ALLOW_USER_THEMES') === '1' && $this->tree->getPreference('ALLOW_THEME_DROPDOWN') === '1') {
			$submenus = [];
			foreach (Theme::installedThemes() as $theme) {
				$class      = 'menu-theme-' . $theme->themeId() . ($theme === $this ? ' active' : '');
				$submenus[] = new Menu($theme->themeName(), '#', $class, [
					'onclick'    => 'return false;',
					'data-theme' => $theme->themeId(),
				]);
			}

			usort($submenus, function (Menu $x, Menu $y) {
				return I18N::strcasecmp($x->getLabel(), $y->getLabel());
			});

			$menu = new Menu(I18N::translate('Theme'), '#', 'menu-theme', [], $submenus);

			return $menu;
		} else {
			return null;
		}
	}

	/**
	 * How many times has the current page been shown?
	 *
	 * @param  PageController $controller
	 *
	 * @return int Number of views, or zero for pages that aren't logged.
	 */
	public function pageViews(PageController $controller) {
		if ($this->tree && $this->tree->getPreference('SHOW_COUNTER')) {
			if (isset($controller->record) && $controller->record instanceof GedcomRecord) {
				return HitCounter::countHit($this->tree, WT_SCRIPT_NAME, $controller->record->getXref());
			} elseif (isset($controller->root) && $controller->root instanceof GedcomRecord) {
				return HitCounter::countHit($this->tree, WT_SCRIPT_NAME, $controller->root->getXref());
			} elseif (WT_SCRIPT_NAME === 'index.php') {
				if (Auth::check() && Filter::get('ctype') !== 'gedcom') {
					return HitCounter::countHit($this->tree, WT_SCRIPT_NAME, 'user:' . Auth::id());
				} else {
					return HitCounter::countHit($this->tree, WT_SCRIPT_NAME, 'gedcom:' . $this->tree->getTreeId());
				}
			}
		}

		return 0;
	}

	/**
	 * Misecellaneous dimensions, fonts, styles, etc.
	 *
	 * @param string $parameter_name
	 *
	 * @return string|int|float
	 */
	public function parameter($parameter_name) {
		$parameters = [
			'chart-background-f'             => 'dddddd',
			'chart-background-m'             => 'cccccc',
			'chart-background-u'             => 'eeeeee',
			'chart-box-x'                    => 250,
			'chart-box-y'                    => 80,
			'chart-font-color'               => '000000',
			'chart-spacing-x'                => 5,
			'chart-spacing-y'                => 10,
			'compact-chart-box-x'            => 240,
			'compact-chart-box-y'            => 50,
			'distribution-chart-high-values' => '555555',
			'distribution-chart-low-values'  => 'cccccc',
			'distribution-chart-no-values'   => 'ffffff',
			'distribution-chart-x'           => 440,
			'distribution-chart-y'           => 220,
			'line-width'                     => 1.5,
			'shadow-blur'                    => 0,
			'shadow-color'                   => '',
			'shadow-offset-x'                => 0,
			'shadow-offset-y'                => 0,
			'stats-small-chart-x'            => 440,
			'stats-small-chart-y'            => 125,
			'stats-large-chart-x'            => 900,
			'image-dline'                    => static::ASSET_DIR . 'images/dline.png',
			'image-dline2'                   => static::ASSET_DIR . 'images/dline2.png',
			'image-hline'                    => static::ASSET_DIR . 'images/hline.png',
			'image-spacer'                   => static::ASSET_DIR . 'images/spacer.png',
			'image-vline'                    => static::ASSET_DIR . 'images/vline.png',
			'image-minus'                    => static::ASSET_DIR . 'images/minus.png',
			'image-plus'                     => static::ASSET_DIR . 'images/plus.png',
		];

		if (array_key_exists($parameter_name, $parameters)) {
			return $parameters[$parameter_name];
		} else {
			throw new \InvalidArgumentException($parameter_name);
		}
	}

	/**
	 * Are there any pending changes for us to approve?
	 *
	 * @return bool
	 */
	public function pendingChangesExist() {
		return $this->tree && $this->tree->hasPendingEdit() && Auth::isModerator($this->tree);
	}

	/**
	 * Create a pending changes link. Some themes prefer an alert/banner to a menu.
	 *
	 * @return string
	 */
	public function pendingChangesLink() {
		return '<a href="' . e(route('show-pending', ['ged' => $this->tree->getName()])) . '">' . $this->pendingChangesLinkText() . '</a>';
	}

	/**
	 * Text to use in the pending changes link.
	 *
	 * @return string
	 */
	public function pendingChangesLinkText() {
		return I18N::translate('There are pending changes for you to moderate.');
	}

	/**
	 * Generate a list of items for the main menu.
	 *
	 * @return Menu[]
	 */
	public function primaryMenu() {
		global $controller;

		if ($this->tree) {
			$individual = $controller->getSignificantIndividual();

			return array_filter(array_merge([
				$this->menuHomePage(),
				$this->menuChart($individual),
				$this->menuLists($controller->getSignificantSurname()),
				$this->menuCalendar(),
				$this->menuReports(),
				$this->menuSearch(),
			], $this->menuModules()));
		} else {
			// No public trees? No genealogy menu!
			return [];
		}
	}

	/**
	 * Create the primary menu.
	 *
	 * @param Menu[] $menus
	 *
	 * @return string
	 */
	public function primaryMenuContent(array $menus) {
		return implode('', array_map(function (Menu $menu) {
			return $menu->bootstrap4();
		}, $menus));
	}

	/**
	 * Generate a list of items for the user menu.
	 *
	 * @return Menu[]
	 */
	public function secondaryMenu() {
		return array_filter([
			$this->menuPendingChanges(),
			$this->menuMyPages(),
			$this->menuFavorites(),
			$this->menuThemes(),
			$this->menuLanguages(),
			$this->menuLogin(),
			$this->menuLogout(),
		]);
	}

	/**
	 * Format the secondary menu.
	 *
	 * @param Menu[] $menus
	 *
	 * @return string
	 */
	public function secondaryMenuContent(array $menus) {
		return implode('', array_map(function (Menu $menu) {
			return $menu->bootstrap4();
		}, $menus));
	}

	/**
	 * A list of CSS files to include for this page.
	 *
	 * @return string[]
	 */
	public function stylesheets() {

		if (I18N::direction() === 'rtl') {
			$stylesheets = [
				WT_ASSETS_URL . 'css/vendor-rtl.css',
				self::STYLESHEET,
			];
		} else {
			$stylesheets = [
				WT_ASSETS_URL . 'css/vendor.css',
				self::STYLESHEET,
			];
		}

		return $stylesheets;
	}

	/**
	 * A fixed string to identify this theme, in settings, etc.
	 *
	 * @return string
	 */
	public function themeId() {
		return static::THEME_DIR;
	}

	/**
	 * What is this theme called?
	 *
	 * @return string
	 */
	abstract public function themeName();

}
