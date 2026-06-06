{{--
    ============================================================
    Urbanflaky — Premium "UF" site preloader
    ------------------------------------------------------------
    Self-contained on purpose: the critical CSS + JS are inlined so
    the loader paints on the very first frame, before the built
    stylesheet finishes downloading (zero FOUC, zero extra request).

    Rendered as a sibling BEFORE #app so the Vue mount never manages
    or removes it. Plays the full intro once per browser session;
    repeat page loads get a snappy minimal version. It exits the
    instant the page is ready (window 'load') with a hard safety cap
    so it can never get stuck covering the page.
    ============================================================
--}}
<div id="uf-preloader" role="presentation" aria-hidden="true">
    <div class="uf-pl-glow"></div>

    <div class="uf-pl-particles" aria-hidden="true">
        <span></span><span></span><span></span>
        <span></span><span></span><span></span>
    </div>

    <div class="uf-pl-stage">
        <div class="uf-pl-logo">
            <svg class="uf-pl-svg" viewBox="0 0 260 140" role="img" aria-label="Urbanflaky">
                <text x="50%" y="50%" text-anchor="middle" dominant-baseline="central" dy=".02em" class="uf-pl-text">UF</text>
            </svg>
            <span class="uf-pl-shine" aria-hidden="true"></span>
        </div>

        <div class="uf-pl-meta">
            <div class="uf-pl-bar"><span></span></div>
            <p class="uf-pl-word">Urbanflaky</p>
        </div>
    </div>

    <style>
        html.uf-pl-active, html.uf-pl-active body { overflow: hidden !important; }

        #uf-preloader {
            --pl-accent:   #c7eb31;
            --pl-accent-2: #d4f04a;
            --pl-fill:     #f6f6f4;
            position: fixed;
            inset: 0;
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #050505;
            background-image: radial-gradient(120% 120% at 50% 38%, #15151c 0%, #0c0c10 44%, #050505 100%);
            opacity: 1;
            visibility: visible;
            will-change: opacity, transform;
            transition: opacity .7s cubic-bezier(.4, 0, .2, 1),
                        transform .7s cubic-bezier(.4, 0, .2, 1),
                        visibility .7s linear;
        }

        /* ── Exit: zoom out + fade to reveal the page ── */
        #uf-preloader.uf-pl-exit {
            opacity: 0;
            visibility: hidden;
            transform: scale(1.06);
            pointer-events: none;
        }

        /* ── Ambient breathing accent glow behind the mark ── */
        .uf-pl-glow {
            position: absolute;
            left: 50%;
            top: 46%;
            width: min(72vw, 560px);
            aspect-ratio: 1 / 1;
            transform: translate(-50%, -50%);
            pointer-events: none;
            background: radial-gradient(circle,
                rgba(199, 235, 49, .20) 0%,
                rgba(199, 235, 49, .06) 38%,
                transparent 66%);
            filter: blur(6px);
            animation: ufPlBreathe 3.4s ease-in-out infinite;
        }
        @keyframes ufPlBreathe {
            0%, 100% { opacity: .55; transform: translate(-50%, -50%) scale(.9); }
            50%      { opacity: 1;   transform: translate(-50%, -50%) scale(1.08); }
        }

        /* ── Faint floating particles ── */
        .uf-pl-particles { position: absolute; inset: 0; pointer-events: none; }
        .uf-pl-particles span {
            position: absolute;
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: var(--pl-accent);
            box-shadow: 0 0 8px rgba(199, 235, 49, .8);
            opacity: 0;
            animation: ufPlFloat 6s ease-in-out infinite;
        }
        .uf-pl-particles span:nth-child(1) { left: 16%; top: 32%; animation-delay: 0s; }
        .uf-pl-particles span:nth-child(2) { left: 82%; top: 28%; animation-delay: 1.2s; }
        .uf-pl-particles span:nth-child(3) { left: 24%; top: 70%; animation-delay: .6s; }
        .uf-pl-particles span:nth-child(4) { left: 76%; top: 66%; animation-delay: 1.8s; }
        .uf-pl-particles span:nth-child(5) { left: 50%; top: 17%; animation-delay: .3s; }
        .uf-pl-particles span:nth-child(6) { left: 61%; top: 81%; animation-delay: 2.1s; }
        @keyframes ufPlFloat {
            0%   { transform: translateY(16px) scale(.5); opacity: 0; }
            30%  { opacity: .7; }
            70%  { opacity: .45; }
            100% { transform: translateY(-28px) scale(1); opacity: 0; }
        }

        /* ── Stage ── */
        .uf-pl-stage {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 30px;
            padding: 0 24px;
        }

        /* ── "UF" mark ── */
        .uf-pl-logo { position: relative; }
        .uf-pl-svg {
            display: block;
            width: min(58vw, 300px);
            height: auto;
            overflow: visible;
            animation: ufPlLogoIn .9s cubic-bezier(.2, .8, .2, 1) both,
                       ufPlGlowPulse 2.8s ease-in-out 1.3s infinite;
        }
        @keyframes ufPlLogoIn {
            from { opacity: 0; transform: translateY(10px) scale(.96); filter: blur(6px); }
            to   { opacity: 1; transform: none;                        filter: blur(0); }
        }
        @keyframes ufPlGlowPulse {
            0%, 100% { filter: drop-shadow(0 0 10px rgba(199, 235, 49, .25)); }
            50%      { filter: drop-shadow(0 0 24px rgba(199, 235, 49, .55)); }
        }

        .uf-pl-text {
            font-family: 'Poppins', 'Arial Black', Impact, system-ui, sans-serif;
            font-weight: 800;
            font-size: 118px;
            letter-spacing: 2px;
            fill: transparent;
            stroke: var(--pl-accent);
            stroke-width: 1.4px;
            stroke-linejoin: round;
            stroke-linecap: round;
            stroke-dasharray: 760;
            stroke-dashoffset: 760;
            paint-order: stroke fill;
            /* draw the outline, then flood the fill */
            animation: ufPlDraw 1.5s cubic-bezier(.6, 0, .25, 1) .2s forwards,
                       ufPlFill 1s ease-out 1.35s forwards;
        }
        @keyframes ufPlDraw { to { stroke-dashoffset: 0; } }
        @keyframes ufPlFill { to { fill: var(--pl-fill); stroke-width: .5px; } }

        /* ── Light sweep across the wordmark ── */
        .uf-pl-shine {
            position: absolute;
            top: -25%;
            bottom: -25%;
            left: 0;
            width: 38%;
            pointer-events: none;
            background: linear-gradient(100deg,
                transparent 0%,
                rgba(255, 255, 255, .65) 50%,
                transparent 100%);
            mix-blend-mode: screen;
            opacity: 0;
            transform: translateX(-160%) skewX(-18deg);
            animation: ufPlShine 2.2s cubic-bezier(.4, 0, .2, 1) 1.9s 1 forwards;
        }
        @keyframes ufPlShine {
            0%   { transform: translateX(-160%) skewX(-18deg); opacity: 0; }
            20%  { opacity: .85; }
            60%  { opacity: .85; }
            100% { transform: translateX(360%) skewX(-18deg);  opacity: 0; }
        }

        /* ── Progress line + brand word ── */
        .uf-pl-meta {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
            opacity: 0;
            animation: ufPlMetaIn .8s ease 1.5s both;
        }
        @keyframes ufPlMetaIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: none; }
        }
        .uf-pl-bar {
            position: relative;
            width: 200px;
            max-width: 60vw;
            height: 2px;
            border-radius: 2px;
            background: rgba(255, 255, 255, .10);
            overflow: hidden;
        }
        .uf-pl-bar span {
            position: absolute;
            inset: 0 auto 0 0;
            width: 0;
            border-radius: 2px;
            background: linear-gradient(90deg, var(--pl-accent), var(--pl-accent-2));
            box-shadow: 0 0 12px rgba(199, 235, 49, .7);
            animation: ufPlBar 1.9s cubic-bezier(.25, .6, .2, 1) 1.55s forwards;
        }
        @keyframes ufPlBar {
            0%   { width: 0; }
            55%  { width: 62%; }
            100% { width: 86%; }
        }
        /* On ready, snap the bar to full before the exit */
        #uf-preloader.uf-pl-done .uf-pl-bar span {
            width: 100% !important;
            animation: none;
            transition: width .3s ease;
        }
        .uf-pl-word {
            margin: 0;
            font-family: 'Poppins', system-ui, sans-serif;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .42em;
            text-indent: .42em;
            text-transform: uppercase;
            color: rgba(245, 245, 245, .78);
        }

        /* ── Small screens ── */
        @media (max-width: 480px) {
            .uf-pl-stage { gap: 24px; }
            .uf-pl-svg   { width: 64vw; }
            .uf-pl-word  { font-size: 10px; letter-spacing: .38em; text-indent: .38em; }
        }

        /* ── Respect reduced-motion: show the final state, no looping motion ── */
        @media (prefers-reduced-motion: reduce) {
            .uf-pl-glow,
            .uf-pl-particles span,
            .uf-pl-svg,
            .uf-pl-text,
            .uf-pl-shine,
            .uf-pl-meta,
            .uf-pl-bar span { animation: none !important; }
            .uf-pl-particles { display: none; }
            .uf-pl-text { fill: var(--pl-fill); stroke-width: .5px; stroke-dashoffset: 0; }
            .uf-pl-meta { opacity: 1; }
            .uf-pl-bar span { width: 86%; }
            #uf-preloader.uf-pl-exit { transform: none; }
        }
    </style>

    <script>
        (function () {
            var pl = document.getElementById('uf-preloader');
            if (!pl) return;

            var root = document.documentElement;
            root.classList.add('uf-pl-active');

            var first   = true;
            try { first = !sessionStorage.getItem('ufVisited'); } catch (e) {}

            var MIN  = first ? 1800 : 700;   // min on-screen time so the intro reads premium
            var MAX  = 6000;                 // hard safety cap — never get stuck
            var start = Date.now();
            var done  = false;

            function finish() {
                if (done) return;
                done = true;

                try { sessionStorage.setItem('ufVisited', '1'); } catch (e) {}

                pl.classList.add('uf-pl-done');   // fill the progress bar

                // brief beat so the bar visibly completes, then play the exit
                setTimeout(function () {
                    pl.classList.add('uf-pl-exit');
                    root.classList.remove('uf-pl-active');

                    var remove = function () { if (pl) pl.style.display = 'none'; };
                    pl.addEventListener('transitionend', remove, { once: true });
                    setTimeout(remove, 900); // fallback if transitionend never fires
                }, 240);
            }

            function onReady() {
                var wait = Math.max(0, MIN - (Date.now() - start));
                setTimeout(finish, wait);
            }

            if (document.readyState === 'complete') {
                onReady();
            } else {
                window.addEventListener('load', onReady);
            }

            setTimeout(finish, MAX); // absolute safety net
        })();
    </script>
</div>

<noscript>
    <style>#uf-preloader { display: none !important; }</style>
</noscript>
