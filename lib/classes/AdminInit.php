<?php

namespace WebPExpress;

/**
 *
 */

class AdminInit
{
    public static function init() {

        // uncomment next line to debug an error during activation
        //include __DIR__ . "/../debug.php";

        if (Option::getOption('webp-express-actions-pending')) {
            \WebPExpress\Actions::processQueuedActions();
        }

        self::addHooks();


    }

    public static function runMigrationIfNeeded()
    {
        // When an update requires a migration, the number should be increased
        define('WEBPEXPRESS_MIGRATION_VERSION', '14');

        if (WEBPEXPRESS_MIGRATION_VERSION != Option::getOption('webp-express-migration-version', 0)) {
            // run migration logic
            include WEBPEXPRESS_PLUGIN_DIR . '/lib/migrate/migrate.php';
        }

        // uncomment next line to test-run a migration
        //include WEBPEXPRESS_PLUGIN_DIR . '/lib/migrate/migrate14.php';
    }

    public static function pageNowIs($pageId)
    {
        global $pagenow;

        if ((!isset($pagenow)) || (empty($pagenow))) {
            return false;
        }
        return ($pageId == $pagenow);
    }


    public static function addHooksAfterAdminInit()
    {

        if (current_user_can('manage_options')) {

            // Hooks related to conversion page (in media)
            //if (self::pageNowIs('upload.php')) {
                if (isset($_GET['page']) && ('webp_express_conversion_page' === $_GET['page'])) {
                    //add_action('admin_enqueue_scripts', array('\WebPExpress\WCFMPage', 'enqueueScripts'));
                    add_action('admin_head', array('\WebPExpress\WCFMPage', 'addToHead'));
                }
            //}

            // Hooks related to options page
            if (self::pageNowIs('options-general.php') || self::pageNowIs('settings.php')) {
                if (isset($_GET['page']) && ('webp_express_settings_page' === $_GET['page'])) {
                    add_action('admin_enqueue_scripts', array('\WebPExpress\OptionsPage', 'enqueueScripts'));
                }
            }

            // Hooks related to plugins page
            if (self::pageNowIs('plugins.php')) {
                add_action('admin_enqueue_scripts', array('\WebPExpress\PluginPageScript', 'enqueueScripts'));
            }

            add_action("admin_post_webpexpress_settings_submit", array('\WebPExpress\OptionsPageHooks', 'submitHandler'));


            // Ajax actions
            add_action('wp_ajax_list_unconverted_files', array('\WebPExpress\BulkConvert', 'processAjaxListUnconvertedFiles'));
            add_action('wp_ajax_convert_file', array('\WebPExpress\Convert', 'processAjaxConvertFile'));
            add_action('wp_ajax_webpexpress_view_log', array('\WebPExpress\ConvertLog', 'processAjaxViewLog'));
            add_action('wp_ajax_webpexpress_purge_cache', array('\WebPExpress\CachePurge', 'processAjaxPurgeCache'));
            add_action('wp_ajax_webpexpress_purge_log', array('\WebPExpress\LogPurge', 'processAjaxPurgeLog'));
            add_action('wp_ajax_webpexpress_dismiss_message', array('\WebPExpress\DismissableMessages', 'processAjaxDismissMessage'));
            add_action('wp_ajax_webpexpress_dismiss_global_message', array('\WebPExpress\DismissableGlobalMessages', 'processAjaxDismissGlobalMessage'));
            add_action('wp_ajax_webpexpress_self_test', array('\WebPExpress\SelfTest', 'processAjax'));
            add_action('wp_ajax_webpexpress-wcfm-api', array('\WebPExpress\WCFMApi', 'processRequest'));


            // Add settings link on the plugins list page
            add_filter('plugin_action_links_' . plugin_basename(WEBPEXPRESS_PLUGIN), array('\WebPExpress\AdminUi', 'pluginActionLinksFilter'), 10, 2);

            // Add settings link in multisite
            add_filter('network_admin_plugin_action_links_' . plugin_basename(WEBPEXPRESS_PLUGIN), array('\WebPExpress\AdminUi', 'networkPluginActionLinksFilter'), 10, 2);
        }

    }

    public static function addHooks()
    {

        // Plugin activation, deactivation and uninstall
        register_activation_hook(WEBPEXPRESS_PLUGIN, array('\WebPExpress\PluginActivate', 'activate'));
        register_deactivation_hook(WEBPEXPRESS_PLUGIN, array('\WebPExpress\PluginDeactivate', 'deactivate'));
        register_uninstall_hook(WEBPEXPRESS_PLUGIN, array('\WebPExpress\PluginUninstall', 'uninstall'));

                /*$start = microtime(true);
                BiggerThanSourceDummyFilesBulk::updateStatus(Config::loadConfig());
                echo microtime(true) - $start;*/


        // Some hooks must be registered AFTER admin_init...
        add_action("admin_init", array('\WebPExpress\AdminInit', 'addHooksAfterAdminInit'));

        // Run migration AFTER admin_init hook (important, as insert_with_markers injection otherwise fails, see #394)
        // PS: "plugins_loaded" is to early, as insert_with_markers fails.
        // PS: Unfortunately Message::addMessage doesnt print until next load now, we should look into that.
        // PPS: It does run. It must be the Option that does not react
        //add_action("admin_init", array('\WebPExpress\AdminInit', 'runMigrationIfNeeded'));

        add_action("admin_init", array('\WebPExpress\AdminInit', 'runMigrationIfNeeded'));

        add_action("admin_notices", array('\WebPExpress\DismissableGlobalMessages', 'printMessages'));

        if (Multisite::isNetworkActivated()) {
            if (is_network_admin()) {
                add_action("network_admin_menu", array('\WebPExpress\AdminUi', 'networAdminMenuHook'));
            } else {
                add_action("admin_menu", array('\WebPExpress\AdminUi', 'adminMenuHookMultisite'));
            }

        } else {
            add_action("admin_menu", array('\WebPExpress\AdminUi', 'adminMenuHook'));
        }

        // Print pending messages, if any
        if (Option::getOption('webp-express-messages-pending')) {
            add_action(Multisite::isNetworkActivated() ? 'network_admin_notices' : 'admin_notices', array('\WebPExpress\Messenger', 'printPendingMessages'));
        }


        // PS:
        // Filters for processing upload hooks in order to convert images upon upload (wp_handle_upload / image_make_intermediate_size)
        // are located in webp-express.php

    }
}
