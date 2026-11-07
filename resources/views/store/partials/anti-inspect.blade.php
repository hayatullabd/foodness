@php
    $antiInspectActive = !empty($store['anti_inspect_enabled']) && $store['anti_inspect_enabled'] === '1' && !auth('admin')->check();
    $antiCopyActive = !empty($store['anti_copy_enabled']) && $store['anti_copy_enabled'] === '1' && !auth('admin')->check();
@endphp

@if ($antiInspectActive || $antiCopyActive)
    <style>
        @if ($antiCopyActive)
            /* Disable selection and image drag */
            body, html {
                -webkit-touch-callout: none;
                -webkit-user-select: none;
                -khtml-user-select: none;
                -moz-user-select: none;
                -ms-user-select: none;
                user-select: none;
            }
            img, a {
                -webkit-user-drag: none;
                -khtml-user-drag: none;
                -moz-user-drag: none;
                -o-user-drag: none;
                user-drag: none;
            }
            /* Allow selection inside inputs and textareas so customers can fill out checkout forms */
            input, textarea, select {
                -webkit-user-select: text !important;
                -moz-user-select: text !important;
                -ms-user-select: text !important;
                user-select: text !important;
            }
        @endif
    </style>

    <script>
        (function() {
            'use strict';

            @if ($antiInspectActive)
            // 1. Disable Right Click (Context Menu)
            document.addEventListener('contextmenu', function(e) {
                const targetTag = e.target ? e.target.tagName.toLowerCase() : '';
                if (targetTag !== 'input' && targetTag !== 'textarea') {
                    e.preventDefault();
                    return false;
                }
            }, { capture: true });

            // 2. Block DevTools & Source Keyboard Shortcuts
            document.addEventListener('keydown', function(e) {
                // F12
                if (e.key === 'F12' || e.keyCode === 123) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }

                const isCtrlOrCmd = e.ctrlKey || e.metaKey;

                if (isCtrlOrCmd) {
                    // Ctrl + U / Cmd + U (View Source)
                    if (e.key === 'u' || e.key === 'U' || e.keyCode === 85) {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    }

                    // Ctrl + S / Cmd + S (Save Page)
                    if (e.key === 's' || e.key === 'S' || e.keyCode === 83) {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    }

                    // Ctrl + Shift + I / J / C (DevTools & Console)
                    if (e.shiftKey) {
                        const key = (e.key || '').toUpperCase();
                        if (key === 'I' || key === 'J' || key === 'C' || e.keyCode === 73 || e.keyCode === 74 || e.keyCode === 67) {
                            e.preventDefault();
                            e.stopPropagation();
                            return false;
                        }
                    }
                }
            }, { capture: true });

            // 3. Clear and neutralise console methods
            try {
                if (window.console) {
                    const noop = function() {};
                    window.console.log = noop;
                    window.console.warn = noop;
                    window.console.error = noop;
                    window.console.info = noop;
                    window.console.debug = noop;
                }
            } catch (err) {}

            // 4. Anti-Debugging & DevTools dock detection
            let devtoolsOpen = false;
            const threshold = 160;

            const triggerDebugger = function() {
                try {
                    const fn = new Function('debugger');
                    fn();
                } catch (e) {}
            };

            const detectDevTools = function() {
                const widthThreshold = (window.outerWidth - window.innerWidth) > threshold;
                const heightThreshold = (window.outerHeight - window.innerHeight) > threshold;

                if (widthThreshold || heightThreshold) {
                    devtoolsOpen = true;
                    triggerDebugger();
                } else {
                    devtoolsOpen = false;
                }
            };

            window.addEventListener('resize', detectDevTools, { passive: true });
            setInterval(detectDevTools, 2000);
            @endif

            @if ($antiCopyActive)
            // 5. Prevent Dragging Images
            document.addEventListener('dragstart', function(e) {
                if (e.target && e.target.nodeName === 'IMG') {
                    e.preventDefault();
                    return false;
                }
            }, { capture: true });

            // 6. Prevent Select Start (except on form inputs)
            document.addEventListener('selectstart', function(e) {
                const targetTag = e.target ? e.target.tagName.toLowerCase() : '';
                if (targetTag !== 'input' && targetTag !== 'textarea') {
                    e.preventDefault();
                    return false;
                }
            }, { capture: true });
            @endif
        })();
    </script>
@endif
