<?php
/**
 * Branded maintenance page for Nederlandse Obesitas Kliniek.
 *
 * WordPress automatically loads wp-content/maintenance.php when a .maintenance
 * flag file exists in the WordPress root. This file is a standalone page with
 * zero WordPress dependency — pure PHP/HTML/CSS.
 *
 * @see https://developer.wordpress.org/reference/functions/wp_maintenance/
 */

http_response_code(503);
header('Retry-After: 3600');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Robots-Tag: noindex, nofollow');
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Onderhoud — Nederlandse Obesitas Kliniek</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/wp-content/themes/nok-2025-v1/assets/img/icon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/wp-content/themes/nok-2025-v1/assets/img/icon/favicon-16x16.png">
    <style>
        @font-face {
            font-family: 'Realist';
            src: url('/wp-content/themes/nok-2025-v1/assets/fonts/realist-new/Realist-Medium.woff2') format('woff2');
            font-weight: 500;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Realist';
            src: url('/wp-content/themes/nok-2025-v1/assets/fonts/realist-new/Realist-Bold.woff2') format('woff2');
            font-weight: 700;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Inter';
            src: url('/wp-content/themes/nok-2025-v1/assets/fonts/inter/InterVariable.woff2') format('woff2');
            font-weight: 100 900;
            font-style: normal;
            font-display: swap;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            font-size: 1rem;
            line-height: 1.6;
            color: #1a2a3a;
            background: linear-gradient(160deg, #e4e6ee 0%, #f3f4f9 100%);
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .card {
            background: #fff;
            border-radius: 2rem;
            max-width: 520px;
            width: 100%;
            padding: 3rem 2.5rem 2.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #00aae5, #60b178, #ffd41f);
        }

        .logo {
            display: block;
            height: 2.25rem;
            width: auto;
            margin: 0 auto 2rem;
            color: #1a2a3a;
        }

        .icon {
            display: block;
            width: 120px;
            height: auto;
            margin: 0 auto 1.75rem;
        }

        h1 {
            font-family: 'Realist', system-ui, sans-serif;
            font-weight: 700;
            font-size: clamp(1.625rem, 4vw, 2.25rem);
            line-height: 1.2;
            color: #1a2a3a;
            margin-bottom: 0.75rem;
        }

        .body-text {
            color: #4a5568;
            margin-bottom: 1.75rem;
            font-size: 0.9375rem;
        }

        .phone-box {
            background: #ffd41f;
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.75rem;
        }

        .phone-box a {
            display: block;
            font-family: 'Realist', system-ui, sans-serif;
            font-weight: 700;
            font-size: 1.375rem;
            color: #1a2a3a;
            text-decoration: none;
            letter-spacing: 0.02em;
        }

        .phone-box a:hover {
            text-decoration: underline;
        }

        .phone-box p {
            font-size: 0.8125rem;
            color: #1a2a3a;
            margin-top: 0.25rem;
            opacity: 0.8;
        }

        .footer-text {
            font-size: 0.8125rem;
            color: #8896a6;
            font-family: 'Realist', system-ui, sans-serif;
            font-weight: 500;
        }

        /* Mobile */
        @media (max-width: 480px) {
            body {
                padding: 1rem;
            }

            .card {
                border-radius: 1.25rem;
                padding: 2.25rem 1.5rem 1.75rem;
            }

            .icon {
                width: 90px;
            }

            .logo {
                margin-bottom: 1.5rem;
            }
        }

        /* Dark mode */
        @media (prefers-color-scheme: dark) {
            body {
                background: linear-gradient(160deg, #00132f 0%, #0b2355 100%);
            }

            .card {
                background: #14477c;
                box-shadow: 0 4px 24px rgba(0, 0, 0, 0.2);
            }

            .logo {
                color: #f3f4f9;
            }

            h1 {
                color: #f3f4f9;
            }

            .body-text {
                color: #c5d0dc;
            }

            .phone-box a {
                color: #1a2a3a;
            }

            .phone-box p {
                color: #1a2a3a;
            }

            .footer-text {
                color: #8ba4bf;
            }
        }
    </style>
</head>
<body>
    <main class="card" role="main">
        <!-- NOK Logo -->
        <svg class="logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 464 174" aria-label="Nederlandse Obesitas Kliniek"><path d="M120.05,24.86a11.25,11.25,0,0,0,4.2-9A11.35,11.35,0,0,0,120,6.7c-3-2.43-6.18-2.91-10-2.91h-4.85v24.1h4.78c4,0,7.11-.48,10.18-3M108,6.28H110c3,0,5.64.38,8,2.33a9.42,9.42,0,0,1,.14,14.3c-2.36,2.07-5,2.49-8.13,2.49H108Z" transform="translate(-2.57 -2.64)" fill="currentColor"/><polygon points="17.83 6.68 37.91 26.34 37.91 1.16 35.05 1.16 35.05 19.82 14.95 0.13 14.95 25.25 17.83 25.25 17.83 6.68" fill="currentColor"/><polygon points="63.78 25.25 77.14 25.25 77.14 22.76 66.65 22.76 66.65 13.3 76.82 13.3 76.82 10.79 66.65 10.79 66.65 3.64 77.14 3.64 77.14 1.15 63.78 1.15 63.78 25.25" fill="currentColor"/><polygon points="159.54 22.76 149.05 22.76 149.05 13.3 159.24 13.3 159.24 10.79 149.05 10.79 149.05 3.64 159.54 3.64 159.54 1.15 146.18 1.15 146.18 25.25 159.54 25.25 159.54 22.76" fill="currentColor"/><path d="M190.37,17.31h.72l7.78,10.58h3.52l-8.19-10.8c4-.32,6.45-2.94,6.45-6.65,0-5.44-4.55-6.65-9.43-6.65H187.5v24.1h2.87Zm0-11h.85c3.45,0,6.56.38,6.56,4.35,0,3.74-3.28,4.32-6.53,4.32h-.88Z" transform="translate(-2.57 -2.64)" fill="currentColor"/><polygon points="233.92 22.76 226.81 22.76 226.81 1.15 223.95 1.15 223.95 25.25 233.92 25.25 233.92 22.76" fill="currentColor"/><path d="M269.32,2.64,257.43,27.89h3.14l3.11-6.84h11l3,6.84h3.17Zm-4.48,15.92,4.41-9.66,4.3,9.66Z" transform="translate(-2.57 -2.64)" fill="currentColor"/><polygon points="304.67 6.68 324.76 26.34 324.76 1.16 321.89 1.16 321.89 19.82 301.8 0.13 301.8 25.25 304.67 25.25 304.67 6.68" fill="currentColor"/><path d="M368.16,24.86a11.31,11.31,0,0,0,4.2-9,11.33,11.33,0,0,0-4.27-9.17c-3-2.43-6.18-2.91-10-2.91h-4.86v24.1H358c4,0,7.1-.48,10.18-3M356.07,6.28h2.07c3,0,5.65.38,8,2.33a9.43,9.43,0,0,1,.14,14.3c-2.36,2.07-5,2.49-8.14,2.49h-2.07Z" transform="translate(-2.57 -2.64)" fill="currentColor"/><path d="M403.59,25.82a5.18,5.18,0,0,1-5.17-4.48l-2.8.74a7.85,7.85,0,0,0,8,6.22c4.48,0,8.1-3.22,8.1-7.44,0-3.83-2.84-5.4-6.18-6.77l-1.71-.71c-1.75-.74-4-1.7-4-3.77s2-3.74,4.28-3.74a4.76,4.76,0,0,1,4.44,2.62l2.29-1.38a7.49,7.49,0,0,0-6.67-3.74c-3.83,0-7.2,2.4-7.2,6.14,0,3.46,2.59,4.9,5.6,6.17l1.57.64c2.4,1,4.74,2,4.74,4.79s-2.52,4.71-5.28,4.71" transform="translate(-2.57 -2.64)" fill="currentColor"/><polygon points="447.24 22.76 436.75 22.76 436.75 13.3 446.92 13.3 446.92 10.79 436.75 10.79 436.75 3.64 447.24 3.64 447.24 1.15 433.87 1.15 433.87 25.25 447.24 25.25 447.24 22.76" fill="currentColor"/><polygon points="32.53 149.45 28.84 149.45 18.36 159.35 18.36 149.45 15.61 149.45 15.61 172.57 18.36 172.57 18.36 162.61 19.05 161.96 29.14 172.57 32.92 172.57 20.98 160.21 32.53 149.45" fill="currentColor"/><polygon points="92.18 149.45 89.44 149.45 89.44 172.57 98.97 172.57 98.97 170.17 92.18 170.17 92.18 149.45" fill="currentColor"/><rect x="155.62" y="149.44" width="2.73" height="23.12" fill="currentColor"/><polygon points="236.58 167.34 217.38 148.46 217.38 172.57 220.12 172.57 220.12 154.75 239.33 173.6 239.33 149.45 236.58 149.45 236.58 167.34" fill="currentColor"/><rect x="298.39" y="149.44" width="2.74" height="23.12" fill="currentColor"/><polygon points="359.99 172.57 372.75 172.57 372.75 170.17 362.73 170.17 362.73 161.1 372.45 161.1 372.45 158.71 362.73 158.71 362.73 151.83 372.75 151.83 372.75 149.45 359.99 149.45 359.99 172.57" fill="currentColor"/><polygon points="448.19 149.45 444.51 149.45 434.03 159.35 434.03 149.45 431.29 149.45 431.29 172.57 434.03 172.57 434.03 162.61 434.71 161.96 444.79 172.57 448.58 172.57 436.64 160.21 448.19 149.45" fill="currentColor"/><path d="M39.83,38.53c-24.62,0-37.26,26.14-37.26,51s12.64,51,37.26,51,37.27-26.13,37.27-51-12.66-51-37.27-51m0,73.32c-9.63,0-15.29-10.94-15.29-22.31S30.2,67.23,39.83,67.23,55.11,78.17,55.11,89.54s-5.67,22.31-15.28,22.31" transform="translate(-2.57 -2.64)" fill="currentColor"/><path d="M131.82,86c6.91-4.07,9.7-12.47,9.7-19.79,0-20.32-12.63-27.76-33.2-27.76H81.43V140.58h33.93c20.13,0,35.42-9,35.42-29,0-11.24-5.89-23.44-19-25.6M103,54.41h2.8c9.11,0,15.56,2.71,15.56,13.14S116.24,81,105.67,81H103Zm4.55,70.18H103V96.13h3.52c10.88,0,22.63.55,22.63,13.82s-10,14.64-21.6,14.64" transform="translate(-2.57 -2.64)" fill="currentColor"/><polygon points="172.38 94.28 210.09 94.28 210.09 74.57 172.38 74.57 172.38 55.5 212.92 55.5 212.92 35.78 154.32 35.78 154.32 137.92 214.28 137.92 214.28 118.2 172.38 118.2 172.38 94.28" fill="currentColor"/><path d="M253.11,82.87C243.94,79.52,236,76.63,236,65.1c0-9.19,4.34-14,12.55-14s11.73,4.79,11.73,15.52v1.48h13.42l0-1.5c-.25-18.64-8.74-28.09-25.24-28.09-24.43,0-26.33,21.31-26.33,27.84,0,19.69,12.19,24.29,22.93,28.35,9.2,3.47,17.13,6.46,17.13,18.07,0,9.06-5.46,15.39-13.27,15.39-13.38,0-14.45-9-14.45-20.73V105.9H221.05v1.48c0,16.48,3.23,33.32,27.16,33.32,6.54,0,27.85-2.16,27.85-29.85,0-19.63-12.19-24.07-22.95-28" transform="translate(-2.57 -2.64)" fill="currentColor"/><rect x="277.8" y="35.79" width="13.12" height="102.15" fill="currentColor"/><polygon points="354.85 35.88 296.71 35.88 296.71 47.99 320.89 47.99 320.89 137.97 330.65 137.97 330.65 47.99 354.85 47.99 354.85 35.88" fill="currentColor"/><path d="M375.15,38.53l-33.89,102h7.95l8.88-27.34h41.7l8.89,27.34h8.53l-32.82-102Zm-14,65.92,18-56.28,17.9,56.28Z" transform="translate(-2.57 -2.64)" fill="currentColor"/><path d="M442.54,85.34c-10.71-4-20-7.51-20-21,0-10.22,5.56-20.53,18-20.53,11.7,0,18.69,7.42,18.69,19.86v3.76h5.36v-3.5c0-11.76-6.23-25.5-23.8-25.5-18.65,0-23.57,16.59-23.57,25.38,0,16.92,9.75,21.75,23.53,26.74,9.85,3.59,20,7.29,20,21,0,17.32-10.77,23.48-20.84,23.48-12.24,0-19.54-8.52-19.54-22.8v-5.65h-5.36v5.65c0,13,6.56,28.18,25,28.18,18,0,26.08-14.15,26.08-28.18,0-18-12.54-22.77-23.61-26.93" transform="translate(-2.57 -2.64)" fill="currentColor"/></svg>

        <!-- Brand icon (concentric gradient rings) -->
        <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1199.94 1065.5" aria-hidden="true"><defs><linearGradient id="a" x1="286.02" x2="932.8" y1="1063.52" y2="115.37" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#c1dff3"/><stop offset=".5" stop-color="#cee0cf"/><stop offset="1" stop-color="#faebc0"/></linearGradient><linearGradient id="b" x1="295.93" x2="919.49" y1="1047.28" y2="133.18" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#7ac5ed"/><stop offset=".5" stop-color="#9ec9a4"/><stop offset="1" stop-color="#fedf7e"/></linearGradient><linearGradient id="c" x1="305.18" x2="906.4" y1="1031.67" y2="150.31" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#00aae5"/><stop offset=".5" stop-color="#60b178"/><stop offset="1" stop-color="#ffd41f"/></linearGradient></defs><g style="isolation:isolate"><path fill="#f6f6f6" d="M599.81 334.13c-185 0-288 106-288 262.65s103 262.64 288 262.64c183.55 0 288-105.95 288-262.64s-104.45-262.65-288-262.65Zm10.6 798.38C215 1132.51.06 917.62.06 599.76S217.94 67 610.41 67C1004.38 67 1200 281.9 1200 599.76s-197.11 532.75-589.59 532.75Z" opacity=".5" style="mix-blend-mode:multiply" transform="translate(-.06 -67.01)"/><path fill="url(#a)" d="M599.81 263c-198.47 0-313.88 126.84-313.88 334.77s115.41 334.28 313.88 334.28c197.48 0 313.88-126.35 313.88-334.28S797.29 263 599.81 263Zm7.07 869.51c-349.2 0-548.67-204.94-548.67-532.75S259.67 67 606.88 67c348.2 0 534.83 204.94 534.83 532.75s-187.62 532.76-534.83 532.76Z" opacity=".67" transform="translate(-.06 -67.01)"/><path fill="url(#b)" d="M599.81 191.87c-211.91 0-339.75 147.73-339.75 406.9s127.84 405.9 339.75 405.9c211.41 0 339.74-146.74 339.74-405.9s-128.33-406.9-339.74-406.9Zm3.54 940.64c-302.94 0-487-195-487-532.75S301.4 67 603.35 67c302.43 0 480.06 195 480.06 532.75s-178.12 532.76-480.06 532.76Z" opacity=".83" transform="translate(-.06 -67.01)"/><path fill="url(#c)" d="M599.81 120.73c-225.34 0-365.62 168.63-365.62 479s140.28 477.57 365.62 477.57 365.61-167.14 365.61-477.54-140.27-479.03-365.61-479.03Zm0 1011.78c-256.68 0-425.31-185-425.31-532.75S343.13 67 599.81 67s425.31 185.05 425.31 532.75-168.63 532.76-425.31 532.76Z" transform="translate(-.06 -67.01)"/></g></svg>

        <h1>Onderhoud</h1>
        <p class="body-text">We werken even aan onze website om deze te verbeteren. Probeer het zo nog eens — we zijn snel weer terug!</p>

        <div class="phone-box">
            <p>Heb je een vraag of wil je direct iemand spreken? </p>
            <a href="tel:0888832444">088 - 88 32 444</a>
        </div>

        <p class="footer-text">Probeer het over een moment opnieuw</p>
    </main>
</body>
</html>
