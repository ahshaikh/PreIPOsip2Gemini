<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    /**
     * Get analytics configuration
     */
    public function getConfig()
    {
        $config = [
            'enabled' => setting('analytics_enabled', true),
            'google' => [
                'enabled' => setting('analytics_google_enabled', false),
                'tracking_id' => setting('analytics_google_id', ''),
                'ga4_enabled' => setting('analytics_google_ga4_enabled', false),
                'measurement_id' => setting('analytics_google_measurement_id', ''),
            ],
            'gtm' => [
                'enabled' => setting('analytics_gtm_enabled', false),
                'container_id' => setting('analytics_gtm_id', ''),
            ],
            'facebook' => [
                'enabled' => setting('analytics_facebook_pixel_enabled', false),
                'pixel_id' => setting('analytics_facebook_pixel_id', ''),
            ],
            'hotjar' => [
                'enabled' => setting('analytics_hotjar_enabled', false),
                'site_id' => setting('analytics_hotjar_id', ''),
            ],
            'mixpanel' => [
                'enabled' => setting('analytics_mixpanel_enabled', false),
                'token' => setting('analytics_mixpanel_token', ''),
            ],
            'custom' => [
                'script' => setting('analytics_custom_script', ''),
            ],
            'privacy' => [
                'track_logged_users' => setting('analytics_track_logged_users', true),
                'anonymize_ip' => setting('analytics_anonymize_ip', true),
                'respect_dnt' => setting('analytics_respect_dnt', true),
            ],
        ];

        return response()->json($config);
    }

    /**
     * Update analytics configuration
     */
    public function updateConfig(Request $request)
    {
        $validated = $request->validate([
            'enabled' => 'boolean',
            'google_enabled' => 'boolean',
            'google_tracking_id' => 'nullable|string|regex:/^UA-\d{4,10}-\d{1,4}$/',
            'ga4_enabled' => 'boolean',
            'google_measurement_id' => 'nullable|string|regex:/^G-[A-Z0-9]{10}$/',
            'gtm_enabled' => 'boolean',
            'gtm_container_id' => 'nullable|string|regex:/^GTM-[A-Z0-9]{7,8}$/',
            'facebook_enabled' => 'boolean',
            'facebook_pixel_id' => 'nullable|string|regex:/^\d{15,16}$/',
            'hotjar_enabled' => 'boolean',
            'hotjar_site_id' => 'nullable|string|regex:/^\d{6,8}$/',
            'mixpanel_enabled' => 'boolean',
            'mixpanel_token' => 'nullable|string',
            'custom_script' => 'nullable|string|max:10000',
            'track_logged_users' => 'boolean',
            'anonymize_ip' => 'boolean',
            'respect_dnt' => 'boolean',
        ]);

        $settingsMap = [
            'enabled' => 'analytics_enabled',
            'google_enabled' => 'analytics_google_enabled',
            'google_tracking_id' => 'analytics_google_id',
            'ga4_enabled' => 'analytics_google_ga4_enabled',
            'google_measurement_id' => 'analytics_google_measurement_id',
            'gtm_enabled' => 'analytics_gtm_enabled',
            'gtm_container_id' => 'analytics_gtm_id',
            'facebook_enabled' => 'analytics_facebook_pixel_enabled',
            'facebook_pixel_id' => 'analytics_facebook_pixel_id',
            'hotjar_enabled' => 'analytics_hotjar_enabled',
            'hotjar_site_id' => 'analytics_hotjar_id',
            'mixpanel_enabled' => 'analytics_mixpanel_enabled',
            'mixpanel_token' => 'analytics_mixpanel_token',
            'custom_script' => 'analytics_custom_script',
            'track_logged_users' => 'analytics_track_logged_users',
            'anonymize_ip' => 'analytics_anonymize_ip',
            'respect_dnt' => 'analytics_respect_dnt',
        ];

        foreach ($validated as $key => $value) {
            if (isset($settingsMap[$key])) {
                setting([$settingsMap[$key] => $value]);
            }
        }

        return response()->json([
            'message' => 'Analytics configuration updated successfully',
        ]);
    }

    /**
     * Get analytics script snippets for frontend integration
     */
    public function getScripts()
    {
        $scripts = [];

        // Google Analytics (Universal Analytics)
        if (setting('analytics_google_enabled', false)) {
            $trackingId = setting('analytics_google_id');
            $anonymizeIp = setting('analytics_anonymize_ip', true) ? "'anonymize_ip': true," : '';

            $scripts['google_analytics'] = <<<HTML
<!-- Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id={$trackingId}"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '{$trackingId}', {
    {$anonymizeIp}
  });
</script>
HTML;
        }

        // Google Analytics 4 (GA4)
        if (setting('analytics_google_ga4_enabled', false)) {
            $measurementId = setting('analytics_google_measurement_id');
            $anonymizeIp = setting('analytics_anonymize_ip', true) ? "'anonymize_ip': true," : '';

            $scripts['google_analytics_4'] = <<<HTML
<!-- Google Analytics 4 -->
<script async src="https://www.googletagmanager.com/gtag/js?id={$measurementId}"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '{$measurementId}', {
    {$anonymizeIp}
  });
</script>
HTML;
        }

        // Google Tag Manager
        if (setting('analytics_gtm_enabled', false)) {
            $containerId = setting('analytics_gtm_id');

            $scripts['google_tag_manager_head'] = <<<HTML
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','{$containerId}');</script>
<!-- End Google Tag Manager -->
HTML;

            $scripts['google_tag_manager_body'] = <<<HTML
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={$containerId}"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
HTML;
        }

        // Facebook Pixel
        if (setting('analytics_facebook_pixel_enabled', false)) {
            $pixelId = setting('analytics_facebook_pixel_id');

            $scripts['facebook_pixel'] = <<<HTML
<!-- Facebook Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '{$pixelId}');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id={$pixelId}&ev=PageView&noscript=1"
/></noscript>
<!-- End Facebook Pixel Code -->
HTML;
        }

        // Hotjar
        if (setting('analytics_hotjar_enabled', false)) {
            $siteId = setting('analytics_hotjar_id');

            $scripts['hotjar'] = <<<HTML
<!-- Hotjar Tracking Code -->
<script>
    (function(h,o,t,j,a,r){
        h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
        h._hjSettings={hjid:{$siteId},hjsv:6};
        a=o.getElementsByTagName('head')[0];
        r=o.createElement('script');r.async=1;
        r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
        a.appendChild(r);
    })(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv=');
</script>
<!-- End Hotjar Tracking Code -->
HTML;
        }

        // Mixpanel
        if (setting('analytics_mixpanel_enabled', false)) {
            $token = setting('analytics_mixpanel_token');

            $scripts['mixpanel'] = <<<HTML
<!-- Mixpanel -->
<script type="text/javascript">
(function(f,b){if(!b.__SV){var e,g,i,h;window.mixpanel=b;b._i=[];b.init=function(e,f,c){function g(a,d){var b=d.split(".");2==b.length&&(a=a[b[0]],d=b[1]);a[d]=function(){a.push([d].concat(Array.prototype.slice.call(arguments,0)))}}var a=b;"undefined"!==typeof c?a=b[c]=[]:c="mixpanel";a.people=a.people||[];a.toString=function(a){var d="mixpanel";"mixpanel"!==c&&(d+="."+c);a||(d+=" (stub)");return d};a.people.toString=function(){return a.toString(1)+".people (stub)"};i="disable time_event track track_pageview track_links track_forms track_with_groups add_group set_group remove_group register register_once alias unregister identify name_tag set_config reset opt_in_tracking opt_out_tracking has_opted_in_tracking has_opted_out_tracking clear_opt_in_out_tracking start_batch_senders people.set people.set_once people.unset people.increment people.append people.union people.track_charge people.clear_charges people.delete_user people.remove".split(" ");
for(h=0;h<i.length;h++)g(a,i[h]);var j="set set_once union unset remove delete".split(" ");a.get_group=function(){function b(c){d[c]=function(){call2_args=arguments;call2=[c].concat(Array.prototype.slice.call(call2_args,0));a.push([e,call2])}}for(var d={},e=["get_group"].concat(Array.prototype.slice.call(arguments,0)),c=0;c<j.length;c++)b(j[c]);return d};b._i.push([e,f,c])};b.__SV=1.2;e=f.createElement("script");e.type="text/javascript";e.async=!0;e.src="undefined"!==typeof MIXPANEL_CUSTOM_LIB_URL?
MIXPANEL_CUSTOM_LIB_URL:"file:"===f.location.protocol&&"//cdn.mxpnl.com/libs/mixpanel-2-latest.min.js".match(/^\\/\\//)?"https://cdn.mxpnl.com/libs/mixpanel-2-latest.min.js":"//cdn.mxpnl.com/libs/mixpanel-2-latest.min.js";g=f.getElementsByTagName("script")[0];g.parentNode.insertBefore(e,g)}})(document,window.mixpanel||[]);
mixpanel.init("{$token}");
</script>
<!-- End Mixpanel -->
HTML;
        }

        // Custom Script
        if (!empty(setting('analytics_custom_script'))) {
            $scripts['custom'] = setting('analytics_custom_script');
        }

        return response()->json([
            'scripts' => $scripts,
            'privacy' => [
                'track_logged_users' => setting('analytics_track_logged_users', true),
                'anonymize_ip' => setting('analytics_anonymize_ip', true),
                'respect_dnt' => setting('analytics_respect_dnt', true),
            ],
        ]);
    }

    /**
     * Test analytics integration
     */
    public function test(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|in:google,ga4,gtm,facebook,hotjar,mixpanel',
        ]);

        $provider = $validated['provider'];
        $results = [];

        switch ($provider) {
            case 'google':
                $trackingId = setting('analytics_google_id');
                $results = [
                    'provider' => 'Google Analytics (Universal)',
                    'tracking_id' => $trackingId,
                    'configured' => !empty($trackingId),
                    'format_valid' => preg_match('/^UA-\d{4,10}-\d{1,4}$/', $trackingId),
                ];
                break;

            case 'ga4':
                $measurementId = setting('analytics_google_measurement_id');
                $results = [
                    'provider' => 'Google Analytics 4',
                    'measurement_id' => $measurementId,
                    'configured' => !empty($measurementId),
                    'format_valid' => preg_match('/^G-[A-Z0-9]{10}$/', $measurementId),
                ];
                break;

            case 'gtm':
                $containerId = setting('analytics_gtm_id');
                $results = [
                    'provider' => 'Google Tag Manager',
                    'container_id' => $containerId,
                    'configured' => !empty($containerId),
                    'format_valid' => preg_match('/^GTM-[A-Z0-9]{7,8}$/', $containerId),
                ];
                break;

            case 'facebook':
                $pixelId = setting('analytics_facebook_pixel_id');
                $results = [
                    'provider' => 'Facebook Pixel',
                    'pixel_id' => $pixelId,
                    'configured' => !empty($pixelId),
                    'format_valid' => preg_match('/^\d{15,16}$/', $pixelId),
                ];
                break;

            case 'hotjar':
                $siteId = setting('analytics_hotjar_id');
                $results = [
                    'provider' => 'Hotjar',
                    'site_id' => $siteId,
                    'configured' => !empty($siteId),
                    'format_valid' => preg_match('/^\d{6,8}$/', $siteId),
                ];
                break;

            case 'mixpanel':
                $token = setting('analytics_mixpanel_token');
                $results = [
                    'provider' => 'Mixpanel',
                    'token' => substr($token, 0, 8) . '...',
                    'configured' => !empty($token),
                    'format_valid' => strlen($token) >= 32,
                ];
                break;
        }

        $results['status'] = $results['configured'] && $results['format_valid'] ? 'valid' : 'invalid';

        return response()->json($results);
    }

    /**
     * Get analytics status overview
     */
    public function status()
    {
        $providers = [
            'google' => [
                'name' => 'Google Analytics',
                'enabled' => setting('analytics_google_enabled', false),
                'configured' => !empty(setting('analytics_google_id')),
            ],
            'ga4' => [
                'name' => 'Google Analytics 4',
                'enabled' => setting('analytics_google_ga4_enabled', false),
                'configured' => !empty(setting('analytics_google_measurement_id')),
            ],
            'gtm' => [
                'name' => 'Google Tag Manager',
                'enabled' => setting('analytics_gtm_enabled', false),
                'configured' => !empty(setting('analytics_gtm_id')),
            ],
            'facebook' => [
                'name' => 'Facebook Pixel',
                'enabled' => setting('analytics_facebook_pixel_enabled', false),
                'configured' => !empty(setting('analytics_facebook_pixel_id')),
            ],
            'hotjar' => [
                'name' => 'Hotjar',
                'enabled' => setting('analytics_hotjar_enabled', false),
                'configured' => !empty(setting('analytics_hotjar_id')),
            ],
            'mixpanel' => [
                'name' => 'Mixpanel',
                'enabled' => setting('analytics_mixpanel_enabled', false),
                'configured' => !empty(setting('analytics_mixpanel_token')),
            ],
        ];

        $totalProviders = count($providers);
        $enabledProviders = collect($providers)->filter(fn($p) => $p['enabled'])->count();
        $configuredProviders = collect($providers)->filter(fn($p) => $p['configured'])->count();

        return response()->json([
            'overview' => [
                'analytics_enabled' => setting('analytics_enabled', true),
                'total_providers' => $totalProviders,
                'enabled_providers' => $enabledProviders,
                'configured_providers' => $configuredProviders,
            ],
            'providers' => $providers,
            'privacy' => [
                'track_logged_users' => setting('analytics_track_logged_users', true),
                'anonymize_ip' => setting('analytics_anonymize_ip', true),
                'respect_dnt' => setting('analytics_respect_dnt', true),
            ],
        ]);
    }
}
