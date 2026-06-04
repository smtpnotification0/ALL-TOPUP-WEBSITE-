<!-- Web Application Manifest -->
<link rel="manifest" href="{{ route('manifest') }}">
<!-- Chrome for Android theme color -->
<meta name="theme-color" content="{{ $config['theme_color'] }}">

<!-- Add to homescreen for Chrome on Android -->
<meta name="mobile-web-app-capable" content="{{ $config['display'] == 'standalone' ? 'yes' : 'no' }}">
<meta name="application-name" content="{{ $config['short_name'] }}">
@if (!empty($settings->pwa_icon))
<link rel="icon" type="image/png" sizes="512x512" href="{{ get_image($settings->pwa_icon) }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ get_image($settings->pwa_icon) }}">
@endif

<!-- Add to homescreen for Safari on iOS -->
<meta name="apple-mobile-web-app-capable" content="{{ $config['display'] == 'standalone' ? 'yes' : 'no' }}">
<meta name="apple-mobile-web-app-status-bar-style" content="{{ $config['status_bar'] }}">
<meta name="apple-mobile-web-app-title" content="{{ $config['short_name'] }}">
@if (!empty($settings->pwa_icon))
<link rel="apple-touch-icon" sizes="180x180" href="{{ get_image($settings->pwa_icon) }}">
<link rel="apple-touch-icon" sizes="152x152" href="{{ get_image($settings->pwa_icon) }}">
<link rel="apple-touch-icon" sizes="144x144" href="{{ get_image($settings->pwa_icon) }}">
<link rel="apple-touch-icon" sizes="120x120" href="{{ get_image($settings->pwa_icon) }}">
<link rel="apple-touch-icon" sizes="114x114" href="{{ get_image($settings->pwa_icon) }}">
<link rel="apple-touch-icon" sizes="76x76" href="{{ get_image($settings->pwa_icon) }}">
<link rel="apple-touch-icon" sizes="72x72" href="{{ get_image($settings->pwa_icon) }}">
<link rel="apple-touch-icon" sizes="60x60" href="{{ get_image($settings->pwa_icon) }}">
<link rel="apple-touch-icon" sizes="57x57" href="{{ get_image($settings->pwa_icon) }}">
@endif

<!-- Tile for Win8 -->
<meta name="msapplication-TileColor" content="{{ $config['background_color'] }}">
@if (!empty($settings->pwa_icon))
<meta name="msapplication-TileImage" content="{{ get_image($settings->pwa_icon) }}">
@endif

<script type="text/javascript">
    // Initialize the service worker
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/serviceworker.js', {
            scope: '.'
        }).then(function(registration) {
            //
        }, function(err) {
            //
        });
    }
</script>
