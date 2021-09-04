<?php

/**
 * The Main class for Web App
 * @package WebApp
 * @author SleePy <sleepy @ simplemachines (dot) org>
 * @copyright 2021
 * @license 3-Clause BSD https://opensource.org/licenses/BSD-3-Clause
 * @version 1.0
 */
class WebApp
{
	public static $DisplayOptions = [
		0 => 'standalone',
		1 => 'fullscreen',
		2 => 'minimal',
		3 => 'browser',
	];
	
	public static $OrientationOptions = [
		0 => 'any',
		1 => 'natural',
		2 => 'landscape-primary',
		3 => 'landscape-secondary',
		4 => 'portrait',
		5 => 'portrait-primary',
		6 => 'portrait-secondary',
	];

	// The default manifest	
	public static $default_manifest = [
		'name' => null,
		'short_name' => null,
		'theme_color' => '#03173d',
		'background_color' => 'standalone',
		'orientation' => 'portrait',
		'scope' => null,
		'start_url' => null,
		'icons' => [
			'src' =>  'favicon.ico',
			'type' => 'image/x-icon',
			'sizes' => '512x512'
		]
	];

	// Orientation, display and icons are handled separtely.
	public static $all_options = [
		'name', 'short_name', 'theme_color', 'background_color', 'scope', 'start_url'
	];

	/**
	 * Adds in the headers we need for the webapp.
	 *
	 * @api
	 * @CalledIn SMF 2.1
	 * @version 1.0
	 * @since 1.0
	 * @uses integrate_load_theme - Hook SMF2.1
	 * @return void No return is generated
	 */
	public static function hook_load_theme(): void
	{
		global $context, $boardurl, $scripturl;

		// Add the meta tags in.
		$context['meta_tags'][] = ['name' => 'apple-mobile-web-app-capable', 'content' => 'yes'];
		$context['meta_tags'][] = ['name' => 'mobile-wep-app-capable', 'content' => 'yes'];
		$context['meta_tags'][] = ['name' => 'apple-mobile-web-app-status-bar-style', 'content' => 'black'];

		$icon = $boardurl . '/favicon.ico';

		// No good place to call these in the header, so just put them into html_headers.
		$context['html_headers'] .= '
	<link rel="apple-touch-icon" sizes="192x192" href="' . $icon . '">
	<link rel="manifest" href="' . $scripturl . '?action=manifest;v=1">';
	}

	/**
	 * Adds in a action for us to call later.
	 *
	 * @api
	 * @CalledIn SMF 2.1
	 * @version 1.0
	 * @since 1.0
	 * @uses integrate_actions - Hook SMF2.1
	 * @return void
	 */
	public static function hook_actions(array &$actionArray): void
	{
		$actionArray['manifest'] = ['WebApp.php', 'WebApp::hook_action_called'];
	}

	/**
	 * Called by SMF if we call the action=maniffest.
	 *
	 * @api
	 * @CalledIn SMF 2.1
	 * @version 1.0
	 * @since 1.0
	 * @uses integrate_actions - Hook SMF2.1
	 * @return void
	 */
	public static function hook_action_called(): void
	{
		global $mbname, $boardurl, $scripturl, $modSettings, $txt, $context;

		$manifest = array_merge(self::$default_manifest, [
			'name' => $mbname,
			'short_name' => substr($mbname, 0, 15),
			'scope' => $boardurl,
			'start_url' => $scripturl,
			'icons' => [
				'src' => $boardurl . '/favicon.ico',
			]
		]);

		// The settings we have.
		if (isset($modSettings['webapp_display']))
			$manifest['display'] = self::$DisplayOptions[$modSettings['webapp_display']];
		if (isset($modSettings['webapp_display']))
			$manifest['orientation'] = self::$OrientationOptions[$modSettings['webapp_orientation']];

		// Set some standard settings the easy way.
		foreach (self::$all_options as $opt)
			if (isset($modSettings['webapp_' . $opt]) && $modSettings['webapp_' . $opt] != '')
				$manifest[$opt] = (string) $modSettings['webapp_' . $opt];

		// May be a better way to do this.  This also may not be the best way to do this.
		$manifest['lang'] = $txt['lang_locale'];
		if (!empty($context['right_to_left']))
			$manifest['dir'] = 'rtl';

		// Do we have a icon?  The specification allows multiple icons.  Do we really want to implant that?
		// We don't implant sizes (space separated list) or purpose.
		if (!empty($modSettings['webapp_icon']))
		{
			$manifest['icons']['src'] = $modSettings['webapp_icon'];
			$manifest['icons']['type'] = $modSettings['webapp_icon_type'];
		}

		/* Not implanted specification items
			categories - SMF doesn't have a place to track a forum's purpose that fits in the usual cateogires: https://github.com/w3c/manifest/wiki/Categories
			description - We could use the slogan from the theme, but this doesn't provide much benefit really.
			iarc_rating_id - There is a process to get a rating, so most forums won't do this.
			related_applications - Doesn't seem relevant to link to other applications.		
			prefer_related_applications - Doesn't seem relevant to link to other applications.		
			shortcuts - Contextual menu driven here seems like things could get difficult and messy.
			screenshots - Seems only necessary if your submitting it to a store.
		*/

		ob_end_clean();
		header('Content-Type: application/json; charset=UTF8');
		echo json_encode($manifest);
		die;
	}

	/**
	 * Startup the Admin Panels Additions.
	 *
	 * @param array $admin_areas A associate array from the software with all valid admin areas.
	 *
	 * @api
	 * @CalledIn SMF 2.1
	 * @version 1.0
	 * @since 1.0
	 * @uses integrate_admin_areas - Hook SMF2.1
	 * @return void
	 */
	public static function hook_admin_areas(array &$admin_areas): void
	{
		global $txt;

		loadLanguage('WebApp');
		$admin_areas['config']['areas']['modsettings']['subsections']['webapp'] = [$txt['webapp_title']];
	}

	/**
	 * For the help function, load up our text.
	 *
	 *
	 * @api
	 * @CalledIn SMF 2.1
	 * @version 1.0
	 * @since 1.0
	 * @uses integrate_helpadmin - Hook SMF2.1
	 * @return void
	 */
	public static function hook_helpadmin(): void
	{
		loadLanguage('WebApp');
	}

	/**
	 * Setup the Modification's setup page.
	 * For some versions, we put the logs into the modifications sections, its easier.
	 *
	 * @param array $subActions A associate array from the software with all valid modification sections.
	 *
	 * @api
	 * @CalledIn SMF 2.1
	 * @see SFSA::setupModifyModifications()
	 * @version 1.0
	 * @since 1.0
	 * @uses integrate_modify_modifications - Hook SMF2.0
	 * @uses integrate_modify_modifications - Hook SMF2.1
	 * @return void
	 */
	public static function hook_modify_modifications(array &$subActions): void
	{
		$subActions['webapp'] = 'WebApp::startupAdminConfiguration';
	}

	/**
	 * The actual settings page.
	 *
	 * @param bool $return_config If true, returns the configuration options for searches.
	 *
	 * @internal
	 * @CalledIn SMF 2.0, SMF 2.1
	 * @version 1.0
	 * @since 1.0
	 * @uses integrate_modify_modifications - Hook SMF2.0
	 * @uses integrate_modify_modifications - Hook SMF2.1
	 * @return array But only when searching
	 */
	public static function startupAdminConfiguration(bool $return_config = false): array
	{
		global $txt, $scripturl, $context, $settings, $sc, $modSettings;

		$displayOptions = [];
		foreach (self::$DisplayOptions as $idx => $val)
			$displayOptions[$idx] = $txt['webapp_display_' . $val];

		$orientationOptions = [];
		foreach (self::$OrientationOptions as $idx => $val)
			$orientationOptions[$idx] = $txt['webapp_orientation_' . $val];
			
		$config_vars = [
				['title', 'webappgentitle', 'label' => $txt['webapp_title']],

				['check', 'webapp_enabled'],
				['text', 'webapp_theme_color'],
				['text', 'webapp_background_color'],
				'',
				['text', 'webapp_icon'],
				['select', 'webapp_icon_type', ['image/png', 'image/jpg', 'image/svg', 'image/webp']],
				['select', 'webapp_display', $displayOptions],
				['select', 'webapp_orientation', $orientationOptions],
		];

		if ($return_config)
			return $config_vars;

		// Saving?
		if (isset($_GET['save']))
		{
			checkSession();

			saveDBSettings($config_vars);

			writeLog();
			redirectexit($scripturl . '?action=admin;area=modsettings;sa=webapp');
		}

		$context['post_url'] = $scripturl . '?action=admin;area=modsettings;sa=webapp' . ';save';
		prepareDBSettingContext($config_vars);

		return [];
	}
}