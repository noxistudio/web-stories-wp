<?php
/**
 * Class Assets
 *
 * @package   Google\Web_Stories
 * @copyright 2020 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link      https://github.com/google/web-stories-wp
 */

/**
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\Web_Stories;

/**
 * Class Assets
 *
 * @package Google\Web_Stories
 */
class Assets {
	/**
	 * An array of registered styles.
	 *
	 * @var array
	 */
	protected $register_styles = [];

	/**
	 * An array of registered scripts.
	 *
	 * @var array
	 */
	protected $register_scripts = [];

	/**
	 * Get path to file and directory.
	 *
	 * @since 1.8.0
	 *
	 * @param string $path Path.
	 *
	 * @return string
	 */
	public function get_base_path( string $path ): string {
		return WEBSTORIES_PLUGIN_DIR_PATH . $path;
	}

	/**
	 * Get url of file and directory.
	 *
	 * @since 1.8.0
	 *
	 * @param string $path Path.
	 *
	 * @return string
	 */
	public function get_base_url( string $path ): string {
		return WEBSTORIES_PLUGIN_DIR_URL . $path;
	}

	/**
	 * Get asset metadata.
	 *
	 * @since 1.8.0
	 *
	 * @param string $handle Script handle.
	 *
	 * @return array Array containing combined contents of "<$handle>.asset.php" and "<$handle>.chunks.php".
	 */
	public function get_asset_metadata( string $handle ): array {
		$base_path = $this->get_base_path( 'assets/js/' );

		// *.asset.php is generated by DependencyExtractionWebpackPlugin.
		// *.chunks.php is generated by HtmlWebpackPlugin with a custom template.
		$asset_file  = $base_path . $handle . '.asset.php';
		$chunks_file = $base_path . $handle . '.chunks.php';

		// phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		$asset = is_readable( $asset_file ) ? require $asset_file : [];
		// phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		$chunks = is_readable( $chunks_file ) ? require $chunks_file : [];

		// A hash calculated based on the file content of the entry point bundle at <$handle>.js.
		$asset['version'] = $asset['version'] ?? WEBSTORIES_VERSION;

		$asset['dependencies'] = $asset['dependencies'] ?? [];
		$asset['js']           = $chunks['js'] ?? [];
		$asset['css']          = $chunks['css'] ?? [];
		$asset['chunks']       = $chunks['chunks'] ?? [];

		return $asset;
	}

	/**
	 * Register script using handle.
	 *
	 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
	 *
	 * @since 1.8.0
	 *
	 * @param string $script_handle Handle of script.
	 * @param array  $script_dependencies Array of extra dependencies.
	 * @param bool   $with_i18n Optional. Whether to setup i18n for this asset. Default true.
	 *
	 * @return void
	 */
	public function register_script_asset( string $script_handle, array $script_dependencies = [], bool $with_i18n = true ) {
		if ( isset( $this->register_scripts[ $script_handle ] ) ) {
			return;
		}

		$base_script_path = $this->get_base_url( 'assets/js/' );
		$in_footer        = true;

		$asset = $this->get_asset_metadata( $script_handle );

		// Register any chunks of $script_handle first.
		// `$asset['js']` are preloaded chunks, `$asset['chunks']` dynamically imported ones.
		foreach ( $asset['js'] as $chunk ) {
			$this->register_script(
				$chunk,
				$base_script_path . $chunk . '.js',
				[],
				WEBSTORIES_VERSION,
				$in_footer,
				$with_i18n
			);
		}

		// Dynamically imported chunks MUST NOT be added as dependencies here.
		$dependencies = array_merge( $asset['dependencies'], $script_dependencies, $asset['js'] );

		$entry_version = $asset['version'];
		$this->register_script(
			$script_handle,
			$base_script_path . $script_handle . '.js',
			$dependencies,
			$entry_version,
			$in_footer,
			$with_i18n
		);

		// "Save" all the script's chunks so we can later manually fetch them and their translations if needed.
		wp_script_add_data( $script_handle, 'chunks', $asset['chunks'] );

		// Register every dynamically imported chunk as a script, just so
		// that we can print their translations whenever the main script is enqueued.
		// The actual enqueueing of these chunks is done by the main script via dynamic imports.
		foreach ( $asset['chunks'] as $dynamic_chunk ) {
			$this->register_script(
				$dynamic_chunk,
				$base_script_path . $dynamic_chunk . '.js',
				[],
				WEBSTORIES_VERSION,
				$in_footer,
				$with_i18n
			);

			if ( $with_i18n ) {
				wp_add_inline_script( $script_handle, (string) wp_scripts()->print_translations( $dynamic_chunk, false ) );
			}
		}
	}

	/**
	 * Enqueue script using handle.
	 *
	 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
	 *
	 * @since 1.8.0
	 *
	 * @param string $script_handle Handle of script.
	 * @param array  $script_dependencies Array of extra dependencies.
	 * @param bool   $with_i18n Optional. Whether to setup i18n for this asset. Default true.
	 *
	 * @return void
	 */
	public function enqueue_script_asset( string $script_handle, array $script_dependencies = [], bool $with_i18n = true ) {
		$this->register_script_asset( $script_handle, $script_dependencies, $with_i18n );
		$this->enqueue_script( $script_handle );
	}

	/**
	 * Register style using handle.
	 *
	 * @since 1.8.0
	 *
	 * @param string $style_handle Handle of style.
	 * @param array  $style_dependencies Array of extra dependencies.
	 *
	 * @return void
	 */
	public function register_style_asset( string $style_handle, array $style_dependencies = [] ) {
		if ( isset( $this->register_styles[ $style_handle ] ) ) {
			return;
		}

		$base_style_path = $this->get_base_url( 'assets/css/' );

		// Register any chunks of $style_handle first.
		$asset = $this->get_asset_metadata( $style_handle );
		// Webpack appends "-[contenthash]" to filenames of chunks, so omit the `?ver=` query param.
		$chunk_version = null;
		foreach ( $asset['css'] as $style_chunk ) {
			$this->register_style(
				$style_chunk,
				$base_style_path . $style_chunk . '.css',
				[],
				$chunk_version
			);
		}
		$style_dependencies = array_merge( $style_dependencies, $asset['css'] );

		$entry_version = $asset['version'];
		$this->register_style(
			$style_handle,
			$base_style_path . $style_handle . '.css',
			$style_dependencies,
			$entry_version
		);

		wp_style_add_data( $style_handle, 'rtl', 'replace' );
	}

	/**
	 * Enqueue style using handle.
	 *
	 * @since 1.8.0
	 *
	 * @param string $style_handle Handle of style.
	 * @param array  $style_dependencies Array of extra dependencies.
	 *
	 * @return void
	 */
	public function enqueue_style_asset( string $style_handle, array $style_dependencies = [] ) {
		$this->register_style_asset( $style_handle, $style_dependencies );
		$this->enqueue_style( $style_handle );
	}

	/**
	 * Register a CSS stylesheet.
	 *
	 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
	 *
	 * @since 1.8.0
	 *
	 * @param string           $style_handle Name of the stylesheet. Should be unique.
	 * @param string|bool      $src    Full URL of the stylesheet, or path of the stylesheet relative to the WordPress root directory.
	 *                                 If source is set to false, stylesheet is an alias of other stylesheets it depends on.
	 * @param string[]         $deps   Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
	 * @param string|bool|null $ver    Optional. String specifying stylesheet version number, if it has one, which is added to the URL
	 *                                 as a query string for cache busting purposes. If version is set to false, a version
	 *                                 number is automatically added equal to current installed WordPress version.
	 *                                 If set to null, no version is added.
	 * @param string           $media  Optional. The media for which this stylesheet has been defined.
	 *                                 Default 'all'. Accepts media types like 'all', 'print' and 'screen', or media queries like
	 *                                 '(orientation: portrait)' and '(max-width: 640px)'.
	 * @return bool Whether the style has been registered. True on success, false on failure.
	 */
	public function register_style( string $style_handle, $src, array $deps = [], $ver = false, string $media = 'all' ): bool {
		if ( ! isset( $this->register_styles[ $style_handle ] ) ) {
			$this->register_styles[ $style_handle ] = wp_register_style( $style_handle, $src, $deps, $ver, $media );
		}

		return $this->register_styles[ $style_handle ];
	}

	/**
	 * Register a new script.
	 *
	 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
	 *
	 * @since 1.8.0
	 *
	 * @param string           $script_handle    Name of the script. Should be unique.
	 * @param string|bool      $src       Full URL of the script, or path of the script relative to the WordPress root directory.
	 *                                    If source is set to false, script is an alias of other scripts it depends on.
	 * @param string[]         $deps      Optional. An array of registered script handles this script depends on. Default empty array.
	 * @param string|bool|null $ver       Optional. String specifying script version number, if it has one, which is added to the URL
	 *                                    as a query string for cache busting purposes. If version is set to false, a version
	 *                                    number is automatically added equal to current installed WordPress version.
	 *                                    If set to null, no version is added.
	 * @param bool             $in_footer Optional. Whether to enqueue the script before </body> instead of in the <head>.
	 *                                    Default 'false'.
	 * @param bool             $with_i18n Optional. Whether to setup i18n for this asset. Default true.
	 * @return bool Whether the script has been registered. True on success, false on failure.
	 */
	public function register_script( string $script_handle, $src, array $deps = [], $ver = false, bool $in_footer = false, bool $with_i18n = true ): bool {
		if ( ! isset( $this->register_scripts[ $script_handle ] ) ) {
			$this->register_scripts[ $script_handle ] = wp_register_script( $script_handle, $src, $deps, $ver, $in_footer );

			if ( $src && $with_i18n ) {
				wp_set_script_translations( $script_handle, 'web-stories' );
			}
		}

		return $this->register_scripts[ $script_handle ];
	}

	/**
	 * Enqueue a style.
	 *
	 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
	 *
	 * @since 1.8.0
	 *
	 * @param string           $style_handle Name of the stylesheet. Should be unique.
	 * @param string           $src    Full URL of the stylesheet, or path of the stylesheet relative to the WordPress root directory.
	 *                                 Default empty.
	 * @param string[]         $deps   Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
	 * @param string|bool|null $ver    Optional. String specifying stylesheet version number, if it has one, which is added to the URL
	 *                                 as a query string for cache busting purposes. If version is set to false, a version
	 *                                 number is automatically added equal to current installed WordPress version.
	 *                                 If set to null, no version is added.
	 * @param string           $media  Optional. The media for which this stylesheet has been defined.
	 *                                 Default 'all'. Accepts media types like 'all', 'print' and 'screen', or media queries like
	 *                                 '(orientation: portrait)' and '(max-width: 640px)'.
	 * @return void
	 */
	public function enqueue_style( string $style_handle, string $src = '', array $deps = [], $ver = false, string $media = 'all' ) {
		$this->register_style( $style_handle, $src, $deps, $ver, $media );
		wp_enqueue_style( $style_handle, $src, $deps, $ver, $media );
	}

	/**
	 * Enqueue a script.
	 *
	 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
	 *
	 * @since 1.8.0
	 *
	 * @param string           $script_handle    Name of the script. Should be unique.
	 * @param string           $src       Full URL of the script, or path of the script relative to the WordPress root directory.
	 *                                    Default empty.
	 * @param string[]         $deps      Optional. An array of registered script handles this script depends on. Default empty array.
	 * @param string|bool|null $ver       Optional. String specifying script version number, if it has one, which is added to the URL
	 *                                    as a query string for cache busting purposes. If version is set to false, a version
	 *                                    number is automatically added equal to current installed WordPress version.
	 *                                    If set to null, no version is added.
	 * @param bool             $in_footer Optional. Whether to enqueue the script before </body> instead of in the <head>.
	 *                                    Default 'false'.
	 * @param bool             $with_i18n Optional. Whether to setup i18n for this asset. Default true.
	 * @return void
	 */
	public function enqueue_script( string $script_handle, string $src = '', array $deps = [], $ver = false, bool $in_footer = false, bool $with_i18n = false ) {
		$this->register_script( $script_handle, $src, $deps, $ver, $in_footer, $with_i18n );
		wp_enqueue_script( $script_handle, $src, $deps, $ver, $in_footer );
	}

	/**
	 * Remove admin styles.
	 *
	 * @since 1.8.0
	 *
	 * @param array $styles Array to style to be removed.
	 *
	 * @return void
	 */
	public function remove_admin_style( array $styles ) {
		wp_styles()->registered['wp-admin']->deps = array_diff( wp_styles()->registered['wp-admin']->deps, $styles );
	}

	/**
	 * Returns the translations for a script and all of its chunks.
	 *
	 * @since 1.12.0
	 *
	 * @param string $script_handle    Name of the script. Should be unique.
	 * @return array Script translations.
	 */
	public function get_translations( string $script_handle ): array {
		$chunks = wp_scripts()->get_data( $script_handle, 'chunks' );

		$translations = [
			(string) load_script_textdomain( $script_handle, 'web-stories' ),
		];

		foreach ( $chunks as $dynamic_chunk ) {
			$translations[] = (string) load_script_textdomain( $dynamic_chunk, 'web-stories' );
		}

		return array_filter( $translations );
	}
}
