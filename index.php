<?php

/**
 * cPanel fallback when document root cannot point to /public.
 * Prefer setting document root to: /home/USER/domain/public
 */
require __DIR__.'/public/index.php';
